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

namespace SimonSchaufi\TYPO3Scan\ResultFilter;

use Closure;
use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\Scanner\Domain\Model\ScanMatch;
use SimonSchaufi\Scanner\Result\ScanResult;

class DirectoryResultFilter
{
    /**
     * Filter out only the types of changes the user wants in the report
     *
     * @param string $only comma separated list of change types, e.g. "breaking,deprecation"
     */
    public function filterByType(DirectoryResult $directoryResult, string $only): DirectoryResult
    {
        $onlyTypes = array_map(strtoupper(...), explode(',', $only));

        return $this->applyMatchFilter(
            $directoryResult,
            static fn (ScanMatch $scanMatch): bool => in_array($scanMatch->getType(), $onlyTypes, true)
        );
    }

    /**
     * Filter the types of changes by indicators (confidence)
     *
     * @param string $indicators comma separated list of indicators, e.g. "strong,weak"
     */
    public function filterByIndicators(DirectoryResult $directoryResult, string $indicators): DirectoryResult
    {
        $indicatorNames = array_map(strtoupper(...), explode(',', $indicators));

        return $this->applyMatchFilter(
            $directoryResult,
            static fn (ScanMatch $scanMatch): bool => in_array(strtoupper($scanMatch->getIndicator()->value), $indicatorNames, true)
        );
    }

    /**
     * Filter out unwanted issue numbers
     *
     * @param list<string> $ignoreIssues issue numbers that must not be reported
     * @param list<string> $showIssues   issue numbers that are reported, everything else is skipped
     */
    public function filterByIssueNumbers(DirectoryResult $directoryResult, array $ignoreIssues, array $showIssues): DirectoryResult
    {
        if ($ignoreIssues === [] && $showIssues === []) {
            return $directoryResult;
        }

        $defaultSkip = $showIssues !== [];

        return $this->applyMatchFilter(
            $directoryResult,
            static function (ScanMatch $scanMatch) use ($ignoreIssues, $showIssues, $defaultSkip): bool {
                $skip = $defaultSkip;
                foreach ($scanMatch->getRestFiles() as $restFile) {
                    if (preg_match('#^[a-zA-Z]+-(\\d+)-#', $restFile, $matches)) {
                        $issueNumber = $matches[1];
                        if (in_array($issueNumber, $ignoreIssues, true)) {
                            $skip = true;
                        }
                        if (in_array($issueNumber, $showIssues, true)) {
                            $skip = false;
                        }
                    }
                }

                return !$skip;
            }
        );
    }

    /**
     * Apply a per-match predicate across every file in the directory result.
     *
     * Files whose matches all fail the predicate are dropped from the output and their
     * line counts are removed from the directory statistics.
     *
     * @param Closure(ScanMatch): bool $matchPredicate returns true if a match should be kept
     */
    private function applyMatchFilter(DirectoryResult $directoryResult, Closure $matchPredicate): DirectoryResult
    {
        $filteredDirectoryResult = $this->createFilteredResult($directoryResult);

        $numberOfEffectiveCodeLines = $directoryResult->getEffectiveCodeLines();
        $numberOfIgnoredLines = $directoryResult->getIgnoredLines();

        /** @var ScanResult $scanResult */
        foreach ($directoryResult as $scanResult) {
            $filteredMatches = array_values(array_filter($scanResult->getMatches(), $matchPredicate));

            if ($filteredMatches !== []) {
                $filteredDirectoryResult->append(
                    new ScanResult(
                        $scanResult->getFile(),
                        $filteredMatches,
                        null,
                        $scanResult->isFileIgnored(),
                        $scanResult->getEffectiveCodeLines(),
                        $scanResult->getIgnoredLines()
                    )
                );
            } else {
                // The file leaves the report, so only now its lines stop counting
                $numberOfEffectiveCodeLines -= $scanResult->getEffectiveCodeLines();
                $numberOfIgnoredLines -= $scanResult->getIgnoredLines();
            }
        }

        $filteredDirectoryResult->setEffectiveCodeLines($numberOfEffectiveCodeLines);
        $filteredDirectoryResult->setIgnoredLines($numberOfIgnoredLines);

        return $filteredDirectoryResult;
    }

    /**
     * Create a fresh DirectoryResult pre-populated with the directory-level statistics
     * from the given source (path, ignored-file count).
     *
     * Line counts are intentionally left at their initial state so that
     * applyMatchFilter() can overwrite them after the per-file walk.
     */
    private function createFilteredResult(DirectoryResult $source): DirectoryResult
    {
        $filteredDirectoryResult = new DirectoryResult($source->getPath());
        $filteredDirectoryResult->setNumberOfIgnoredFiles($source->getNumberOfIgnoredFiles());

        return $filteredDirectoryResult;
    }
}
