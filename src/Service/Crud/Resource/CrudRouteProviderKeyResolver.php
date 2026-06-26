<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

/**
 * Builds provider lookup keys from the normalized route grammar.
 */
final readonly class CrudRouteProviderKeyResolver
{
    /**
     * @return list<string>
     */
    public function providerKeys(string $resource, ?string $viewPath, ?string $ViewToken, string $operation, ?string $subjectField = null, string|int|null $subjectValue = null): array
    {
        $keys = [];
        $subjectPath = $this->subjectPath($subjectField, $subjectValue);
        if (null !== $viewPath && '' !== $viewPath) {
            if (null !== $subjectPath && '' !== $subjectPath) {
                if (null !== $ViewToken && '' !== $ViewToken) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$viewPath.'.'.$ViewToken.'.'.$operation);
                    if ('detail' === $operation) {
                        $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$viewPath.'.'.$ViewToken.'.show');
                        $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$viewPath.'.'.$ViewToken.'.view');
                    }
                }

                $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$viewPath.'.'.$operation);
                if ('index' === $operation) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$viewPath);
                }
            }

            if (null !== $ViewToken && '' !== $ViewToken) {
                $keys[] = str_replace('/', '.', $resource.'.'.$viewPath.'.'.$ViewToken.'.'.$operation);
                if ('detail' === $operation) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$viewPath.'.'.$ViewToken.'.show');
                    $keys[] = str_replace('/', '.', $resource.'.'.$viewPath.'.'.$ViewToken.'.view');
                }
            }

            $keys[] = str_replace('/', '.', $resource.'.'.$viewPath.'.'.$operation);
            if ('index' === $operation) {
                $keys[] = str_replace('/', '.', $resource.'.'.$viewPath);
            }
        }

        $keys[] = $resource.'.'.$operation;
        if ('detail' === $operation) {
            $keys[] = $resource.'.show';
            $keys[] = $resource.'.view';
        }

        return array_values(array_unique($keys));
    }

    private function subjectPath(?string $subjectField, string|int|null $subjectValue): ?string
    {
        if ('subject' !== $subjectField) {
            return null;
        }

        if (null === $subjectValue || '' === $subjectValue) {
            return null;
        }

        return (string) $subjectValue;
    }
}
