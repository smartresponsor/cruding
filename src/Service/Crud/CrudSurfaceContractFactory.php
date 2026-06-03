<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\CrudInterfacingProviderSurfaceBuilderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\Form\FormView;

final readonly class CrudSurfaceContractFactory
{
    public function __construct(
        private CrudInterfacingProviderSurfaceBuilderInterface $providerSurfaceBuilder,
    ) {
    }

    public function create(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): CrudSurfaceContract
    {
        $built = $this->providerSurfaceBuilder->build($page, $object, $form);
        $workbench = is_array($built['workbench'] ?? null) ? $built['workbench'] : [];
        $locations = is_array($built['locations'] ?? null) ? $built['locations'] : [];
        $view = $this->viewFromOperation((string) ($workbench['routeContext']['operation'] ?? $page->context->operation));

        return new CrudSurfaceContract(
            CrudSurfaceContract::WORD,
            $view,
            CrudSurfaceContract::defaultSlotMap(),
            $workbench,
            [
                'page' => [
                    'title' => $page->title,
                    'template' => $page->template,
                    'meta' => $this->sanitizeValue($page->meta),
                ],
                'crud' => $built,
                'viewModes' => ['table', 'cards'],
                'sourceView' => $page->template,
                'sourceOperation' => $page->context->operation,
                'locations' => $locations,
            ],
            $locations,
        );
    }

    private function viewFromOperation(string $operation): string
    {
        return match ($operation) {
            'show' => 'detail',
            'new', 'edit' => 'form',
            default => 'index',
        };
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeValue($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return $value::class;
        }

        if (is_resource($value)) {
            return get_resource_type($value);
        }

        return $value;
    }
}
