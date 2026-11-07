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

namespace SimonSchaufi\TYPO3Scan\Statistics;

use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;

class ScanStatistics
{
    /**
     * Count the number of matches per indicator (strong / weak) and per change type
     * (breaking, deprecation, important, feature, …).
     *
     * @return array<string, int>
     */
    public function getCountsByType(DirectoryResult $directoryResult): array
    {
        $countsByType = [
            'strong' => 0,
            'weak' => 0,
        ];
        foreach ($directoryResult as $scanResult) {
            foreach ($scanResult->getMatches() as $fileMatch) {
                $indicator = $fileMatch->getIndicator()->value;
                if (!array_key_exists($indicator, $countsByType)) {
                    $countsByType[$indicator] = 0;
                }
                $countsByType[$indicator]++;

                $type = $fileMatch->getType();
                if (!array_key_exists($type, $countsByType)) {
                    $countsByType[$type] = 0;
                }
                $countsByType[$type]++;
            }
        }

        return $countsByType;
    }

    /**
     * Turn raw counts into human-readable percentage strings relative to $total.
     *
     * @param array<string, int> $counts
     *
     * @return array<string, int|string>
     */
    public function getPercentagesByType(array $counts, int $total): array
    {
        $result = [];
        foreach ($counts as $type => $count) {
            if ($total <= 0) {
                $result[$type] = 0;
            } else {
                $result[$type] = number_format(100 * $count / $total, 1) . '% (' . $count . ')';
            }
        }

        return $result;
    }

    /**
     * Return the directory-level line statistics as a plain array suitable for
     * passing directly to a template context.
     *
     * @return array{numberOfIgnoredFiles: int, numberOfEffectiveCodeLines: int, numberOfIgnoreLines: int}
     */
    public function getSummary(DirectoryResult $directoryResult): array
    {
        return [
            'numberOfIgnoredFiles' => $directoryResult->getNumberOfIgnoredFiles(),
            'numberOfEffectiveCodeLines' => $directoryResult->getEffectiveCodeLines(),
            'numberOfIgnoreLines' => $directoryResult->getIgnoredLines(),
        ];
    }
}
