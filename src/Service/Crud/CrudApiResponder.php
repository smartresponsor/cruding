<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Api\ApiProblemResponseFactory;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class CrudApiResponder implements CrudApiResponderInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ApiProblemResponseFactory $problemResponseFactory,
    ) {
    }

    public function collection(CrudContext $context, array $objects): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'count' => count($objects),
            'items' => $this->normalize($objects),
        ]);
    }

    public function item(CrudContext $context, object $object, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'item' => $this->normalize($object),
        ], $status);
    }

    public function deleted(CrudContext $context): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'deleted' => true,
        ], JsonResponse::HTTP_OK);
    }

    public function notFound(string $resourcePath, string $detail = 'Resource not found.'): JsonResponse
    {
        return $this->problemResponseFactory->notFound($detail, [
            'resourcePath' => $resourcePath,
        ]);
    }

    public function validationError(CrudContext $context, FormInterface $form): JsonResponse
    {
        $errors = [];
        foreach ($form->getErrors(true, true) as $error) {
            $errors[] = [
                'field' => $error->getOrigin()?->getName() ?? '_form',
                'message' => $error->getMessage(),
            ];
        }

        return $this->problemResponseFactory->unprocessable(
            'Validation failed for API CRUD request.',
            $errors,
            ['resourcePath' => $context->resourcePath],
        );
    }

    private function normalize(object|array $data): mixed
    {
        return $this->serializer->normalize($data, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => static function (object $object): string|int|null {
                if (method_exists($object, 'getSlug')) {
                    $slug = $object->getSlug();

                    return is_scalar($slug) ? $slug : null;
                }

                if (method_exists($object, 'getId')) {
                    $id = $object->getId();

                    return is_scalar($id) ? $id : null;
                }

                return $object::class;
            },
        ]);
    }
}
