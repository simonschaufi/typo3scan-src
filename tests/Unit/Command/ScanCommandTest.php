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

namespace SimonSchaufi\TYPO3Scan\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\TYPO3Scan\Command\Format;
use SimonSchaufi\TYPO3Scan\Command\ScanCommand;
use SimonSchaufi\TYPO3Scan\ResultFilter\DirectoryResultFilter;
use SimonSchaufi\TYPO3Scan\Service\ScannerService;
use SimonSchaufi\TYPO3Scan\Service\ScannerServiceFactory;
use SimonSchaufi\TYPO3Scan\Service\TemplatePathResolver;
use SimonSchaufi\TYPO3Scan\Statistics\ScanStatistics;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class ScanCommandTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Format enum tests
    // -------------------------------------------------------------------------

    public function testFormatEnumCoversExpectedValues(): void
    {
        self::assertSame(['html', 'junit', 'markdown', 'plain', 'typo3'], Format::values());
    }

    public function testFormatEnumTemplateNameIsUppercasedTwig(): void
    {
        self::assertSame('plain.twig', Format::Plain->templateName());
        self::assertSame('html.twig', Format::Html->templateName());
        self::assertSame('junit.twig', Format::Junit->templateName());
        self::assertSame('markdown.twig', Format::Markdown->templateName());
        self::assertSame('typo3.twig', Format::TYPO3->templateName());
    }

    public function testFormatTryFromReturnsNullForUnknownValue(): void
    {
        self::assertNull(Format::tryFrom('foobar'));
    }

    // -------------------------------------------------------------------------
    // ScanCommand input-validation tests
    // -------------------------------------------------------------------------

    public function testExecuteFailsForNonExistentPath(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => '/this/path/does/not/exist/at/all',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Path does not exist', $tester->getErrorOutput());
    }

    public function testExecuteFailsForInvalidFormat(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => sys_get_temp_dir(),
                '--format' => 'foobar',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Invalid format "foobar"', $tester->getErrorOutput());
        self::assertStringContainsString('html', $tester->getErrorOutput());
    }

    public function testExecuteFailsForNonExistentTemplatePath(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => sys_get_temp_dir(),
                '--templatePath' => '/does/not/exist',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Template path does not exist', $tester->getErrorOutput());
    }

    // -------------------------------------------------------------------------
    // resolveExtensionContext tests
    // -------------------------------------------------------------------------

    /**
     * @return iterable<string, array{0: string, 1: array{basePath: string, extensionName: string}}>
     */
    public static function extPathProvider(): iterable
    {
        yield 'no ext segment' => [
            '/var/www/html/my_project',
            [
                'basePath' => '/var/www/html/my_project/',
                'extensionName' => '',
            ],
        ];

        yield 'ext in path, extension name extracted' => [
            '/var/www/html/typo3/ext/my_extension/Classes',
            [
                'basePath' => '/var/www/html/typo3/ext/my_extension/',
                'extensionName' => 'my_extension',
            ],
        ];

        yield 'ext is the scan target itself' => [
            '/var/www/html/typo3/ext/my_extension',
            [
                'basePath' => '/var/www/html/typo3/ext/my_extension/',
                'extensionName' => 'my_extension',
            ],
        ];
    }

    /**
     * @param array{basePath: string, extensionName: string} $expected
     */
    #[DataProvider('extPathProvider')]
    public function testResolveExtensionContextParsesExtPath(string $input, array $expected): void
    {
        $command = $this->makeCommand();

        $method = new \ReflectionMethod($command, 'resolveExtensionContext');
        $actual = $method->invoke($command, $input);

        self::assertSame($expected, $actual);
    }

    // -------------------------------------------------------------------------
    // getIssueNumbers tests
    // -------------------------------------------------------------------------

    /**
     * @return iterable<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function issueNumbersProvider(): iterable
    {
        yield 'single issue (ignore)' => ['--ignore', '12345', ['12345']];
        yield 'multiple issues (ignore)' => ['--ignore', '12345,67890', ['12345', '67890']];
        yield 'empty string returns empty array' => ['--ignore', '', []];
        yield 'single issue (issue)' => ['--issue', '99999', ['99999']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('issueNumbersProvider')]
    public function testGetIssueNumbersParsesCommaSeparatedInput(string $optionName, string $rawValue, array $expected): void
    {
        $command = $this->makeCommand();
        $app = new Application();
        $app->addCommand($command);
        $app->find('scan');

        // Bind the definition so the input validates correctly; path is required so we provide it.
        $input = new ArrayInput(
            [
                'path' => sys_get_temp_dir(),
                $optionName => $rawValue,
            ],
            $command->getDefinition()
        );

        $method = new \ReflectionMethod($command, 'getIssueNumbers');
        $actual = $method->invoke($command, $input, ltrim($optionName, '-'));

        self::assertSame($expected, $actual);
    }

    // -------------------------------------------------------------------------
    // --exclude option tests
    // -------------------------------------------------------------------------

    public function testExcludeOptionAcceptsSingleStringValue(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => sys_get_temp_dir(),
                '--exclude' => 'var/cache',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        // No error; the empty fake scanner result means success (0 matches).
        self::assertSame(0, $exitCode);
        self::assertSame('', $tester->getErrorOutput());
    }

    public function testExcludeOptionAcceptsMultipleValues(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => sys_get_temp_dir(),
                '--exclude' => ['var/cache', 'vendor', 'node_modules'],
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(0, $exitCode);
        self::assertSame('', $tester->getErrorOutput());
    }

    // -------------------------------------------------------------------------
    // --title option tests
    // -------------------------------------------------------------------------

    public function testTitleOptionCustomizesRenderedOutput(): void
    {
        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(
            [
                'path' => sys_get_temp_dir(),
                '--title' => 'Custom Scan Title',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(0, $exitCode);
        self::assertSame('', $tester->getErrorOutput());
        self::assertStringContainsString('Custom Scan Title', $tester->getDisplay());
    }

    // -------------------------------------------------------------------------
    // Helper: build a CommandTester with a fake ScannerServiceFactory
    // -------------------------------------------------------------------------

    private function buildCommandTester(): CommandTester
    {
        $command = $this->makeCommand();
        $app = new Application();
        $app->addCommand($command);

        return new CommandTester($app->find('scan'));
    }

    private function makeCommand(): ScanCommand
    {
        $emptyDirectoryResult = new DirectoryResult(sys_get_temp_dir());
        $fakeScannerService = $this->createStub(ScannerService::class);
        $fakeScannerService->method('scan')->willReturn($emptyDirectoryResult);

        $fakeFactory = $this->createStub(ScannerServiceFactory::class);
        $fakeFactory->method('create')->willReturn($fakeScannerService);

        return new ScanCommand(
            new DirectoryResultFilter(),
            new ScanStatistics(),
            new Filesystem(),
            $fakeFactory,
            new TemplatePathResolver(__DIR__ . '/../../../resources/templates'),
        );
    }
}
