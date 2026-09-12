<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2).'/src/DTO';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
        continue;
    }

    $className = $file->getBasename('.php');
    if (!str_ends_with($className, 'DTO')) {
        fwrite(STDERR, $file->getPathname().PHP_EOL);
        exit(1);
    }
}

echo "PASS: every class under src/DTO uses the DTO suffix.\n";
