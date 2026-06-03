<?php

declare(strict_types=1);

namespace App\Cruding\Value\Surface;

final readonly class CrudSurfaceContract
{
    public const WORD = 'crud';

    /**
     * @param array<string, string> $slotMap
     * @param array<string, mixed>  $workbench
     * @param array<string, mixed>  $slots
     */
    public function __construct(
        public string $word,
        public string $view,
        public array $slotMap,
        public array $workbench,
        public array $slots,
    ) {
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     slotMap: array<string, string>,
     *     workbench: array<string, mixed>,
     *     slots: array<string, mixed>,
     *     adminProviderPageTitle: string,
     *     adminProviderResourceName: string,
     *     adminProviderResourceLabel: string,
     *     adminProviderOperation: string,
     *     adminProviderSurface: string,
     *     adminProviderDefaultView: string,
     *     adminProviderViewModes: list<string>
     * }
     */
    public function toTemplateContext(): array
    {
        $routeContext = is_array($this->workbench['routeContext'] ?? null) ? $this->workbench['routeContext'] : [];

        return [
            'word' => $this->word,
            'view' => $this->view,
            'slotMap' => $this->slotMap,
            'workbench' => $this->workbench,
            'slots' => $this->slots,
            'adminProviderPageTitle' => is_scalar($this->workbench['title'] ?? null) ? (string) $this->workbench['title'] : 'Cruding',
            'adminProviderResourceName' => is_scalar($routeContext['resourcePath'] ?? null) ? (string) $routeContext['resourcePath'] : 'resource',
            'adminProviderResourceLabel' => is_scalar($routeContext['resourceLabel'] ?? null) ? (string) $routeContext['resourceLabel'] : (is_scalar($this->workbench['title'] ?? null) ? (string) $this->workbench['title'] : 'Cruding'),
            'adminProviderOperation' => is_scalar($routeContext['operation'] ?? null) ? (string) $routeContext['operation'] : $this->view,
            'adminProviderSurface' => is_scalar($routeContext['surface'] ?? null) ? (string) $routeContext['surface'] : 'admin',
            'adminProviderDefaultView' => is_scalar($this->slots['defaultView'] ?? null) ? (string) $this->slots['defaultView'] : 'table',
            'adminProviderViewModes' => is_array($this->slots['viewModes'] ?? null) ? array_values(array_filter(array_map(
                static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
                $this->slots['viewModes']
            ), static fn (string $value): bool => '' !== $value)) : ['table', 'cards'],
        ];
    }

    /**
     * @return array{
     *     word: string,
     *     view: string,
     *     workbench: array<string, mixed>,
     *     slots: array<string, mixed>
     * }
     */
    public function toFallbackData(): array
    {
        return [
            'word' => $this->word,
            'view' => $this->view,
            'workbench' => $this->workbench,
            'slots' => $this->slots,
        ];
    }
}
