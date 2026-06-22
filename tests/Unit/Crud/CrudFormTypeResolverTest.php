<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Resolver\Crud\CrudFormTypeResolver;
use App\Tests\Fixture\Entity\ProductEntity;
use App\Tests\Fixture\Form\ProductEntityType;
use PHPUnit\Framework\TestCase;

final class CrudFormTypeResolverTest extends TestCase
{
    public function testResolveUsesExplicitHostProvidedFormTypeMap(): void
    {
        $resolver = new CrudFormTypeResolver([
            ProductEntity::class => ProductEntityType::class,
        ]);

        self::assertSame(ProductEntityType::class, $resolver->resolve(ProductEntity::class));
    }

    public function testResolveUsesSymfonyFormNamingConvention(): void
    {
        $resolver = new CrudFormTypeResolver();

        self::assertSame(ProductEntityType::class, $resolver->resolve(ProductEntity::class));
    }
}
