<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\Dto\Surface\CrudRouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Parses route templates into the canonical resource/surface/action grammar.
 */
final readonly class CrudRouteShapeResolver
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function resolve(Request $request): ?CrudRouteContext
    {
        $routeName = $this->routeName($request);
        $routeTemplate = $this->routeTemplate($routeName);
        $segments = $this->segments($routeTemplate ?? $request->getPathInfo());

        if ([] === $segments) {
            return null;
        }

        $values = $this->segmentValues($segments, $request);
        if ([] === $values) {
            return null;
        }

        $resource = $this->token((string) ($values[0]['value'] ?? ''));
        if ('' === $resource) {
            return null;
        }

        $subjectField = null;
        $subjectValue = null;
        $surfacePath = null;
        $itemField = null;
        $itemValue = null;
        $operation = 'index';

        $count = count($values);
        if (1 === $count) {
            $operation = 'index';
        } elseif (2 === $count) {
            if ($values[1]['dynamic']) {
                $subjectField = $this->placeholderField($values[1]['name']);
                $subjectValue = $this->scalarValue($values[1]['value']);
                $operation = 'detail';
            } else {
                $operation = $this->token((string) $values[1]['value']);
            }
        } else {
            if ($values[1]['dynamic']) {
                $subjectField = $this->placeholderField($values[1]['name']);
                $subjectValue = $this->scalarValue($values[1]['value']);
            }

            $surfacePath = $this->token((string) ($values[2]['value'] ?? '')) ?: null;

            if (3 === $count) {
                $operation = null === $surfacePath ? 'detail' : 'index';
            } elseif (4 === $count) {
                if ($values[3]['dynamic'] && 'action' !== $values[3]['name']) {
                    $itemField = $this->placeholderField($values[3]['name']);
                    $itemValue = $this->scalarValue($values[3]['value']);
                    $operation = 'detail';
                } else {
                    $operation = $this->token((string) $values[3]['value']);
                }
            } else {
                if ($values[3]['dynamic']) {
                    $itemField = $this->placeholderField($values[3]['name']);
                    $itemValue = $this->scalarValue($values[3]['value']);
                } elseif (null !== $surfacePath) {
                    $surfacePath .= '/'.$this->token((string) $values[3]['value']);
                }

                $operation = $values[$count - 1]['dynamic'] && 'action' !== $values[$count - 1]['name']
                    ? 'detail'
                    : $this->token((string) $values[$count - 1]['value']);
            }
        }

        $operation = '' !== $operation ? $operation : 'index';
        $view = $this->viewFromOperation($operation);
        $resourcePath = $resource;
        $providerKeys = $this->providerKeys($resource, $surfacePath, $operation);
        $templateCandidates = $this->templateCandidates($resource, $surfacePath);

        return new CrudRouteContext(
            resource: $resource,
            resourcePath: $resourcePath,
            operation: $operation,
            view: $view,
            surfacePath: $surfacePath,
            subjectField: $subjectField,
            subjectValue: $subjectValue,
            itemField: $itemField,
            itemValue: $itemValue,
            routeName: $routeName,
            routeTemplate: $routeTemplate,
            routeParameters: $this->routeParameters($request),
            providerKeys: $providerKeys,
            templateCandidates: $templateCandidates,
        );
    }

    /**
     * @return list<array{value: string|int|null, dynamic: bool, name: ?string}>
     */
    private function segmentValues(array $segments, Request $request): array
    {
        $values = [];
        foreach ($segments as $segment) {
            $placeholder = $this->placeholderName($segment);
            if (null !== $placeholder) {
                $value = $request->attributes->get($placeholder);
                if (null === $value && in_array($placeholder, ['resource', 'subject', 'surface', 'action', 'item'], true)) {
                    $value = $request->attributes->get($placeholder);
                }

                $values[] = [
                    'value' => $this->scalarValue($value),
                    'dynamic' => true,
                    'name' => $placeholder,
                ];
                continue;
            }

            $values[] = [
                'value' => $segment,
                'dynamic' => false,
                'name' => null,
            ];
        }

        return array_values(array_filter(
            $values,
            static fn (array $value): bool => null !== $value['value'] && '' !== (string) $value['value'],
        ));
    }

    /**
     * @return list<string>
     */
    private function segments(string $path): array
    {
        $path = trim($path, '/');
        if ('' === $path) {
            return [];
        }

        return array_values(array_filter(explode('/', $path), static fn (string $segment): bool => '' !== $segment));
    }

    private function routeName(Request $request): ?string
    {
        $routeName = $request->attributes->get('_route');

        return is_string($routeName) && '' !== $routeName ? $routeName : null;
    }

    private function routeTemplate(?string $routeName): ?string
    {
        if (null === $routeName || !method_exists($this->router, 'getRouteCollection')) {
            return null;
        }

        $routeCollection = $this->router->getRouteCollection();
        $route = $routeCollection->get($routeName);
        if (null === $route) {
            return null;
        }

        return $route->getPath();
    }

    private function placeholderName(string $segment): ?string
    {
        if (1 !== preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function placeholderField(?string $name): ?string
    {
        if (null === $name || '' === $name) {
            return null;
        }

        return $name;
    }

    private function scalarValue(mixed $value): string|int|null
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && '' !== $value) {
            return ctype_digit($value) ? (int) $value : $value;
        }

        return null;
    }

    private function token(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?: $value;
        $value = strtolower(str_replace('_', '-', $value));
        $value = preg_replace('/[^a-z0-9\/-]+/', '-', $value) ?: $value;
        $value = trim(preg_replace('/-+/', '-', $value) ?: $value, '-/');

        return $value;
    }

    private function viewFromOperation(string $operation): string
    {
        return match ($operation) {
            'show' => 'detail',
            'new', 'edit' => 'form',
            default => $operation,
        };
    }

    /**
     * @return list<string>
     */
    private function providerKeys(string $resource, ?string $surfacePath, string $operation): array
    {
        $keys = [];
        if (null !== $surfacePath && '' !== $surfacePath) {
            $keys[] = str_replace('/', '.', $resource.'.'.$surfacePath.'.'.$operation);
            if ('index' === $operation) {
                $keys[] = str_replace('/', '.', $resource.'.'.$surfacePath);
            }
        }

        $keys[] = $resource.'.'.$operation;
        if ('detail' === $operation) {
            $keys[] = $resource.'.show';
            $keys[] = $resource.'.view';
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    private function templateCandidates(string $resource, ?string $surfacePath): array
    {
        $base = null !== $surfacePath && '' !== $surfacePath ? $resource.'/'.$surfacePath : $resource;

        return array_values(array_unique([
            $base.'/index.html.twig',
            $resource.'/index.html.twig',
            'index.html.twig',
        ]));
    }

    /**
     * @return array<string, string|int|null>
     */
    private function routeParameters(Request $request): array
    {
        $parameters = [];
        foreach ($request->attributes->all() as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            if (is_scalar($value) || null === $value) {
                $parameters[(string) $key] = is_bool($value) ? (int) $value : $value;
            }
        }

        return $parameters;
    }
}
