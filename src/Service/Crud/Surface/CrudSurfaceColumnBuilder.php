<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

final class CrudSurfaceColumnBuilder
{
    public function __construct(
        private readonly CrudSurfaceRowBuilder $rowBuilder,
        private readonly CrudSurfaceLabelFormatter $labelFormatter,
    ) {
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    public function build(array $objects, string $resourcePath, string $component): array
    {
        $columns = [
            ['key' => 'title', 'label' => $this->labelFormatter->humanize($resourcePath), 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ['key' => 'code', 'label' => 'Code', 'type' => 'text', 'isCode' => true, 'isStatus' => false],
            ['key' => 'owner', 'label' => 'Owner', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'isCode' => false, 'isStatus' => true],
            ['key' => 'locale', 'label' => 'Locale', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
        ];

        if ([] !== $objects) {
            $first = $this->rowBuilder->build([$objects[0]], $resourcePath, $component)[0];
            $knownKeys = array_column($columns, 'key');
            foreach (array_keys($first) as $key) {
                if ('id' !== $key && !in_array($key, $knownKeys, true)) {
                    $columns[] = ['key' => $key, 'label' => $this->labelFormatter->humanize($key), 'type' => 'text', 'isCode' => false, 'isStatus' => false];
                }
            }
        }

        return $columns;
    }
}
