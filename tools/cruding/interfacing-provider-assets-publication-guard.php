<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$requiredFiles = [
    'public/interfacing/admin-body/provider-registry.js' => ['InterfacingAdminBodyProviderRegistry', 'registerProvider'],
    'public/interfacing/admin-body/runtime.js' => ['data-interfacing-admin-body-mount', 'provider.mount'],
    'public/interfacing/admin-body/canonical-providers.js' => ['InterfacingAntDesignProAdminBodyProvider', 'InterfacingPrimeReactAdminBodyProvider'],
    'public/interfacing/admin-body/canonical-providers.interfacing-interface-ui.css' => ['ant', 'body'],
    'public/interfacing/admin-body/providers/antd-pro.js' => ['antd-pro', 'attachAntDesignProProvider'],
    'public/interfacing/admin-body/providers/primereact.js' => ['primereact', 'attachPrimeReactProvider'],
];

$errors = [];
foreach ($requiredFiles as $relativePath => $needles) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        $errors[] = sprintf('Missing Interfacing provider asset: %s', $relativePath);
        continue;
    }

    $content = (string) file_get_contents($path);
    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            $errors[] = sprintf('Interfacing provider asset %s does not contain expected marker %s', $relativePath, $needle);
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo 'Cruding Interfacing provider asset publication guard passed.' . PHP_EOL;
