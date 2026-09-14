<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Runtime;

use App\Cruding\Normalizer\Runtime\CrudRuntimeTokenNormalizer;
use App\Cruding\Service\Runtime\CrudRuntimeComposerInventoryReader;
use App\Cruding\Service\Runtime\CrudRuntimeDecisionGuard;
use App\Cruding\Service\Runtime\CrudRuntimeLockReader;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use PHPUnit\Framework\TestCase;

final class CrudRuntimeDecisionGuardTest extends TestCase
{
    public function testReportsComposerAndLockMismatch(): void
    {
        $projectDir = $this->createProjectDir([
            'scope' => ['cruding', 'viewing'],
            'entity' => ['alpha'],
            'packages' => ['cruding/crud', 'viewing/view'],
        ], [
            'require' => ['cruding/crud' => 'dev-master'],
        ]);

        $guard = new CrudRuntimeDecisionGuard(
            routeGuard: new CrudRuntimeRouteGuard(
                scopeTokens: ['cruding', 'viewing'],
                entityTokens: ['alpha'],
                viewTokens: ['card'],
                reservedRootTokens: ['cruding', 'viewing'],
                allowedResourceTokens: ['alpha'],
                conflictingEntityTokens: [],
                resourceRequirement: '(?:alpha)',
                resourcePathRequirement: '(?:alpha)(?:/[a-z0-9][a-z0-9_-]*)*',
                viewTokenRequirement: '(?:card)',
                operationTokens: ['show'],
                resourcePathReservedTokens: [],
                identitySlugRequirement: '(?!(?:card|show)$)[A-Za-z0-9][A-Za-z0-9_-]*',
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
            'entity' => ['alpha'],
            'packages' => ['cruding/crud'],
        ], [
            'require' => ['cruding/crud' => 'dev-master'],
        ]);

        $guard = new CrudRuntimeDecisionGuard(
            routeGuard: new CrudRuntimeRouteGuard(
                scopeTokens: ['cruding'],
                entityTokens: ['alpha'],
                viewTokens: ['card'],
                reservedRootTokens: ['cruding'],
                allowedResourceTokens: ['alpha'],
                conflictingEntityTokens: [],
                resourceRequirement: '(?:alpha)',
                resourcePathRequirement: '(?:alpha)(?:/[a-z0-9][a-z0-9_-]*)*',
                viewTokenRequirement: '(?:card)',
                operationTokens: ['show'],
                resourcePathReservedTokens: [],
                identitySlugRequirement: '(?!(?:card|show)$)[A-Za-z0-9][A-Za-z0-9_-]*',
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

    public function testRouteGuardNormalizesRootResourcePathAndExposesPolicy(): void
    {
        $guard = new CrudRuntimeRouteGuard(
            scopeTokens: ['cruding'],
            entityTokens: ['alpha'],
            viewTokens: ['card'],
            reservedRootTokens: ['cruding'],
            operationTokens: ['show'],
            resourcePathReservedTokens: ['show'],
            allowedResourceTokens: ['alpha', 'beta'],
            conflictingEntityTokens: ['beta'],
            resourceRequirement: '(?:alpha|beta)',
            resourcePathRequirement: '(?:alpha|beta)(?:/[a-z0-9][a-z0-9_-]*)*',
            viewTokenRequirement: '(?:card)',
            identitySlugRequirement: '(?!(?:card|show)$)[A-Za-z0-9][A-Za-z0-9_-]*',
        );

        self::assertTrue($guard->allowsResourcePath('/ALPHA/document'));
        self::assertTrue($guard->allowsResourcePath('beta'));
        self::assertFalse($guard->allowsResourcePath(''));
        self::assertFalse($guard->allowsResourcePath('/gamma/document'));

        $policy = $guard->policy();
        self::assertSame(['alpha', 'beta'], $policy->allowedResourceTokens);
        self::assertSame(['beta'], $policy->conflictingEntityTokens);
        self::assertTrue($policy->hasConflicts());
        self::assertSame('(?:alpha|beta)', $policy->resourceRequirement);
    }

    public function testRuntimeLockReaderReturnsMissingStateWithoutLockFile(): void
    {
        $projectDir = sys_get_temp_dir().'/cruding-runtime-lock-missing-'.bin2hex(random_bytes(6));
        mkdir($projectDir.'/config/kernel', 0777, true);

        $lock = (new CrudRuntimeLockReader(
            new CrudRuntimeTokenNormalizer(),
            $projectDir,
            'test',
            'config/kernel/runtime_scope.%env%.lock.php',
        ))->read();

        self::assertFalse($lock->found);
        self::assertNull($lock->path);
        self::assertSame('test', $lock->appEnv);
        self::assertSame([], $lock->scopeTokens);
        self::assertSame([], $lock->packageNames);
    }

    public function testRuntimeLockReaderNormalizesNestedAndCsvPayloads(): void
    {
        $projectDir = sys_get_temp_dir().'/cruding-runtime-lock-nested-'.bin2hex(random_bytes(6));
        mkdir($projectDir.'/config/kernel', 0777, true);
        $payload = [
            'runtime' => [
                'scope' => [
                    'components' => ' Cruding, Viewing, cruding ',
                    'packages' => ['cruding/crud', 'viewing/view', 'cruding/crud'],
                ],
                'routing' => [
                    'entities' => [' Alpha ', 'beta', '', 42],
                    'view_tokens' => ' Card, detail, card ',
                    'reserved_roots' => [' Admin ', 'api', 'admin'],
                ],
            ],
        ];
        file_put_contents(
            $projectDir.'/config/kernel/runtime_scope.test.lock.php',
            '<?php return '.var_export($payload, true).';'.PHP_EOL,
        );

        $lock = (new CrudRuntimeLockReader(
            new CrudRuntimeTokenNormalizer(),
            $projectDir,
            'test',
            'config/kernel/runtime_scope.%env%.lock.php',
        ))->read();

        self::assertTrue($lock->found);
        self::assertSame(['cruding', 'viewing'], $lock->scopeTokens);
        self::assertSame(['alpha', 'beta'], $lock->entityTokens);
        self::assertSame(['card', 'detail'], $lock->viewTokens);
        self::assertSame(['admin', 'api'], $lock->reservedTokens);
        self::assertSame(['cruding/crud', 'viewing/view'], $lock->packageNames);
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
