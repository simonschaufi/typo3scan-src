# TYPO3 scanner as standalone CLI application

Scans code for usage of deprecated and or changed code.

## What about actually FIXING the broken code automatically?

TYPO3scan only _reports_ on code that needs to be fixed when migrating to a higher version of TYPO3.
If you want a tool that **automatically fixes the code for you** take a look at
[TYPO3 Rector](https://github.com/sabbelasichon/typo3-rector) and [Fractor](https://github.com/FriendsOfTYPO3/fractor).

> [!TIP]
> The **TYPO3 Upgrade Analyzer** is a tool that generates a report for your Website
> based on the results of TYPO3 Rector, Fractor and the TYPO3 Scanner!
> Get it here: https://www.simonschaufelberger.de/typo3-upgrade-analyzer-en

## What does it do?

[![demo](https://asciinema.org/a/201851.png)](https://asciinema.org/a/201851?autoplay=1)

TYPO3 publishes [breaking changes and deprecations since version 7](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Index.html).

This tool scans a folder for any code that is broken or deprecated.
It's a wrapper around the [TYPO3 scanner library](https://github.com/simonschaufi/scanner) that has been extracted from the TYPO3 core.
You can scan for deprecations and breaking changes for TYPO3 v7 and up.

## Is TYPO3 scan helping you to migrate your TYPO3 site more smoothly?

Then please buy me a drink some day ;-)

Thank you! ♥

## Contributing

If you want to help improve this tool to reduce the amount of false positives,
improve matchers, add new matchers etc., your contributions are very welcome!

Most of the time you will want to change the **TYPO3 scanner library** or your
fork of it and then run that library inside the **typo3scan** tool.

### Set up the development environment

Clone this repository (or your fork of the scanner repository):

```bash
git clone https://github.com/simonschaufi/typo3scan-src.git
```

Clone the TYPO3 scanner library repository (or your fork of the scanner repository):

```bash
cd packages
git clone https://github.com/simonschaufi/typo3-cms-scanner.git
```

In `composer.json` require the package with `@dev` so that your local package is
loaded instead (make sure to never commit this change!):

```json
{
    "require": {
        "simonschaufi/typo3-cms-scanner": "@dev"
    }
}
```

Run `composer update`.

Composer will now **link** the local scanner library into the **typo3scan** project.
Any changes to the scanner library will be directly reflected when executing typo3scan.

### Run the TYPO3scan tool

You can execute the typo3scan tool from the `bin` directory.

```bash
bin/typo3scan scan ~/tmp/testExtension
```

### Run tests

```bash
vendor/bin/phpunit
```

and for the typo3-cms-scanner:

```bash
cd packages/typo3-cms-scanner
composer install
vendor/bin/phpunit
```

## Static Analysis

This plugin uses [PHPStan](https://phpstan.org/) for static analysis.

Run PHPStan from the root directory:

```bash
vendor/bin/phpstan analyse
```

### Build the phar

Make sure you have [Box](https://github.com/box-project/box) installed.

```bash
composer bin box install
composer build
```

## Sponsors

This project was generously sponsored by [Stichting Praktijkleren](https://www.stichtingpraktijkleren.nl/).
