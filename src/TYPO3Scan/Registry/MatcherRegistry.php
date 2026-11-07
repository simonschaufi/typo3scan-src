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

namespace SimonSchaufi\TYPO3Scan\Registry;

use SimonSchaufi\Scanner\Php\Matcher;

final class MatcherRegistry
{
    /**
     * @return list<class-string>
     */
    public function getMatcherConfigurations(string $version): array
    {
        return match ((int)$version) {
            7 => [
                Matcher\ArrayDimensionMatcher::class,
                Matcher\ArrayGlobalMatcher::class,
                Matcher\ArrayMatcher::class, // only version 7
                Matcher\ClassConstantMatcher::class, // only version 7
                Matcher\ClassNameMatcher::class,
                Matcher\ClassNamePatternMatcher::class, // only version 7
                Matcher\ConstantMatcher::class,
                Matcher\GlobalMatcher::class, // only version 7
                Matcher\MethodArgumentDroppedMatcher::class,
                Matcher\MethodArgumentRequiredMatcher::class,
                Matcher\MethodArgumentUnusedMatcher::class,
                Matcher\MethodCallMatcher::class,
                Matcher\MethodCallStaticMatcher::class,
                Matcher\PropertyProtectedMatcher::class,
                Matcher\PropertyPublicMatcher::class,
            ],
            8 => [
                Matcher\ArrayDimensionMatcher::class,
                Matcher\ArrayGlobalMatcher::class,
                Matcher\ClassNameMatcher::class,
                Matcher\ConstantMatcher::class,
                Matcher\MethodArgumentDroppedMatcher::class,
                Matcher\MethodArgumentDroppedStaticMatcher::class, // since version 8
                Matcher\MethodArgumentRequiredMatcher::class,
                Matcher\MethodArgumentUnusedMatcher::class,
                Matcher\MethodCallMatcher::class,
                Matcher\MethodCallStaticMatcher::class,
                Matcher\PropertyPublicMatcher::class,
            ],
            // https://github.com/TYPO3/typo3/blob/9.5/typo3/sysext/install/Classes/Controller/UpgradeController.php#L106
            // https://github.com/TYPO3/typo3/blob/10.4/typo3/sysext/install/Classes/Controller/UpgradeController.php#L122
            // https://github.com/TYPO3/typo3/blob/11.5/typo3/sysext/install/Classes/Controller/UpgradeController.php#L124
            9, 10, 11 => [
                Matcher\ArrayDimensionMatcher::class,
                Matcher\ArrayGlobalMatcher::class,
                Matcher\ClassConstantMatcher::class,
                Matcher\ClassNameMatcher::class,
                Matcher\ConstantMatcher::class,
                Matcher\ConstructorArgumentMatcher::class,
                Matcher\PropertyAnnotationMatcher::class,
                Matcher\MethodAnnotationMatcher::class,
                Matcher\FunctionCallMatcher::class,
                Matcher\InterfaceMethodChangedMatcher::class,
                Matcher\MethodArgumentDroppedMatcher::class,
                Matcher\MethodArgumentDroppedStaticMatcher::class,
                Matcher\MethodArgumentRequiredMatcher::class,
                Matcher\MethodArgumentRequiredStaticMatcher::class,
                Matcher\MethodArgumentUnusedMatcher::class,
                Matcher\MethodCallMatcher::class,
                Matcher\MethodCallStaticMatcher::class,
                Matcher\PropertyExistsStaticMatcher::class,
                Matcher\PropertyProtectedMatcher::class,
                Matcher\PropertyPublicMatcher::class,
            ],
            // https://github.com/TYPO3/typo3/blob/12.4/typo3/sysext/install/Classes/Controller/UpgradeController.php#L109
            12 => [
                Matcher\ArrayDimensionMatcher::class,
                Matcher\ArrayGlobalMatcher::class,
                Matcher\ClassConstantMatcher::class,
                Matcher\ClassNameMatcher::class,
                Matcher\ConstantMatcher::class,
                Matcher\ConstructorArgumentMatcher::class,
                Matcher\PropertyAnnotationMatcher::class,
                Matcher\MethodAnnotationMatcher::class,
                Matcher\FunctionCallMatcher::class,
                Matcher\InterfaceMethodChangedMatcher::class,
                Matcher\MethodArgumentDroppedMatcher::class,
                Matcher\MethodArgumentDroppedStaticMatcher::class,
                Matcher\MethodArgumentRequiredMatcher::class,
                Matcher\MethodArgumentRequiredStaticMatcher::class,
                Matcher\MethodArgumentUnusedMatcher::class,
                Matcher\MethodCallMatcher::class,
                Matcher\MethodCallStaticMatcher::class,
                Matcher\PropertyExistsStaticMatcher::class,
                Matcher\PropertyProtectedMatcher::class,
                Matcher\PropertyPublicMatcher::class,
                Matcher\ScalarStringMatcher::class, // since version 12
            ],
            // https://github.com/TYPO3/typo3/blob/13.4/typo3/sysext/install/Classes/Controller/UpgradeController.php#L105
            // https://github.com/TYPO3/typo3/blob/14.3/typo3/sysext/install/Classes/ExtensionScanner/Php/MatcherRegistry.php
            default => [
                Matcher\ArrayDimensionMatcher::class,
                Matcher\ArrayGlobalMatcher::class,
                Matcher\ClassConstantMatcher::class,
                Matcher\ClassNameMatcher::class,
                Matcher\ConstantMatcher::class,
                Matcher\ConstructorArgumentMatcher::class,
                Matcher\PropertyAnnotationMatcher::class,
                Matcher\MethodAnnotationMatcher::class,
                Matcher\FunctionCallMatcher::class,
                Matcher\AbstractMethodImplementationMatcher::class, // since version 13
                Matcher\InterfaceMethodChangedMatcher::class,
                Matcher\MethodArgumentDroppedMatcher::class,
                Matcher\MethodArgumentDroppedStaticMatcher::class,
                Matcher\MethodArgumentRequiredMatcher::class,
                Matcher\MethodArgumentRequiredStaticMatcher::class,
                Matcher\MethodArgumentUnusedMatcher::class,
                Matcher\MethodCallMatcher::class,
                Matcher\MethodCallArgumentValueMatcher::class,
                Matcher\MethodCallStaticMatcher::class,
                Matcher\PropertyExistsStaticMatcher::class,
                Matcher\PropertyProtectedMatcher::class,
                Matcher\PropertyPublicMatcher::class,
                Matcher\ScalarStringMatcher::class,
            ],
        };
    }
}
