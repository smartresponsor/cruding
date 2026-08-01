<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\Resource\CrudInterfacingProviderResourceBuilderInterface;
use Symfony\Component\Form\FormView;

/**
 * Converts Cruding-owned resource pages into the canonical Interfacing provider view.
 *
 * Controllers keep Cruding as the route/data/mutation owner. Interfacing owns
 * the final UI document and provider-native rendering.
 */
final class CrudInterfacingProviderResourceBuilder implements CrudInterfacingProviderResourceBuilderInterface
{
    public function __construct(
        private readonly CrudResourceOperationResolver $operationResolver,
        private readonly CrudResourceLabelFormatter $labelFormatter,
        private readonly CrudResourceRowBuilder $rowBuilder,
        private readonly CrudResourceColumnBuilder $columnBuilder,
        private readonly CrudResourceFilterBuilder $filterBuilder,
        private readonly CrudResourceFormFieldBuilder $formFieldBuilder,
        private readonly CrudResourceActionBuilder $actionBuilder,
        private readonly CrudResourceWorkbenchBuilder $workbenchBuilder,
        private readonly CrudResourceLocationBuilder $locationBuilder,
    ) {
    }

    public function build(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): array
    {
        $context = $page->context;
        $operation = $this->operationResolver->resolve($context->operation, $page->template);
        $resourcePath = trim(str_replace('_', '-', $context->resourcePath), '/');
        $component = 'cruding';
        $objects = null !== $object && [] === $page->objects ? [$object] : $page->objects;
        $projectedRows = is_array($page->meta['projectedRows'] ?? null) ? $page->meta['projectedRows'] : null;

        $rows = null !== $projectedRows ? $projectedRows : $this->rowBuilder->build($objects, $resourcePath, $component);
        $columns = $this->columnBuilder->build($objects, $resourcePath, $component);
        $filters = $this->filterBuilder->build($resourcePath);
        $formFields = $this->formFieldBuilder->build($form, $resourcePath);
        $actions = $this->actionBuilder->build($page->actions);
        $workbench = $this->workbenchBuilder->build($page, $rows, $columns, $filters, $formFields, $actions, $resourcePath, $component, $operation);

        return [
            'component' => $component,
            'resource' => $resourcePath,
            'operation' => $operation,
            'view' => $context->view,
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
