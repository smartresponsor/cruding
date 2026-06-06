<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

use App\Cruding\Dto\Crud\CrudPageDefinition;

final class CrudSurfaceWorkbenchBuilder
{
    public function __construct(
        private readonly CrudSurfaceLabelFormatter $labelFormatter,
        private readonly CrudSurfaceOperationResolver $operationResolver,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<array<string, mixed>> $columns
     * @param list<array<string, mixed>> $filters
     * @param list<array<string, mixed>> $formFields
     * @param list<array<string, mixed>> $actions
     *
     * @return array<string, mixed>
     */
    public function build(CrudPageDefinition $page, array $rows, array $columns, array $filters, array $formFields, array $actions, string $resourcePath, string $component, string $operation): array
    {
        $mode = $this->operationResolver->workbenchMode($operation);

        return [
            'title' => $page->title,
            'component' => $component,
            'sourceComponent' => 'cruding',
            'renderingOwner' => 'interfacing',
            'routeContext' => [
                'resourcePath' => $resourcePath,
                'resourceLabel' => $this->labelFormatter->humanize($resourcePath),
                'resourceCollectionLabel' => $this->labelFormatter->humanize($resourcePath),
                'operation' => $operation,
                'surface' => $page->context->surface,
                'mode' => $mode,
                'collectionHref' => '/'.$resourcePath.'/',
                'sourceComponent' => 'cruding',
                'sourceView' => $page->template,
            ],
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $filters,
            'formFields' => $formFields,
            'formSections' => [],
            'headerActions' => $actions,
            'paginationLabel' => sprintf('%d Cruding-owned records exposed through Interfacing', count($rows)),
            'resourceUrl' => 'show' === $operation && null !== $page->context->identifierValue
                ? '/'.trim($resourcePath, '/').'/'.(string) $page->context->identifierValue
                : null,
            'diagnostics' => [
                'sourceComponent' => 'cruding',
                'sourceView' => $page->template,
                'renderingContract' => 'Interfacing resource index candidate chain',
                'fallbackContract' => 'Interfacing index then local component then Viewing',
                'localTwigShellPrimaryRendering' => false,
            ],
        ];
    }
}
