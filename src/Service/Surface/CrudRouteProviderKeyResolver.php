<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

/**
 * Builds provider lookup keys from the normalized route grammar.
 */
final readonly class CrudRouteProviderKeyResolver
{
    /**
     * @return list<string>
     */
    public function providerKeys(string $resource, ?string $surfacePath, ?string $surfaceToken, string $operation, ?string $subjectField = null, string|int|null $subjectValue = null): array
    {
        $keys = [];
        $subjectPath = $this->subjectPath($subjectField, $subjectValue);
        if (null !== $surfacePath && '' !== $surfacePath) {
            if (null !== $subjectPath && '' !== $subjectPath) {
                if (null !== $surfaceToken && '' !== $surfaceToken) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$surfacePath.'.'.$surfaceToken.'.'.$operation);
                    if ('detail' === $operation) {
                        $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$surfacePath.'.'.$surfaceToken.'.show');
                        $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$surfacePath.'.'.$surfaceToken.'.view');
                    }
                }

                $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$surfacePath.'.'.$operation);
                if ('index' === $operation) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$subjectPath.'.'.$surfacePath);
                }
            }

            if (null !== $surfaceToken && '' !== $surfaceToken) {
                $keys[] = str_replace('/', '.', $resource.'.'.$surfacePath.'.'.$surfaceToken.'.'.$operation);
                if ('detail' === $operation) {
                    $keys[] = str_replace('/', '.', $resource.'.'.$surfacePath.'.'.$surfaceToken.'.show');
                    $keys[] = str_replace('/', '.', $resource.'.'.$surfacePath.'.'.$surfaceToken.'.view');
                }
            }

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
