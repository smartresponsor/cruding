<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Api;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Factory\Api\CrudApiProblemResponseFactory;
use App\Cruding\Responder\CrudApiResponder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CrudApiResponderTest extends TestCase
{
    public function testCollectionKeepsStableExternalShape(): void
    {
        $object = new \stdClass();
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with([$object], 'json', self::isType('array'))
            ->willReturn([['id' => 1]]);

        $payload = $this->payload($this->responder($normalizer)->collection($this->context(), [$object]));

        self::assertSame('product', $payload['resource']);
        self::assertSame(1, $payload['count']);
        self::assertSame([['id' => 1]], $payload['items']);
    }

    public function testItemKeepsStableExternalShapeAndStatus(): void
    {
        $object = new \stdClass();
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with($object, 'json', self::isType('array'))
            ->willReturn(['id' => 1]);

        $response = $this->responder($normalizer)->item($this->context(), $object, Response::HTTP_CREATED);
        $payload = $this->payload($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('product', $payload['resource']);
        self::assertSame(['id' => 1], $payload['item']);
    }

    public function testDeleteKeepsStableExternalShape(): void
    {
        $normalizer = $this->createStub(NormalizerInterface::class);

        $payload = $this->payload($this->responder($normalizer)->deleted($this->context()));

        self::assertSame('product', $payload['resource']);
        self::assertTrue($payload['deleted']);
    }

    private function responder(NormalizerInterface $normalizer): CrudApiResponder
    {
        return new CrudApiResponder($normalizer, new CrudApiProblemResponseFactory());
    }

    private function context(): CrudContextDTO
    {
        return new CrudContextDTO('api', 'index', 'product', \stdClass::class, 'id', null, null);
    }

    /** @return array<string, mixed> */
    private function payload(Response $response): array
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }
}
