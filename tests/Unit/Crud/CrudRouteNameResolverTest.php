<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudRouteNameResolver;
use PHPUnit\Framework\TestCase;

final class CrudRouteNameResolverTest extends TestCase
{
    public function testAllUiLinksUseTokenizedCatchAllRoute(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'index', 'product', 'App\Cruding\Entity\Product', 'slug', null, null);

        self::assertSame('cruding_tokenized_catch_all', $resolver->resolveIndex($context));
        self::assertSame('cruding_tokenized_catch_all', $resolver->resolveNew($context));
        self::assertSame('cruding_tokenized_catch_all', $resolver->resolveShow($context));
        self::assertSame('cruding_tokenized_catch_all', $resolver->resolveEdit($context));
        self::assertSame('cruding_tokenized_catch_all', $resolver->resolveDelete($context));
    }

    public function testParametersCarryTokenizedCrudPath(): void
    {
        $resolver = new CrudRouteNameResolver();
        $context = new CrudContext('public', 'show', 'product/price', 'App\Cruding\Entity\Price', 'slug', 'gold', null);

        self::assertSame(['crudPath' => 'product/price'], $resolver->parameters($context, null, null, 'index'));
        self::assertSame(['crudPath' => 'product/price/new'], $resolver->parameters($context, null, null, 'new'));
        self::assertSame(['crudPath' => 'product/price/gold'], $resolver->parameters($context, 'gold', 'slug', 'show'));
        self::assertSame(['crudPath' => 'product/price/edit/gold'], $resolver->parameters($context, 'gold', 'slug', 'edit'));
        self::assertSame(['crudPath' => 'product/price/delete/gold'], $resolver->parameters($context, 'gold', 'slug', 'delete'));
    }
}
