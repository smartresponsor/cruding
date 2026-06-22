<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$passivePath = $root.'/src/Service/Crud/PassiveCrudService.php';
$abstractPath = $root.'/src/Service/Crud/AbstractCrudService.php';

$passive = file_get_contents($passivePath);
$abstract = file_get_contents($abstractPath);

assert(false !== $passive, 'Cannot read PassiveCrudService.php.');
assert(false !== $abstract, 'Cannot read AbstractCrudService.php.');
assert(str_contains($passive, 'final class PassiveCrudService extends AbstractCrudService'), 'Passive entrypoint service must extend the abstract entrypoint service without readonly inheritance mismatch.');
assert(!str_contains($passive, 'readonly class PassiveCrudService'), 'Passive entrypoint service must not be readonly while its abstract parent is non-readonly.');
assert(str_contains($abstract, 'abstract class AbstractCrudService'), 'Abstract entrypoint service must remain the non-readonly base class for hook defaults.');

fwrite(STDOUT, "PASS: EntryPoint inheritance has no readonly/non-readonly mismatch for passive services.\n");
