<?php

declare(strict_types=1);

namespace App\Cruding\Factory\Resource;

use App\Cruding\DTO\CrudPageDefinitionDTO;
use App\Cruding\ServiceInterface\Resource\CrudInterfacingProviderResourceBuilderInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\Form\FormView;

/**
 * Creates resource contract factory values used by Cruding workflows.
 */
final readonly class CrudResourceContractFactory
{
    public function __construct(
        private CrudInterfacingProviderResourceBuilderInterface $providerViewBuilder,
    ) {
    }

    /**      * Executes the create operation.      */
    public function create(CrudPageDefinitionDTO $page, ?object $object = null, ?FormView $form = null): CrudResourceContract
    {
        $built = $this->providerViewBuilder->build($page, $object, $form);
        $workbench = is_array($built['workbench'] ?? null) ? $built['workbench'] : [];
        /** @var array<string, mixed> $workbench */
        $locations = is_array($built['locations'] ?? null) ? $built['locations'] : [];
        /** @var array<string, mixed> $locations */
        $routeContext = is_array($workbench['routeContext'] ?? null) ? $workbench['routeContext'] : [];
        $routeOperation = $routeContext['operation'] ?? $page->context->operation;
        $view = $this->viewFromOperation(is_string($routeOperation) ? $routeOperation : $page->context->operation);

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
