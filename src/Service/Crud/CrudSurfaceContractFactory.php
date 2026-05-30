<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\CrudInterfacingProviderSurfaceBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudTemplateResolverInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\Form\FormView;

final readonly class CrudSurfaceContractFactory
{
    public function __construct(
        private CrudInterfacingProviderSurfaceBuilderInterface $providerSurfaceBuilder,
        private CrudTemplateResolverInterface $templateResolver,
    ) {
    }

    public function create(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): CrudSurfaceContract
    {
        $built = $this->providerSurfaceBuilder->build($page, $object, $form);
        $workbench = is_array($built['workbench'] ?? null) ? $built['workbench'] : [];
        $view = $this->viewFromOperation((string) ($workbench['routeContext']['operation'] ?? $page->context->operation));
        $templatePath = $this->templateResolver->resolveSurfaceTemplate($page->context->resourcePath);

        return new CrudSurfaceContract(
            CrudSurfaceContract::WORD,
            $view,
            $templatePath,
            $this->slotMap(),
            $workbench,
            [
                'page' => [
                    'title' => $page->title,
                    'template' => $page->template,
                    'meta' => $this->sanitizeValue($page->meta),
                ],
                'crud' => $built,
                'viewModes' => ['table', 'cards'],
                'sourceTemplate' => $page->template,
                'sourceOperation' => $page->context->operation,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function slotMap(): array
    {
        return [
            'top.search' => 'Search',
            'left.panel' => 'Resource operations',
            'main.body' => 'Resource workbench',
            'right.panel' => 'Actions',
        ];
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
