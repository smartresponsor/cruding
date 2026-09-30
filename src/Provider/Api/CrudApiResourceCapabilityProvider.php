<?php

declare(strict_types=1);

namespace App\Cruding\Provider\Api;

use App\Cruding\DTO\Api\CrudApiFieldCapabilityDTO;
use App\Cruding\DTO\Api\CrudApiResourceCapabilityDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\CrudCapabilityResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Derives external CRUD operation and field capabilities from resolved context and form metadata.
 */
final readonly class CrudApiResourceCapabilityProvider
{
    public function __construct(
        private CrudCapabilityResolverInterface $capabilityResolver,
        private FormFactoryInterface $formFactory,
    ) {
    }

    /**
     * Builds the public capability DTO for a resolved CRUD resource context.
     */
    public function provide(CrudContextDTO $context): CrudApiResourceCapabilityDTO
    {
        $capabilities = $this->capabilityResolver->resolve($context);
        $supportsId = true === $capabilities['supportsId'];
        $supportsSlug = true === $capabilities['supportsSlug'];
        $supportsIdentity = $supportsId || $supportsSlug;
        $hasForm = null !== $context->formTypeClass;

        return new CrudApiResourceCapabilityDTO(
            resource: $context->resourcePath,
            identity: [
                'supportsId' => $supportsId,
                'supportsSlug' => $supportsSlug,
            ],
            operations: [
                'index' => true,
                'show' => $supportsIdentity,
                'create' => $hasForm,
                'update' => $hasForm && $supportsIdentity,
                'delete' => $supportsIdentity,
            ],
            fields: $this->fields($context->formTypeClass),
        );
    }

    /** @return list<CrudApiFieldCapabilityDTO> */
    private function fields(?string $formTypeClass): array
    {
        if (null === $formTypeClass) {
            return [];
        }

        $builder = $this->formFactory->createNamedBuilder('', $formTypeClass);

        $fields = [];
        foreach ($builder->all() as $child) {
            $fields[] = $this->field($child);
        }

        return $fields;
    }

    private function field(FormBuilderInterface $field): CrudApiFieldCapabilityDTO
    {
        $options = $field->getOptions();
        $attr = is_array($options['attr'] ?? null) ? $options['attr'] : [];
        $constraints = $this->constraints($options['constraints'] ?? []);
        $required = true === ($options['required'] ?? false);

        $minLength = $this->integerHint($attr['minlength'] ?? null);
        $maxLength = $this->integerHint($attr['maxlength'] ?? null);
        $min = $this->numberHint($attr['min'] ?? null);
        $max = $this->numberHint($attr['max'] ?? null);

        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotBlank || $constraint instanceof NotNull) {
                $required = true;
            }
            if ($constraint instanceof Length) {
                $minLength = $constraint->min ?? $minLength;
                $maxLength = $constraint->max ?? $maxLength;
            }
            if ($constraint instanceof Range) {
                $min = $this->numberHint($constraint->min) ?? $min;
                $max = $this->numberHint($constraint->max) ?? $max;
            }
        }

        return new CrudApiFieldCapabilityDTO(
            name: $field->getName(),
            required: $required,
            readOnly: true === ($options['disabled'] ?? false) || true === ($attr['readonly'] ?? false),
            multiple: true === ($options['multiple'] ?? false),
            choices: $this->choices($options),
            minLength: $minLength,
            maxLength: $maxLength,
            min: $min,
            max: $max,
        );
    }

    /** @return list<Constraint> */
    private function constraints(mixed $constraints): array
    {
        if ($constraints instanceof Constraint) {
            return [$constraints];
        }

        if (!is_array($constraints)) {
            return [];
        }

        return array_values(array_filter(
            $constraints,
            static fn (mixed $constraint): bool => $constraint instanceof Constraint,
        ));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array{label: string, value: string|int|float|bool|null}>
     */
    private function choices(array $options): array
    {
        $sourceChoices = $options['choices'] ?? null;
        if (!is_array($sourceChoices)) {
            return [];
        }

        $choiceValue = is_callable($options['choice_value'] ?? null) ? $options['choice_value'] : null;

        return $this->flattenChoices($sourceChoices, $choiceValue);
    }

    /**
     * @param array<array-key, mixed>     $sourceChoices
     * @param callable(mixed): mixed|null $choiceValue
     *
     * @return list<array{label: string, value: string|int|float|bool|null}>
     */
    private function flattenChoices(array $sourceChoices, ?callable $choiceValue, string $prefix = ''): array
    {
        $choices = [];
        foreach ($sourceChoices as $label => $value) {
            $labelText = is_string($label) ? trim($label) : '';
            if (is_array($value)) {
                $groupPrefix = '' === $labelText ? $prefix : ('' === $prefix ? $labelText : $prefix.' › '.$labelText);
                $choices = array_merge($choices, $this->flattenChoices($value, $choiceValue, $groupPrefix));
                continue;
            }

            try {
                $serializedValue = null !== $choiceValue
                    ? $choiceValue($value)
                    : ($value instanceof \BackedEnum ? $value->value : $value);
            } catch (\Throwable) {
                continue;
            }

            if (!is_scalar($serializedValue) && null !== $serializedValue) {
                continue;
            }

            $leafLabel = '' !== $labelText
                ? $labelText
                : ($value instanceof \BackedEnum ? $value->name : (string) $serializedValue);
            $choices[] = [
                'label' => '' === $prefix ? $leafLabel : $prefix.' › '.$leafLabel,
                'value' => $serializedValue,
            ];
        }

        return $choices;
    }

    private function integerHint(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        return null;
    }

    private function numberHint(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return null;
    }
}
