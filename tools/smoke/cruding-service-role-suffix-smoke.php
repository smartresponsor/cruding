<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2).'/src/Service/Crud';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
        continue;
    }

    $className = $file->getBasename('.php');
    if (!str_contains($className, 'Crud')) {
        fwrite(STDERR, $file->getPathname().PHP_EOL);
        exit(1);
    }
}

echo "PASS: Cruding service role prefix.\n";
