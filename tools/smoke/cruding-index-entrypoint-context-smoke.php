<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$operation = file_get_contents($root.'/src/Service/Crud/Operation/CrudIndexOperation.php');
$controller = file_get_contents($root.'/src/Controller/Crud/CrudTokenizedController.php');

if (!is_string($operation) || !is_string($controller)) {
    fwrite(STDERR, "CRUD index dispatch source is unavailable.\n");
    exit(1);
}

if (str_contains($operation, "entityClass: ''") || str_contains($operation, 'tryExplicitRouteEntrypoint')) {
    fwrite(STDERR, "CRUD index operation must not manufacture an unresolved context.\n");
    exit(1);
}

if (!str_contains($controller, "'index' => \$this->runIndex(\$request)")) {
    fwrite(STDERR, "Tokenized controller must own index entrypoint dispatch.\n");
    exit(1);
}

if (!str_contains($controller, '\$this->entrypointRunner->tryRun(\$request, \$context)')) {
    fwrite(STDERR, "Index entrypoint dispatch must use a resolved CRUD context.\n");
    exit(1);
}

echo "PASS: CRUD index entrypoints use the resolved context owned by tokenized dispatch.\n";
