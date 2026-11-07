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

namespace SimonSchaufi\TYPO3Scan\Twig\Filter;

use Twig\TwigFilter;

final class GetLinesFromFileFilter
{
    /**
     * TWIG filter to get lines from file
     */
    public function getFilter(): TwigFilter
    {
        return new TwigFilter('getLinesFromFile', fn (string $fileName, int $lineNumber, int $before = 2, int $after = 2) => $this->getLinesFromFile($fileName, $lineNumber, $before, $after));
    }

    /**
     * Return several specific lines from a file
     */
    private function getLinesFromFile(string $fileName, int $lineNumber, int $before = 2, int $after = 2): string
    {
        $before = (int)abs($before);
        $after = (int)abs($after);
        $file = new \SplFileObject($fileName);
        $lines = [];

        $start = $lineNumber - 1 - $before;
        $start = max(0, $start);
        $end = $start + $before + $after + 1;

        for ($position = $start; $position < $end; $position++) {
            if (!$file->eof()) {
                $file->seek($position);
                $line = $file->current();
                if (is_string($line)) {
                    $lines[] = trim($line);
                }
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
