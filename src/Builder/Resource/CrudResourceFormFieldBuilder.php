<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Resource;

use App\Cruding\Service\Resource\CrudResourceLabelFormatter;
use Symfony\Component\Form\FormView;

/**
 * Builds resource form field builder values used by Cruding workflows.
 */
final class CrudResourceFormFieldBuilder
{
    public function __construct(private readonly CrudResourceLabelFormatter $labelFormatter)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(?FormView $form, string $resourcePath): array
    {
        if (null === $form) {
            return [
                ['nameEntity' => 'title', 'label' => $this->labelFormatter->humanize($resourcePath), 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
                ['nameEntity' => 'status', 'label' => 'Status', 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
            ];
        }

        $fields = [];
        foreach ($form->children as $nameEntity => $child) {
            if (str_starts_with((string) $nameEntity, '_')) {
                continue;
            }

            $vars = $child->vars;
            $blockPrefixes = is_array($vars['block_prefixes'] ?? null) ? $vars['block_prefixes'] : [];
            $fieldType = isset($blockPrefixes[1]) && is_string($blockPrefixes[1]) ? $blockPrefixes[1] : 'text';
            $attr = is_array($vars['attr'] ?? null) ? $vars['attr'] : [];
            $fields[] = [
                'nameEntity' => (string) $nameEntity,
                'label' => is_string($vars['label'] ?? null) && '' !== $vars['label'] ? $vars['label'] : $this->labelFormatter->humanize((string) $nameEntity),
                'type' => $this->fieldType($fieldType),
                'required' => (bool) ($vars['required'] ?? false),
                'value' => is_scalar($vars['value'] ?? null) ? (string) $vars['value'] : null,
                'placeholder' => is_scalar($attr['placeholder'] ?? null) ? (string) $attr['placeholder'] : null,
                'helpText' => is_string($vars['help'] ?? null) ? $vars['help'] : null,
                'validationState' => null,
                'errorText' => null,
                'options' => is_array($vars['choices'] ?? null) ? $vars['choices'] : [],
            ];
        }

        return [] !== $fields ? $fields : [
            ['nameEntity' => 'title', 'label' => $this->labelFormatter->humanize($resourcePath), 'type' => 'text', 'required' => false, 'value' => null, 'placeholder' => null, 'helpText' => null, 'validationState' => null, 'errorText' => null, 'options' => []],
        ];
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
}
