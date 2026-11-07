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

namespace SimonSchaufi\TYPO3Scan\Service;

use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\Scanner\Domain\Model\MatcherBundle;
use SimonSchaufi\Scanner\Domain\Model\MatcherBundleCollection;
use SimonSchaufi\Scanner\Scanner;
use SimonSchaufi\Scanner\ScannerFactory;
use SimonSchaufi\TYPO3Scan\Registry\MatcherRegistry;

class ScannerService
{
    private string $matcherBundleBasePath = '';
    private readonly MatcherBundleCollection $collection;
    private readonly Scanner $scanner;

    public function __construct(MatcherRegistry $matcherRegistry, string $version)
    {
        $this->setMatcherBundlePath();

        $matcherConfiguration = $matcherRegistry->getMatcherConfigurations($version);

        $this->collection = new MatcherBundleCollection(
            new MatcherBundle(
                $this->matcherBundleBasePath . 'v' . $version,
                $matcherConfiguration
            )
        );

        $this->scanner = ScannerFactory::create()->createFor();
    }

    /**
     * @param string[] $excludeDirs
     */
    public function scan(string $path, array $excludeDirs): DirectoryResult
    {
        return $this->scanner->scanPath($path, $this->collection, $excludeDirs);
    }

    /**
     * Find the matcher bundles, either inside of the phar file,
     * or from the upper dir in case we are installed via composer
     */
    private function setMatcherBundlePath(): void
    {
        foreach ([
            __DIR__ . '/../../vendor/simonschaufi/typo3-cms-scanner/config/Matcher/',
            __DIR__ . '/../../../../simonschaufi/typo3-cms-scanner/config/Matcher/',
        ] as $file) {
            if (is_dir($file)) {
                $this->matcherBundleBasePath = $file;
                break;
            }
        }
    }
}
