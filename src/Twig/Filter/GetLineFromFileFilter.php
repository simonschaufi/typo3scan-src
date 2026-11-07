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

final class GetLineFromFileFilter
{
    /**
     * TWIG filter to get line from file
     */
    public function getFilter(): TwigFilter
    {
        return new TwigFilter('getLineFromFile', fn (string $fileName, int $lineNumber) => $this->getLineFromFile($fileName, $lineNumber));
    }

    /**
     * Return a specific line from a file
     */
    private function getLineFromFile(string $fileName, int $lineNumber): string
    {
        $file = new \SplFileObject($fileName);
        if (!$file->eof()) {
            $file->seek($lineNumber - 1);
            $line = $file->current();
            if (is_string($line)) {
                return trim($line);
            }
        }
        return '';
    }
}
