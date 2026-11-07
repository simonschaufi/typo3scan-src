# AGENTS.md

## Project
TYPO3scan — a CLI tool that scans TYPO3 code for deprecated/breaking changes (v7+). It wraps the [simonschaufi/scanner](https://github.com/simonschaufi/scanner) library (`simonschaufi/typo3-cms-scanner`). PHP 8.3, Symfony Console 7.4, Twig reports.

## Layout
- `bin/typo3scan` — CLI entry point (version hardcoded here, bumped by `release.sh`)
- `src/TYPO3Scan/` — PSR-4 namespace `SimonSchaufi\TYPO3Scan\` (Command, Console, Registry, ResultFilter, Service, Statistics, Twig)
- `tests/` — PHPUnit, namespace `SimonSchaufi\TYPO3Scan\Tests\`

## Commands
- Tests: `composer ci:tests:unit` (or `composer local:unit-tests`)
- Static analysis: `composer ci:php:stan` (PHPStan level 9, baseline in `phpstan-baseline.neon`)
- Code style: `composer ci:php:ecs` / fix with `composer fix:php:ecs`
- Rector: `composer ci:php:rector` / fix with `composer fix:php:rector`

## Notes
- Matcher/rule logic lives in the scanner library, not here — most real fixes go to that repo.
