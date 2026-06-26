<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = [
    $root.'/config/routes/cruding_crud.yaml',
    $root.'/config/routes/cruding_api_crud.yaml',
];

foreach ($routes as $routeFile) {
    $content = file_get_contents($routeFile);
    if (false === $content) {
        fwrite(STDERR, sprintf("Unable to read %s.\n", $routeFile));
        exit(1);
    }

    if (!str_contains($content, "crudPath: '[a-z0-9][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*'")) {
        fwrite(STDERR, sprintf("Expected bounded tokenized path payload regex in %s.\n", $routeFile));
        exit(1);
    }

    if (str_contains($content, "crudPath: '.+'")) {
        fwrite(STDERR, sprintf("Legacy global catch-all remains in %s.\n", $routeFile));
        exit(1);
    }
}

fwrite(STDOUT, "Cruding catch-all resource boundary smoke passed.\n");
