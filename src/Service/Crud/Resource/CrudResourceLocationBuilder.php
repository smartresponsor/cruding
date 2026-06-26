<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final class CrudResourceLocationBuilder
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<array<string, mixed>> $columns
     * @param list<array<string, mixed>> $filters
     * @param list<array<string, mixed>> $formFields
     * @param list<array<string, mixed>> $actions
     *
     * @return array<string, mixed>
     */
    public function build(array $workbench, array $rows, array $columns, array $filters, array $formFields, array $actions): array
    {
        return [
            'top' => [
                [
                    'key' => 'resource_header',
                    'type' => 'resource_header',
                    'data' => [
                        'title' => $workbench['title'] ?? 'Cruding',
                        'routeContext' => $workbench['routeContext'] ?? [],
                    ],
                    'meta' => [],
                ],
            ],
            'filter' => [
                [
                    'key' => 'resource_filter',
                    'type' => 'resource_filter',
                    'data' => ['items' => $filters],
                    'meta' => [],
                ],
            ],
            'body' => [
                [
                    'key' => 'resource_workbench',
                    'type' => 'resource_workbench',
                    'data' => [
                        'rows' => $rows,
                        'columns' => $columns,
                        'formFields' => $formFields,
                        'workbench' => $workbench,
                    ],
                    'meta' => [],
                ],
            ],
            'right' => [
                [
                    'key' => 'resource_action',
                    'type' => 'resource_action',
                    'data' => ['items' => $actions],
                    'meta' => [],
                ],
            ],
        ];
    }
}
