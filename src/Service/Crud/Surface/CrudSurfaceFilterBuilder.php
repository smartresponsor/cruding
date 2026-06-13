<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

final class CrudSurfaceFilterBuilder
{
    public function __construct(private readonly CrudSurfaceLabelFormatter $labelFormatter)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(string $resourcePath): array
    {
        return [
            ['nameEntity' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => null, 'placeholder' => 'Search '.$this->labelFormatter->humanize($resourcePath), 'options' => []],
            ['nameEntity' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => null, 'placeholder' => 'Any status', 'options' => []],
        ];
    }
}
