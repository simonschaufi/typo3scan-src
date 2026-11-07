<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 scanner.
 *
 * (c) Simon Schaufelberger
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace SimonSchaufi\TYPO3Scan\Command;

use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\TYPO3Scan\ResultFilter\DirectoryResultFilter;
use SimonSchaufi\TYPO3Scan\Service\ScannerServiceFactory;
use SimonSchaufi\TYPO3Scan\Service\TemplatePathResolver;
use SimonSchaufi\TYPO3Scan\Statistics\ScanStatistics;
use SimonSchaufi\TYPO3Scan\Twig\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Loader\FilesystemLoader;

final class ScanCommand extends Command
{
    // Option / argument names
    private const string ARG_PATH = 'path';
    private const string OPT_TARGET = 'target';
    private const string OPT_ONLY = 'only';
    private const string OPT_INDICATORS = 'indicators';
    private const string OPT_FORMAT = 'format';
    private const string OPT_REPORT_FILE = 'reportFile';
    private const string OPT_TEMPLATE_PATH = 'templatePath';
    private const string OPT_IGNORE = 'ignore';
    private const string OPT_ISSUE = 'issue';
    private const string OPT_EXCLUDE = 'exclude';

    // Default option values
    private const string DEFAULT_TARGET = '14';
    private const string DEFAULT_ONLY = 'breaking,deprecation,important,feature';
    private const string DEFAULT_INDICATORS = 'strong,weak';

