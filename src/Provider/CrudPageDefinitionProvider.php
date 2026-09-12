<?php

declare(strict_types=1);

namespace App\Cruding\Provider;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudPageActionDefinitionDTO;
use App\Cruding\DTO\CrudPageDefinitionDTO;
use App\Cruding\Service\CrudCollectionProjectionReader;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;

/**
 * Provides page definition provider data to Cruding consumers.
 */
final readonly class CrudPageDefinitionProvider implements CrudPageDefinitionProviderInterface
{
    public function __construct(
        private CrudObjectFinderInterface $objectFinder,
        private CrudCollectionProjectionReader $collectionProjectionReader,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudRouteNameResolverInterface $routeNameResolver,
    ) {
    }

    /**      * Provides index.      */
    public function provideIndex(CrudContextDTO $context): CrudPageDefinitionDTO
    {
        $access = $this->accessContextBuilder->build($context);
        $actions = [];

        if (null !== $context->formTypeClass && $access->canEdit) {
            $actions[] = new CrudPageActionDefinitionDTO(
                'new',
                'Create',
                $this->routeNameResolver->resolveNew($context),
                $this->routeNameResolver->parameters($context, null, null, 'new'),
            );
        }

        $projectedRows = $this->collectionProjectionReader->read($context);

        return new CrudPageDefinitionDTO(
            $context,
            $access,
            sprintf('%s index', $context->resourcePath),
            'index',
            null === $projectedRows ? $this->objectFinder->findAll($context) : [],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'view' => $context->view,
                'operation' => $context->operation,
                'projectedRows' => $projectedRows,
            ],
        );
    }

    /**      * Provides show.      */
    public function provideShow(CrudContextDTO $context, object $object): CrudPageDefinitionDTO
    {
        $access = $this->accessContextBuilder->build($context, $object);
        $actions = [
            new CrudPageActionDefinitionDTO(
                'index',
                'Back to list',
                $this->routeNameResolver->resolveIndex($context),
                $this->routeNameResolver->parameters($context, null, null, 'index'),
            ),
        ];

        if (null !== $context->formTypeClass && $access->canEdit) {
            $actions[] = new CrudPageActionDefinitionDTO(
                'edit',
                'Edit',
                $this->routeNameResolver->resolveEdit($context),
                $this->routeNameResolver->parameters($context, null, null, 'edit'),
            );
        }

        return new CrudPageDefinitionDTO(
            $context,
            $access,
            sprintf('%s show', $context->resourcePath),
            'detail',
            [$object],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'view' => $context->view,
                'operation' => $context->operation,
                'identifierField' => $context->identifierField,
                'identifierValue' => $context->identifierValue,
            ],
        );
    }

    /**      * Provides page.      */
    public function providePage(CrudContextDTO $context, ?object $object = null): CrudPageDefinitionDTO
    {
        if (null === $object) {
            $access = $this->accessContextBuilder->build($context);
            $projectedRows = $this->collectionProjectionReader->read($context);

            return new CrudPageDefinitionDTO(
                $context,
                $access,
                sprintf('%s page', $context->resourcePath),
                'page',
                null === $projectedRows ? $this->objectFinder->findAll($context) : [],
                [],
                [
                    'resourcePath' => $context->resourcePath,
                    'view' => $context->view,
                    'operation' => $context->operation,
                    'projectedRows' => $projectedRows,
                    'collectionPage' => true,
                ],
            );
        }

        $access = $this->accessContextBuilder->build($context, $object);
        $actions = [
            new CrudPageActionDefinitionDTO(
                'index',
                'Back to list',
                $this->routeNameResolver->resolveIndex($context),
                $this->routeNameResolver->parameters($context, null, null, 'index'),
            ),
        ];

        if (null !== $context->formTypeClass && $access->canEdit) {
            $actions[] = new CrudPageActionDefinitionDTO(
                'edit',
                'Edit',
                $this->routeNameResolver->resolveEdit($context),
                $this->routeNameResolver->parameters($context, null, null, 'edit'),
            );
        }

        return new CrudPageDefinitionDTO(
            $context,
            $access,
            sprintf('%s page', $context->resourcePath),
            'page',
            [$object],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'view' => $context->view,
                'operation' => $context->operation,
                'identifierField' => $context->identifierField,
                'identifierValue' => $context->identifierValue,
                'collectionPage' => false,
            ],
        );
    }

    /**      * Provides new.      */
    public function provideNew(CrudContextDTO $context, object $object, mixed $formView): CrudPageDefinitionDTO
    {
        $access = $this->accessContextBuilder->build($context, $object);

        return new CrudPageDefinitionDTO(
            $context,
            $access,
            sprintf('%s new', $context->resourcePath),
            'new',
            [$object],
            [
                new CrudPageActionDefinitionDTO(
                    'index',
                    'Back to list',
                    $this->routeNameResolver->resolveIndex($context),
                    $this->routeNameResolver->parameters($context, null, null, 'index'),
                ),
            ],
            [
                'resourcePath' => $context->resourcePath,
                'view' => $context->view,
                'operation' => $context->operation,
                'formView' => $formView,
            ],
        );
    }

    /**      * Provides edit.      */
    public function provideEdit(CrudContextDTO $context, object $object, mixed $formView): CrudPageDefinitionDTO
    {
        $access = $this->accessContextBuilder->build($context, $object);
        $actions = [
            new CrudPageActionDefinitionDTO(
                'index',
                'Back to list',
                $this->routeNameResolver->resolveIndex($context),
                $this->routeNameResolver->parameters($context, null, null, 'index'),
            ),
        ];

        if ($access->canDelete) {
            $actions[] = new CrudPageActionDefinitionDTO(
                'delete',
                'Delete',
                $this->routeNameResolver->resolveDelete($context),
                $this->routeNameResolver->parameters($context, null, null, 'delete'),
                'danger',
            );
        }

        return new CrudPageDefinitionDTO(
            $context,
            $access,
            sprintf('%s edit', $context->resourcePath),
            'edit',
            [$object],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'view' => $context->view,
                'operation' => $context->operation,
                'formView' => $formView,
            ],
        );
    }
}
