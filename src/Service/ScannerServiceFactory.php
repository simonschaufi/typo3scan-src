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

use SimonSchaufi\TYPO3Scan\Registry\MatcherRegistry;

/**
 * Creates a ScannerService for a given TYPO3 version.
 *
 * Injecting this factory into ScanCommand keeps the command testable:
 * tests can substitute a fake factory without touching the real scanner.
 */
readonly class ScannerServiceFactory
{
    public function __construct(
        private MatcherRegistry $matcherRegistry,
    ) {
    }

    public function create(string $version): ScannerService
    {
        return new ScannerService($this->matcherRegistry, $version);
    }
}
