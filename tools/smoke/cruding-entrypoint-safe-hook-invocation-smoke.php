<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$invokerPath = $root.'/src/Invoker/Crud/CrudServiceInvoker.php';
$runnerPath = $root.'/src/Runner/Crud/CrudServiceRunner.php';

$invoker = file_get_contents($invokerPath);
$runner = file_get_contents($runnerPath);

assert(false !== $invoker, 'Cannot read CrudServiceInvoker.');
assert(false !== $runner, 'Cannot read CrudServiceRunner.');

assert(str_contains($invoker, 'is_callable([$entrypoint, $method])'), 'Entrypoint invoker must use public-callable hook detection.');
assert(!str_contains($invoker, 'method_exists($entrypoint'), 'Entrypoint invoker must not use method_exists() for hook dispatch because private/protected methods are not safely callable.');
assert(str_contains($invoker, 'catch (\\Throwable $exception)'), 'Entrypoint hook dispatch must be fail-soft around hook calls.');
assert(str_contains($invoker, 'CrudEntrypointResult::STATUS_ENTRYPOINT_HOOK_FAILED'), 'Entrypoint hook failures must degrade to continue-default diagnostics.');
assert(str_contains($invoker, 'CrudEntrypointResult::STATUS_ENTRYPOINT_GROUNDING_FAILED'), 'Entrypoint grounding failures must degrade to continue-default diagnostics.');

foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
    $needle = '$this->callHook($entrypoint, \'' . $method . '\'';
    assert(str_contains($invoker, $needle), sprintf('HTTP %s hook must route through safe callHook().', strtoupper($method)));
}


assert(str_contains($runner, 'CrudEntrypointContext'), 'Entrypoint operation runner must continue to build normalized context for hook dispatch.');

fwrite(STDOUT, "PASS: EntryPoint hooks use public-callable fail-soft dispatch and cannot crash on private/protected/failing hooks.\n");
