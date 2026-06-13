<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeComposerInventory;

/**
 * Reads composer.json and composer.lock package inventory from the Symfony host project.
 */
final readonly class CrudRuntimeComposerInventoryReader
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function read(): CrudRuntimeComposerInventory
    {
        $composerJsonPath = $this->projectDir.'/composer.json';
        $composerLockPath = $this->projectDir.'/composer.lock';

        return new CrudRuntimeComposerInventory(
            projectDir: $this->projectDir,
            composerJsonPath: is_file($composerJsonPath) ? $composerJsonPath : null,
            composerLockPath: is_file($composerLockPath) ? $composerLockPath : null,
            declaredPackageNames: is_file($composerJsonPath) ? $this->readComposerJsonPackageNames($composerJsonPath) : [],
            installedPackageNames: is_file($composerLockPath) ? $this->readComposerLockPackageNames($composerLockPath) : [],
        );
    }

    /**
     * @return list<string>
     */
    private function readComposerJsonPackageNames(string $path): array
    {
        $payload = $this->readJsonFile($path);
        $packages = [];
        foreach (['require', 'require-dev', 'replace', 'provide'] as $section) {
            $values = $payload[$section] ?? null;
            if (!\is_array($values)) {
                continue;
            }

            foreach (array_keys($values) as $packageName) {
                if (\is_string($packageName) && str_contains($packageName, '/')) {
                    $packages[$packageName] = $packageName;
                }
            }
        }

        return array_values($packages);
    }

    /**
     * @return list<string>
     */
    private function readComposerLockPackageNames(string $path): array
    {
        $payload = $this->readJsonFile($path);
        $packages = [];
        foreach (['packages', 'packages-dev'] as $section) {
            $values = $payload[$section] ?? null;
            if (!\is_array($values)) {
                continue;
            }

            foreach ($values as $package) {
                if (!\is_array($package)) {
                    continue;
                }

                $nameEntity = $package['nameEntity'] ?? null;
                if (\is_string($nameEntity) && str_contains($nameEntity, '/')) {
                    $packages[$nameEntity] = $nameEntity;
                }
            }
        }

        return array_values($packages);
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            return [];
        }

        $payload = json_decode($contents, true);

        return \is_array($payload) ? $payload : [];
    }
}
