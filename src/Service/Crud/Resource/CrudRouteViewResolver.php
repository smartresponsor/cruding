<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

/**
 * Maps a canonical operation token to a provider-facing view nameEntity.
 */
final readonly class CrudRouteViewResolver
{
    public function viewFromOperation(string $operation): string
    {
        return match ($operation) {
            'show' => 'detail',
            'new', 'edit' => 'form',
            default => $operation,
        };
    }
}
