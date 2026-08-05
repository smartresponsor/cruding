<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final class CrudResourceRowBuilder
{
    public function __construct(
        private readonly CrudResourceScalarReader $scalarReader,
        private readonly CrudResourceLabelFormatter $labelFormatter,
    ) {
    }

    /**
     * @param list<object> $objects
     *
     * @return list<array<string, mixed>>
     */
    public function build(array $objects, string $resourcePath, string $component): array
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
            $rows[] = $this->buildObjectRow($object, $resourcePath, $component, $index);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildObjectRow(object $object, string $resourcePath, string $component, int $index): array
    {
        $id = $this->scalarReader->read($object, ['getId', 'id']) ?? $this->scalarReader->read($object, ['getSlug', 'slug']) ?? $resourcePath.'-'.($index + 1);
        $title = $this->scalarReader->read($object, ['getTitle', 'title', 'getName', 'nameEntity', 'getLabel', 'label', 'getCode', 'code']) ?? $this->labelFormatter->shortClass($object);
        $status = $this->scalarReader->read($object, ['getStatus', 'status', 'isEnabled', 'enabled', 'isActive', 'active']) ?? 'loaded';
        $code = $this->scalarReader->read($object, ['getCode', 'code', 'getSku', 'sku', 'getSlug', 'slug']) ?? strtoupper(str_replace('/', '-', $resourcePath)).'-'.($index + 1);
        $locale = $this->scalarReader->read($object, ['getLocale', 'locale', 'getContentLocale', 'contentLocale']) ?? 'en';
        $displayName = $this->scalarReader->read($object, ['getDisplayName', 'displayName']);
        $about = $this->scalarReader->read($object, ['getAbout', 'about']);
        $website = $this->scalarReader->read($object, ['getWebsite', 'website']);
        $avatarPath = $this->scalarReader->read($object, ['getAvatarPath', 'avatarPath']);
        $coverPath = $this->scalarReader->read($object, ['getCoverPath', 'coverPath']);

        return array_filter([
            'id' => $id,
            'title' => $title,
            'displayName' => $displayName,
            'about' => $about,
            'website' => $website,
            'avatarPath' => $avatarPath,
            'coverPath' => $coverPath,
            'code' => $code,
            'owner' => $component,
            'status' => is_bool($status) ? ($status ? 'active' : 'inactive') : $status,
            'locale' => $locale,
        ], static fn (mixed $value): bool => null !== $value && '' !== $value);
    }
}
