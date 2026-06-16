<?php

declare(strict_types=1);

$projectDirectory = dirname(__DIR__, 2);
$configDirectory = $projectDirectory . '/config';

if (!is_dir($configDirectory)) {
    fwrite(STDERR, "Missing config directory: config\n");

    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $configDirectory,
        FilesystemIterator::SKIP_DOTS,
    ),
);

$violations = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $relativePath = substr($file->getPathname(), strlen($projectDirectory) + 1);
    $violations[] = str_replace('\\', '/', $relativePath);
}

sort($violations);

if ($violations !== []) {
    fwrite(
        STDERR,
        "Executable PHP configuration is forbidden. Use declarative YAML files instead:\n- "
        . implode("\n- ", $violations)
        . "\n",
    );

    exit(1);
}

echo "PASS: config contains declarative files only.\n";
