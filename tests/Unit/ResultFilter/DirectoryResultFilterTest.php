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

namespace SimonSchaufi\TYPO3Scan\Tests\Unit\ResultFilter;

use PHPUnit\Framework\TestCase;
use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\Scanner\Domain\Model\ScanMatch;
use SimonSchaufi\Scanner\Result\ScanResult;
use SimonSchaufi\TYPO3Scan\ResultFilter\DirectoryResultFilter;
use SimonSchaufi\TYPO3Scan\Tests\Unit\ResultFilter\Fixtures\DirectoryResultFixture;

final class DirectoryResultFilterTest extends TestCase
{
    private DirectoryResultFilter $subject;

    protected function setUp(): void
    {
        $this->subject = new DirectoryResultFilter();
    }

    public function testFilterByTypeKeepsOnlyTheRequestedType(): void
    {
        $filtered = $this->subject->filterByType(DirectoryResultFixture::typicalScan(), 'breaking');

        self::assertSame(
            [
                DirectoryResultFixture::FILE_SERVICE => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_BREAKING]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 80 - 60 - 40 lines, 15 - 4 - 2 - 0 ignored lines
        self::assertStatistics($filtered, 170, 9, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByTypeKeepsSeveralRequestedTypes(): void
    {
        $filtered = $this->subject->filterByType(DirectoryResultFixture::typicalScan(), 'deprecation,feature');

        // The controller file keeps one of its two matches, so it stays in the report
        // with all of its lines.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_DEPRECATION]],
                ],
                DirectoryResultFixture::FILE_HELPER => [
                    [ScanMatch::TYPE_FEATURE, [DirectoryResultFixture::RST_FEATURE]],
                ],
                DirectoryResultFixture::FILE_HOOK => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_HOOK]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 120 lines, 15 - 6 ignored lines
        self::assertStatistics($filtered, 230, 9, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByTypeKeepsEverythingForTheDefaultOptionValue(): void
    {
        $filtered = $this->subject->filterByType(
            DirectoryResultFixture::typicalScan(),
            'breaking,deprecation,important,feature'
        );

        self::assertSame(
            [
                DirectoryResultFixture::FILE_SERVICE => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_BREAKING]],
                ],
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_DEPRECATION]],
                    [ScanMatch::TYPE_IMPORTANT, [DirectoryResultFixture::RST_IMPORTANT]],
                ],
                DirectoryResultFixture::FILE_HELPER => [
                    [ScanMatch::TYPE_FEATURE, [DirectoryResultFixture::RST_FEATURE]],
                ],
                DirectoryResultFixture::FILE_HOOK => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_HOOK]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        self::assertSame(DirectoryResultFixture::PATH, $filtered->getPath());
        self::assertStatistics($filtered, 350, 15, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByTypeDropsEverythingForAnEmptyOptionValue(): void
    {
        $filtered = $this->subject->filterByType(DirectoryResultFixture::typicalScan(), '');

        // "--only" without a value is passed on as an empty value, which matches no type.
        self::assertSame([], self::retainedMatches($filtered));
        // Only the lines of the two files without any match are left.
        self::assertStatistics(
            $filtered,
            DirectoryResultFixture::UNMATCHED_EFFECTIVE_CODE_LINES,
            DirectoryResultFixture::UNMATCHED_IGNORED_LINES,
            DirectoryResultFixture::NUMBER_OF_IGNORED_FILES
        );
    }

    public function testFilterByIndicatorsKeepsOnlyStrongMatches(): void
    {
        $filtered = $this->subject->filterByIndicators(DirectoryResultFixture::typicalScan(), 'strong');

        // The controller file keeps the strong match of its two, so it stays in the report.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_SERVICE => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_BREAKING]],
                ],
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_IMPORTANT, [DirectoryResultFixture::RST_IMPORTANT]],
                ],
                DirectoryResultFixture::FILE_HOOK => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_HOOK]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 60 lines, 15 - 2 ignored lines
        self::assertStatistics($filtered, 290, 13, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIndicatorsKeepsOnlyWeakMatches(): void
    {
        $filtered = $this->subject->filterByIndicators(DirectoryResultFixture::typicalScan(), 'weak');

        self::assertSame(
            [
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_DEPRECATION]],
                ],
                DirectoryResultFixture::FILE_HELPER => [
                    [ScanMatch::TYPE_FEATURE, [DirectoryResultFixture::RST_FEATURE]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 120 - 40 lines, 15 - 6 - 0 ignored lines
        self::assertStatistics($filtered, 190, 9, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIndicatorsKeepsEverythingForTheDefaultOptionValue(): void
    {
        $filtered = $this->subject->filterByIndicators(DirectoryResultFixture::typicalScan(), 'strong,weak');

        self::assertCount(4, $filtered);
        self::assertSame(5, $filtered->countAll());
        self::assertStatistics($filtered, 350, 15, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIndicatorsDropsEverythingForAnEmptyOptionValue(): void
    {
        $filtered = $this->subject->filterByIndicators(DirectoryResultFixture::typicalScan(), '');

        // "--indicators" without a value is passed on as an empty value, which matches no indicator.
        self::assertSame([], self::retainedMatches($filtered));
        self::assertStatistics(
            $filtered,
            DirectoryResultFixture::UNMATCHED_EFFECTIVE_CODE_LINES,
            DirectoryResultFixture::UNMATCHED_IGNORED_LINES,
            DirectoryResultFixture::NUMBER_OF_IGNORED_FILES
        );
    }

    public function testFilterByIssueNumbersReturnsTheInputWhenNoIssuesAreGiven(): void
    {
        $directoryResult = DirectoryResultFixture::typicalScan();

        $filtered = $this->subject->filterByIssueNumbers($directoryResult, [], []);

        self::assertSame($directoryResult, $filtered);
    }

    public function testFilterByIssueNumbersKeepsMatchesWhoseIssueIsNotIgnored(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(DirectoryResultFixture::typicalScan(), ['80929'], []);

        // The controller file only loses one of its two matches, so it keeps all of its lines.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_SERVICE => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_BREAKING]],
                ],
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_IMPORTANT, [DirectoryResultFixture::RST_IMPORTANT]],
                ],
                DirectoryResultFixture::FILE_HELPER => [
                    [ScanMatch::TYPE_FEATURE, [DirectoryResultFixture::RST_FEATURE]],
                ],
                DirectoryResultFixture::FILE_HOOK => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_HOOK]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        self::assertStatistics($filtered, 350, 15, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIssueNumbersRemovesFilesWhoseMatchesAreAllIgnored(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(
            DirectoryResultFixture::typicalScan(),
            ['80929', '44444'],
            []
        );

        self::assertSame(
            [
                DirectoryResultFixture::FILE_SERVICE => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_BREAKING]],
                ],
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_IMPORTANT, [DirectoryResultFixture::RST_IMPORTANT]],
                ],
                DirectoryResultFixture::FILE_HELPER => [
                    [ScanMatch::TYPE_FEATURE, [DirectoryResultFixture::RST_FEATURE]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 40 lines, 15 - 0 ignored lines
        self::assertStatistics($filtered, 310, 15, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIssueNumbersKeepsOnlyMatchesOfTheGivenIssues(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(DirectoryResultFixture::typicalScan(), [], ['80929']);

        self::assertSame(
            [
                DirectoryResultFixture::FILE_CONTROLLER => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_DEPRECATION]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 120 - 60 - 40 lines, 15 - 6 - 2 - 0 ignored lines
        self::assertStatistics($filtered, 130, 7, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIssueNumbersShowsAnIssueEvenWhenItIsIgnoredToo(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(
            DirectoryResultFixture::typicalScan(),
            ['44444', '80929'],
            ['44444']
        );

        // 44444 is in both lists: showing an issue wins over ignoring it.
        // 80929 is only ignored, so the controller file loses both of its matches.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_HOOK => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_HOOK]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 350 - 120 - 80 - 60 lines, 15 - 6 - 4 - 2 ignored lines
        self::assertStatistics($filtered, 90, 3, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIssueNumbersKeepsEverythingWhenNoIssueIsIgnored(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(DirectoryResultFixture::typicalScan(), ['99999'], []);

        self::assertCount(4, $filtered);
        self::assertSame(5, $filtered->countAll());
        self::assertStatistics($filtered, 350, 15, DirectoryResultFixture::NUMBER_OF_IGNORED_FILES);
    }

    public function testFilterByIssueNumbersKeepsMatchesWhoseRestFilesCarryNoIssueNumber(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(DirectoryResultFixture::mixedRestFiles(), ['98765'], []);

        // "Deprecation-legacy-files.md" and the match without any rest file carry no issue
        // number, so they can not be ignored. "Breaking-98765-second.rst" carries the
        // ignored issue, which drops the combined file.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_LEGACY => [
                    [ScanMatch::TYPE_DEPRECATION, [DirectoryResultFixture::RST_WITHOUT_ISSUE_NUMBER]],
                ],
                DirectoryResultFixture::FILE_WITHOUT_REST_FILES => [
                    [ScanMatch::TYPE_DEPRECATION, []],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 50 - 10 lines, 2 - 0 ignored lines
        self::assertStatistics($filtered, 40, 2, 0);
    }

    public function testFilterByIssueNumbersKeepsAMatchWhenOneOfItsRestFilesIsShown(): void
    {
        $filtered = $this->subject->filterByIssueNumbers(DirectoryResultFixture::mixedRestFiles(), [], ['11111']);

        // Only the match citing issue 11111 survives and still carries both of its rest files.
        self::assertSame(
            [
                DirectoryResultFixture::FILE_COMBINED => [
                    [ScanMatch::TYPE_BREAKING, [DirectoryResultFixture::RST_FIRST, DirectoryResultFixture::RST_SECOND]],
                ],
            ],
            self::retainedMatches($filtered)
        );
        // 50 - 25 - 15 lines, 2 - 1 - 1 ignored lines
        self::assertStatistics($filtered, 10, 0, 0);
    }

    public function testFiltersRunInSequenceDoNotSubtractTheSameLinesTwice(): void
    {
        $directoryResult = DirectoryResultFixture::typicalScan();

        $filtered = $this->subject->filterByType($directoryResult, 'breaking');
        $filtered = $this->subject->filterByIndicators($filtered, 'weak');
        $filtered = $this->subject->filterByIssueNumbers($filtered, [], []);

        // "--only breaking" leaves the service file, its only match is a strong one and
        // no issue is given, so the issue filter returns its input unchanged.
        self::assertSame([], self::retainedMatches($filtered));
        // 350 - 120 lines of the first pass, then 170 - 120 lines of the second pass,
        // 15 - 6 then 9 - 6 ignored lines.
        self::assertStatistics(
            $filtered,
            DirectoryResultFixture::UNMATCHED_EFFECTIVE_CODE_LINES,
            DirectoryResultFixture::UNMATCHED_IGNORED_LINES,
            DirectoryResultFixture::NUMBER_OF_IGNORED_FILES
        );
    }

    /**
     * Every file left in the report with the type and the rest files of its matches, in order.
     *
     * @return array<string, list<array{0: string, 1: list<string>}>>
     */
    private static function retainedMatches(DirectoryResult $directoryResult): array
    {
        $retainedMatches = [];
        /** @var ScanResult $scanResult */
        foreach ($directoryResult as $scanResult) {
            $retainedMatches[$scanResult->getFile()] = array_map(
                static fn (ScanMatch $match): array => [$match->getType(), $match->getRestFiles()],
                $scanResult->getMatches()
            );
        }

        return $retainedMatches;
    }

    private static function assertStatistics(
        DirectoryResult $directoryResult,
        int $effectiveCodeLines,
        int $ignoredLines,
        int $numberOfIgnoredFiles
    ): void {
        self::assertSame($effectiveCodeLines, $directoryResult->getEffectiveCodeLines(), 'effective code lines');
        self::assertSame($ignoredLines, $directoryResult->getIgnoredLines(), 'ignored lines');
        self::assertSame($numberOfIgnoredFiles, $directoryResult->getNumberOfIgnoredFiles(), 'numberOfIgnoredFiles');
    }
}
