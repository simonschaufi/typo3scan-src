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

namespace SimonSchaufi\TYPO3Scan\Tests\Integration\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimonSchaufi\TYPO3Scan\Command\ScanCommand;
use SimonSchaufi\TYPO3Scan\Registry\MatcherRegistry;
use SimonSchaufi\TYPO3Scan\ResultFilter\DirectoryResultFilter;
use SimonSchaufi\TYPO3Scan\Service\ScannerServiceFactory;
use SimonSchaufi\TYPO3Scan\Service\TemplatePathResolver;
use SimonSchaufi\TYPO3Scan\Statistics\ScanStatistics;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integration tests for ScanCommand using real fixture PHP files.
 *
 * The fixture project under tests/Integration/Fixtures/Project/ contains PHP files
 * that intentionally reference APIs removed/deprecated in TYPO3 v12:
 *
 *  - Classes/Controller/MyController.php  → uses BackendController::addCssFile()
 *                                            and BackendController::addJavascript()
 *                                            (Breaking-80700, weak match)
 *  - Classes/Utility/MyUtility.php        → uses the removed class RemoveXSS
 *                                            (Breaking-80700 / Deprecation-76164, strong match)
 *
 * Tests verify that the real scanner detects these issues and that the rendered
 * reports (plain text, markdown, JUnit XML) contain the expected patterns.
 */
final class ScanCommandIntegrationTest extends TestCase
{
    private const string FIXTURE_PATH = __DIR__ . '/../Fixtures/Project';

    private CommandTester $tester;
    private string $tempDir;

