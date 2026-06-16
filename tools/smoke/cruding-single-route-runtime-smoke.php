<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

if (is_file($root.'/config/routes.php')) {
    fwrite(STDERR, "config/routes.php duplicates the canonical split YAML route runtime.\n");
    exit(1);
}

echo "PASS: Cruding uses only the canonical split YAML route runtime.\n";
