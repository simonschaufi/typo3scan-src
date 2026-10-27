<?php

/**
 * Copyright (c) 2018 Michiel Roos
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace M12R\TYPO3Scan\Service;

use M12R\Scanner\Domain\Model\DirectoryMatches;
use M12R\Scanner\Domain\Model\MatcherBundleCollection;
use M12R\Scanner\Matcher;
use M12R\Scanner\ScannerFactory;

/**
 * Class ScannerService
 * @package M12R\TYPO3Scan\Service
 */
class ScannerService
{
    /**
     * @var string
     */
    private static $matcherBundleBasePath = '';

    /**
     * @var MatcherBundleCollection
     */
    private $collection;

    /**
     * @var \M12R\Scanner\Scanner
     */
    private $scanner;

    /**
     * Scanner constructor.
     */
    public function __construct($version)
    {
        $this->setMatcherBundlePath();

        switch ($version) {
            case '14':
                $this->collection = new MatcherBundleCollection(
                    new \M12R\Scanner\Domain\Model\MatcherBundle(
                        self::$matcherBundleBasePath . 'v' . $version,
                        '',
                        Matcher\ArrayDimensionMatcher::class,
                        Matcher\ArrayGlobalMatcher::class,
                        Matcher\ClassConstantMatcher::class,
                        Matcher\ClassNameMatcher::class,
                        Matcher\ConstantMatcher::class,
                        Matcher\ConstructorArgumentMatcher::class,
                        Matcher\FunctionCallMatcher::class,
                        Matcher\InterfaceMethodChangedMatcher::class,
                        Matcher\MethodAnnotationMatcher::class,
                        Matcher\MethodArgumentDroppedMatcher::class,
                        Matcher\MethodArgumentDroppedStaticMatcher::class,
                        Matcher\MethodArgumentRequiredMatcher::class,
                        Matcher\MethodArgumentRequiredStaticMatcher::class,
                        Matcher\MethodArgumentUnusedMatcher::class,
                        Matcher\MethodCallArgumentValueMatcher::class,
                        Matcher\MethodCallMatcher::class,
                        Matcher\MethodCallStaticMatcher::class,
                        Matcher\PropertyAnnotationMatcher::class,
                        Matcher\PropertyExistsStaticMatcher::class,
                        Matcher\PropertyProtectedMatcher::class,
                        Matcher\PropertyPublicMatcher::class,
                        Matcher\ScalarStringMatcher::class
                    )
                );
                break;
            case '13':
            case '12':
            case '11':
            case '10':
                $this->collection = new MatcherBundleCollection(
                    new \M12R\Scanner\Domain\Model\MatcherBundle(
                        self::$matcherBundleBasePath . 'v' . $version,
                        '',
                        Matcher\ArrayDimensionMatcher::class,
                        Matcher\ArrayGlobalMatcher::class,
                        Matcher\ClassConstantMatcher::class,
                        Matcher\ClassNameMatcher::class,
                        Matcher\ConstantMatcher::class,
                        Matcher\ConstructorArgumentMatcher::class,
                        Matcher\FunctionCallMatcher::class,
                        Matcher\InterfaceMethodChangedMatcher::class,
                        Matcher\MethodAnnotationMatcher::class,
                        Matcher\MethodArgumentDroppedMatcher::class,
                        Matcher\MethodArgumentDroppedStaticMatcher::class,
                        Matcher\MethodArgumentRequiredMatcher::class,
                        Matcher\MethodArgumentRequiredStaticMatcher::class,
                        Matcher\MethodArgumentUnusedMatcher::class,
                        Matcher\MethodCallMatcher::class,
                        Matcher\MethodCallStaticMatcher::class,
                        Matcher\PropertyAnnotationMatcher::class,
                        Matcher\PropertyExistsStaticMatcher::class,
                        Matcher\PropertyProtectedMatcher::class,
                        Matcher\PropertyPublicMatcher::class
                    )
                );
                break;
            case '9':
                $this->collection = new MatcherBundleCollection(
                    new \M12R\Scanner\Domain\Model\MatcherBundle(
                        self::$matcherBundleBasePath . 'v' . $version,
                        '',
                        Matcher\ArrayDimensionMatcher::class,
                        Matcher\ArrayGlobalMatcher::class,
                        Matcher\ClassConstantMatcher::class,
                        Matcher\ClassNameMatcher::class,
                        Matcher\ConstantMatcher::class,
                        Matcher\FunctionCallMatcher::class,
                        Matcher\InterfaceMethodChangedMatcher::class,
                        Matcher\MethodAnnotationMatcher::class,
                        Matcher\MethodArgumentDroppedMatcher::class,
                        Matcher\MethodArgumentDroppedStaticMatcher::class,
                        Matcher\MethodArgumentRequiredMatcher::class,
                        Matcher\MethodArgumentRequiredStaticMatcher::class,
                        Matcher\MethodArgumentUnusedMatcher::class,
                        Matcher\MethodCallMatcher::class,
                        Matcher\MethodCallStaticMatcher::class,
                        Matcher\PropertyAnnotationMatcher::class,
                        Matcher\PropertyExistsStaticMatcher::class,
                        Matcher\PropertyProtectedMatcher::class,
                        Matcher\PropertyPublicMatcher::class
                    )
                );
                break;
            case '8':
                $this->collection = new MatcherBundleCollection(
                    new \M12R\Scanner\Domain\Model\MatcherBundle(
                        self::$matcherBundleBasePath . 'v' . $version,
                        '',
                        Matcher\ArrayDimensionMatcher::class,
                        Matcher\ArrayGlobalMatcher::class,
                        Matcher\ClassNameMatcher::class,
                        Matcher\ConstantMatcher::class,
                        Matcher\MethodArgumentDroppedMatcher::class,
                        Matcher\MethodArgumentDroppedStaticMatcher::class,
                        Matcher\MethodArgumentRequiredMatcher::class,
                        Matcher\MethodArgumentUnusedMatcher::class,
                        Matcher\MethodCallMatcher::class,
                        Matcher\MethodCallStaticMatcher::class,
                        Matcher\PropertyPublicMatcher::class
                    )
                );
                break;
            case '7':
            default:
                $this->collection = new MatcherBundleCollection(
                    new \M12R\Scanner\Domain\Model\MatcherBundle(
                        self::$matcherBundleBasePath . 'v' . $version,
                        '',
                        Matcher\ArrayMatcher::class,
                        Matcher\ArrayDimensionMatcher::class,
                        Matcher\ArrayGlobalMatcher::class,
                        Matcher\ClassConstantMatcher::class,
                        Matcher\ClassNameMatcher::class,
                        Matcher\ClassNamePatternMatcher::class,
                        Matcher\ConstantMatcher::class,
                        Matcher\GlobalMatcher::class,
                        Matcher\MethodArgumentDroppedMatcher::class,
                        Matcher\MethodArgumentRequiredMatcher::class,
                        Matcher\MethodArgumentUnusedStaticMatcher::class,
                        Matcher\MethodCallMatcher::class,
                        Matcher\MethodCallStaticMatcher::class,
                        Matcher\PropertyProtectedMatcher::class,
                        Matcher\PropertyPublicMatcher::class
                    )
                );
                break;
        }

        $this->scanner = ScannerFactory::create()->createFor();
    }

    /**
     * @param string[] $excludeDirs
     */
    public function scan(string $path, array $excludeDirs): DirectoryMatches
    {
        $result = $this->scanner->scanPath(
            $path,
            $this->collection,
            $excludeDirs,
        );
        return $result;
    }

    /**
     * Find the matcher bundles, either inside of the phar file,
     * or from the upper dir in case we are installed via composer
     */
    private function setMatcherBundlePath()
    {
        foreach ([__DIR__ . '/../../../vendor/m12r/typo3-cms-scanner/config/Matcher/', __DIR__ . '/../../../../../m12r/typo3-cms-scanner/config/Matcher/'] as $file) {
            if (is_dir($file)) {
                $this::$matcherBundleBasePath = $file;
                break;
            }
        }
    }
}
