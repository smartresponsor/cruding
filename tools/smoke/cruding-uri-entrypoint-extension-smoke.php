<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Dto/Crud/Entrypoint/CrudEntrypointContext.php',
    'src/Dto/Crud/Entrypoint/CrudEntrypointResult.php',
    'src/Dto/Crud/Entrypoint/CrudEntrypointResolution.php',
    'src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php',
    'src/Service/Crud/Entrypoint/AbstractCrudIndexService.php',
    'src/Service/Crud/Entrypoint/AbstractCrudShowService.php',
    'src/Service/Crud/Entrypoint/AbstractCrudCreateService.php',
    'src/Service/Crud/Entrypoint/AbstractCrudEditService.php',
    'src/Service/Crud/Entrypoint/CrudDefaultEntrypointBehavior.php',
    'src/Service/Crud/Entrypoint/CrudDefaultEntrypointRegistry.php',
    'src/Service/Crud/Entrypoint/DefaultCrudEntrypointService.php',
    'src/Service/Crud/Entrypoint/DefaultCrudIndexService.php',
    'src/Service/Crud/Entrypoint/DefaultCrudShowService.php',
    'src/Service/Crud/Entrypoint/DefaultCrudCreateService.php',
    'src/Service/Crud/Entrypoint/DefaultCrudEditService.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudEntrypointBehaviorInterface.php',
];

foreach ($requiredFiles as $relativePath) {
    assert(is_file($root.'/'.$relativePath), sprintf('Missing entrypoint file: %s', $relativePath));
}

require_once $root.'/src/Dto/Crud/CrudContext.php';
require_once $root.'/src/Service/Crud/Entrypoint/CrudEntrypointClassNameResolver.php';

$classNameResolver = new App\Cruding\Service\Crud\Entrypoint\CrudEntrypointClassNameResolver();
$candidates = $classNameResolver->candidateClassNames(new App\Cruding\Dto\Crud\CrudContext(
    surface: 'public',
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
] === $candidates, 'Entrypoint class resolver must preserve canonical service names.');

$abstract = file_get_contents($root.'/src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php');
$behavior = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudDefaultEntrypointBehavior.php');
$registry = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudDefaultEntrypointRegistry.php');
$resolver = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudEntrypointResolver.php');
$resolution = file_get_contents($root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResolution.php');

assert(false !== $resolver && strpos($resolver, 'candidateServiceIds') < strpos($resolver, 'candidateClassNames'), 'Explicit service ids must be checked before URI-derived classes.');
assert(false !== $resolver && str_contains($resolver, 'CrudDefaultEntrypointRegistry'), 'Resolver must select a contextual default service.');
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
assert(false !== $registry && str_contains($registry, 'DefaultCrudEntrypointService'), 'Registry must provide a generic terminal object.');

fwrite(STDOUT, "PASS: entrypoints resolve explicit service, FQCN service, or contextual default behavior.\n");
