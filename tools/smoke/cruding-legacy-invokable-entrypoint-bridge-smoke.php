<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$invokerPath = $root.'/src/Invoker/Crud/CrudServiceInvoker.php';
$resolverPath = $root.'/src/Resolver/Crud/CrudServiceResolver.php';
$resultPath = $root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResult.php';

$invoker = file_get_contents($invokerPath);
$resolver = file_get_contents($resolverPath);
$result = file_get_contents($resultPath);

assert(false !== $invoker, 'Cannot read CrudServiceInvoker.');
assert(false !== $resolver, 'Cannot read CrudServiceResolver.');
assert(false !== $result, 'Cannot read CrudEntrypointResult.');

assert(str_contains($resolver, 'is_callable($service)'), 'Resolver must keep invokable services raw instead of wrapping them as passive services.');
assert(str_contains($invoker, "isPublicCallable(\$entrypoint, '__invoke')"), 'Invoker must detect public legacy __invoke fallback.');
assert(str_contains($invoker, 'callLegacyInvokable'), 'Invoker must route legacy invokable fallback through a dedicated fail-soft method.');
assert(str_contains($invoker, '$entrypoint($context->request)'), 'Legacy __invoke bridge must call existing services with the Symfony request object.');
assert(str_contains($result, 'STATUS_LEGACY_INVOKABLE_FAILED'), 'Legacy invokable failures must have a stable diagnostic status.');
assert(str_contains($invoker, 'CrudEntrypointResult::STATUS_LEGACY_INVOKABLE_FAILED'), 'Legacy invokable exceptions must degrade to continue-default diagnostics.');

$hookCallOffset = strpos($invoker, 'match ($method)');
$legacyCallOffset = strpos($invoker, 'callLegacyInvokable');
assert(false !== $hookCallOffset && false !== $legacyCallOffset && $hookCallOffset < $legacyCallOffset, 'HTTP get/post/etc. hooks must remain preferred before legacy __invoke fallback.');

fwrite(STDOUT, "PASS: Legacy __invoke entrypoint bridge is fail-soft and runs only after method-specific hooks.\n");
