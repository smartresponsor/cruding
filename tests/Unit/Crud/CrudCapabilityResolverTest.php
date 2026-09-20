<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Contract\Capability\CrudSluggableInterface;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Resolver\CrudCapabilityResolver;
use PHPUnit\Framework\TestCase;

final class CrudCapabilityResolverTest extends TestCase
{
    public function testSupportsExplicitInterfaceBeforeAliases(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => [
                'interfaces' => [CrudSluggableInterface::class],
                'methods_any' => ['getSlug'],
            ],
        ]);

        $subject = new class implements CrudSluggableInterface {
            public function getSlug(): string
            {
                return 'demo-slug';
            }
        };

        $match = $resolver->match('sluggable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('explicit_interface', $match->source);
        self::assertSame(CrudSluggableInterface::class, $match->interfaceName);
    }

    public function testFallsBackToAliasMethodWhenNoInterfaceExists(): void
    {
        $resolver = new CrudCapabilityResolver([
            'identifiable' => [
                'interfaces' => [],
                'methods_any' => ['getId'],
            ],
        ]);

        $subject = new class {
            public function getId(): int
            {
                return 42;
            }
        };

        $match = $resolver->match('identifiable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('alias_method', $match->source);
        self::assertSame('getId', $match->accessor);
        self::assertSame('method', $match->accessorType);
    }

    public function testFallsBackToAliasPropertyWhenNoInterfaceOrMethodExists(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => [
                'interfaces' => [],
                'methods_any' => [],
                'properties_any' => ['slug'],
            ],
        ]);

        $subject = new class {
            public string $slug = 'demo-slug';
        };

        $match = $resolver->match('sluggable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('alias_property', $match->source);
        self::assertSame('slug', $match->accessor);
        self::assertSame('property', $match->accessorType);
    }

    public function testProfileIncludesUnsupportedCapabilities(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => [
                'interfaces' => [CrudSluggableInterface::class],
            ],
            'displayable' => [
                'methods_any' => ['displayLabel'],
            ],
        ]);

        $subject = new class implements CrudSluggableInterface {
            public function getSlug(): string
            {
                return 'demo';
            }
        };

        $profile = $resolver->profile($subject);

        self::assertArrayHasKey('sluggable', $profile->matches);
        self::assertArrayHasKey('displayable', $profile->matches);
        self::assertTrue($profile->matches['sluggable']->supported);
        self::assertFalse($profile->matches['displayable']->supported);
        self::assertTrue($profile->supports('sluggable'));
        self::assertFalse($profile->supports('unknown'));
        self::assertFalse($profile->match('unknown')->supported);
    }

    public function testResolveUsesObjectCapabilitiesAndRejectsUnknownClass(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => ['methods_any' => ['getSlug']],
            'identifiable' => ['properties_any' => ['id']],
        ]);
        $context = new CrudContextDTO(
            view: 'public',
            operation: 'show',
            resourcePath: 'demo',
            entityClass: 'App\\Tests\\MissingEntity',
            identifierField: 'id',
            identifierValue: null,
            formTypeClass: null,
        );
        $subject = new class {
            public int $id = 7;

            public function getSlug(): string
            {
                return 'demo';
            }
        };

        self::assertSame(
            ['supportsSlug' => true, 'supportsId' => true],
            $resolver->resolve($context, $subject),
        );
        self::assertFalse($resolver->supports('sluggable', 'App\\Tests\\MissingEntity'));
        self::assertFalse($resolver->match('sluggable', 'App\\Tests\\MissingEntity')->supported);
    }
}
