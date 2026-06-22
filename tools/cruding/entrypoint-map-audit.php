<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$autoload = $root.'/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

foreach ([
    'src/Dto/Crud/CrudContext.php',
    'src/Resolver/Crud/CrudServiceClassNameResolver.php',
] as $requiredFile) {
    require_once $root.'/'.$requiredFile;
}

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Resolver\Crud\CrudServiceClassNameResolver;

$options = parseArguments($argv);
$paths = $options['path'] ?? [];

if ([] === $paths) {
    fwrite(STDERR, usage());
    exit(1);
}

$appSrc = rtrim((string) ($options['app-src'][0] ?? 'src'), '/\\');
$classResolver = new CrudServiceClassNameResolver();
$operationTokens = operationTokens($root.'/config/cruding_reserved_token.yaml');
$rows = [];

foreach ($paths as $path) {
    $intent = resolveIntent((string) $path, $operationTokens);
    $context = new CrudContext(
        surface: (string) ($options['surface'][0] ?? 'public'),
        operation: $intent['operation'],
        resourcePath: $intent['resourcePath'],
        entityClass: (string) ($options['entity-class'][0] ?? ''),
        identifierField: $intent['identifierField'],
        identifierValue: $intent['identifierValue'],
        formTypeClass: null,
    );

    $candidates = [];
    foreach ($classResolver->candidateClassNames($context) as $candidateClassName) {
        $relativePath = classNameToPath((string) $candidateClassName, $appSrc);
        $absolutePath = $root.'/'.$relativePath;
        $candidates[] = [
            'className' => (string) $candidateClassName,
            'relativePath' => $relativePath,
            'classExists' => class_exists((string) $candidateClassName),
            'fileExists' => is_file($absolutePath),
        ];
    }

    $rows[] = [
        'path' => normalizePath((string) $path),
        'resourcePath' => $intent['resourcePath'],
        'operation' => $intent['operation'],
        'identifierField' => $intent['identifierField'],
        'identifierValue' => $intent['identifierValue'],
        'actorScope' => $intent['actorScope'] ?? null,
        'candidates' => $candidates,
    ];
}

$format = strtolower((string) ($options['format'][0] ?? 'text'));
if ('json' === $format) {
    fwrite(STDOUT, json_encode([
        'ok' => true,
        'writeAction' => false,
        'rows' => $rows,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    exit(0);
}

fwrite(STDOUT, "Cruding URI-derived entrypoint map audit\n");
fwrite(STDOUT, "writeAction: false\n\n");

foreach ($rows as $row) {
    fwrite(STDOUT, sprintf("Path: %s\n", $row['path']));
    fwrite(STDOUT, sprintf("  resourcePath: %s\n", $row['resourcePath']));
    fwrite(STDOUT, sprintf("  operation: %s\n", $row['operation']));
    if (isset($row['actorScope']) && null !== $row['actorScope']) {
        fwrite(STDOUT, sprintf("  actorScope: %s\n", $row['actorScope']));
    }
    if (null !== $row['identifierValue']) {
        fwrite(STDOUT, sprintf("  %s: %s\n", $row['identifierField'], (string) $row['identifierValue']));
    }

    foreach ($row['candidates'] as $index => $candidate) {
        fwrite(STDOUT, sprintf("  candidate #%d: %s\n", $index + 1, $candidate['className']));
        fwrite(STDOUT, sprintf("    path: %s\n", $candidate['relativePath']));
        fwrite(STDOUT, sprintf("    classExists: %s\n", $candidate['classExists'] ? 'yes' : 'no'));
        fwrite(STDOUT, sprintf("    fileExists: %s\n", $candidate['fileExists'] ? 'yes' : 'no'));
    }

    fwrite(STDOUT, "\n");
}

/**
 * @return array<string, list<string>>
 */
function parseArguments(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            continue;
        }

        $argument = substr($argument, 2);
        [$nameEntity, $value] = array_pad(explode('=', $argument, 2), 2, '1');
        $options[$nameEntity] ??= [];
        $options[$nameEntity][] = $value;
    }

    return $options;
}

function usage(): string
{
    return <<<'TXT'
Usage:
  php tools/cruding/entrypoint-map-audit.php --path=/alpha/index [--path=/alpha/attachment/media/edit/123] [--format=text|json]

This is a read-only audit. It prints URI-derived entrypoint candidates and never creates files.

TXT;
}

/**
 * @return list<string>
 */
function operationTokens(string $path): array
{
    $tokens = [];
    $insideOperation = false;
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, 'cruding.reserved_route_token.operation:')) {
            $insideOperation = true;
            continue;
        }

        if ($insideOperation && preg_match('/^cruding\.reserved_route_token\.[a-zA-Z0-9_.-]+:/', $trimmed)) {
            $insideOperation = false;
        }

        if ($insideOperation && preg_match('/^-\s*([a-zA-Z0-9_-]+)$/', $trimmed, $matches)) {
            $tokens[] = strtolower($matches[1]);
        }
    }

    return array_values(array_unique($tokens));
}

