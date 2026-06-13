<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Service\Crud\CrudObjectFactory;
use PHPUnit\Framework\TestCase;

final class CrudObjectFactoryTest extends TestCase
{
    public function testCreateInstantiatesClassWithoutConstructor(): void
    {
        $factory = new CrudObjectFactory();

        $object = $factory->create(ObjectFactoryNoConstructorFixture::class);

        self::assertInstanceOf(ObjectFactoryNoConstructorFixture::class, $object);
    }

    public function testCreateUsesDefaultAndScalarConstructorValues(): void
    {
        $factory = new CrudObjectFactory();

        $object = $factory->create(ObjectFactoryScalarConstructorFixture::class);

        self::assertSame('', $object->nameEntity);
        self::assertSame(0, $object->count);
        self::assertFalse($object->enabled);
        self::assertSame([], $object->meta);
        self::assertSame('fallback', $object->defaulted);
    }

    public function testCreateFallsBackWhenConstructorCannotBeSatisfied(): void
    {
        $factory = new CrudObjectFactory();

        $object = $factory->create(ObjectFactoryObjectConstructorFixture::class);

        self::assertInstanceOf(ObjectFactoryObjectConstructorFixture::class, $object);
    }
}

final class ObjectFactoryNoConstructorFixture
{
}

final readonly class ObjectFactoryScalarConstructorFixture
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $nameEntity,
        public int $count,
        public bool $enabled,
        public array $meta,
        public string $defaulted = 'fallback',
    ) {
    }
}

final readonly class ObjectFactoryObjectConstructorFixture
{
    public function __construct(public \DateTimeImmutable $createdAt)
    {
    }
}
