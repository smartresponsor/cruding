<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\Resource\CrudInterfacingProviderResourceBuilderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\Form\FormView;

final readonly class CrudResourceContractFactory
{
    public function __construct(
        private CrudInterfacingProviderResourceBuilderInterface $providerViewBuilder,
    ) {
    }

    public function create(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): CrudResourceContract
    {
        $built = $this->providerViewBuilder->build($page, $object, $form);
        $workbench = is_array($built['workbench'] ?? null) ? $built['workbench'] : [];
        $locations = is_array($built['locations'] ?? null) ? $built['locations'] : [];
        $view = $this->viewFromOperation((string) ($workbench['routeContext']['operation'] ?? $page->context->operation));

        return new CrudResourceContract(
            CrudResourceContract::WORD,
            $view,
            CrudResourceContract::defaultSlotMap(),
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
            'page' => 'page',
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
