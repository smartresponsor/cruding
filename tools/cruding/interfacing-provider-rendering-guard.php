<?php

declare(strict_types=1);

function guard_fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

$root = dirname(__DIR__, 2);
$requiredFiles = [
    'src/DependencyInjection/CrudingExtension.php',
    'src/DependencyInjection/Configuration.php',
    'src/Service/Crud/Surface/CrudSurfaceContractFactory.php',
    'config/routes/cruding_crud.yaml',
    'config/routes/cruding_api_crud.yaml',
];

foreach ($requiredFiles as $relativePath) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        guard_fail(sprintf('Required Cruding file is missing: %s', $relativePath));
    }
}

foreach ([
    'src/Service/Crud/CrudTemplateResolver.php',
    'src/ServiceInterface/Crud/CrudTemplateResolverInterface.php',
    'src/Service/Crud/CrudSurfaceResponseFactory.php',
    'templates/crud/index.html.twig',
] as $legacyFile) {
    if (is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacyFile))) {
        guard_fail(sprintf('Legacy Cruding rendering file must not exist: %s', $legacyFile));
    }
}

$factory = file_get_contents($root . '/src/Service/Crud/Surface/CrudSurfaceContractFactory.php') ?: '';
$surfaceContract = file_get_contents($root . '/src/Value/Surface/CrudSurfaceContract.php') ?: '';
$builder = file_get_contents($root . '/src/Service/Crud/Surface/CrudInterfacingProviderSurfaceBuilder.php') ?: '';
$pageProvider = file_get_contents($root . '/src/Provider/Crud/CrudPageDefinitionProvider.php') ?: '';
$extension = file_get_contents($root . '/src/DependencyInjection/CrudingExtension.php') ?: '';
$configuration = file_get_contents($root . '/src/DependencyInjection/Configuration.php') ?: '';
$contextResolver = file_get_contents($root . '/src/Resolver/Crud/CrudContextResolver.php') ?: '';
$routes = file_get_contents($root . '/config/routes.yaml') ?: '';
$services = file_get_contents($root . '/config/services.yaml') ?: '';
$composer = file_get_contents($root . '/composer.json') ?: '';

if (!str_contains($contextResolver, '$this->resourcePathParser->normalize')) {
    guard_fail('CrudContextResolver must normalize the incoming resourcePath before lookup/rendering.');
}

if (str_contains($factory . $surfaceContract . $builder . $pageProvider, '.html.twig')) {
    guard_fail('Cruding source must not contain producer-owned Twig template paths.');
}

if (str_contains($factory . $surfaceContract . $builder . $pageProvider, 'CrudTemplateResolver')) {
    guard_fail('Cruding source must not depend on a producer-owned template resolver.');
}

$extensionNeedles = [
    "new FileLocator(\\dirname(__DIR__, 2).'/config')",
    "->load('services.yaml')",
    'new Configuration()',
    "=> 'Cruding'",
];

foreach ($extensionNeedles as $needle) {
    if (!str_contains($extension, $needle)) {
        guard_fail(sprintf('CrudingExtension does not contain required bundle loading/Twig registration needle: %s', $needle));
    }
}

foreach (['resource_path_requirement', 'capability_map', 'entity_class_alias_map', 'form_type_map'] as $configNeedle) {
    if (!str_contains($configuration, $configNeedle)) {
        guard_fail(sprintf('Configuration tree misses Cruding option: %s', $configNeedle));
    }
}

if (str_contains($composer, 'objecting/object') || str_contains($composer, '../Objecting')) {
    guard_fail('Cruding must not require Objecting directly; CRUD routing stays entity-agnostic and host-mapped.');
}

$forbiddenRuntimeNeedles = [
    'provider_surface.html.twig',
    'bridgeComponent',
    'bridgeResource',
    "'vendor' ===",
    'profile/show',
];

foreach ($forbiddenRuntimeNeedles as $needle) {
    if (str_contains($factory . $surfaceContract . $builder . $pageProvider, $needle)) {
        guard_fail(sprintf('Forbidden rendering drift remains in runtime code: %s', $needle));
    }
}

$forbiddenPaths = [
    'src/Controller/ObjectMeta',
    'src/Controller/Relation',
    'src/Dto/ObjectMeta',
    'src/Dto/Relation',
    'src/Service/ObjectMeta',
    'src/Service/Relation',
    'src/ServiceInterface/ObjectMeta',
    'src/ServiceInterface/Relation',
    'template',
    'config/routes/app_crud.yaml',
    'config/routes/app_api_crud.yaml',
];

foreach ($forbiddenPaths as $relativePath) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (file_exists($path)) {
        guard_fail(sprintf('Forbidden non-CRUD path remains active: %s', $relativePath));
    }
}

$forbiddenConfigNeedles = [
    'routes/app_crud.yaml',
    'routes/app_api_crud.yaml',
    'app.capability_map',
    'app.cruding.resource_path_requirement',
    'app.cruding.entity_class_alias_map',
    'app.cruding.form_type_map',
    'KERNEL_CLASS" value="App\\Messaging',
    'testsuite nameEntity="Catalog"',
    'testsuite nameEntity="Panther"',
];

foreach ($forbiddenConfigNeedles as $needle) {
    if (str_contains($routes . $services . (file_get_contents($root . '/phpunit.xml') ?: '') . (file_get_contents($root . '/phpunit.xml.dist') ?: ''), $needle)) {
        guard_fail(sprintf('Forbidden config/test drift remains: %s', $needle));
    }
}

foreach (['symfony/config', 'symfony/dependency-injection', 'symfony/form', 'phpunit/phpunit'] as $package) {
    if (!str_contains($composer, '"'.$package.'"')) {
        guard_fail(sprintf('composer.json misses explicit Symfony bundle infrastructure package: %s', $package));
    }
}

$gitignore = is_file($root . '/.gitignore') ? (file_get_contents($root . '/.gitignore') ?: '') : '';
foreach (['/vendor/', '/composer.lock'] as $ignoredPath) {
    if (!str_contains($gitignore, $ignoredPath)) {
        guard_fail(sprintf('.gitignore must ignore local development artifact: %s', $ignoredPath));
    }
}

$testForbiddenNeedles = [
    'App\\Accessing\\',
    'App\\Cataloging\\',
    'App\\Taxating\\',
    'App\\Vendoring\\',
    'App\\Tests\\Fixture\\Entity\\Catalog\\',
    'CatalogCategory',
    'OrderEntity',
    'ownable',
    'taggable',
    'attachable',
];

$testIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/tests', FilesystemIterator::SKIP_DOTS));
foreach ($testIterator as $file) {
    if (!$file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if (!is_string($content)) {
        guard_fail('Unable to read test file: '.$file->getPathname());
    }

    foreach ($testForbiddenNeedles as $needle) {
        if (str_contains($content, $needle)) {
            guard_fail('Forbidden legacy/neighbour fixture needle "'.$needle.'" found in '.$file->getPathname());
        }
    }
}

echo "Cruding rendering guard passed.\n";

