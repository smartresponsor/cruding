<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Service\Api\CrudApiProblemResponseFactory;
use App\Cruding\ServiceInterface\CrudApiResponderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Provides the api responder responsibility within the Cruding component.
 */
final readonly class CrudApiResponder implements CrudApiResponderInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private CrudApiProblemResponseFactory $problemResponseFactory,
    ) {
    }

    /**      * Executes the collection operation.      */
    public function collection(CrudContextDTO $context, array $objects): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'count' => count($objects),
            'items' => $this->normalize($objects),
        ]);
    }

    /**      * Executes the item operation.      */
    public function item(CrudContextDTO $context, object $object, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'item' => $this->normalize($object),
        ], $status);
    }

    /**      * Executes the deleted operation.      */
    public function deleted(CrudContextDTO $context): JsonResponse
    {
        return new JsonResponse([
            'resource' => $context->resourcePath,
            'deleted' => true,
        ], JsonResponse::HTTP_OK);
    }

    /**      * Executes the not found operation.      */
    public function notFound(string $resourcePath, string $detail = 'Resource not found.'): JsonResponse
    {
        return $this->problemResponseFactory->notFound($detail, [
            'resourcePath' => $resourcePath,
        ]);
    }

    /**      * Executes the validation error operation.      */
    public function validationError(CrudContextDTO $context, FormInterface $form): JsonResponse
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
