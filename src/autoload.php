<?php
/**
 * ================================================================
 * SATORI Forms Autoloader
 * ================================================================
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Satori\\Forms\\';
    $baseDir = __DIR__ . '/';

    if (0 !== strpos($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_readable($file)) {
        require $file;
    }
});
