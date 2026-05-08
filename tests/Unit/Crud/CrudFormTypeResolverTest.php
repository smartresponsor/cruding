<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Service\Crud\CrudFormTypeResolver;
use App\Vendoring\Entity\Vendor\VendorEntity;
use App\Vendoring\Form\Vendor\VendorCreateForm;
use PHPUnit\Framework\TestCase;

final class CrudFormTypeResolverTest extends TestCase
{
    public function testResolveUsesExplicitVendorCreateForm(): void
    {
        $resolver = new CrudFormTypeResolver();

        self::assertSame(VendorCreateForm::class, $resolver->resolve(VendorEntity::class));
    }
}
