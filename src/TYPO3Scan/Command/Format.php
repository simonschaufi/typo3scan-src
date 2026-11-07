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

namespace SimonSchaufi\TYPO3Scan\Command;

enum Format: string
{
    case Html = 'html';
    case Junit = 'junit';
    case Markdown = 'markdown';
    case Plain = 'plain';
    case TYPO3 = 'typo3';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $f): string => $f->value, self::cases());
    }

    public function templateName(): string
    {
        return $this->value . '.twig';
    }
}
