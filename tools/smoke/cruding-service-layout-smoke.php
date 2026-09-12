<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

foreach ([
    'src/Service/Crud',
    'src/ServiceInterface/Crud',
    'src/Controller/Crud',
    'src/Controller/Api/Crud',
    'src/Builder/Crud',
    'src/Dispatcher/Crud',
    'src/Factory/Crud',
    'src/Guard/Crud',
    'src/Handler/Crud',
    'src/Invoker/Crud',
    'src/Parser/Crud',
    'src/Provider/Crud',
    'src/Resolver/Crud',
    'src/Runner/Crud',
] as $directory) {
    if (is_dir($root.'/'.$directory)) {
        exit(1);
    }
}

foreach (['Runtime', 'Resource', 'Operation'] as $directory) {
    assert(is_dir($root.'/src/Service/'.$directory), 'Missing canonical Service/'.$directory.' directory.');
}

assert(is_dir($root.'/src/Factory/Api'), 'Missing canonical Factory/Api directory.');
assert(is_dir($root.'/src/Normalizer'), 'Missing canonical Normalizer directory.');
assert(is_dir($root.'/src/Responder'), 'Missing canonical Responder directory.');

echo "PASS: canonical role-first Cruding service tree.\n";
