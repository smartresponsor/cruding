<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudPageActionDefinition;
use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;

final readonly class CrudPageDefinitionProvider implements CrudPageDefinitionProviderInterface
{
    public function __construct(
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudRouteNameResolverInterface $routeNameResolver,
    ) {
    }

    public function provideIndex(CrudContext $context): CrudPageDefinition
    {
        $access = $this->accessContextBuilder->build($context);
        $actions = [];

        if (null !== $context->formTypeClass && $access->canEdit) {
            $actions[] = new CrudPageActionDefinition(
                'new',
                'Create',
                $this->routeNameResolver->resolveNew($context),
                $this->routeNameResolver->parameters($context, null, null),
            );
        }

        return new CrudPageDefinition(
            $context,
            $access,
            sprintf('%s index', $context->resourcePath),
            'index',
            $this->objectFinder->findAll($context),
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'surface' => $context->surface,
                'operation' => $context->operation,
            ],
        );
    }

    public function provideShow(CrudContext $context, object $object): CrudPageDefinition
    {
        $access = $this->accessContextBuilder->build($context, $object);
        $actions = [
            new CrudPageActionDefinition(
                'index',
                'Back to list',
                $this->routeNameResolver->resolveIndex($context),
                $this->routeNameResolver->parameters($context, null, null),
            ),
        ];

        if (null !== $context->formTypeClass && $access->canEdit) {
            $actions[] = new CrudPageActionDefinition(
                'edit',
                'Edit',
                $this->routeNameResolver->resolveEdit($context),
                $this->routeNameResolver->parameters($context),
            );
        }

        return new CrudPageDefinition(
            $context,
            $access,
            sprintf('%s show', $context->resourcePath),
            'show',
            [$object],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'surface' => $context->surface,
                'operation' => $context->operation,
                'identifierField' => $context->identifierField,
                'identifierValue' => $context->identifierValue,
            ],
        );
    }

    public function provideNew(CrudContext $context, object $object, mixed $formView): CrudPageDefinition
    {
        $access = $this->accessContextBuilder->build($context, $object);

        return new CrudPageDefinition(
            $context,
            $access,
            sprintf('%s new', $context->resourcePath),
            'new',
            [$object],
            [
                new CrudPageActionDefinition(
                    'index',
                    'Back to list',
                    $this->routeNameResolver->resolveIndex($context),
                    $this->routeNameResolver->parameters($context, null, null),
                ),
            ],
            [
                'resourcePath' => $context->resourcePath,
                'surface' => $context->surface,
                'operation' => $context->operation,
                'formView' => $formView,
            ],
        );
    }

    public function provideEdit(CrudContext $context, object $object, mixed $formView): CrudPageDefinition
    {
        $access = $this->accessContextBuilder->build($context, $object);
        $actions = [
            new CrudPageActionDefinition(
                'index',
                'Back to list',
                $this->routeNameResolver->resolveIndex($context),
                $this->routeNameResolver->parameters($context, null, null),
            ),
        ];

        if ($access->canDelete) {
            $actions[] = new CrudPageActionDefinition(
                'delete',
                'Delete',
                $this->routeNameResolver->resolveDelete($context),
                $this->routeNameResolver->parameters($context),
                'danger',
            );
        }

        return new CrudPageDefinition(
            $context,
            $access,
            sprintf('%s edit', $context->resourcePath),
            'edit',
            [$object],
            $actions,
            [
                'resourcePath' => $context->resourcePath,
                'surface' => $context->surface,
                'operation' => $context->operation,
                'formView' => $formView,
            ],
        );
    }
}
