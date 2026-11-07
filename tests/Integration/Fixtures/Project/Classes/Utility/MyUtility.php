<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Utility;

use RemoveXSS;

/**
 * This fixture file uses a removed class (RemoveXSS) that is detected by
 * the ClassNameMatcher in TYPO3 v12.
 */
class MyUtility
{
    public function sanitize(string $input): string
    {
        // RemoveXSS was deprecated and removed (Breaking-80700, Deprecation-76164)
        return RemoveXSS::process($input);
    }
}
