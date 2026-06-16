<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$requiredFiles = [
    'src/Dto/Crud/Entrypoint/CrudEntrypointContext.php',
    'src/Dto/Crud/Entrypoint/CrudEntrypointResult.php',
    'src/Dto/Crud/Entrypoint/CrudEntrypointResolution.php',
    'src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointClassNameResolver.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointDispatcher.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointExplicitServiceResolver.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointInvoker.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointOperationRunner.php',
    'src/Service/Crud/Entrypoint/CrudEntrypointResolver.php',
    'src/Service/Crud/Entrypoint/NullCrudEntrypointService.php',
    'src/Service/Crud/Entrypoint/PassiveCrudEntrypointService.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudEntrypointDispatcherInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudEntrypointServiceInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudGroundedEntrypointInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudGetEntrypointInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudPostEntrypointInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudPutEntrypointInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudPatchEntrypointInterface.php',
    'src/ServiceInterface/Crud/Entrypoint/CrudDeleteEntrypointInterface.php',
];

foreach ($requiredFiles as $relativePath) {
    assert(is_file($root.'/'.$relativePath), sprintf('Missing entrypoint file: %s', $relativePath));
}

require_once $root.'/src/Dto/Crud/CrudContext.php';
require_once $root.'/src/Service/Crud/Entrypoint/CrudEntrypointClassNameResolver.php';

$classNameResolver = new App\Cruding\Service\Crud\Entrypoint\CrudEntrypointClassNameResolver();
$classNameCandidates = $classNameResolver->candidateClassNames(
    new App\Cruding\Dto\Crud\CrudContext(
        surface: 'public',
        operation: 'edit',
        resourcePath: 'vendor/attachment/document',
        entityClass: 'App\\Entity\\DocumentEntity',
        identifierField: 'id',
        identifierValue: 123,
        formTypeClass: null,
    ),
);

assert(
    [
        'App\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentEditService',
        'App\\Service\\Http\\Vendor\\Attachment\\Document\\AttachmentDocumentEditService',
    ] === $classNameCandidates,
    'Entrypoint class resolver must derive canonical App\\Service\\Http root/tail/operation service classes.',
);

$explicitResolver = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudEntrypointExplicitServiceResolver.php');
$abstract = file_get_contents($root.'/src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php');
$dispatcher = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudEntrypointDispatcher.php');
$invoker = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudEntrypointInvoker.php');
$resolver = file_get_contents($root.'/src/Service/Crud/Entrypoint/CrudEntrypointResolver.php');

assert(false !== $explicitResolver && str_contains($explicitResolver, '_crud_entrypoint_service'), 'Explicit registered service lookup must remain first-class.');
assert(false !== $resolver && strpos($resolver, 'candidateServiceIds') < strpos($resolver, 'candidateClassNames'), 'Resolver must check explicit service ids before URI-derived class names.');
assert(false !== $resolver && str_contains($resolver, 'CrudEntrypointResolution::STATUS_CLASS_EXISTS_BUT_NOT_REGISTERED'), 'Resolver must fail softly when class exists but is not registered.');
assert(false !== $resolver && str_contains($resolver, 'CrudEntrypointResolution::STATUS_MISSING'), 'Resolver must provide a missing-class null fallback.');
assert(false !== $dispatcher && str_contains($dispatcher, 'CrudEntrypointOperationRunner'), 'Entrypoint dispatcher must delegate to the operation runner.');
assert(false !== $dispatcher && str_contains($dispatcher, '$this->operationRunner->tryRun('), 'Entrypoint dispatcher must preserve fail-soft tryRun dispatch.');

foreach (['isGrounded', 'get', 'post', 'put', 'patch', 'delete'] as $method) {
    assert(false !== $abstract && str_contains($abstract, 'function '.$method.'('), sprintf('Abstract entrypoint must provide %s default.', $method));
    assert(false !== $invoker && str_contains($invoker, $method), sprintf('Invoker must know %s hook.', $method));
}

$indexOperation = file_get_contents($root.'/src/Service/Crud/Operation/CrudIndexOperation.php');
assert(false !== $indexOperation, 'Cannot read CrudIndexOperation.');
assert(str_contains($indexOperation, 'CrudEntrypointDispatcherInterface'), 'CrudIndexOperation must depend on the typed entrypoint dispatcher contract.');
assert(str_contains($indexOperation, '$this->entrypointDispatcher->tryRun('), 'CrudIndexOperation must dispatch the resolved CRUD context through the entrypoint dispatcher.');

foreach (['CrudShowOperation', 'CrudCreateOperation', 'CrudEditOperation', 'CrudDeleteOperation'] as $operationClass) {
    $path = sprintf('%s/src/Service/Crud/Operation/%s.php', $root, $operationClass);
    $code = file_get_contents($path);
    assert(false !== $code, sprintf('Cannot read %s.', $operationClass));
    assert(str_contains($code, 'CrudEntrypointOperationRunner'), sprintf('%s must call entrypoint runner.', $operationClass));
}

fwrite(STDOUT, "PASS: URI-derived CRUD entrypoints are fail-soft, method-aware, and not collapsed into a per-resource mega-service.\n");