/**
 * @param list<string> $operationTokens
 * @return array{resourcePath: string, operation: string, identifierField: string, identifierValue: string|int|null, actorScope?: ?string}
 */
function resolveIntent(string $path, array $operationTokens): array
{
    $segments = array_values(array_filter(explode('/', trim(normalizePath($path), '/')), static fn (string $segment): bool => '' !== $segment));
    if ([] !== $segments && 'api' === strtolower($segments[0])) {
        $segments = array_values(array_slice($segments, 1));
    }
    $actorScope = null;
    if ([] !== $segments && 'my' === strtolower($segments[0])) {
        $actorScope = 'my';
        $segments = array_values(array_slice($segments, 1));
    }

    if ([] === $segments) {
        return [
            'resourcePath' => '',
            'operation' => 'index',
            'identifierField' => 'id',
            'identifierValue' => null,
            'actorScope' => $actorScope,
        ];
    }

    $last = strtolower((string) end($segments));
    $beforeLast = 2 <= count($segments) ? strtolower($segments[count($segments) - 2]) : null;

    if (in_array($last, $operationTokens, true)) {
        $resource = array_slice($segments, 0, -1);

        return [
            'resourcePath' => implode('/', $resource),
            'operation' => $last,
            'identifierField' => 'id',
            'identifierValue' => null,
            'actorScope' => $actorScope,
        ];
    }

    if (null !== $beforeLast && in_array($beforeLast, $operationTokens, true)) {
        $identifier = $segments[count($segments) - 1];
        $resource = array_slice($segments, 0, -2);

        return [
            'resourcePath' => implode('/', $resource),
            'operation' => $beforeLast,
            'identifierField' => ctype_digit($identifier) ? 'id' : 'slug',
            'identifierValue' => ctype_digit($identifier) ? (int) $identifier : $identifier,
            'actorScope' => $actorScope,
        ];
    }

    if (1 === count($segments)) {
        return [
            'resourcePath' => implode('/', $segments),
            'operation' => 'index',
            'identifierField' => 'id',
            'identifierValue' => null,
            'actorScope' => $actorScope,
        ];
    }

    $identifier = $segments[count($segments) - 1];
    $resource = array_slice($segments, 0, -1);

    return [
        'resourcePath' => implode('/', $resource),
        'operation' => 'show',
        'identifierField' => ctype_digit($identifier) ? 'id' : 'slug',
        'identifierValue' => ctype_digit($identifier) ? (int) $identifier : $identifier,
    ];
}

function normalizePath(string $path): string
{
    $path = trim($path);
    if ('' === $path) {
        return '/';
    }

    return '/'.trim($path, '/');
}

function classNameToPath(string $className, string $appSrc): string
{
    $prefix = 'App\\';
    if (!str_starts_with($className, $prefix)) {
        return str_replace('\\', '/', $className).'.php';
    }

    $relative = substr($className, strlen($prefix));

    return $appSrc.'/'.str_replace('\\', '/', $relative).'.php';
}
