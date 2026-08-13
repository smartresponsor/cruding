<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__.'/../../src/Service/Crud/CrudDefaultServiceBehavior.php');
assert(false !== $source);
assert(str_contains($source, 'CrudMutationLifecycleContext'));
assert(str_contains($source, 'CrudMutationLifecycleDispatcher'));
assert(str_contains($source, "'create'"));
assert(str_contains($source, 'mutationLifecycleDispatcher->execute'));

echo "cruding create lifecycle smoke: ok\n";

