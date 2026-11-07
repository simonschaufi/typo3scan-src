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

use Symfony\Component\Finder\Finder;
use Twig\TwigFilter;

final class GetChangeTitleFilter
{
    /**
     * TWIG filter to get the title of the change
     */
    public function getFilter(): TwigFilter
    {
        return new TwigFilter('getChangeTitle', function ($path) {
            $rstPath = $this->findRstFile($path);
            return $this->extractTitleFromRstFile($rstPath);
        });
    }

    /**
     * Find the real path to the rst file
     * The Matchers sometimes include the version number of the TYPO3 version,
     * other times they don't. So we can't rely on the rst file path given in
     * the matcher.
     *
     * Instead we take the last part (basename) of the rst file and then look up
     * the file in the Changelog dir.
     */
    private function findRstFile(string $path): string
    {
        static $restFiles = [];
        if (empty($restFiles)) {
            $restFinder = new Finder();
            $restFilesList = $restFinder->files()->in(__DIR__ . '/../../../Resources/Private/Changelog')->name('*.rst');
            foreach ($restFilesList as $restFile) {
                $pathName = $restFile->getPathname();
                $restFiles[basename($pathName)] = $pathName;
            }
            // Special case for the ClassNamePatternMatcher Rules from v7
            $restFiles['Deprecation-legacy-files.md'] = 'Deprecation-legacy-files.md';
            $restFiles['Deprecation-non-namespaced.md'] = 'Deprecation-non-namespaced.md';
        }
        return $restFiles[basename($path)];
    }

    private function extractTitleFromRstFile(string $path): string
    {
        static $restFileTitles = [
            // Special case for the ClassNamePatternMatcher Rules from v7
            3899088142 => 'Renamed TYPO3 core libraries', // key: crc32('Deprecation-legacy-files.md')
            3619663099 => 'Use of non-namespaced classes', // key: crc32('Deprecation-non-namespaced.md')
        ];
        $cacheKey = crc32($path);
        if (array_key_exists($cacheKey, $restFileTitles)) {
            return $restFileTitles[$cacheKey];
        }
        $result = '';
        $thisShouldBeTheHeader = false;
        $fileHandle = fopen($path, 'rb');
        if ($fileHandle === false) {
            return '';
        }

        while (($line = fgets($fileHandle)) !== false) {
            if ($thisShouldBeTheHeader) {
                $result = trim($line);
                break;
            }
            if (str_starts_with($line, '============')) {
                $thisShouldBeTheHeader = true;
            }
        }
        $restFileTitles[$cacheKey] = $result;
        return $result;
    }
}
