<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$result = readRequired($root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResult.php');
$resolution = readRequired($root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResolution.php');
$abstract = readRequired($root.'/src/Service/Crud/Entrypoint/AbstractCrudEntrypointService.php');
$behavior = readRequired($root.'/src/Service/Crud/Entrypoint/CrudDefaultEntrypointBehavior.php');
$registry = readRequired($root.'/src/Service/Crud/Entrypoint/CrudDefaultEntrypointRegistry.php');
$resolver = readRequired($root.'/src/Service/Crud/Entrypoint/CrudEntrypointResolver.php');
$invoker = readRequired($root.'/src/Service/Crud/Entrypoint/CrudEntrypointInvoker.php');

foreach (['STATUS_DEFAULT_BEHAVIOR', 'STATUS_DEFAULT_BEHAVIOR_UNAVAILABLE'] as $constant) {
    assert(str_contains($result, 'public const '.$constant), sprintf('Result must expose %s.', $constant));
}

assert(str_contains($resolution, 'STATUS_DEFAULT_SERVICE'), 'Resolution must expose contextual default selection.');
assert(str_contains($resolution, 'fallbackReason'), 'Resolution must retain fallback diagnostics.');
assert(str_contains($abstract, 'setDefaultBehavior'), 'Base service must receive default behavior.');
assert(str_contains($abstract, 'beforeDefault'), 'Base service must expose a pre-default hook.');
assert(str_contains($abstract, 'afterDefault'), 'Base service must expose a post-default hook.');
assert(str_contains($abstract, '$this->defaultBehavior->execute($context)'), 'A thin subclass must inherit executable behavior.');
assert(str_contains($behavior, 'CrudEntrypointBehaviorInterface'), 'Default behavior must implement the executable contract.');
assert(str_contains($registry, 'DefaultCrudIndexService'), 'Registry must provide action-specific defaults.');
assert(strpos($resolver, 'candidateServiceIds') < strpos($resolver, 'candidateClassNames'), 'Explicit service ids must remain first.');
assert(str_contains($resolver, 'CrudDefaultEntrypointRegistry'), 'Resolver must select a contextual default service.');
assert(str_contains($resolver, 'STATUS_DEFAULT_SERVICE'), 'Resolver must return a default service when no consumer service is selected.');
assert(str_contains($invoker, 'CrudEntrypointResult::STATUS_NO_ENTRYPOINT_OVERRIDE'), 'Invoker must keep safe normalization for consumer overrides.');

fwrite(STDOUT, "PASS: EntryPoint contract provides contextual defaults and template hooks.\n");

function readRequired(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Cannot read %s.', $path));

    return $content;
}
