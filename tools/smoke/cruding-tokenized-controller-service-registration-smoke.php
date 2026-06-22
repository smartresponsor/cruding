<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$servicesPath = $root . '/config/services.yaml';
$webControllerPath = $root . '/src/Controller/Crud/CrudController.php';
$apiControllerPath = $root . '/src/Controller/Api/Crud/CrudApiController.php';

foreach ([$servicesPath, $webControllerPath, $apiControllerPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, 'Missing required file: ' . $path . PHP_EOL);
        exit(1);
    }
}

$services = file_get_contents($servicesPath);
$webController = file_get_contents($webControllerPath);
$apiController = file_get_contents($apiControllerPath);

$needles = [
    'App\\Cruding\\Controller\\Crud\\CrudController:',
    'App\\Cruding\\Controller\\Api\\Crud\\CrudApiController:',
    "tags: ['controller.service_arguments']",
    'public: true',
];

foreach ($needles as $needle) {
    if (!str_contains($services, $needle)) {
        fwrite(STDERR, 'Missing services.yaml tokenized controller registration needle: ' . $needle . PHP_EOL);
        exit(1);
    }
}

foreach ([$webControllerPath => $webController, $apiControllerPath => $apiController] as $path => $source) {
    if (!str_contains($source, 'use Symfony\\Component\\HttpKernel\\Attribute\\AsController;')) {
        fwrite(STDERR, 'Missing AsController import in ' . $path . PHP_EOL);
        exit(1);
    }

    if (!str_contains($source, '#[AsController]')) {
        fwrite(STDERR, 'Missing #[AsController] attribute in ' . $path . PHP_EOL);
        exit(1);
    }
}

echo 'PASS: tokenized CRUD controllers are explicitly registered as Symfony controller services.' . PHP_EOL;
