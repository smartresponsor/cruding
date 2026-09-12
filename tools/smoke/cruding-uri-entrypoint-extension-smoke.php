<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/DTO/Entrypoint/CrudServiceContextDTO.php',
    'src/DTO/Entrypoint/CrudServiceResultDTO.php',
    'src/DTO/Entrypoint/CrudServiceResolutionDTO.php',
    'src/Service/AbstractCrudService.php',
    'src/Service/AbstractCrudIndexService.php',
    'src/Service/AbstractCrudShowService.php',
    'src/Service/AbstractCrudCreateService.php',
    'src/Service/AbstractCrudEditService.php',
    'src/Service/CrudDefaultServiceBehavior.php',
    'src/Service/CrudDefaultServiceRegistry.php',
    'src/Service/DefaultCrudService.php',
    'src/Service/DefaultCrudIndexService.php',
    'src/Service/DefaultCrudShowService.php',
    'src/Service/DefaultCrudCreateService.php',
    'src/Service/DefaultCrudEditService.php',
    'src/ServiceInterface/Entrypoint/CrudServiceBehaviorInterface.php',
];

foreach ($requiredFiles as $relativePath) {
    assert(is_file($root.'/'.$relativePath), sprintf('Missing entrypoint file: %s', $relativePath));
}

require_once $root.'/src/DTO/CrudContextDTO.php';
require_once $root.'/src/Resolver/CrudServiceClassNameResolver.php';

$classNameResolver = new App\Cruding\Resolver\CrudServiceClassNameResolver();
$candidates = $classNameResolver->candidateShortClassNames(new App\Cruding\DTO\CrudContextDTO(
    view: 'public',
    operation: 'edit',
    resourcePath: 'vendor/attachment/document',
    entityClass: 'App\\Entity\\DocumentEntity',
    identifierField: 'id',
    identifierValue: 123,
    formTypeClass: null,
));

assert([
    'VendorAttachmentDocumentEditService',
    'AttachmentDocumentEditService',
] === $candidates, 'Host entrypoint short service names must remain canonical.');

$componentCandidates = $classNameResolver->candidateShortClassNames(new App\Cruding\DTO\CrudContextDTO(
    view: 'public',
    operation: 'index',
    resourcePath: 'vendor',
    entityClass: 'App\\Vendoring\\Entity\\Vendor\\VendorEntity',
    identifierField: 'id',
    identifierValue: null,
    formTypeClass: null,
));

assert([
    'VendorIndexService',
] === $componentCandidates, 'Component entrypoint short service name must be derived.');

$abstract = file_get_contents($root.'/src/Service/AbstractCrudService.php');
$behavior = file_get_contents($root.'/src/Service/CrudDefaultServiceBehavior.php');
$registry = file_get_contents($root.'/src/Service/CrudDefaultServiceRegistry.php');
$resolver = file_get_contents($root.'/src/Resolver/CrudServiceResolver.php');
$resolution = file_get_contents($root.'/src/DTO/Entrypoint/CrudServiceResolutionDTO.php');

assert(false !== $resolver && strpos($resolver, 'candidateServiceIds') < strpos($resolver, 'candidateClassNames'), 'Explicit service ids must be checked before URI-derived classes.');
assert(false !== $resolver && str_contains($resolver, 'CrudDefaultServiceRegistry'), 'Resolver must select a contextual default service.');
assert(false !== $resolver && str_contains($resolver, 'STATUS_DEFAULT_SERVICE'), 'Resolver must return a default service when consumer service is absent.');
assert(false !== $resolution && str_contains($resolution, 'fallbackReason'), 'Resolution diagnostics must preserve the fallback reason.');
assert(false !== $abstract && str_contains($abstract, '#[Required]'), 'Base entrypoint must receive behavior without constructor coupling.');
assert(false !== $abstract && str_contains($abstract, 'beforeDefault'), 'Base entrypoint must expose a pre-default hook.');
assert(false !== $abstract && str_contains($abstract, 'afterDefault'), 'Base entrypoint must expose a post-default hook.');
assert(false !== $abstract && str_contains($abstract, '$this->defaultBehavior->execute($context)'), 'A thin subclass must inherit executable behavior.');
assert(false !== $behavior && str_contains($behavior, "'index' =>"), 'Default behavior must implement index.');
assert(false !== $behavior && str_contains($behavior, "'show' =>"), 'Default behavior must implement show.');
assert(false !== $behavior && str_contains($behavior, "'new', 'create' =>"), 'Default behavior must implement create flow.');
assert(false !== $behavior && str_contains($behavior, "'edit', 'update' =>"), 'Default behavior must implement edit flow.');
assert(false !== $behavior && str_contains($behavior, 'crud_operation_not_supported'), 'Unknown operations must end safely.');
assert(false !== $registry && str_contains($registry, 'DefaultCrudIndexService'), 'Registry must provide an index object.');
assert(false !== $registry && str_contains($registry, 'DefaultCrudService'), 'Registry must provide a generic terminal object.');

fwrite(STDOUT, "PASS: entrypoints resolve explicit service, component FQCN, host FQCN, or contextual default behavior.\n");
