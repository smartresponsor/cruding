<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

use App\Cruding\Dto\Surface\CrudResolvedRouteShape;

/**
 * Applies Cruding's resource/surface/action grammar to positional route segment values.
 */
final readonly class CrudRouteGrammarResolver
{
    public function __construct(
        private CrudRouteValueNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<array{value: string|int|null, dynamic: bool, name: ?string}> $values
     */
    public function resolve(array $values): ?CrudResolvedRouteShape
    {
        if ([] === $values) {
            return null;
        }

        $resource = $this->normalizer->token((string) ($values[0]['value'] ?? ''));
        if ('' === $resource) {
            return null;
        }

        $subjectField = null;
        $subjectValue = null;
        $surfacePath = null;
        $surfaceToken = null;
        $itemField = null;
        $itemValue = null;
        $operation = 'index';

        $count = count($values);
        if (1 === $count) {
            $operation = 'index';
        } elseif (2 === $count) {
            if ($values[1]['dynamic']) {
                $subjectField = $this->normalizer->placeholderField($values[1]['nameEntity']);
                $subjectValue = $this->normalizer->scalarValue($values[1]['value']);
                $operation = 'detail';
            } else {
                $operation = $this->normalizer->token((string) $values[1]['value']);
            }
        } else {
            if ($values[1]['dynamic']) {
                $subjectField = $this->normalizer->placeholderField($values[1]['nameEntity']);
                $subjectValue = $this->normalizer->scalarValue($values[1]['value']);
            }

            $surfacePath = $this->normalizer->token((string) ($values[2]['value'] ?? '')) ?: null;

            if (3 === $count) {
                $operation = null === $surfacePath ? 'detail' : 'index';
            } elseif (4 === $count) {
                if ($values[3]['dynamic'] && 'action' !== $values[3]['nameEntity']) {
                    $itemField = $this->normalizer->placeholderField($values[3]['nameEntity']);
                    $itemValue = $this->normalizer->scalarValue($values[3]['value']);
                    $operation = 'detail';
                } else {
                    $operation = $this->normalizer->token((string) $values[3]['value']);
                }
            } elseif ($this->isSurfaceTokenSegment($values[3])) {
                $surfaceToken = $this->normalizer->token((string) $values[3]['value']) ?: null;
                if ($values[4]['dynamic']) {
                    $itemField = $this->normalizer->placeholderField($values[4]['nameEntity']);
                    $itemValue = $this->normalizer->scalarValue($values[4]['value']);
                }

                $operation = 5 === $count
                    ? 'detail'
                    : $this->normalizer->token((string) $values[$count - 1]['value']);
            } else {
                if ($values[3]['dynamic']) {
                    $itemField = $this->normalizer->placeholderField($values[3]['nameEntity']);
                    $itemValue = $this->normalizer->scalarValue($values[3]['value']);
                } elseif (null !== $surfacePath) {
                    $surfacePath .= '/'.$this->normalizer->token((string) $values[3]['value']);
                }

                $operation = $values[$count - 1]['dynamic'] && 'action' !== $values[$count - 1]['nameEntity']
                    ? 'detail'
                    : $this->normalizer->token((string) $values[$count - 1]['value']);
            }
        }

        $operation = '' !== $operation ? $operation : 'index';

        return new CrudResolvedRouteShape(
            resource: $resource,
            operation: $operation,
            surfacePath: $surfacePath,
            surfaceToken: $surfaceToken,
            subjectField: $subjectField,
            subjectValue: $subjectValue,
            itemField: $itemField,
            itemValue: $itemValue,
        );
    }

    /**
     * @param array{value: string|int|null, dynamic: bool, name: ?string} $segment
     */
    private function isSurfaceTokenSegment(array $segment): bool
    {
        if (!$segment['dynamic']) {
            return true;
        }

        return in_array($segment['nameEntity'], ['token', 'surfaceToken', 'widgetToken'], true);
    }
}
