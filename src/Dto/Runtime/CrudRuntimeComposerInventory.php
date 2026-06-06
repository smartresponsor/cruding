<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Runtime;

/**
 * Composer package inventory visible from the Symfony host project.
 */
final readonly class CrudRuntimeComposerInventory
{
    /**
     * @param list<string> $declaredPackageNames
     * @param list<string> $installedPackageNames
     */
    public function __construct(
        public string $projectDir,
        public ?string $composerJsonPath,
        public ?string $composerLockPath,
        public array $declaredPackageNames,
        public array $installedPackageNames,
    ) {
    }

    /**
     * @return list<string>
     */
    public function allPackageNames(): array
    {
        $packages = [];
        foreach ($this->declaredPackageNames as $packageName) {
            $packages[$packageName] = $packageName;
        }
        foreach ($this->installedPackageNames as $packageName) {
            $packages[$packageName] = $packageName;
        }

        return array_values($packages);
    }

    public function hasPackage(string $packageName): bool
    {
        return \in_array($packageName, $this->declaredPackageNames, true)
            || \in_array($packageName, $this->installedPackageNames, true);
    }
}
