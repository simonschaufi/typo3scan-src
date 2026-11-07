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

final class GetOnlineDocumentFilter
{
    /**
     * TWIG filter to get link to online documentation
     */
    public function getFilter(): TwigFilter
    {
        return new TwigFilter('getOnlineDocument', fn (string $path) => $this->findOnlineDocumentation($path));
    }

    /**
     * Find the real path to the online documentation
     * The Matchers sometimes include the version number of the TYPO3 version,
     * other times they don't. So we can't rely on the rst file path given in
     * the matcher.
     *
     * Instead we take the last part (basename) of the rst file and then look up
     * the file in the Changelog dir.
     */
    private function findOnlineDocumentation(string $path): string
    {
        static $onlineDocumentationLinks = [
            // Special case for the ClassNamePatternMatcher Rules from v7
            1299497039 => 'https://gist.github.com/Tuurlijk/f857bf41e559ce3908290fb96d98b5e4', // key: crc32('/Deprecation-legacy-files.md')
            2959317077 => 'https://gist.github.com/Tuurlijk/79aba880880e6340ffd2720ff1c5b623', // key: crc32('/Deprecation-non-namespaced.md')
        ];
        $cacheKey = crc32($path);
        if (array_key_exists($cacheKey, $onlineDocumentationLinks)) {
            return $onlineDocumentationLinks[$cacheKey];
        }
        $onlineDocument = '';
        $base = 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/';
        $links = file(__DIR__ . '/../../../Resources/Private/links.txt');
        if ($links === false) {
            $links = [];
        }
        $filename = basename($path);
        foreach ($links as $link) {
            $link = rtrim($link);
            if (str_ends_with($link, $filename)) {
                $onlineDocument = $base . $link;
                $onlineDocument = str_replace('.rst', '.html', $onlineDocument);
                break;
            }
        }
        $onlineDocumentationLinks[$cacheKey] = $onlineDocument;
        return $onlineDocument;
    }
}
