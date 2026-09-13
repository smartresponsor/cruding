<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Resource;

use App\Cruding\Service\Resource\CrudResourceLabelFormatter;
use App\Tabling\Service\TableColumnMetadataBuilder;

/**
 * Builds resource column builder values used by Cruding workflows.
 */
final class CrudResourceColumnBuilder
{
    public function __construct(
        private readonly CrudResourceRowBuilder $rowBuilder,
        private readonly CrudResourceLabelFormatter $labelFormatter,
        private readonly TableColumnMetadataBuilder $tableColumnMetadataBuilder,
    ) {
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    public function build(array $objects, string $resourcePath, string $component): array
    {
        $rows = [] !== $objects ? $this->rowBuilder->build([$objects[0]], $resourcePath, $component) : [];

        return $this->tableColumnMetadataBuilder->build($rows, $this->labelFormatter->humanize($resourcePath));
    }
}
