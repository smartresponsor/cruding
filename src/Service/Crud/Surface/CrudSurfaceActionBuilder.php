<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

use App\Cruding\Dto\Crud\CrudPageActionDefinition;

final class CrudSurfaceActionBuilder
{
    /**
     * @param list<CrudPageActionDefinition> $actions
     *
     * @return list<array<string, mixed>>
     */
    public function build(array $actions): array
    {
        $items = [];
        foreach ($actions as $action) {
            $items[] = [
                'label' => $action->label,
                'href' => $this->hrefForAction($action),
                'variant' => 'danger' === $action->scope ? 'danger' : ('new' === $action->name ? 'primary' : 'default'),
                'operation' => $action->name,
                'enabled' => $action->enabled,
                'visibility' => $action->enabled ? 'visible' : 'disabled',
            ];
        }

        return $items;
    }

    private function hrefForAction(CrudPageActionDefinition $action): string
    {
        $resourcePath = (string) ($action->routeParameters['resourcePath'] ?? 'resource');

        return match ($action->name) {
            'new' => '/'.trim($resourcePath, '/').'/new/',
            'index' => '/'.trim($resourcePath, '/').'/',
            'edit' => '/'.trim($resourcePath, '/').'/edit/'.(string) ($action->routeParameters['slug'] ?? $action->routeParameters['id'] ?? 'sample'),
            'delete' => '/'.trim($resourcePath, '/').'/delete/'.(string) ($action->routeParameters['slug'] ?? $action->routeParameters['id'] ?? 'sample'),
            default => '/'.trim($resourcePath, '/').'/',
        };
    }
}
