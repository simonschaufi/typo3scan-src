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

final class GetFilenameFilter
{
    /**
     * TWIG filter for getting the name of a file without the extension
     */
    public function getFilter(): TwigFilter
    {
        return new TwigFilter('getFilename', fn ($path) => pathinfo($path, PATHINFO_FILENAME));
    }
}
