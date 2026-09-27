<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Failure;

use App\Cruding\Factory\Api\CrudApiProblemResponseFactory;
use App\Cruding\Provider\Failure\CrudFailureProvider;
use App\Failing\Registry\FailureRegistry;
use App\Failing\Resolver\FailureResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudFailureProviderTest extends TestCase
{
    public function testExistingCrudNotFoundContractResolvesThroughFailingWithoutRenaming(): void
    {
        $registry = new FailureRegistry([new CrudFailureProvider()]);
        $resolved = (new FailureResolver($registry))->resolve(new NotFoundHttpException('Missing resource.'));

        self::assertNotNull($resolved);
        self::assertSame(CrudFailureProvider::RESOURCE_NOT_FOUND, (string) $resolved->code);
        self::assertSame('urn:cruding:problem:crud_not_found', (string) $resolved->type);
        self::assertSame(404, $resolved->httpStatus);

        $existingResponse = (new CrudApiProblemResponseFactory())->notFound();
        $existingPayload = json_decode((string) $existingResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($existingPayload);
        self::assertSame(CrudFailureProvider::RESOURCE_NOT_FOUND, $existingPayload['code']);
        self::assertSame((string) $resolved->type, $existingPayload['type']);
        self::assertSame($resolved->httpStatus, $existingResponse->getStatusCode());
    }
}
