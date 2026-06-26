<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Dto/Crud/Entrypoint/CrudServiceContext.php',
    'src/Dto/Crud/Entrypoint/CrudServiceResult.php',
    'src/Dto/Crud/Entrypoint/CrudServiceResolution.php',
    'src/Service/Crud/AbstractCrudService.php',
    'src/Service/Crud/AbstractCrudIndexService.php',
    'src/Service/Crud/AbstractCrudShowService.php',
    'src/Service/Crud/AbstractCrudCreateService.php',
    'src/Service/Crud/AbstractCrudEditService.php',
    'src/Service/Crud/CrudDefaultServiceBehavior.php',
    'src/Service/Crud/CrudDefaultServiceRegistry.php',
    'src/Service/Crud/DefaultCrudService.php',
    'src/Service/Crud/DefaultCrudIndexService.php',
    'src/Service/Crud/DefaultCrudShowService.php',
    'src/Service/Crud/DefaultCrudCreateService.php',
    'src/Service/Crud/DefaultCrudEditService.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudServiceBehaviorInterface.php',
];

foreach ($requiredFiles as $relativePath) {
    assert(is_file($root.'/'.$relativePath), sprintf('Missing entrypoint file: %s', $relativePath));
}

require_once $root.'/src/Dto/Crud/CrudContext.php';
require_once $root.'/src/Resolver/Crud/CrudServiceClassNameResolver.php';

$classNameResolver = new App\Cruding\Resolver\Crud\CrudServiceClassNameResolver();
$candidates = $classNameResolver->candidateClassNames(new App\Cruding\Dto\Crud\CrudContext(
    view: 'public',
    operation: 'edit',
    resourcePath: 'vendor/attachment/document',
    entityClass: 'App\\Entity\\DocumentEntity',
    identifierField: 'id',
    identifierValue: 123,
    formTypeClass: null,
));

assert([
    'App\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentEditService',
    'App\\Service\\Http\\Vendor\\Attachment\\Document\\AttachmentDocumentEditService',
] === $candidates, 'Host entrypoint service names must remain canonical.');

$componentCandidates = $classNameResolver->candidateClassNames(new App\Cruding\Dto\Crud\CrudContext(
    view: 'public',
    operation: 'index',
    resourcePath: 'vendor',
    entityClass: 'App\\Vendoring\\Entity\\Vendor\\VendorEntity',
    identifierField: 'id',
    identifierValue: null,
    formTypeClass: null,
));

assert([
    'App\\Vendoring\\Service\\Http\\Vendor\\VendorIndexService',
    'App\\Service\\Http\\Vendor\\VendorIndexService',
] === $componentCandidates, 'Component entrypoint must be preferred before the host fallback class.');

$abstract = file_get_contents($root.'/src/Service/Crud/AbstractCrudService.php');
$behavior = file_get_contents($root.'/src/Service/Crud/CrudDefaultServiceBehavior.php');
$registry = file_get_contents($root.'/src/Service/Crud/CrudDefaultServiceRegistry.php');
$resolver = file_get_contents($root.'/src/Resolver/Crud/CrudServiceResolver.php');
$resolution = file_get_contents($root.'/src/Dto/Crud/Entrypoint/CrudServiceResolution.php');

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
