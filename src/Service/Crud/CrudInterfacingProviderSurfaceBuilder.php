<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudPageActionDefinition;
use App\Cruding\Dto\Crud\CrudPageDefinition;
use App\Cruding\ServiceInterface\Crud\CrudInterfacingProviderSurfaceBuilderInterface;
use Symfony\Component\Form\FormView;

/**
 * Converts Cruding-owned resource pages into the canonical Interfacing provider surface.
 *
 * Controllers keep Cruding as the route/data/mutation owner. Interfacing owns
 * the final UI document and provider-native rendering.
 */
final class CrudInterfacingProviderSurfaceBuilder implements CrudInterfacingProviderSurfaceBuilderInterface
{
    public function build(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): array
    {
        $context = $page->context;
        $operation = '' !== $context->operation ? $context->operation : $this->operationFromTemplate($page->template);
        $resourcePath = trim(str_replace('_', '-', $context->resourcePath), '/');
        $component = 'cruding';
        $objects = null !== $object && [] === $page->objects ? [$object] : $page->objects;

        $rows = $this->rowsFor($objects, $resourcePath, $component);
        $columns = $this->columnsFor($objects, $resourcePath, $component);
        $filters = $this->filtersFor($resourcePath);
        $formFields = $this->formFieldsFor($form, $resourcePath);
        $actions = $this->actionsFor($page->actions);

        return [
            'component' => $component,
            'resource' => $resourcePath,
            'operation' => $operation,
            'surface' => $context->surface,
            'title' => '' !== $page->title ? $page->title : $this->humanize($resourcePath),
            'collectionLabel' => $this->humanize($resourcePath),
            'defaultView' => in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'table'),
            'rows' => $rows,
            'columns' => $columns,
            'filters' => $filters,
            'formFields' => $formFields,
            'headerActions' => $actions,
            'workbench' => $this->workbenchFor($page, $objects, $form, $resourcePath, $component, $operation),
        ];
    }

    /**
     * @param list<object> $objects
     *
     * @return array<string, mixed>
     */
    private function workbenchFor(CrudPageDefinition $page, array $objects, ?FormView $form, string $resourcePath, string $component, string $operation): array
    {
        $mode = in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'collection');
        $rows = $this->rowsFor($objects, $resourcePath, $component);
        $columns = $this->columnsFor($objects, $resourcePath, $component);

        return [
            'title' => $page->title,
            'component' => $component,
            'sourceComponent' => 'cruding',
            'renderingOwner' => 'interfacing',
            'routeContext' => [
                'resourcePath' => $resourcePath,
                'resourceLabel' => $this->humanize($resourcePath),
                'resourceCollectionLabel' => $this->humanize($resourcePath),
                'operation' => $operation,
                'surface' => $page->context->surface,
                'mode' => $mode,
                'collectionHref' => '/'.$resourcePath.'/',
                'sourceComponent' => 'cruding',
                'sourceTemplate' => $page->template,
            ],
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $this->filtersFor($resourcePath),
            'formFields' => $this->formFieldsFor($form, $resourcePath),
            'formSections' => [],
            'headerActions' => $this->actionsFor($page->actions),
            'paginationLabel' => sprintf('%d Cruding-owned records exposed through Interfacing', count($rows)),
            'resourceUrl' => 'show' === $operation && null !== $page->context->identifierValue
                ? '/'.trim($resourcePath, '/').'/'.(string) $page->context->identifierValue
                : null,
            'diagnostics' => [
                'sourceComponent' => 'cruding',
                'sourceTemplate' => $page->template,
                'renderingContract' => '@Interfacing/<resource>/index.html.twig',
                'fallbackContract' => '@Interfacing/base.html.twig, then @Cruding/crud/index.html.twig',
                'localTwigShellPrimaryRendering' => false,
            ],
        ];
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    private function rowsFor(array $objects, string $resourcePath, string $component): array
    {
        if ([] === $objects) {
            return [
                [
                    'id' => $resourcePath.'-empty',
                    'title' => 'No records loaded yet',
                    'code' => strtoupper(str_replace('/', '-', $resourcePath)).'-EMPTY',
                    'owner' => $component,
                    'status' => 'empty',
                    'locale' => 'en',
                ],
            ];
        }

        $rows = [];
        foreach ($objects as $index => $object) {
            $rows[] = $this->rowForObject($object, $resourcePath, $component, $index);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowForObject(object $object, string $resourcePath, string $component, int $index): array
    {
        $id = $this->readScalar($object, ['getId', 'id']) ?? $this->readScalar($object, ['getSlug', 'slug']) ?? $resourcePath.'-'.($index + 1);
        $title = $this->readScalar($object, ['getTitle', 'title', 'getName', 'name', 'getLabel', 'label', 'getCode', 'code']) ?? $this->shortClass($object);
        $status = $this->readScalar($object, ['getStatus', 'status', 'isEnabled', 'enabled', 'isActive', 'active']) ?? 'loaded';
        $code = $this->readScalar($object, ['getCode', 'code', 'getSku', 'sku', 'getSlug', 'slug']) ?? strtoupper(str_replace('/', '-', $resourcePath)).'-'.($index + 1);
        $locale = $this->readScalar($object, ['getLocale', 'locale', 'getContentLocale', 'contentLocale']) ?? 'en';

        return [
            'id' => $id,
            'title' => $title,
            'code' => $code,
            'owner' => $component,
            'status' => is_bool($status) ? ($status ? 'active' : 'inactive') : $status,
            'locale' => $locale,
        ];
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    private function columnsFor(array $objects, string $resourcePath, string $component): array
    {
        $columns = [
            ['key' => 'title', 'label' => $this->humanize($resourcePath), 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ['key' => 'code', 'label' => 'Code', 'type' => 'text', 'isCode' => true, 'isStatus' => false],
            ['key' => 'owner', 'label' => 'Owner', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text', 'isCode' => false, 'isStatus' => true],
            ['key' => 'locale', 'label' => 'Locale', 'type' => 'text', 'isCode' => false, 'isStatus' => false],
        ];

        if ([] !== $objects) {
            $first = $this->rowsFor([$objects[0]], $resourcePath, $component)[0];
            $knownKeys = array_column($columns, 'key');
            foreach (array_keys($first) as $key) {
                if ('id' !== $key && !in_array($key, $knownKeys, true)) {
                    $columns[] = ['key' => $key, 'label' => $this->humanize($key), 'type' => 'text', 'isCode' => false, 'isStatus' => false];
                }
            }
        }

        return $columns;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function filtersFor(string $resourcePath): array
    {
        return [
            ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => null, 'placeholder' => 'Search '.$this->humanize($resourcePath), 'options' => []],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => null, 'placeholder' => 'Any status', 'options' => []],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formFieldsFor(?FormView $form, string $resourcePath): array
    {
        if (null === $form) {
            return [
                ['name' => 'title', 'label' => $this->humanize($resourcePath), 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
                ['name' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
            ];
        }

        $fields = [];
        foreach ($form->children as $name => $child) {
            if (str_starts_with((string) $name, '_')) {
                continue;
            }

            $vars = $child->vars;
            $fields[] = [
                'name' => (string) $name,
                'label' => is_string($vars['label'] ?? null) && '' !== $vars['label'] ? $vars['label'] : $this->humanize((string) $name),
                'type' => $this->fieldType((string) ($vars['block_prefixes'][1] ?? 'text')),
                'required' => (bool) ($vars['required'] ?? false),
                'value' => is_scalar($vars['value'] ?? null) ? (string) $vars['value'] : null,
                'placeholder' => is_scalar($vars['attr']['placeholder'] ?? null) ? (string) $vars['attr']['placeholder'] : null,
                'helpText' => is_string($vars['help'] ?? null) ? $vars['help'] : null,
                'validationState' => null,
                'errorText' => null,
                'options' => is_array($vars['choices'] ?? null) ? $vars['choices'] : [],
            ];
        }

        return [] !== $fields ? $fields : [
            ['name' => 'title', 'label' => $this->humanize($resourcePath), 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
        ];
    }

    /**
     * @param list<CrudPageActionDefinition> $actions
     *
     * @return list<array<string, mixed>>
     */
    private function actionsFor(array $actions): array
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

    /**
     * @param list<string> $readers
     */
    private function readScalar(object $object, array $readers): string|int|float|bool|null
    {
        foreach ($readers as $reader) {
            if (method_exists($object, $reader)) {
                try {
                    $value = $object->{$reader}();
                } catch (\Throwable) {
                    continue;
                }

                return is_scalar($value) ? $value : null;
            }

            if (property_exists($object, $reader)) {
                try {
                    $reflectionProperty = new \ReflectionProperty($object, $reader);
                    if (!$reflectionProperty->isPublic()) {
                        continue;
                    }

                    $value = $reflectionProperty->getValue($object);
                } catch (\Throwable) {
                    continue;
                }

                return is_scalar($value) ? $value : null;
            }
        }

        return null;
    }

    private function fieldType(string $type): string
    {
        return match ($type) {
            'checkbox' => 'boolean',
            'choice', 'enum' => 'select',
            'textarea' => 'textarea',
            'date', 'datetime', 'datetime_immutable' => 'date',
            'integer', 'number', 'money' => 'number',
            default => 'text',
        };
    }

    private function operationFromTemplate(string $template): string
    {
        return match (true) {
            str_contains($template, '/new.') => 'new',
            str_contains($template, '/edit.') => 'edit',
            str_contains($template, '/show.') => 'show',
            default => 'index',
        };
    }

    private function humanize(string $value): string
    {
        $text = str_replace(['_', '-', '/'], ' ', $value);
        $text = preg_replace('/\s+/', ' ', $text) ?: $value;

        return ucwords(trim($text));
    }

    private function shortClass(object $object): string
    {
        $class = $object::class;
        $position = strrpos($class, '\\');

        return false === $position ? $class : substr($class, $position + 1);
    }
}
