<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

final class CrudFormTypeResolver
{
    public function resolve(string $entityClass): ?string
    {
        $explicit = $this->explicitTypes();
        if (isset($explicit[$entityClass])) {
            return $explicit[$entityClass];
        }

        $entityParts = explode('\\Entity\\', $entityClass, 2);
        if (!isset($entityParts[1])) {
            return null;
        }

        $namespaceRoot = $entityParts[0];
        $entityTail = $entityParts[1];
        $segments = explode('\\', $entityTail);
        $shortName = (string) end($segments);
        $parentSegments = $segments;
        array_pop($parentSegments);

        $candidates = [
            sprintf('%s\\Form\\%sType', $namespaceRoot, $shortName),
        ];

        if ([] !== $parentSegments) {
            $candidates[] = sprintf('%s\\Form\\%s\\%sType', $namespaceRoot, implode('\\', $parentSegments), $shortName);
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<class-string, class-string>
     */
    private function explicitTypes(): array
    {
        return array_filter([
            \App\Cataloging\Entity\Catalog\CatalogCategoryEntity::class => class_exists(\App\Cataloging\Form\CategoryAdminCategoryType::class) ? \App\Cataloging\Form\CategoryAdminCategoryType::class : null,
        ]);
    }
}