    protected function setUp(): void
    {
        $command = new ScanCommand(
            new DirectoryResultFilter(),
            new ScanStatistics(),
            new Filesystem(),
            new ScannerServiceFactory(new MatcherRegistry()),
            new TemplatePathResolver(__DIR__ . '/../../../resources/templates'),
        );
        $app = new Application();
        $app->addCommand($command);
        $this->tester = new CommandTester($app->find('scan'));

        $this->tempDir = sys_get_temp_dir() . '/typo3scan_integration_' . uniqid('', true);
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    // -------------------------------------------------------------------------
    // Smoke test: fixture path is real and contains the expected files
    // -------------------------------------------------------------------------

    public function testFixtureDirectoryExists(): void
    {
        self::assertDirectoryExists(self::FIXTURE_PATH);
        self::assertFileExists(self::FIXTURE_PATH . '/Classes/Controller/MyController.php');
        self::assertFileExists(self::FIXTURE_PATH . '/Classes/Utility/MyUtility.php');
    }

    // -------------------------------------------------------------------------
    // Exit-code assertions
    // -------------------------------------------------------------------------

    public function testScanReturnsNonZeroExitCodeWhenMatchesFound(): void
    {
        $exitCode = $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        // Command::FAILURE (1) is returned when total > 0
        self::assertSame(1, $exitCode);
    }

    public function testScanReturnsSuccessForCleanDirectory(): void
    {
        // The temp dir is empty, so no matches should be found.
        $exitCode = $this->tester->execute(
            [
                'path' => $this->tempDir,
                '--target' => '12',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(0, $exitCode);
    }

    // -------------------------------------------------------------------------
    // Plain-text output format
    // -------------------------------------------------------------------------

    public function testPlainOutputContainsExpectedMatchCount(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('Found 3 matches', $output);
        self::assertStringContainsString('TYPO3 12', $output);
    }

    public function testPlainOutputListsDeprecatedMethodAddCssFile(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('addCssFile()', $output);
        self::assertStringContainsString('(weak)', $output);
    }

    public function testPlainOutputListsDeprecatedMethodAddJavascript(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('addJavascript()', $output);
    }

    public function testPlainOutputListsRemovedClassRemoveXSS(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('RemoveXSS', $output);
        self::assertStringContainsString('(strong)', $output);
    }

    public function testPlainOutputContainsFileReferences(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('Classes/Controller/MyController.php', $output);
        self::assertStringContainsString('Classes/Utility/MyUtility.php', $output);
    }

    public function testPlainOutputContainsRestFileLinks(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        // Both deprecated methods reference Breaking-80700
        self::assertStringContainsString('Breaking-80700', $output);
        // The RemoveXSS class references Deprecation-76164
        self::assertStringContainsString('Deprecation-76164', $output);
        // addCssFile / addJavascript also reference Deprecation-80491
        self::assertStringContainsString('Deprecation-80491', $output);
    }

    // -------------------------------------------------------------------------
    // Markdown output format
    // -------------------------------------------------------------------------

    public function testMarkdownOutputHasHeadings(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'markdown',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        // Top-level heading contains the scanned path
        self::assertMatchesRegularExpression('/^# .+/m', $output);
        // File-level headings
        self::assertStringContainsString('## Classes/Controller/MyController.php', $output);
        self::assertStringContainsString('## Classes/Utility/MyUtility.php', $output);
    }

    public function testMarkdownOutputHasMatchHeadingsWithIndicator(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'markdown',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('### Call to method "addCssFile()" *(weak)*', $output);
        self::assertStringContainsString('### Usage of class "RemoveXSS" *(strong)*', $output);
    }

    public function testMarkdownOutputContainsSummaryTable(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'markdown',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        // The Markdown template renders column headers with an extra space before
        // each cell, e.g. "| strong |  weak |  BREAKING |"
        self::assertStringContainsString('| strong |', $output);
        self::assertMatchesRegularExpression('/\|\s+weak\s+\|/', $output);
        self::assertStringContainsString('| --- |', $output);
    }

    public function testMarkdownOutputContainsChangelogLinks(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'markdown',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        // Should contain markdown-formatted documentation links
        self::assertMatchesRegularExpression('/\[.+\]\(https:\/\/docs\.typo3\.org\/.+\)/', $output);
    }

    // -------------------------------------------------------------------------
    // JUnit XML output format
    // -------------------------------------------------------------------------

    public function testJunitOutputIsValidXml(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'junit',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = $this->tester->getDisplay();

        self::assertNotFalse(simplexml_load_string($xml), 'JUnit output must be valid XML');
    }

    public function testJunitOutputHasTestsuitesRoot(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'junit',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());

        self::assertNotFalse($xml);
        self::assertSame('testsuites', $xml->getName());
        self::assertSame('3', (string)$xml['tests']);
        self::assertSame('3', (string)$xml['errors']);
    }

    public function testJunitOutputContainsTestsuitePerFile(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'junit',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());
        self::assertNotFalse($xml);

        $suiteNames = [];
        foreach ($xml->testsuite as $suite) {
            $suiteNames[] = (string)$suite['name'];
        }

        self::assertContains('Classes/Controller/MyController.php', $suiteNames);
        self::assertContains('Classes/Utility/MyUtility.php', $suiteNames);
    }

    public function testJunitOutputContainsFailureElements(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'junit',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());
        self::assertNotFalse($xml);

        // The controller suite has 2 matches → 2 testcase elements
        foreach ($xml->testsuite as $suite) {
            if ((string)$suite['name'] === 'Classes/Controller/MyController.php') {
                self::assertSame('2', (string)$suite['tests']);
                self::assertCount(2, $suite->testcase);
                // Each testcase must have a <failure> child
                foreach ($suite->testcase as $testcase) {
                    self::assertNotEmpty($testcase->failure);
                }
                break;
            }
        }
    }

    // -------------------------------------------------------------------------
    // TYPO3 XML output format
    // -------------------------------------------------------------------------

    public function testTypo3OutputIsValidXml(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'typo3',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = $this->tester->getDisplay();

        self::assertNotFalse(simplexml_load_string($xml), 'JUnit output must be valid XML');
    }

    public function testTypo3OutputHasTestsuitesRoot(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'typo3',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());

