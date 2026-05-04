<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

final readonly class CrudEntityClassResolver
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CrudResourcePathParser $resourcePathParser,
    ) {
    }

    public function resolve(string $resourcePath): string
    {
        $entityClass = $this->tryResolve($resourcePath);
        if (null !== $entityClass) {
            return $entityClass;
        }

        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    public function tryResolve(string $resourcePath): ?string
    {
        $normalizedPath = $this->resourcePathParser->normalize($resourcePath);
        static $candidateMap = null;

        if (null === $candidateMap) {
            $candidateMap = $this->buildCandidateMap();
        }

        $explicit = $this->explicitAliases();
        if (isset($explicit[$normalizedPath])) {
            return $explicit[$normalizedPath];
        }

        if (isset($candidateMap[$normalizedPath])) {
            return $candidateMap[$normalizedPath];
        }

        $tail = $this->resourcePathParser->tail($normalizedPath);
        if ('' !== $tail && isset($candidateMap[$tail])) {
            return $candidateMap[$tail];
        }

        return null;
    }

    /**
     * @return array<string, class-string>
     */
    private function buildCandidateMap(): array
    {
        $candidates = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            $metadataFactory = $manager->getMetadataFactory();
            foreach ($metadataFactory->getAllMetadata() as $metadata) {
                $class = $metadata->getName();
                foreach ($this->buildKeys($class) as $key) {
                    $candidates[$key] ??= $class;
                }
            }
        }

        return $candidates;
    }

    /**
     * @return list<string>
     */
    private function buildKeys(string $class): array
    {
        $parts = explode('\\Entity\\', $class, 2);
        $tail = $parts[1] ?? preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $segments = array_values(array_filter(array_map([$this, 'normalizeSegment'], explode('\\', $tail))));

        if ([] === $segments) {
            return [];
        }

        $keys = [];
        $keys[] = implode('/', $segments);
        $keys[] = (string) end($segments);
        $keys[] = $this->normalizeEntityAlias($class);

        $deduplicated = [];
        foreach ($segments as $segment) {
            $last = [] === $deduplicated ? null : end($deduplicated);
            if ($segment !== $last) {
                $deduplicated[] = $segment;
            }
        }

        if ([] !== $deduplicated) {
            $keys[] = implode('/', $deduplicated);
            $keys[] = (string) end($deduplicated);
            $keys[] = $this->normalizeEntityAlias($class);
        }

        return array_values(array_unique(array_filter($keys, static fn (mixed $value): bool => is_string($value) && '' !== $value)));
    }

    /**
     * @return array<string, class-string>
     */
    private function explicitAliases(): array
    {
        return array_filter([
            'access' => class_exists(\App\Accessing\Entity\AccessAccountEntity::class) ? \App\Accessing\Entity\AccessAccountEntity::class : null,
            'address' => class_exists(\App\Entity\AddressEntity::class) ? \App\Entity\AddressEntity::class : null,
            'adjudication' => class_exists(\App\Entity\AdjudicationRuleEntity::class) ? \App\Entity\AdjudicationRuleEntity::class : null,
            'analytics' => class_exists(\App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity::class) ? \App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity::class : null,
            'catalog' => class_exists(\App\Cataloging\Entity\Catalog\CatalogCategoryEntity::class) ? \App\Cataloging\Entity\Catalog\CatalogCategoryEntity::class : null,
            'billing' => class_exists(\App\Billing\Entity\BillingInvoiceEntity::class) ? \App\Billing\Entity\BillingInvoiceEntity::class : null,
            'order' => class_exists(\App\Entity\Order\OrderEntity::class) ? \App\Entity\Order\OrderEntity::class : null,
            'orders' => class_exists(\App\Entity\Order\OrderEntity::class) ? \App\Entity\Order\OrderEntity::class : null,
            'product' => class_exists(\App\Entity\Product\ProductTypeEntity::class) ? \App\Entity\Product\ProductTypeEntity::class : null,
            'shipment' => class_exists(\App\Entity\ShipmentCarrierEntity::class) ? \App\Entity\ShipmentCarrierEntity::class : null,
            'shipping' => class_exists(\App\Entity\ShipmentCarrierEntity::class) ? \App\Entity\ShipmentCarrierEntity::class : null,
            'taxating' => class_exists(\App\Taxating\Entity\Taxation\TaxationEntity::class) ? \App\Taxating\Entity\Taxation\TaxationEntity::class : null,
            'vendor' => class_exists(\App\Vendoring\Entity\Vendor\VendorEntity::class) ? \App\Vendoring\Entity\Vendor\VendorEntity::class : null,
        ]);
    }

    private function normalizeEntityAlias(string $class): string
    {
        $base = preg_replace('{^.*\\\\}', '', $class) ?? $class;
        $base = preg_replace('/Entity$/', '', $base) ?? $base;

        return $this->normalizeSegment($base);
    }

    private function normalizeSegment(string $segment): string
    {
        $withHyphen = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $segment));

        return str_replace('_', '-', $withHyphen);
    }
}
