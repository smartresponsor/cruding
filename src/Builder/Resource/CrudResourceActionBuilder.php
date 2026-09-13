<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Resource;

use App\Cruding\DTO\CrudPageActionDefinitionDTO;
use App\Tabling\DTO\TableActionDTO;
use App\Tabling\Service\TableActionMetadataBuilder;

/**
 * Builds resource action builder values used by Cruding workflows.
 */
final class CrudResourceActionBuilder
{
    public function __construct(private readonly TableActionMetadataBuilder $tableActionMetadataBuilder)
    {
    }

    /**
     * @param list<CrudPageActionDefinitionDTO> $actions
     *
     * @return list<array<string, mixed>>
     */
    public function build(array $actions): array
    {
        $items = [];
        foreach ($actions as $action) {
            $tableAction = new TableActionDTO(
                $action->nameEntity,
                $action->label,
                $action->routeName,
                $action->routeParameters,
                null,
                $action->scope,
                'danger' === $action->scope,
                $action->enabled,
            );
            $items[] = $this->tableActionMetadataBuilder->build($tableAction, $this->hrefForAction($action));
        }

        return $items;
    }

    private function hrefForAction(CrudPageActionDefinitionDTO $action): string
    {
        $resourcePath = (string) ($action->routeParameters['resourcePath'] ?? 'resource');

        return match ($action->nameEntity) {
            'new' => '/'.trim($resourcePath, '/').'/new/',
            'index' => '/'.trim($resourcePath, '/').'/',
            'edit' => '/'.trim($resourcePath, '/').'/edit/'.(string) ($action->routeParameters['slug'] ?? $action->routeParameters['id'] ?? 'sample'),
            'delete' => '/'.trim($resourcePath, '/').'/delete/'.(string) ($action->routeParameters['slug'] ?? $action->routeParameters['id'] ?? 'sample'),
            default => '/'.trim($resourcePath, '/').'/',
        };
    }
}
