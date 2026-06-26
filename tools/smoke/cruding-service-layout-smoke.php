<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

foreach (['Api', 'Runtime', 'view'] as $directory) {
    if (is_dir($root.'/src/Service/'.$directory)) {
        exit(1);
    }
}

echo "PASS: categorized Cruding service tree.\n";
