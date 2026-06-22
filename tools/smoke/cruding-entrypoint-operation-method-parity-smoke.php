<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tokens = readFileStrict($root.'/config/cruding_reserved_token.yaml');
$controller = readFileStrict($root.'/src/Controller/Crud/CrudController.php');
$classResolver = readFileStrict($root.'/src/Resolver/Crud/CrudServiceClassNameResolver.php');
$context = readFileStrict($root.'/src/Dto/Crud/Entrypoint/CrudEntrypointContext.php');
$invoker = readFileStrict($root.'/src/Invoker/Crud/CrudServiceInvoker.php');
$abstract = readFileStrict($root.'/src/Service/Crud/AbstractCrudService.php');

$configOperations = extractConfiguredOperations($tokens);
assert([] !== $configOperations, 'Configured CRUD operation token list must not be empty.');

foreach (['index', 'show', 'new', 'create', 'edit', 'update', 'delete', 'bulk', 'import', 'export', 'archive', 'restore', 'duplicate'] as $operation) {
    assert(in_array($operation, $configOperations, true), sprintf('Operation token "%s" must remain configured.', $operation));
}

foreach (['CrudServiceRunner', 'runEntrypointOnly', 'DEFAULT_OPERATION_HANDLER'] as $needle) {
    assert(str_contains($controller, $needle), sprintf('Tokenized controller must keep entrypoint parity via %s.', $needle));
}

foreach (['httpMethod', 'isHttpMethod', 'isGet', 'isPost', 'isPut', 'isPatch', 'isDelete', 'isOperation', 'isGrounded'] as $needle) {
    $haystack = 'isGrounded' === $needle ? $invoker.$abstract : $context;
    assert(str_contains($haystack, $needle), sprintf('Entrypoint method contract must expose %s.', $needle));
}

assert(str_contains($classResolver, '$context->operation'), 'URI-derived class resolver must include operation token from CrudContext.');
assert(str_contains($classResolver, '$root.implode'), 'URI-derived class name must include root resource + tail + operation.');

fwrite(STDOUT, sprintf(
    "PASS: %d CRUD operation tokens reach method-aware URI-derived entrypoint runner through tokenized controller.\n",
    count($configOperations)
));

/**
 * @return list<string>
 */
function extractConfiguredOperations(string $tokens): array
{
    $values = [];
    $inOperationBlock = false;

    foreach (preg_split('/\R/', $tokens) ?: [] as $line) {
        if (preg_match('/^\s*cruding\.reserved_route_token\.operation:\s*$/', $line)) {
            $inOperationBlock = true;
            continue;
        }

        if (!$inOperationBlock) {
            continue;
        }

        if (preg_match('/^\s*cruding\.reserved_route_token\.[a-z_]+:\s*$/', $line)) {
            break;
        }

        if (preg_match('/^\s*-\s*(?<token>[a-z_]+)\s*$/', $line, $match)) {
            $values[] = $match['token'];
        }
    }

    return array_values(array_unique($values));
}

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
