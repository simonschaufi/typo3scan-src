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

use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\ProtectedToPrivateFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/src/TYPO3Scan',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
    )
    ->withConfiguredRule(ClassAttributesSeparationFixer::class, [
        'elements' => [
            'property' => 'none',
            'const' => 'none',
            'method' => 'one',
        ],
    ])
    ->withConfiguredRule(CastSpacesFixer::class, [
        'space' => 'none',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => <<<EOF
This file is part of the TYPO3 scanner.

(c) Simon Schaufelberger

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.
EOF
    ])
    ->withRules([
        ProtectedToPrivateFixer::class,
    ])
    ->withSkip([
        __DIR__ . '/tests/Integration/Fixtures',
        NotOperatorWithSuccessorSpaceFixer::class,
    ]);
