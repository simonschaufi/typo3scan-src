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

namespace SimonSchaufi\TYPO3Scan\Twig;

use SimonSchaufi\TYPO3Scan\Twig\Filter\EscapeDollarFilter;
use SimonSchaufi\TYPO3Scan\Twig\Filter\GetChangeTitleFilter;
use SimonSchaufi\TYPO3Scan\Twig\Filter\GetFilenameFilter;
use SimonSchaufi\TYPO3Scan\Twig\Filter\GetLineFromFileFilter;
use SimonSchaufi\TYPO3Scan\Twig\Filter\GetLinesFromFileFilter;
use SimonSchaufi\TYPO3Scan\Twig\Filter\GetOnlineDocumentFilter;

final class Environment extends \Twig\Environment
{
    public function addCustomFilters(): void
    {
        $this->addFilter((new GetChangeTitleFilter())->getFilter());
        $this->addFilter((new EscapeDollarFilter())->getFilter());
        $this->addFilter((new GetLineFromFileFilter())->getFilter());
        $this->addFilter((new GetLinesFromFileFilter())->getFilter());
        $this->addFilter((new GetFilenameFilter())->getFilter());
        $this->addFilter((new GetOnlineDocumentFilter())->getFilter());
    }
}
