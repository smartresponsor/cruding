<?php

declare(strict_types=1);

namespace App\Cruding\Provider\Failure;

use App\Failing\Contract\FailureProviderInterface;
use App\Failing\DTO\FailureDefinitionDTO;
use App\Failing\ValueObject\FailureCode;
use App\Failing\ValueObject\FailureType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Declares Cruding-owned public failure vocabulary without owning shared failure mechanics.
 */
final class CrudFailureProvider implements FailureProviderInterface
{
    public const string RESOURCE_NOT_FOUND = 'crud_not_found';

    public function definitions(): iterable
    {
        yield new FailureDefinitionDTO(
            new FailureCode(self::RESOURCE_NOT_FOUND),
            new FailureType('urn:cruding:problem:crud_not_found'),
            Response::HTTP_NOT_FOUND,
            'Not Found',
            NotFoundHttpException::class,
        );
    }
}
