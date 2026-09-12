<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$issues = [];
$classCount = 0;
$methodCount = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        $issues[] = $file->getPathname() . ': unreadable';
        continue;
    }

    foreach ($lines as $index => $line) {
        if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+|final\s+readonly\s+|abstract\s+readonly\s+)*(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/', $line, $match)) {
            ++$classCount;
            if (!hasDocblock($lines, $index)) {
                $issues[] = sprintf('%s:%d %s %s has no descriptive PHPDoc.', $file->getPathname(), $index + 1, $match[1], $match[2]);
            }
        }

        if (preg_match('/^\s*public\s+(?:static\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $line, $match)) {
            if ($match[1] === '__construct') {
                continue;
            }

            ++$methodCount;
            if (!hasDocblock($lines, $index)) {
                $issues[] = sprintf('%s:%d public method %s() has no descriptive PHPDoc.', $file->getPathname(), $index + 1, $match[1]);
            }
        }
    }
}

assert($issues === [], "Cruding PHPDoc coverage must remain complete.\n" . implode("\n", $issues));

echo sprintf(
    "PASS: Cruding PHPDoc coverage is complete for %d named types and %d public behavior methods.\n",
    $classCount,
    $methodCount,
);

function hasDocblock(array $lines, int $index): bool
{
    for ($cursor = $index - 1; $cursor >= 0; --$cursor) {
        $trimmed = trim($lines[$cursor]);
        if ($trimmed === '' || str_starts_with($trimmed, '#[')) {
            continue;
        }

        return str_ends_with($trimmed, '*/');
    }

    return false;
}
