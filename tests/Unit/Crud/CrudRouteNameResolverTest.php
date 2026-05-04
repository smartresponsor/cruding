<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudRouteNameResolver;
use PHPUnit\Framework\TestCase;

final class CrudRouteNameResolverTest extends TestCase
{
    public function testResolveNewReturnsCreateRoute(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'new', 'product', 'App\Cruding\Entity\Product', 'slug', null, null, 'product/product');

        self::assertSame('cruding_new', $resolver->resolveNew($context));
    }

    public function testResolveShowUsesSlugByDefault(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product', 'App\Cruding\\Entity\\Product', 'slug', 'demo', null, 'product/product');

        self::assertSame('cruding_show_slug', $resolver->resolveShow($context));
    }

    public function testResolveShowUsesIdWhenRequested(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('admin', 'show', 'product', 'App\Cruding\\Entity\\Product', 'id', 15, null, 'product/product');

        self::assertSame('cruding_show_id', $resolver->resolveShow($context));
    }

    public function testResolveEditUsesSlugByDefault(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'edit', 'product', 'App\Cruding\\Entity\\Product', 'slug', 'demo', null, 'product/product');

        self::assertSame('cruding_edit_slug', $resolver->resolveEdit($context));
    }

    public function testResolveDeleteUsesIdWhenRequested(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('admin', 'delete', 'product', 'App\Cruding\\Entity\\Product', 'id', 15, null, 'product/product');

        self::assertSame('cruding_delete_id', $resolver->resolveDelete($context));
    }

    public function testParametersCarryResourcePathAndIdentifier(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product/price', 'App\Cruding\\Entity\\Price', 'slug', 'gold', null, 'product/price');

        self::assertSame([
            'resourcePath' => 'product/price',
            'slug' => 'gold',
        ], $resolver->parameters($context));
    }
}
