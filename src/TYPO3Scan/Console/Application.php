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

namespace SimonSchaufi\TYPO3Scan\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * Generator: http://www.patorjk.com/software/taag/
     * Font: Slant
     */
    private const string LOGO = '   ________  ______  ____ _____
  /_  __/\ \/ / __ \/ __ \__  /   ______________ _____
   / /    \  / /_/ / / / //_ <   / ___/ ___/ __ `/ __ \
  / /     / / ____/ /_/ /__/ /  (__  ) /__/ /_/ / / / /
 /_/     /_/_/    \____/____/  /____/\___/\__,_/_/ /_/

        https://github.com/simonschaufi/typo3scan

        Hand coded with %s️ by Simon Schaufelberger

';

    public function getHelp(): string
    {
        $love = $this->isColorSupported() ? "\e[31m♥\e[0m" : "♥";
        return sprintf(self::LOGO, $love) . parent::getHelp();
    }

    /**
     * Check if color output is supported
     */
    private function isColorSupported(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
        }
        return \function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }
}
