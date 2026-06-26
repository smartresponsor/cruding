<?php

declare(strict_types=1);

namespace App\Cruding\Value\Resource;

final readonly class CrudResourceContract
{
    public const WORD = 'crud';

    /**
     * @param array<string, string> $slotMap
     * @param array<string, mixed>  $workbench
     * @param array<string, mixed>  $slots
     * @param array<string, mixed>  $locations
     */
    public function __construct(
        public string $word,
        public string $view,
        public array $slotMap,
        public array $workbench,
        public array $slots,
        public array $locations = [],
    ) {
    }

    /**
     * @param array<string, mixed> $routeContext
     * @param array<string, mixed> $locations
     * @param array<string, mixed> $meta
     */
    public static function forResource(
        string $view,
        array $routeContext,
        array $locations,
        array $meta = [],
    ): self {
        return new self(
            self::WORD,
            $view,
            self::defaultSlotMap(),
            [
                'title' => is_scalar($meta['title'] ?? null) ? (string) $meta['title'] : 'Cruding resource',
                'component' => 'cruding',
                'sourceComponent' => 'cruding',
                'renderingOwner' => 'interfacing',
                'routeContext' => $routeContext,
                'locations' => $locations,
                'meta' => $meta,
            ],
            [
                'locations' => $locations,
                'meta' => $meta,
                'defaultView' => $view,
                'viewModes' => [$view],
            ],
            $locations,
        );
    }

    /**
     * @return array<string, string>
     */
    public static function defaultSlotMap(): array
    {
        return [
            'top' => 'Top resource area',
            'left' => 'Left resource area',
            'body' => 'Body resource area',
            'right' => 'Right resource area',
            'bottom' => 'Bottom resource area',
            'stickyTop' => 'Sticky top resource area',
            'stickyBottom' => 'Sticky bottom resource area',
            'tool' => 'Tool resource area',
            'filter' => 'Filter resource area',
            'menu' => 'Menu resource area',
            'diagnostic' => 'Diagnostic resource area',
            'top.search' => 'Search',
            'left.panel' => 'Resource operations',
            'main.body' => 'Resource workbench',
            'right.panel' => 'Actions',
        ];
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     slotMap: array<string, string>,
     *     workbench: array<string, mixed>,
     *     slots: array<string, mixed>,
     *     locations: array<string, mixed>,
     *     adminProviderPageTitle: string,
     *     adminProviderResourceName: string,
     *     adminProviderResourceLabel: string,
     *     adminProviderOperation: string,
     *     adminProviderview: string,
     *     adminProviderDefaultView: string,
     *     adminProviderViewModes: list<string>
     * }
     */
    public function toTemplateContext(): array
    {
        $routeContext = is_array($this->workbench['routeContext'] ?? null) ? $this->workbench['routeContext'] : [];
        $meta = is_array($this->workbench['meta'] ?? null) ? $this->workbench['meta'] : [];

        return [
            'word' => $this->word,
            'view' => $this->view,
            'slotMap' => $this->slotMap,
            'workbench' => $this->workbench,
            'slots' => $this->slots,
            'locations' => $this->resolvedLocations(),
            'meta' => $meta,
            'format' => is_scalar($meta['format'] ?? null) ? (string) $meta['format'] : 'auto',
            'adminProviderPageTitle' => is_scalar($this->workbench['title'] ?? null) ? (string) $this->workbench['title'] : 'Cruding',
            'adminProviderResourceName' => is_scalar($routeContext['resourcePath'] ?? null) ? (string) $routeContext['resourcePath'] : 'resource',
            'adminProviderResourceLabel' => is_scalar($routeContext['resourceLabel'] ?? null) ? (string) $routeContext['resourceLabel'] : (is_scalar($this->workbench['title'] ?? null) ? (string) $this->workbench['title'] : 'Cruding'),
            'adminProviderOperation' => is_scalar($routeContext['operation'] ?? null) ? (string) $routeContext['operation'] : $this->view,
            'adminProviderview' => is_scalar($routeContext['view'] ?? null) ? (string) $routeContext['view'] : 'admin',
            'adminProviderDefaultView' => is_scalar($this->slots['defaultView'] ?? null) ? (string) $this->slots['defaultView'] : 'table',
            'adminProviderViewModes' => is_array($this->slots['viewModes'] ?? null) ? array_values(array_filter(array_map(
                static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
                $this->slots['viewModes']
            ), static fn (string $value): bool => '' !== $value)) : ['table', 'cards'],
        ] + $meta;
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     workbench: array<string, mixed>,
     *     slots: array<string, mixed>,
     *     locations: array<string, mixed>
     * }
     */
    public function toFallbackData(): array
    {
        $meta = is_array($this->workbench['meta'] ?? null) ? $this->workbench['meta'] : [];

        return [
            'word' => $this->word,
            'view' => $this->view,
            'workbench' => $this->workbench,
            'slots' => $this->slots,
            'locations' => $this->resolvedLocations(),
            'meta' => $meta,
            'format' => is_scalar($meta['format'] ?? null) ? (string) $meta['format'] : 'auto',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvedLocations(): array
    {
        if ([] !== $this->locations) {
            return $this->locations;
        }

        return is_array($this->slots['locations'] ?? null) ? $this->slots['locations'] : [];
    }
}
