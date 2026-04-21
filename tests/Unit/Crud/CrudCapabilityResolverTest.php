<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Contract\Capability\SluggableInterface;
use App\Cruding\Service\Crud\CrudCapabilityResolver;
use PHPUnit\Framework\TestCase;

final class CrudCapabilityResolverTest extends TestCase
{
    public function testSupportsExplicitInterfaceBeforeAliases(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => [
                'interfaces' => [SluggableInterface::class],
                'methods_any' => ['getSlug'],
            ],
        ]);

        $subject = new class implements SluggableInterface {
            public function getSlug(): string
            {
                return 'demo-slug';
            }
        };

        $match = $resolver->match('sluggable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('explicit_interface', $match->source);
        self::assertSame(SluggableInterface::class, $match->interfaceName);
    }

    public function testFallsBackToAliasMethodWhenNoInterfaceExists(): void
    {
        $resolver = new CrudCapabilityResolver([
            'ownable' => [
                'interfaces' => [],
                'methods_any' => ['getCreatedBy', 'getOwner'],
            ],
        ]);

        $subject = new class {
            public function getCreatedBy(): string
            {
                return 'owner';
            }
        };

        $match = $resolver->match('ownable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('alias_method', $match->source);
        self::assertSame('getCreatedBy', $match->accessor);
        self::assertSame('method', $match->accessorType);
    }

    public function testFallsBackToAliasPropertyWhenNoInterfaceOrMethodExists(): void
    {
        $resolver = new CrudCapabilityResolver([
            'taggable' => [
                'interfaces' => [],
                'methods_any' => [],
                'properties_any' => ['tags'],
            ],
        ]);

        $subject = new class {
            /** @var list<string> */
            public array $tags = [];
        };

        $match = $resolver->match('taggable', $subject);

        self::assertTrue($match->supported);
        self::assertSame('alias_property', $match->source);
        self::assertSame('tags', $match->accessor);
        self::assertSame('property', $match->accessorType);
    }

    public function testProfileIncludesUnsupportedCapabilities(): void
    {
        $resolver = new CrudCapabilityResolver([
            'sluggable' => [
                'interfaces' => [SluggableInterface::class],
            ],
            'attachable' => [
                'methods_any' => ['getAttachments'],
            ],
        ]);

        $subject = new class implements SluggableInterface {
            public function getSlug(): string
            {
                return 'demo';
            }
        };

        $profile = $resolver->profile($subject);

        self::assertArrayHasKey('sluggable', $profile->matches);
        self::assertArrayHasKey('attachable', $profile->matches);
        self::assertTrue($profile->matches['sluggable']->supported);
        self::assertFalse($profile->matches['attachable']->supported);
    }
}
