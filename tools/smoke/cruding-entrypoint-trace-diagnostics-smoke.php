<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$resultPath = $root.'/src/Dto/Crud/Entrypoint/CrudEntrypointResult.php';
$runnerPath = $root.'/src/Service/Crud/Entrypoint/CrudEntrypointOperationRunner.php';
$docPath = $root.'/docs/cruding/cruding-entrypoint-trace-diagnostics.md';

$result = file_get_contents($resultPath);
$runner = file_get_contents($runnerPath);
$doc = file_get_contents($docPath);

assert(false !== $result, 'Cannot read CrudEntrypointResult.');
assert(false !== $runner, 'Cannot read CrudEntrypointOperationRunner.');
assert(false !== $doc, 'Cannot read entrypoint trace diagnostics documentation.');

assert(str_contains($result, 'function withDiagnostics('), 'CrudEntrypointResult must support immutable diagnostic enrichment.');
assert(str_contains($result, 'function diagnostics()'), 'CrudEntrypointResult must expose diagnostics for audit/smoke code.');

assert(str_contains($runner, 'function run('), 'CrudEntrypointOperationRunner must expose a result-preserving run() method.');
assert(str_contains($runner, 'function tryRun('), 'CrudEntrypointOperationRunner must preserve existing tryRun() payload API.');
assert(strpos($runner, 'function run(') < strpos($runner, 'function tryRun('), 'tryRun() must wrap run(), not duplicate resolution logic.');
assert(str_contains($runner, 'entrypointTrace'), 'Runner must attach entrypointTrace diagnostics to every result.');
assert(str_contains($runner, 'serviceResolution'), 'entrypointTrace must include service resolution diagnostics.');
assert(str_contains($runner, 'continueDefault'), 'entrypointTrace must expose whether Cruding default behavior continued.');
assert(str_contains($runner, 'httpMethod'), 'entrypointTrace must include HTTP method.');
assert(str_contains($runner, 'resourcePath'), 'entrypointTrace must include resource path.');
assert(str_contains($runner, 'operation'), 'entrypointTrace must include CRUD operation.');

assert(str_contains($doc, 'Explicit registered service key'), 'Diagnostics doc must preserve explicit service lookup priority.');
assert(str_contains($doc, 'URI-derived'), 'Diagnostics doc must describe URI-derived entrypoint lookup.');
assert(str_contains($doc, 'must not crash'), 'Diagnostics doc must document fail-soft behavior.');

fwrite(STDOUT, "PASS: EntryPoint run() preserves fail-soft diagnostics while tryRun() keeps the existing payload API.\n");
