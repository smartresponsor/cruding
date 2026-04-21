<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudRouteNameResolver;
use PHPUnit\Framework\TestCase;

final class CrudRouteNameResolverTest extends TestCase
{
    public function testResolveShowUsesSlugByDefault(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product', 'App\Cruding\\Entity\\Product', 'slug', 'demo', null, 'product/product');

        self::assertSame('app_crud_show_slug', $resolver->resolveShow($context));
    }

    public function testResolveShowUsesIdWhenRequested(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('admin', 'show', 'product', 'App\Cruding\\Entity\\Product', 'id', 15, null, 'product/product');

        self::assertSame('app_crud_show_id', $resolver->resolveShow($context));
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
