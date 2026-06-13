<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$composerFile = $root . '/composer.json';
$composer = json_decode((string) file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);
$psr4 = array_merge(
    $composer['autoload']['psr-4'] ?? [],
    $composer['autoload-dev']['psr-4'] ?? []
);

assert(isset($psr4['App\\Cruding\\Tests\\']), 'composer.json must expose App\\Cruding\\Tests\\ for existing test namespaces.');
assert(($psr4['App\\Cruding\\Tests\\'] ?? null) === 'tests/', 'App\\Cruding\\Tests\\ must map to tests/.');
assert(isset($psr4['App\\Tests\\']), 'composer.json must keep App\\Tests\\ for neutral test fixtures.');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/tests'));
$issues = [];
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = (string) file_get_contents($path);
    if (!preg_match('/^namespace\s+([^;]+);/m', $code, $namespaceMatch)) {
        continue;
    }

    if (!preg_match('/^(?:abstract\s+|final\s+|readonly\s+)*class\s+(\w+)|^interface\s+(\w+)|^enum\s+(\w+)|^trait\s+(\w+)/m', $code, $symbolMatch)) {
        continue;
    }

    $symbol = '';
    foreach (array_slice($symbolMatch, 1) as $candidate) {
        if (is_string($candidate) && $candidate !== '') {
            $symbol = $candidate;
            break;
        }
    }

    $fqcn = $namespaceMatch[1] . '\\' . $symbol;
    $matched = false;
    foreach ($psr4 as $prefix => $directory) {
        if (!str_starts_with($fqcn, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($fqcn, strlen($prefix))) . '.php';
        $expected = realpath($root . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $relative);
        if ($expected !== false && $expected === realpath($path)) {
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $issues[] = $path . ' => ' . $fqcn;
    }
}

assert($issues === [], "All tests must comply with composer autoload-dev PSR-4.\n" . implode("\n", $issues));

echo "PASS: composer autoload-dev maps App\\Cruding\\Tests\\ and App\\Tests\\ without PSR-4 drift.\n";
