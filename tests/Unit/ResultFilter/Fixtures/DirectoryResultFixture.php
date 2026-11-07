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

namespace SimonSchaufi\TYPO3Scan\Tests\Unit\ResultFilter\Fixtures;

use SimonSchaufi\Scanner\Domain\Model\DirectoryResult;
use SimonSchaufi\Scanner\Domain\Model\ScanMatch;
use SimonSchaufi\Scanner\Result\Indicator;
use SimonSchaufi\Scanner\Result\ScanResult;

/**
 * Directory results in the shape SimonSchaufi\Scanner\Scanner::scanPath() produces them.
 */
final class DirectoryResultFixture
{
    public const string PATH = '/project/typo3conf/ext/example/';
    public const string FILE_SERVICE = 'Classes/Service/FooService.php';
    public const string FILE_CONTROLLER = 'Classes/Controller/ListController.php';
    public const string FILE_HELPER = 'Classes/Utility/Helper.php';
    public const string FILE_HOOK = 'Classes/Hooks/FooHook.php';
    public const string RST_BREAKING = 'Breaking-98765-remove-foo-service.rst';
    public const string RST_DEPRECATION = 'Deprecation-80929-deprecated-property.rst';
    public const string RST_IMPORTANT = 'Important-77777-important-thing.rst';
    public const string RST_FEATURE = 'Feature-12345-new-feature.rst';
    public const string RST_HOOK = 'Deprecation-44444-hook.rst';
    public const string FILE_LEGACY = 'Classes/Legacy/OldApi.php';
    public const string FILE_COMBINED = 'Classes/Service/Combined.php';
    public const string FILE_WITHOUT_REST_FILES = 'Classes/Vendor/ThirdParty.php';
    public const string RST_WITHOUT_ISSUE_NUMBER = 'Deprecation-legacy-files.md';
    public const string RST_FIRST = 'Breaking-11111-first.rst';
    public const string RST_SECOND = 'Breaking-98765-second.rst';

    /**
     * Lines of the two files the scanner found without any match: they are part of the
     * directory statistics but never part of the directory result itself.
     */
    public const int UNMATCHED_EFFECTIVE_CODE_LINES = 50;

    public const int UNMATCHED_IGNORED_LINES = 3;

    /**
     * Files annotated with @extensionScannerIgnoreFile: Scanner::scanPath() counts them
     * but does not scan them, so they are never part of the directory result either.
     */
    public const int NUMBER_OF_IGNORED_FILES = 2;

    /**
     * Four files with matches: one breaking, one with a deprecation and an important
     * change, one feature and one more deprecation.
     */
    public static function typicalScan(): DirectoryResult
    {
        $directoryResult = self::directory(
            self::scanResult(
                self::FILE_SERVICE,
                120,
                6,
                self::scanMatch(ScanMatch::TYPE_BREAKING, Indicator::Strong, [self::RST_BREAKING], 42)
            ),
            self::scanResult(
                self::FILE_CONTROLLER,
                80,
                4,
                self::scanMatch(ScanMatch::TYPE_DEPRECATION, Indicator::Weak, [self::RST_DEPRECATION], 17),
                self::scanMatch(ScanMatch::TYPE_IMPORTANT, Indicator::Strong, [self::RST_IMPORTANT], 55)
            ),
            self::scanResult(
                self::FILE_HELPER,
                60,
                2,
                self::scanMatch(ScanMatch::TYPE_FEATURE, Indicator::Weak, [self::RST_FEATURE], 9)
            ),
            self::scanResult(
                self::FILE_HOOK,
                40,
                0,
                self::scanMatch(ScanMatch::TYPE_DEPRECATION, Indicator::Strong, [self::RST_HOOK], 3)
            )
        );

        $directoryResult->setEffectiveCodeLines($directoryResult->getEffectiveCodeLines() + self::UNMATCHED_EFFECTIVE_CODE_LINES);
        $directoryResult->setIgnoredLines($directoryResult->getIgnoredLines() + self::UNMATCHED_IGNORED_LINES);
        $directoryResult->setNumberOfIgnoredFiles(self::NUMBER_OF_IGNORED_FILES);

        return $directoryResult;
    }

    /**
     * Three files covering the rest file variants a match can carry: one without an
     * issue number in its name, one with two issue numbers and one with none at all.
     */
    public static function mixedRestFiles(): DirectoryResult
    {
        return self::directory(
            self::scanResult(
                self::FILE_LEGACY,
                25,
                1,
                self::scanMatch(ScanMatch::TYPE_DEPRECATION, Indicator::Strong, [self::RST_WITHOUT_ISSUE_NUMBER], 12)
            ),
            self::scanResult(
                self::FILE_COMBINED,
                10,
                0,
                self::scanMatch(ScanMatch::TYPE_BREAKING, Indicator::Strong, [self::RST_FIRST, self::RST_SECOND], 7)
            ),
            self::scanResult(
                self::FILE_WITHOUT_REST_FILES,
                15,
                1,
                self::scanMatch(ScanMatch::TYPE_DEPRECATION, Indicator::Strong, [], 4)
            )
        );
    }

    /**
     * The statistics of a directory are the sum over its files, exactly like
     * SimonSchaufi\Scanner\Scanner::scanPath() builds them.
     */
    private static function directory(ScanResult ...$files): DirectoryResult
    {
        $directoryResult = new DirectoryResult(self::PATH, ...$files);
        $directoryResult->setEffectiveCodeLines(
            array_sum(array_map(static fn (ScanResult $file): int => $file->getEffectiveCodeLines(), $files))
        );
        $directoryResult->setIgnoredLines(
            array_sum(array_map(static fn (ScanResult $file): int => $file->getIgnoredLines(), $files))
        );

        return $directoryResult;
    }

    /**
     * @param list<ScanMatch> $matches
     */
    private static function scanResult(string $file, int $effectiveCodeLines, int $ignoredLines, ScanMatch ...$matches): ScanResult
    {
        return new ScanResult($file, $matches, null, false, $effectiveCodeLines, $ignoredLines);
    }

    /**
     * @param list<string> $restFiles
     */
    private static function scanMatch(string $type, Indicator $indicator, array $restFiles, int $line): ScanMatch
    {
        return new ScanMatch(
            'MethodCallStaticMatcher',
            $indicator,
            'Message of ' . ($restFiles[0] ?? 'match without rest file'),
            $line,
            $restFiles,
            $type
        );
    }
}
