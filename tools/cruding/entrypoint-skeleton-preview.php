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
$path = (string) ($options['path'][0] ?? '');
if ('' === $path) {
    fwrite(STDERR, usage());
    exit(1);
}

$style = strtolower((string) ($options['style'][0] ?? 'empty'));
$operationTokens = operationTokens($root.'/config/cruding_reserved_token.yaml');
$intent = resolveIntent($path, $operationTokens);
$context = new CrudContext(
    view: (string) ($options['view'][0] ?? 'public'),
    operation: $intent['operation'],
    resourcePath: $intent['resourcePath'],
    entityClass: (string) ($options['entity-class'][0] ?? ''),
    identifierField: $intent['identifierField'],
    identifierValue: $intent['identifierValue'],
    formTypeClass: null,
);

$classResolver = new CrudServiceClassNameResolver();
$candidates = $classResolver->candidateClassNames($context);
if ([] === $candidates) {
    fwrite(STDERR, sprintf("Cannot derive entrypoint class for path %s.\n", $path));
    exit(1);
}

$className = (string) $candidates[0];
[$namespace, $shortName] = splitClassName($className);

fwrite(STDOUT, sprintf("// Path: %s\n", normalizePath($path)));
fwrite(STDOUT, sprintf("// Resource: %s\n", $intent['resourcePath']));
fwrite(STDOUT, sprintf("// Operation: %s\n", $intent['operation']));
if (isset($intent['actorScope']) && null !== $intent['actorScope']) {
    fwrite(STDOUT, sprintf("// Actor scope: %s\n", $intent['actorScope']));
}
fwrite(STDOUT, "// writeAction: false\n");
fwrite(STDOUT, "// Preview only. Copy manually if this entrypoint should exist.\n\n");
fwrite(STDOUT, renderSkeleton($namespace, $shortName, $style));

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
  php tools/cruding/entrypoint-skeleton-preview.php --path=/alpha/attachment/media/edit/123 [--style=empty|abstract|get|post]

This is a read-only preview. It prints a self-documenting URI-derived service skeleton and never writes files.

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
        return [
            'resourcePath' => implode('/', array_slice($segments, 0, -1)),
            'operation' => $last,
            'identifierField' => 'id',
            'identifierValue' => null,
            'actorScope' => $actorScope,
        ];
    }

    if (null !== $beforeLast && in_array($beforeLast, $operationTokens, true)) {
        $identifier = $segments[count($segments) - 1];

        return [
            'resourcePath' => implode('/', array_slice($segments, 0, -2)),
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

    return [
        'resourcePath' => implode('/', array_slice($segments, 0, -1)),
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

/**
 * @return array{0: string, 1: string}
 */
function splitClassName(string $className): array
{
    $position = strrpos($className, '\\');
    if (false === $position) {
        return ['', $className];
    }

    return [substr($className, 0, $position), substr($className, $position + 1)];
}

function renderSkeleton(string $namespace, string $shortName, string $style): string
{
    $header = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n";

    return match ($style) {
        'abstract' => $header."\nuse App\\Cruding\\Service\\Crud\\Entrypoint\\AbstractCrudService;\n\nfinal class {$shortName} extends AbstractCrudService\n{\n}\n",
        'get' => $header."\nuse App\\Cruding\\Dto\\Crud\\Entrypoint\\CrudServiceContext;\nuse App\\Cruding\\Dto\\Crud\\Entrypoint\\CrudServiceResult;\nuse App\\Cruding\\ServiceInterface\\Crud\\Entrypoint\\CrudGetServiceInterface;\n\nfinal class {$shortName} implements CrudGetServiceInterface\n{\n    public function get(CrudServiceContext ".'$'."context): ?CrudServiceResult\n    {\n        return null;\n    }\n}\n",
        'post' => $header."\nuse App\\Cruding\\Dto\\Crud\\Entrypoint\\CrudServiceContext;\nuse App\\Cruding\\Dto\\Crud\\Entrypoint\\CrudServiceResult;\nuse App\\Cruding\\ServiceInterface\\Crud\\Entrypoint\\CrudPostServiceInterface;\n\nfinal class {$shortName} implements CrudPostServiceInterface\n{\n    public function post(CrudServiceContext ".'$'."context): ?CrudServiceResult\n    {\n        return null;\n    }\n}\n",
        default => $header."\nfinal class {$shortName}\n{\n}\n",
    };
}
