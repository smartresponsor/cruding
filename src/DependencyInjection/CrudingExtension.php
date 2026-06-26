<?php

declare(strict_types=1);

namespace App\Cruding\DependencyInjection;

use App\Cruding\Service\Crud\Runtime\CrudRuntimeLockReader;
use App\Cruding\Service\Crud\Runtime\CrudRuntimeRouteGuardPolicyBuilder;
use App\Cruding\Service\Crud\Runtime\CrudRuntimeTokenNormalizer;
use App\Cruding\ServiceInterface\Crud\Resource\CrudResourceProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Kernel;

final class CrudingExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param list<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('cruding_reserved_token.yaml');
        $loader->load('services.yaml');

        $configuration = new Configuration();
        /** @var array{
         *     resource_path_requirement: string,
         *     route_guard: array{
         *         runtime_scope_env: string,
         *         runtime_entity_env: string,
         *         runtime_view_token_env: string,
         *         runtime_reserved_env: string,
         *         reserved_tokens: list<string>,
         *         view_tokens: list<string>,
         *         operation_tokens: list<string>,
         *         resource_path_reserved_tokens: list<string>,
         *         runtime_lock_glob: string,
         *         require_runtime_lock: bool,
         *         require_composer_packages: bool,
         *         scope_package_map: array<string, string>
         *     },
         *     capability_map: array<string, mixed>,
         *     entity_class_alias_map: array<string, string>,
         *     form_type_map: array<string, string>
         * } $config
         */
        $config = $this->processConfiguration($configuration, $configs);

        $routeGuard = $config['route_guard'];
        $normalizer = new CrudRuntimeTokenNormalizer();
        $defaultReservedRootTokens = $this->parameterTokenList($container, 'cruding.reserved_route_token.root');
        $defaultviewTokens = $this->parameterTokenList($container, 'cruding.reserved_route_token.view');
        $defaultOperationTokens = $this->parameterTokenList($container, 'cruding.reserved_route_token.operation');
        $defaultResourcePathReservedTokens = $this->parameterTokenList($container, 'cruding.reserved_route_token.resource_path_only');
        $policyBuilder = new CrudRuntimeRouteGuardPolicyBuilder(
            normalizer: $normalizer,
            defaultReservedRootTokens: $defaultReservedRootTokens,
            defaultviewTokens: $defaultviewTokens,
            defaultOperationTokens: $defaultOperationTokens,
            defaultResourcePathReservedTokens: $defaultResourcePathReservedTokens,
        );
        $appEnv = $this->readAppEnv();
        $runtimeLock = (new CrudRuntimeLockReader(
            normalizer: $normalizer,
            projectDir: (string) $container->getParameter('kernel.project_dir'),
            appEnv: $appEnv,
            lockGlob: $routeGuard['runtime_lock_glob'],
        ))->read();

        $scopeRaw = $this->readEnvironmentValue($routeGuard['runtime_scope_env']);
        $entityRaw = $this->readEnvironmentValue($routeGuard['runtime_entity_env']);
        $viewTokenRaw = $this->readEnvironmentValue($routeGuard['runtime_view_token_env']);
        $reservedRaw = $this->readEnvironmentValue($routeGuard['runtime_reserved_env']);

        $lockScopeTokens = $this->fallbackLockTokens($runtimeLock->scopeTokens, $runtimeLock->path, [
            'scope',
            'runtime_scope',
            'APP_RUNTIME_SCOPE',
            'runtime.scope.components',
        ]);
        $lockEntityTokens = $this->fallbackLockTokens($runtimeLock->entityTokens, $runtimeLock->path, [
            'entity',
            'runtime_entity',
            'APP_RUNTIME_ENTITY',
            'runtime.routing.entities',
        ]);
        $lockviewTokens = $this->fallbackLockTokens($runtimeLock->viewTokens, $runtimeLock->path, [
            'view_token',
            'view_tokens',
            'runtime_view_token',
            'APP_RUNTIME_VIEW_TOKEN',
            'runtime.routing.view_tokens',
        ]);
        $lockReservedTokens = $this->fallbackLockTokens($runtimeLock->reservedTokens, $runtimeLock->path, [
            'reserved',
            'reserved_tokens',
            'runtime_reserved',
            'APP_RUNTIME_RESERVED',
            'runtime.routing.reserved_roots',
        ]);

        $effectiveScopeRaw = $this->fallbackCsv($scopeRaw, $lockScopeTokens);
        $effectiveEntityRaw = $this->fallbackCsv($entityRaw, $lockEntityTokens);
        $effectiveviewTokenRaw = $this->fallbackCsv($viewTokenRaw, $lockviewTokens);
        $effectiveReservedRaw = $this->fallbackCsv($reservedRaw, $lockReservedTokens);

        $policy = $policyBuilder->build(
            scopeRaw: $effectiveScopeRaw,
            entityRaw: $effectiveEntityRaw,
            viewTokenRaw: $effectiveviewTokenRaw,
            reservedRaw: $effectiveReservedRaw,
            configuredReservedTokens: $routeGuard['reserved_tokens'],
            configuredviewTokens: $routeGuard['view_tokens'],
            configuredOperationTokens: $routeGuard['operation_tokens'],
            configuredResourcePathReservedTokens: $routeGuard['resource_path_reserved_tokens'],
        );

        if ([] === $policy->entityTokens && [] !== $lockEntityTokens) {
            $policy = $policyBuilder->build(
                scopeRaw: implode(',', $lockScopeTokens),
                entityRaw: implode(',', $lockEntityTokens),
                viewTokenRaw: implode(',', $lockviewTokens),
                reservedRaw: implode(',', $lockReservedTokens),
                configuredReservedTokens: $routeGuard['reserved_tokens'],
                configuredviewTokens: $routeGuard['view_tokens'],
                configuredOperationTokens: $routeGuard['operation_tokens'],
                configuredResourcePathReservedTokens: $routeGuard['resource_path_reserved_tokens'],
            );
        }

        $finalScopeTokens = [] !== $policy->scopeTokens ? $policy->scopeTokens : $normalizer->csvToTokenList($effectiveScopeRaw);
        $finalEntityTokens = [] !== $policy->entityTokens ? $policy->entityTokens : $normalizer->csvToTokenList($effectiveEntityRaw);
        $finalviewTokens = [] !== $policy->viewTokens ? $policy->viewTokens : $normalizer->csvToTokenList($effectiveviewTokenRaw);
        $finalReservedTokens = $policy->reservedRootTokens;
        $finalConflictingEntityTokens = $policy->conflictingEntityTokens;
        $finalAllowedResourceTokens = $policy->allowedResourceTokens;

        if ([] === $finalAllowedResourceTokens && [] !== $finalEntityTokens) {
            $reservedLookup = array_fill_keys($finalReservedTokens, true);
            $conflicts = [];
            $allowed = [];

            foreach ($finalEntityTokens as $entityToken) {
                if (isset($reservedLookup[$entityToken])) {
                    $conflicts[$entityToken] = $entityToken;
                    continue;
                }

                $allowed[$entityToken] = $entityToken;
            }

            $finalAllowedResourceTokens = array_values($allowed);
            $finalConflictingEntityTokens = array_values($conflicts);
        }

        $finalResourceRequirement = $policy->resourceRequirement;
        if ('(?!)' === $finalResourceRequirement && [] !== $finalAllowedResourceTokens) {
            $finalResourceRequirement = $normalizer->alternationRequirement($finalAllowedResourceTokens);
        }

        $finalResourcePathRequirement = $policy->resourcePathRequirement;
        if ('(?!)(?:/[a-z0-9][a-z0-9_-]*)*' === $finalResourcePathRequirement || str_starts_with($finalResourcePathRequirement, '(?!.*')) {
            $reservedSegmentRequirement = $normalizer->alternationRequirement(array_merge(
                $finalviewTokens,
                $policy->operationTokens,
                $policy->resourcePathReservedTokens,
            ));
            $finalResourcePathRequirement = sprintf(
                '%s(?:/(?!(?:%s)$)[a-z0-9][a-z0-9_-]*)*',
                $finalResourceRequirement,
                $reservedSegmentRequirement,
            );
        }

        $container->setParameter('cruding.runtime_scope_env', $routeGuard['runtime_scope_env']);
        $container->setParameter('cruding.runtime_entity_env', $routeGuard['runtime_entity_env']);
        $container->setParameter('cruding.runtime_view_token_env', $routeGuard['runtime_view_token_env']);
        $container->setParameter('cruding.runtime_reserved_env', $routeGuard['runtime_reserved_env']);
        $container->setParameter('cruding.runtime_lock_glob', $routeGuard['runtime_lock_glob']);
        $container->setParameter('cruding.runtime_require_lock', $routeGuard['require_runtime_lock']);
        $container->setParameter('cruding.runtime_require_composer_packages', $routeGuard['require_composer_packages']);
        $container->setParameter('cruding.runtime_scope_package_map', $routeGuard['scope_package_map']);
        $container->setParameter('cruding.app_env', $appEnv);
        $container->setParameter('cruding.runtime_lock_path', $runtimeLock->path);
        $container->setParameter('cruding.runtime_lock_found', $runtimeLock->found);
        $container->setParameter('cruding.runtime_lock_scope_tokens', $lockScopeTokens);
        $container->setParameter('cruding.runtime_lock_entity_tokens', $lockEntityTokens);
        $container->setParameter('cruding.runtime_lock_view_tokens', $lockviewTokens);
        $container->setParameter('cruding.runtime_lock_reserved_tokens', $lockReservedTokens);
        $container->setParameter('cruding.runtime_effective_scope_raw', $effectiveScopeRaw);
        $container->setParameter('cruding.runtime_effective_entity_raw', $effectiveEntityRaw);
        $container->setParameter('cruding.runtime_effective_view_token_raw', $effectiveviewTokenRaw);
        $container->setParameter('cruding.runtime_effective_reserved_raw', $effectiveReservedRaw);
        $container->setParameter('cruding.runtime_scope_tokens', $finalScopeTokens);
        $container->setParameter('cruding.runtime_entity_tokens', $finalEntityTokens);
        $container->setParameter('cruding.runtime_view_tokens', $finalviewTokens);
        $container->setParameter('cruding.runtime_operation_tokens', $policy->operationTokens);
        $container->setParameter('cruding.runtime_resource_path_reserved_tokens', $policy->resourcePathReservedTokens);
        $container->setParameter('cruding.runtime_reserved_tokens', $finalReservedTokens);
        $container->setParameter('cruding.runtime_allowed_resource_tokens', $finalAllowedResourceTokens);
        $container->setParameter('cruding.runtime_conflicting_entity_tokens', $finalConflictingEntityTokens);
        $container->setParameter('cruding.resource_requirement', $finalResourceRequirement);
        $container->setParameter('cruding.resource_path_requirement', $finalResourcePathRequirement);
        $container->setParameter('cruding.view_token_requirement', $policy->viewTokenRequirement);
        $container->setParameter('cruding.operation_token_requirement', $normalizer->alternationRequirement($policy->operationTokens));
        $container->setParameter('cruding.identity_slug_requirement', $policy->identitySlugRequirement);
        $container->setParameter('cruding.capability_map', $config['capability_map']);
        $container->setParameter('cruding.entity_class_alias_map', $config['entity_class_alias_map']);
        $container->setParameter('cruding.form_type_map', $config['form_type_map']);

        $container->registerForAutoconfiguration(CrudResourceProviderInterface::class)
            ->addTag('cruding.resource_provider');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $templatesDir = \dirname(__DIR__, 2).'/templates';
        if (!is_dir($templatesDir)) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                $templatesDir => 'Cruding',
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'cruding';
    }

    /** @return list<string> */
    private function parameterTokenList(ContainerBuilder $container, string $nameEntity): array
    {
        if (!$container->hasParameter($nameEntity)) {
            return [];
        }

        $value = $container->getParameter($nameEntity);
        if (!\is_array($value)) {
            return [];
        }

        $tokens = [];
        foreach ($value as $token) {
            if (\is_string($token)) {
                $tokens[] = $token;
            }
        }

        return (new CrudRuntimeTokenNormalizer())->normalizeTokenList($tokens);
    }

    /**
     * @param list<string> $primaryTokens
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function fallbackLockTokens(array $primaryTokens, ?string $lockPath, array $paths): array
    {
        if ([] !== $primaryTokens || null === $lockPath || !is_file($lockPath)) {
            return $primaryTokens;
        }

        $payload = require $lockPath;
        if (!\is_array($payload)) {
            return [];
        }

        foreach ($paths as $path) {
            $value = $this->readPayloadPath($payload, $path);
            if (null === $value) {
                continue;
            }

            return $this->tokenListFromValue($value);
        }

        return [];
    }

    /** @param array<string, mixed> $payload */
    private function readPayloadPath(array $payload, string $path): mixed
    {
        if (array_key_exists($path, $payload)) {
            return $payload[$path];
        }

        $current = $payload;
        foreach (explode('.', $path) as $segment) {
            if (!\is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /** @return list<string> */
    private function tokenListFromValue(mixed $value): array
    {
        $normalizer = new CrudRuntimeTokenNormalizer();
        if (\is_string($value)) {
            return $normalizer->csvToTokenList($value);
        }

        if (!\is_array($value)) {
            return [];
        }

        $tokens = [];
        foreach ($value as $token) {
            if (\is_string($token)) {
                $tokens[] = $token;
            }
        }

        return $normalizer->normalizeTokenList($tokens);
    }

    /** @param list<string> $fallbackTokens */
    private function fallbackCsv(string $primaryRaw, array $fallbackTokens): string
    {
        if ('' !== trim($primaryRaw)) {
            return $primaryRaw;
        }

        return implode(',', $fallbackTokens);
    }

    private function readAppEnv(): string
    {
        if (class_exists(Kernel::class)) {
            $environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV');
            if (is_string($environment) && '' !== trim($environment)) {
                return $environment;
            }
        }

        return 'dev';
    }

    private function readEnvironmentValue(string $nameEntity): string
    {
        $serverValue = $_SERVER[$nameEntity] ?? null;
        if (is_string($serverValue)) {
            return $serverValue;
        }

        $envValue = $_ENV[$nameEntity] ?? null;
        if (is_string($envValue)) {
            return $envValue;
        }

        $getenvValue = getenv($nameEntity);

        return is_string($getenvValue) ? $getenvValue : '';
    }
}
