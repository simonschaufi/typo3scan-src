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

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::CODE_QUALITY,
        PHPUnitSetList::COMPOSER_BASED,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        FinalizeTestCaseClassRector::class,
    ])
    ->withSkip([]);
