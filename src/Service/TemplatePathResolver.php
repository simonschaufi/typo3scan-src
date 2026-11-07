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

final readonly class TemplatePathResolver
{
    public function __construct(
        private string $builtinTemplateDir,
    ) {
    }

    /**
     * Build the ordered list of Twig template directories.
     *
     * Returns the list, or null when the given custom template path is invalid.
     *
     * @return list<string>|null
     */
    public function resolve(string $templatePath): ?array
    {
        $templatePaths = [];

        if ($templatePath !== '') {
            $resolvedTemplatePath = realpath($templatePath);
            if ($resolvedTemplatePath === false || !is_dir($resolvedTemplatePath)) {
                return null;
            }

            $templatePaths[] = $resolvedTemplatePath;
        }

        $templatePaths[] = $this->builtinTemplateDir;

        return $templatePaths;
    }
}
