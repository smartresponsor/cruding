<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$contextPath = $root.'/src/Dto/Crud/Entrypoint/CrudEntrypointContext.php';
$resultPath = $root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResult.php';
$resolutionPath = $root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResolution.php';
$abstractPath = $root.'/src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php';
$resolverPath = $root.'/src/Service/Crud/Entrypoint/CrudEntrypointResolver.php';
$invokerPath = $root.'/src/Service/Crud/Entrypoint/CrudEntrypointInvoker.php';
$docPath = $root.'/docs/cruding/cruding-entrypoint-contract-finalization.md';

$context = file_get_contents($contextPath);
$result = file_get_contents($resultPath);
$resolution = file_get_contents($resolutionPath);
$abstract = file_get_contents($abstractPath);
$resolver = file_get_contents($resolverPath);
$invoker = file_get_contents($invokerPath);
$doc = file_get_contents($docPath);

foreach (compact('context', 'result', 'resolution', 'abstract', 'resolver', 'invoker', 'doc') as $nameEntity => $content) {
    assert(false !== $content, sprintf('Cannot read %s.', $nameEntity));
}

foreach (['HTTP_GET', 'HTTP_POST', 'HTTP_PUT', 'HTTP_PATCH', 'HTTP_DELETE', 'SUPPORTED_HTTP_METHODS'] as $constant) {
    assert(str_contains($context, 'public const '.$constant), sprintf('CrudEntrypointContext must expose %s.', $constant));
}

foreach ([
    'STATUS_CONTINUE_DEFAULT',
    'STATUS_RESPONSE',
    'STATUS_SURFACE_CONTRACT',
    'STATUS_NOT_GROUNDED',
    'STATUS_ENTRYPOINT_GROUNDING_FAILED',
    'STATUS_ENTRYPOINT_HOOK_FAILED',
    'STATUS_NO_ENTRYPOINT_OVERRIDE',
    'STATUS_INVALID_ENTRYPOINT_RESULT_IGNORED',
] as $constant) {
    assert(str_contains($result, 'public const '.$constant), sprintf('CrudEntrypointResult must expose %s.', $constant));
}

foreach ([
    'STATUS_REGISTERED_SERVICE',
    'STATUS_URI_DERIVED_SERVICE',
    'STATUS_CLASS_EXISTS_BUT_NOT_REGISTERED',
    'STATUS_MISSING',
] as $constant) {
    assert(str_contains($resolution, 'public const '.$constant), sprintf('CrudEntrypointResolution must expose %s.', $constant));
    assert(str_contains($resolver, 'CrudEntrypointResolution::'.$constant), sprintf('Resolver must use %s instead of ad-hoc status strings.', $constant));
}

foreach (['CrudGetEntrypointInterface', 'CrudPostEntrypointInterface', 'CrudPutEntrypointInterface', 'CrudPatchEntrypointInterface', 'CrudDeleteEntrypointInterface', 'CrudGroundedEntrypointInterface'] as $interface) {
    assert(str_contains($abstract, $interface), sprintf('Abstract entrypoint service must implement %s.', $interface));
}

assert(str_contains($abstract, 'return true;'), 'Abstract entrypoint service must default isGrounded() to true.');
assert(substr_count($abstract, 'return null;') >= 5, 'Abstract entrypoint service must no-op missing HTTP hooks with null results.');
assert(str_contains($resolver, 'CrudEntrypointContext::SUPPORTED_HTTP_METHODS'), 'Resolver must use the finalized HTTP method contract for passive/raw entrypoint detection.');
assert(!str_contains($resolver, "method_exists(\$service, 'get')"), 'Resolver must not use method_exists() for public hook detection.');
assert(str_contains($invoker, 'CrudEntrypointContext::HTTP_GET'), 'Invoker must dispatch through finalized HTTP method constants.');
assert(str_contains($invoker, 'CrudEntrypointResult::STATUS_NO_ENTRYPOINT_OVERRIDE'), 'Invoker must use result status constants.');
assert(str_contains($doc, 'URI-derived'), 'Contract documentation must keep URI-derived entrypoints as the primary self-documenting model.');
assert(str_contains($doc, 'must not require `VendorCrudService`'), 'Contract documentation must reject per-resource mega-service requirements.');

fwrite(STDOUT, "PASS: EntryPoint public contract is finalized with constants, optional hooks, abstract defaults, and URI-derived self-documenting services.\n");