    public function __construct(
        private readonly DirectoryResultFilter $directoryResultFilter,
        private readonly ScanStatistics $scanStatistics,
        private readonly Filesystem $filesystem,
        private readonly ScannerServiceFactory $scannerServiceFactory,
        private readonly TemplatePathResolver $templatePathResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('scan')
            ->setDescription('Scan a path for deprecated code')
            ->setDefinition([
                new InputArgument(self::ARG_PATH, InputArgument::REQUIRED, 'Path to scan'),
                new InputOption(self::OPT_TARGET, 't', InputOption::VALUE_OPTIONAL, 'TYPO3 version to target', self::DEFAULT_TARGET),
                new InputOption(self::OPT_ONLY, 'o', InputOption::VALUE_OPTIONAL, 'Only report: [breaking, deprecation, important, feature] changes', self::DEFAULT_ONLY),
                new InputOption(self::OPT_INDICATORS, 'i', InputOption::VALUE_OPTIONAL, 'Only report: [strong, weak] matches', self::DEFAULT_INDICATORS),
                new InputOption(self::OPT_FORMAT, 'f', InputOption::VALUE_OPTIONAL, 'Output format [' . implode(', ', Format::values()) . ']', Format::Plain->value),
                new InputOption(self::OPT_REPORT_FILE, 'r', InputOption::VALUE_OPTIONAL, 'Report file', null),
                new InputOption(self::OPT_TEMPLATE_PATH, null, InputOption::VALUE_OPTIONAL, 'Path to template folder'),
                new InputOption(self::OPT_IGNORE, null, InputOption::VALUE_OPTIONAL, 'Issue number(s) to ignore (comma-separated)'),
                new InputOption(self::OPT_ISSUE, null, InputOption::VALUE_OPTIONAL, 'Issue number(s) to show (comma-separated)'),
                new InputOption(self::OPT_EXCLUDE, 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Paths to be excluded'),
            ])
            ->setHelp(
                <<<EOT
The <info>scan</info> command scans a path for deprecated code.

Scan a folder:
<info>php typo3scan.phar scan ~/tmp/source</info>

Scan a folder for v8 changes:
<info>php typo3scan.phar scan --target 8 ~/tmp/source</info>

Scan a folder and output to report file:
<info>php typo3scan.phar scan --target 8 --reportFile ~/tmp/report.txt ~/tmp/source</info>

Scan a folder for v7 changes and output in markdown:
<info>php typo3scan.phar scan --target 7 --format markdown ~/tmp/source</info>

Scan a folder for v7 WEAK changes and output in markdown:
<info>php typo3scan.phar scan --indicators weak --target 7 --format markdown ~/tmp/source</info>

Scan a folder for v9 changes and output in markdown with custom template:
<info>php typo3scan.phar scan --format markdown --templatePath ~/path/to/templates --path ~/tmp/source</info>

Scan a folder for v7 changes, only show the breaking changes and output in markdown:
<info>php typo3scan.phar scan --target 7 --only breaking --format markdown ~/tmp/source</info>

Scan a folder and only show changes for issue #80929:
<info>php typo3scan.phar scan --issue 80929 ~/tmp/source</info>

Scan a folder and do NOT show changes for issue #80929 and #84289:
<info>php typo3scan.phar scan --ignore 80929,84289 ~/tmp/source</info>

Scan a folder and exclude some directories:
<info>php typo3scan.phar scan --exclude var/excluded1 --exclude var/excluded2 ~/tmp/source</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stdErr = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $startTime = microtime(true);

        $path = $this->resolvePath($input, $stdErr);
        if ($path === null) {
            return Command::FAILURE;
        }

        $version = $this->getStringOption($input, self::OPT_TARGET, self::DEFAULT_TARGET);

        $templatePaths = $this->resolveTemplatePaths($input, $stdErr);
        if ($templatePaths === null) {
            return Command::FAILURE;
        }

        $format = $this->resolveFormat($input, $stdErr);
        if (!$format instanceof Format) {
            return Command::FAILURE;
        }

        ['basePath' => $basePath, 'extensionName' => $extensionName] = $this->resolveExtensionContext($path);

        $exclude = $input->getOption('exclude') ?: [];
        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }
        $exclude = array_values(array_filter($exclude, is_string(...)));

        $directoryResult = $this->scan($path, $version, $exclude);
        $directoryResult = $this->applyFilters($directoryResult, $input);

        $total = $directoryResult->countAll();
        $executionTime = microtime(true) - $startTime;

        $context = $this->buildContext($directoryResult, $path, $extensionName, $basePath, $version, $total, $executionTime);

        $twig = new Environment(new FilesystemLoader($templatePaths));
        $twig->addCustomFilters();
        $rendered = $twig->load($format->templateName())->render($context);

        $exitCode = $this->writeOutput($rendered, $input, $stdErr, $output);
        if ($exitCode !== Command::SUCCESS) {
            return $exitCode;
        }

        return $total > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Validate and resolve the scan path argument.
     * Returns the real absolute path, or null on failure (error already written to $stdErr).
     */
    private function resolvePath(InputInterface $input, OutputInterface $stdErr): ?string
    {
        $requestedPath = $input->getArgument(self::ARG_PATH);
        if (!is_string($requestedPath)) {
            $stdErr->writeln('No path given.');
            return null;
        }

        $path = realpath($requestedPath);
        if ($path === false || !is_dir($path)) {
            $stdErr->writeln(sprintf('Path does not exist: "%s"', $requestedPath));
            return null;
        }

        return $path;
    }

    /**
     * Build the ordered list of Twig template directories.
     * Returns the list, or null on failure (error already written to $stdErr).
     *
     * @return list<string>|null
     */
    private function resolveTemplatePaths(InputInterface $input, OutputInterface $stdErr): ?array
    {
        $templatePath = $this->getStringOption($input, self::OPT_TEMPLATE_PATH);

        $templatePaths = $this->templatePathResolver->resolve($templatePath);
        if ($templatePaths === null) {
            $stdErr->writeln(sprintf('Template path does not exist: "%s"', $templatePath));
        }

        return $templatePaths;
    }

    /**
     * Parse and validate the --format option.
     * Returns the Format enum case, or null on failure (error already written to $stdErr).
     */
    private function resolveFormat(InputInterface $input, OutputInterface $stdErr): ?Format
    {
        $value = $this->getStringOption($input, self::OPT_FORMAT, Format::Plain->value);
        $format = Format::tryFrom($value);

        if ($format === null) {
            $stdErr->writeln(sprintf(
                'Invalid format "%s". Allowed values: %s',
                $value,
                implode(', ', Format::values())
            ));
            return null;
        }

        return $format;
    }

    /**
     * Derive the base path and (optionally) extension name from the scan path.
     *
     * @return array{basePath: string, extensionName: string}
     */
    private function resolveExtensionContext(string $path): array
    {
        ['extPath' => $extPath, 'extensionName' => $extensionName] = $this->resolveExtFromPath($path);

        if ($extensionName === '') {
            return [
                'basePath' => rtrim($path, '/') . '/',
                'extensionName' => '',
            ];
        }

        return [
            'basePath' => $extPath . DIRECTORY_SEPARATOR . $extensionName . DIRECTORY_SEPARATOR,
            'extensionName' => $extensionName,
        ];
    }

    /**
     * Walk the path upward to find the /ext/ directory and extract the extension key.
     *
     * Returns the path to the ext/ directory and the extension name (the directory
     * immediately below ext/). Both are empty strings when no /ext/ segment is found.
     *
     * @return array{extPath: string, extensionName: string}
     */
    private function resolveExtFromPath(string $path): array
    {
        $extensionName = '';
        while (($dir = basename($path)) !== '') {
            if ($dir === 'ext') {
                return [
                    'extPath' => $path,
                    'extensionName' => $extensionName,
                ];
            }

            $extensionName = $dir;
            $path = dirname($path);
        }

        return [
            'extPath' => $path,
            'extensionName' => '',
        ];
    }

    /**
     * Run the scanner for the given path and version.
     * @param string[] $excludeDirs
     */
    private function scan(string $path, string $version, array $excludeDirs): DirectoryResult
    {
        return $this->scannerServiceFactory->create($version)->scan($path, $excludeDirs);
    }

    /**
     * Apply all three result filters driven by the console input.
     */
    private function applyFilters(DirectoryResult $directoryResult, InputInterface $input): DirectoryResult
    {
        $directoryResult = $this->directoryResultFilter->filterByType(
            $directoryResult,
            $this->getStringOption($input, self::OPT_ONLY)
        );
        $directoryResult = $this->directoryResultFilter->filterByIndicators(
            $directoryResult,
            $this->getStringOption($input, self::OPT_INDICATORS)
        );

        return $this->directoryResultFilter->filterByIssueNumbers(
            $directoryResult,
            $this->getIssueNumbers($input, self::OPT_IGNORE),
            $this->getIssueNumbers($input, self::OPT_ISSUE)
        );
    }

    /**
     * Assemble the Twig template context.
     *
     * @return array<string, mixed>
     */
    private function buildContext(
        DirectoryResult $directoryResult,
        string $path,
        string $extensionName,
        string $basePath,
        string $version,
        int $total,
        float $executionTime,
    ): array {
        $counts = $this->scanStatistics->getCountsByType($directoryResult);

        return [
            'title' => $extensionName ?: $path,
            'targetVersion' => $version,
            'total' => $total,
            'basePath' => $basePath,
            'statistics' => $this->scanStatistics->getSummary($directoryResult),
            'percentagesByType' => $this->scanStatistics->getPercentagesByType($counts, $total),
            'directoryResult' => $directoryResult,
            'executionTime' => $executionTime,
        ];
    }

    /**
     * Write $rendered to a report file or to stdout.
     * Returns Command::SUCCESS / Command::FAILURE.
     */
    private function writeOutput(
        string $rendered,
        InputInterface $input,
        OutputInterface $stdErr,
        OutputInterface $output,
    ): int {
        $reportFileOption = $this->getStringOption($input, self::OPT_REPORT_FILE);
        if ($reportFileOption === '') {
            $output->write($rendered);
            return Command::SUCCESS;
        }

        $reportFileDir = dirname($reportFileOption);
        if (!is_dir($reportFileDir)) {
            $stdErr->writeln(sprintf('Reportfile path does not exist: "%s"', $reportFileDir));
            return Command::FAILURE;
        }

        $this->filesystem->dumpFile($reportFileOption, $rendered);

        return Command::SUCCESS;
    }

    /**
     * Read a console option as string.
     *
     * Falls back to $default if the option was passed without a value or is absent.
     */
    private function getStringOption(InputInterface $input, string $name, string $default = ''): string
    {
        $value = $input->getOption($name);

        return is_string($value) ? $value : $default;
    }

    /**
     * Read a console option as a comma-separated list of issue numbers.
     *
     * @return list<string>
     */
    private function getIssueNumbers(InputInterface $input, string $name): array
    {
        return array_values(array_filter(explode(',', $this->getStringOption($input, $name))));
    }
}
