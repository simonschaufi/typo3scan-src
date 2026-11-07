<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Controller;

use TYPO3\CMS\Backend\Controller\BackendController;

/**
 * This fixture file contains code that is deprecated/breaking in TYPO3 v12.
 * It is intentionally using removed APIs so the scanner can detect them.
 */
class MyController
{
    public function doSomething(BackendController $controller): void
    {
        // This method was removed in TYPO3 v12 (Breaking-80700)
        $controller->addCssFile('foo', 'bar');

        // This method was also removed in TYPO3 v12 (Breaking-80700)
        $controller->addJavascript('alert("test");');
    }
}
