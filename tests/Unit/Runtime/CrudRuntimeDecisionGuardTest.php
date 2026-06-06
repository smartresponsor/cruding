<?php

declare(strict_types=1);

namespace App\Tests\Unit\Runtime;

use App\Cruding\Service\Runtime\CrudRuntimeComposerInventoryReader;
use App\Cruding\Service\Runtime\CrudRuntimeDecisionGuard;
use App\Cruding\Service\Runtime\CrudRuntimeLockReader;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use App\Cruding\Service\Runtime\CrudRuntimeTokenNormalizer;
use PHPUnit\Framework\TestCase;

final class CrudRuntimeDecisionGuardTest extends TestCase
{
    public function testReportsComposerAndLockMismatch(): void
    {
        $projectDir = $this->createProjectDir([
            'scope' => ['cruding', 'viewing'],
            'entity' => ['vendor'],
            'packages' => ['cruding/crud', 'viewing/view'],
        ], [
            'require' => [
                'cruding/crud' => 'dev-master',
            ],
        ]);

        $guard = new CrudRuntimeDecisionGuard(
            routeGuard: new CrudRuntimeRouteGuard(
                scopeTokens: ['cruding', 'viewing'],
                entityTokens: ['vendor'],
                surfaceTokens: ['show'],
                reservedRootTokens: ['cruding', 'viewing'],
                allowedResourceTokens: ['vendor'],
                conflictingEntityTokens: [],
                resourceRequirement: '(?:vendor)',
                resourcePathRequirement: '(?:vendor)(?:/[a-z0-9][a-z0-9_-]*)*',
                surfaceTokenRequirement: '(?:show)',
            ),
            lockReader: new CrudRuntimeLockReader(new CrudRuntimeTokenNormalizer(), $projectDir, 'test', 'config/kernel/runtime_scope.%env%.lock.php'),
            composerInventoryReader: new CrudRuntimeComposerInventoryReader($projectDir),
            expectedPackageByScopeToken: [
                'cruding' => 'cruding/crud',
                'viewing' => 'viewing/view',
            ],
            requireRuntimeLock: true,
            requireComposerPackages: true,
        );

        $report = $guard->report();

        self::assertFalse($report->passed());
        self::assertContains('Runtime scope token "viewing" expects composer package "viewing/view", but it is not declared/installed.', $report->errors);
    }

    public function testPassesWhenEnvComposerAndLockAgree(): void
    {
        $projectDir = $this->createProjectDir([
            'scope' => ['cruding'],
            'entity' => ['vendor'],
            'packages' => ['cruding/crud'],
        ], [
            'require' => [
                'cruding/crud' => 'dev-master',
            ],
        ]);

        $guard = new CrudRuntimeDecisionGuard(
            routeGuard: new CrudRuntimeRouteGuard(
                scopeTokens: ['cruding'],
                entityTokens: ['vendor'],
                surfaceTokens: ['show'],
                reservedRootTokens: ['cruding'],
                allowedResourceTokens: ['vendor'],
                conflictingEntityTokens: [],
                resourceRequirement: '(?:vendor)',
                resourcePathRequirement: '(?:vendor)(?:/[a-z0-9][a-z0-9_-]*)*',
                surfaceTokenRequirement: '(?:show)',
            ),
            lockReader: new CrudRuntimeLockReader(new CrudRuntimeTokenNormalizer(), $projectDir, 'test', 'config/kernel/runtime_scope.%env%.lock.php'),
            composerInventoryReader: new CrudRuntimeComposerInventoryReader($projectDir),
            expectedPackageByScopeToken: ['cruding' => 'cruding/crud'],
            requireRuntimeLock: true,
            requireComposerPackages: true,
        );

        $report = $guard->report();

        self::assertTrue($report->passed());
        self::assertSame([], $report->errors);
    }

    /**
     * @param array<string, mixed> $lockPayload
     * @param array<string, mixed> $composerJson
     */
    private function createProjectDir(array $lockPayload, array $composerJson): string
    {
        $projectDir = sys_get_temp_dir().'/cruding-runtime-decision-'.bin2hex(random_bytes(6));
        mkdir($projectDir.'/config/kernel', 0777, true);

        file_put_contents($projectDir.'/config/kernel/runtime_scope.test.lock.php', '<?php return '.var_export($lockPayload, true).';'.PHP_EOL);
        file_put_contents($projectDir.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        file_put_contents($projectDir.'/composer.lock', json_encode(['packages' => []], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $projectDir;
    }
}
