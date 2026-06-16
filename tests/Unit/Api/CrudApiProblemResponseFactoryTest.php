<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Api;

use App\Cruding\Service\Crud\Api\CrudApiProblemResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class CrudApiProblemResponseFactoryTest extends TestCase
{
    public function testUnprocessableCreatesProblemJsonPayload(): void
    {
        $factory = new CrudApiProblemResponseFactory();
        $response = $factory->unprocessable('Validation failed.', [['field' => 'slug']], ['resourcePath' => 'product']);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame('Validation Failed', $payload['title']);
        self::assertSame('product', $payload['resourcePath']);
        self::assertSame([['field' => 'slug']], $payload['errors']);
    }
}