        self::assertNotFalse($xml);
        self::assertSame('testsuites', $xml->getName());
        self::assertSame('3', (string)$xml['tests']);
        self::assertSame('3', (string)$xml['errors']);
    }

    public function testTypo3OutputContainsTestsuitePerFile(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'typo3',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());
        self::assertNotFalse($xml);

        $suiteNames = [];
        foreach ($xml->testsuite as $suite) {
            $suiteNames[] = (string)$suite['name'];
        }

        self::assertContains('Classes/Controller/MyController.php', $suiteNames);
        self::assertContains('Classes/Utility/MyUtility.php', $suiteNames);
    }

    public function testTypo3OutputContainsFailureElements(): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'typo3',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $xml = simplexml_load_string($this->tester->getDisplay());
        self::assertNotFalse($xml);

        // The controller suite has 2 matches → 2 testcase elements
        foreach ($xml->testsuite as $suite) {
            if ((string)$suite['name'] === 'Classes/Controller/MyController.php') {
                self::assertSame('2', (string)$suite['tests']);
                self::assertCount(2, $suite->testcase);
                // Each testcase must have a <failure> child
                foreach ($suite->testcase as $testcase) {
                    self::assertNotEmpty($testcase->failure);
                }
                break;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Report file output (--reportFile option)
    // -------------------------------------------------------------------------

    public function testReportFileIsWritten(): void
    {
        $reportFile = $this->tempDir . '/report.txt';

        $exitCode = $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
                '--reportFile' => $reportFile,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCode);
        self::assertFileExists($reportFile);

        $contents = file_get_contents($reportFile) ?: '';
        // The plain template wraps numbers in Symfony console decoration tags
        // (e.g. <comment>3</comment>) which are written verbatim to the file.
        // We strip those tags before the assertion.
        $stripped = strip_tags($contents);
        self::assertStringContainsString('Found 3 matches', $stripped);
    }

    public function testMarkdownReportFileMatchesOutputFormat(): void
    {
        $reportFile = $this->tempDir . '/report.md';

        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'markdown',
                '--reportFile' => $reportFile,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertFileExists($reportFile);
        $contents = file_get_contents($reportFile) ?: '';
        self::assertMatchesRegularExpression('/^# /m', $contents);
        self::assertStringContainsString('## Classes/Controller/MyController.php', $contents);
        self::assertStringContainsString('## Classes/Utility/MyUtility.php', $contents);
    }

    public function testJunitReportFileIsWrittenAndValid(): void
    {
        $reportFile = $this->tempDir . '/report.xml';

        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'junit',
                '--reportFile' => $reportFile,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertFileExists($reportFile);
        $xml = simplexml_load_string(file_get_contents($reportFile) ?: '');
        self::assertNotFalse($xml);
        self::assertSame('testsuites', $xml->getName());
    }

    public function testTypo3ReportFileIsWrittenAndValid(): void
    {
        $reportFile = $this->tempDir . '/report.xml';

        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'typo3',
                '--reportFile' => $reportFile,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertFileExists($reportFile);
        $xml = simplexml_load_string(file_get_contents($reportFile) ?: '');
        self::assertNotFalse($xml);
        self::assertSame('testsuites', $xml->getName());
    }

    public function testReportFileFailsForNonExistentDirectory(): void
    {
        $exitCode = $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--reportFile' => '/does/not/exist/report.txt',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Reportfile path does not exist', $this->tester->getErrorOutput());
    }

    // -------------------------------------------------------------------------
    // Filtering: --only and --indicators options
    // -------------------------------------------------------------------------

    public function testOnlyBreakingFilterExcludesWeakMatches(): void
    {
        // All 3 detected issues carry Breaking-80700, but the MethodCallMatcher
        // matches are "weak" while the ClassNameMatcher match is "strong".
        // --only=breaking keeps all 3; --indicators=strong should drop the two
        // weak BackendController method matches and keep only RemoveXSS.
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => 'plain',
                '--only' => 'breaking',
                '--indicators' => 'strong',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        $output = $this->tester->getDisplay();

        self::assertStringContainsString('RemoveXSS', $output);
        self::assertStringNotContainsString('addCssFile', $output);
        self::assertStringNotContainsString('addJavascript', $output);
    }

    // -------------------------------------------------------------------------
    // Format variants via data provider
    // -------------------------------------------------------------------------

    /**
     * @return iterable<string, array{format: string, pattern: non-empty-string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'plain contains match count' => [
            'format' => 'plain',
            'pattern' => '/Found 3 matches/',
        ];
        yield 'markdown has heading' => [
            'format' => 'markdown',
            'pattern' => '/^# .+/m',
        ];
        yield 'junit is XML with testsuites root' => [
            'format' => 'junit',
            'pattern' => '/<testsuites /',
        ];
        yield 'typo3 is XML with testsuites root' => [
            'format' => 'junit',
            'pattern' => '/<testsuites /',
        ];
        yield 'html contains html tag' => [
            'format' => 'html',
            'pattern' => '/<html|<!DOCTYPE/i',
        ];
    }

    /**
     * @param non-empty-string $pattern
     */
    #[DataProvider('formatProvider')]
    public function testEachFormatProducesExpectedOutput(string $format, string $pattern): void
    {
        $this->tester->execute(
            [
                'path' => self::FIXTURE_PATH,
                '--target' => '12',
                '--format' => $format,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertMatchesRegularExpression($pattern, $this->tester->getDisplay());
    }
}
