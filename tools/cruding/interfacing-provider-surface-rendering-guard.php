<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/ServiceInterface/Crud/CrudInterfacingProviderSurfaceBuilderInterface.php',
    'src/Service/Crud/CrudInterfacingProviderSurfaceBuilder.php',
    'src/Controller/Crud/CrudIndexController.php',
    'src/Controller/Crud/CrudShowController.php',
    'src/Controller/Crud/CrudCreateController.php',
    'src/Controller/Crud/CrudEditController.php',
];

foreach ($requiredFiles as $relativePath) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        fwrite(STDERR, sprintf("Missing required file: %s\n", $relativePath));
        exit(1);
    }
}

$controllerFiles = [
    'src/Controller/Crud/CrudIndexController.php',
    'src/Controller/Crud/CrudShowController.php',
    'src/Controller/Crud/CrudCreateController.php',
    'src/Controller/Crud/CrudEditController.php',
];

foreach ($controllerFiles as $relativePath) {
    $contents = file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if (!is_string($contents)) {
        fwrite(STDERR, sprintf("Unable to read controller: %s\n", $relativePath));
        exit(1);
    }

    if (!str_contains($contents, 'CrudInterfacingProviderSurfaceBuilderInterface')) {
        fwrite(STDERR, sprintf("Controller does not use provider surface builder: %s\n", $relativePath));
        exit(1);
    }

    if (!str_contains($contents, "'interfacing/bridge/provider_surface.html.twig'")) {
        fwrite(STDERR, sprintf("Controller does not render Interfacing provider surface: %s\n", $relativePath));
        exit(1);
    }

    if (preg_match('/render\(\s*\$page->template/', $contents) === 1) {
        fwrite(STDERR, sprintf("Controller still renders its local Cruding template as primary UI: %s\n", $relativePath));
        exit(1);
    }
}

$builder = file_get_contents($root . '/src/Service/Crud/CrudInterfacingProviderSurfaceBuilder.php');
if (!is_string($builder)) {
    fwrite(STDERR, "Unable to read provider surface builder.\n");
    exit(1);
}

$requiredBuilderMarkers = [
    'primaryProvider',
    'ant-design-procomponents',
    'secondaryProvider',
    'primereact',
    'renderingTemplate',
    'interfacing/bridge/provider_surface.html.twig',
    'localTwigShellPrimaryRendering',
];

foreach ($requiredBuilderMarkers as $marker) {
    if (!str_contains($builder, $marker)) {
        fwrite(STDERR, sprintf("Provider surface builder missing marker: %s\n", $marker));
        exit(1);
    }
}

$services = file_get_contents($root . '/config/services.yaml');
if (!is_string($services) || !str_contains($services, 'CrudInterfacingProviderSurfaceBuilderInterface')) {
    fwrite(STDERR, "Provider surface builder interface is not wired in config/services.yaml.\n");
    exit(1);
}

fwrite(STDOUT, "Cruding provider-surface rendering guard passed.\n");
