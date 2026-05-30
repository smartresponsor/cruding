<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Service\Crud\CrudResourcePathParser;
use PHPUnit\Framework\TestCase;

final class CrudResourcePathParserTest extends TestCase
{
    public function testNormalizeCollapsesAndLowercasesPath(): void
    {
        $parser = new CrudResourcePathParser();

        self::assertSame('product/price', $parser->normalize('//Product///Price/'));
    }

    public function testSegmentsReturnsOrderedSegments(): void
    {
        $parser = new CrudResourcePathParser();

        self::assertSame(['product', 'price'], $parser->segments('/product/price/'));
    }

    public function testTailReturnsLastSegment(): void
    {
        $parser = new CrudResourcePathParser();

        self::assertSame('attachment', $parser->tail('/resource/item/attachment/'));
        self::assertSame('', $parser->tail('/'));
    }
}
