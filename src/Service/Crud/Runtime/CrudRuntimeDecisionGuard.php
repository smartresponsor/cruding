<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeDecisionReport;

final readonly class CrudRuntimeDecisionGuard
{
    /** @param array<string, string> $expectedPackageByScopeToken */
    public function __construct(
        private CrudRuntimeRouteGuard $routeGuard,
        private CrudRuntimeLockReader $lockReader,
        private CrudRuntimeComposerInventoryReader $composerInventoryReader,
        private array $expectedPackageByScopeToken,
        private bool $requireRuntimeLock,
        private bool $requireComposerPackages,
    ) {
    }

    public function report(): CrudRuntimeDecisionReport
    {
        $policy = $this->routeGuard->policy();
        $lock = $this->lockReader->read();
        $composer = $this->composerInventoryReader->read();
        $errors = [];
        $warnings = [];

        if ($this->requireRuntimeLock && !$lock->found) {
            $errors[] = 'Runtime lock file is required but was not found under config/kernel/runtime_scope.*lock.php.';
        } elseif (!$lock->found) {
            $warnings[] = 'Runtime lock file was not found; env-derived route policy is not lock-confirmed.';
        }

        if (null === $composer->composerJsonPath) {
            $warnings[] = 'composer.json was not found in the host project directory.';
        }

        if (null === $composer->composerLockPath) {
            $warnings[] = 'composer.lock was not found in the host project directory.';
        }

        if ($policy->hasConflicts()) {
            foreach ($policy->conflictingEntityTokens as $token) {
                $errors[] = sprintf('Runtime entity token "%s" conflicts with reserved runtime/root token.', $token);
            }
        }

        if ($lock->found) {
            $errors = array_merge($errors, $this->missingFromLock('APP_RUNTIME_SCOPE', $policy->scopeTokens, $lock->scopeTokens));
            $errors = array_merge($errors, $this->missingFromLock('APP_RUNTIME_ENTITY', $policy->entityTokens, $lock->entityTokens));
            $warnings = array_merge($warnings, $this->missingFromLock('APP_RUNTIME_VIEW_TOKEN', $policy->viewTokens, $lock->viewTokens));
        }

        foreach ($policy->scopeTokens as $scopeToken) {
            $expectedPackage = $this->expectedPackageByScopeToken[$scopeToken] ?? null;
            if (null === $expectedPackage) {
                $warnings[] = sprintf('No expected composer package mapping is configured for runtime scope token "%s".', $scopeToken);
                continue;
            }

            if (!$composer->hasPackage($expectedPackage)) {
                $message = sprintf('Runtime scope token "%s" expects composer package "%s", but it is not declared/installed.', $scopeToken, $expectedPackage);
                if ($this->requireComposerPackages) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        if ($lock->found) {
            foreach ($lock->packageNames as $packageName) {
                if ($composer->hasPackage($packageName)) {
                    continue;
                }

                $message = sprintf('Runtime lock package "%s" is not declared/installed in composer inventory.', $packageName);
                if ($this->requireComposerPackages) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        return new CrudRuntimeDecisionReport(
            routePolicy: $policy,
            runtimeLock: $lock,
            composerInventory: $composer,
            expectedPackageByScopeToken: $this->expectedPackageByScopeToken,
            errors: $this->unique($errors),
            warnings: $this->unique($warnings),
        );
    }

    /**
     * @param list<string> $requestedTokens
     * @param list<string> $lockedTokens
     *
     * @return list<string>
     */
    private function missingFromLock(string $label, array $requestedTokens, array $lockedTokens): array
    {
        if ([] === $requestedTokens || [] === $lockedTokens) {
            return [];
        }

        $lockedLookup = array_fill_keys($lockedTokens, true);
        $messages = [];
        foreach ($requestedTokens as $token) {
            if (!isset($lockedLookup[$token])) {
                $messages[] = sprintf('%s token "%s" is requested by env but absent from runtime lock.', $label, $token);
            }
        }

        return $messages;
    }

    /** @param list<string> $messages @return list<string> */
    private function unique(array $messages): array
    {
        $unique = [];
        foreach ($messages as $message) {
            $unique[$message] = $message;
        }

        return array_values($unique);
    }
}
