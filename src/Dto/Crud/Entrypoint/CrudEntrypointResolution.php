<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud\Entrypoint;

final readonly class CrudEntrypointResolution
{
    public const STATUS_REGISTERED_SERVICE = 'registered_service';
    public const STATUS_URI_DERIVED_SERVICE = 'uri_derived_service';
    public const STATUS_CLASS_EXISTS_BUT_NOT_REGISTERED = 'class_exists_but_not_registered';
    public const STATUS_MISSING = 'missing';

    /**
     * @param list<string>        $candidateServiceIds
     * @param list<string>        $candidateClassNames
     * @param array<string, bool> $classExists
     * @param array<string, bool> $containerHas
     */
    public function __construct(
        public object $service,
        public string $status,
        public ?string $serviceId,
        public array $candidateServiceIds = [],
        public array $candidateClassNames = [],
        public array $classExists = [],
        public array $containerHas = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostics(): array
    {
        return [
            'status' => $this->status,
            'serviceId' => $this->serviceId,
            'candidateServiceIds' => $this->candidateServiceIds,
            'candidateClassNames' => $this->candidateClassNames,
            'classExists' => $this->classExists,
            'containerHas' => $this->containerHas,
        ];
    }
}
