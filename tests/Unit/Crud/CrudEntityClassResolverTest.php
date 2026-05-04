<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Accessing\Entity\AccessAccountEntity;
use App\Analysing\Entity\Analytics\AnalyticsMetricSnapshotEntity;
use App\Billing\Entity\BillingInvoiceEntity;
use App\Cataloging\Entity\Catalog\CatalogCategoryEntity;
use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use App\Cruding\Service\Crud\CrudEntityClassResolver;
use App\Cruding\Service\Crud\CrudResourcePathParser;
use App\Entity\AddressEntity;
use App\Entity\AdjudicationRuleEntity;
use App\Entity\Order\OrderEntity;
use App\Entity\Product\ProductTypeEntity;
use App\Entity\ShipmentCarrierEntity;
use App\Taxating\Entity\Taxation\TaxationEntity;
use App\Vendoring\Entity\Vendor\VendorCategoryEntity;
use App\Vendoring\Entity\Vendor\VendorEntity;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

final class CrudEntityClassResolverTest extends TestCase
{
    public function testResolveUsesEntityBaseAliasWithoutEntitySuffix(): void
    {
        $resolver = new CrudEntityClassResolver(
            $this->buildRegistry([
                VendorEntity::class,
                VendorCategoryEntity::class,
                OrderEntity::class,
                CatalogCategoryEntity::class,
                BillingInvoiceEntity::class,
                AccessAccountEntity::class,
                AddressEntity::class,
                AdjudicationRuleEntity::class,
                AnalyticsMetricSnapshotEntity::class,
                ProductTypeEntity::class,
                ShipmentCarrierEntity::class,
                TaxationEntity::class,
            ]),
            new CrudResourcePathParser(),
        );

        self::assertSame(AccessAccountEntity::class, $resolver->resolve('access'));
        self::assertSame(AddressEntity::class, $resolver->resolve('address'));
        self::assertSame(AdjudicationRuleEntity::class, $resolver->resolve('adjudication'));
        self::assertSame(AnalyticsMetricSnapshotEntity::class, $resolver->resolve('analytics'));
        self::assertSame(CatalogCategoryEntity::class, $resolver->resolve('catalog'));
        self::assertSame(BillingInvoiceEntity::class, $resolver->resolve('billing'));
        self::assertSame(VendorEntity::class, $resolver->resolve('vendor'));
        self::assertSame(VendorCategoryEntity::class, $resolver->resolve('vendor-category'));
        self::assertSame(OrderEntity::class, $resolver->resolve('order'));
        self::assertSame(OrderEntity::class, $resolver->resolve('orders'));
        self::assertSame(ProductTypeEntity::class, $resolver->resolve('product'));
        self::assertSame(ShipmentCarrierEntity::class, $resolver->resolve('shipment'));
        self::assertSame(ShipmentCarrierEntity::class, $resolver->resolve('shipping'));
        self::assertSame(TaxationEntity::class, $resolver->resolve('taxating'));
        self::assertNull($resolver->tryResolve('unknown-resource'));
        self::expectException(CrudResourceNotFoundException::class);
        $resolver->resolve('unknown-resource');
    }

    /**
     * @param list<class-string> $classes
     */
    private function buildRegistry(array $classes): ManagerRegistry
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn(array_map(
            static fn (string $class): object => new class($class) {
                public function __construct(private string $name)
                {
                }

                public function getName(): string
                {
                    return $this->name;
                }
            },
            $classes
        ));

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getMetadataFactory')->willReturn($metadataFactory);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn([$manager]);

        return $registry;
    }
}
