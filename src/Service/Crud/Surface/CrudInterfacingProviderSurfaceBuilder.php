<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\Surface\CrudInterfacingProviderSurfaceBuilderInterface;
use Symfony\Component\Form\FormView;

/**
 * Converts Cruding-owned resource pages into the canonical Interfacing provider surface.
 *
 * Controllers keep Cruding as the route/data/mutation owner. Interfacing owns
 * the final UI document and provider-native rendering.
 */
final class CrudInterfacingProviderSurfaceBuilder implements CrudInterfacingProviderSurfaceBuilderInterface
{
    public function __construct(
        private readonly CrudSurfaceOperationResolver $operationResolver,
        private readonly CrudSurfaceLabelFormatter $labelFormatter,
        private readonly CrudSurfaceRowBuilder $rowBuilder,
        private readonly CrudSurfaceColumnBuilder $columnBuilder,
        private readonly CrudSurfaceFilterBuilder $filterBuilder,
        private readonly CrudSurfaceFormFieldBuilder $formFieldBuilder,
        private readonly CrudSurfaceActionBuilder $actionBuilder,
        private readonly CrudSurfaceWorkbenchBuilder $workbenchBuilder,
        private readonly CrudSurfaceLocationBuilder $locationBuilder,
    ) {
    }

    public function build(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): array
    {
        $context = $page->context;
        $operation = $this->operationResolver->resolve($context->operation, $page->template);
        $resourcePath = trim(str_replace('_', '-', $context->resourcePath), '/');
        $component = 'cruding';
        $objects = null !== $object && [] === $page->objects ? [$object] : $page->objects;

        $rows = $this->rowBuilder->build($objects, $resourcePath, $component);
        $columns = $this->columnBuilder->build($objects, $resourcePath, $component);
        $filters = $this->filterBuilder->build($resourcePath);
        $formFields = $this->formFieldBuilder->build($form, $resourcePath);
        $actions = $this->actionBuilder->build($page->actions);
        $workbench = $this->workbenchBuilder->build($page, $rows, $columns, $filters, $formFields, $actions, $resourcePath, $component, $operation);

        return [
            'component' => $component,
            'resource' => $resourcePath,
            'operation' => $operation,
            'surface' => $context->surface,
            'title' => '' !== $page->title ? $page->title : $this->labelFormatter->humanize($resourcePath),
            'collectionLabel' => $this->labelFormatter->humanize($resourcePath),
            'defaultView' => $this->operationResolver->defaultView($operation),
            'rows' => $rows,
            'columns' => $columns,
            'filters' => $filters,
            'formFields' => $formFields,
            'headerActions' => $actions,
            'workbench' => $workbench,
            'locations' => $this->locationBuilder->build($workbench, $rows, $columns, $filters, $formFields, $actions),
        ];
    }
}
