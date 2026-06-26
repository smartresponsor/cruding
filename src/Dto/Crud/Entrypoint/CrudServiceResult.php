<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud\Entrypoint;

use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudServiceResult
{
    public const STATUS_CONTINUE_DEFAULT = 'continue_default';
    public const STATUS_RESPONSE = 'response';
    public const STATUS_VIEW_CONTRACT = 'view_contract';
    public const STATUS_DEFAULT_BEHAVIOR = 'default_behavior';
    public const STATUS_DEFAULT_BEHAVIOR_UNAVAILABLE = 'default_behavior_unavailable';
    public const STATUS_NOT_GROUNDED = 'not_grounded';
    public const STATUS_ENTRYPOINT_GROUNDING_FAILED = 'entrypoint_grounding_failed';
    public const STATUS_ENTRYPOINT_HOOK_FAILED = 'entrypoint_hook_failed';
    public const STATUS_NO_ENTRYPOINT_OVERRIDE = 'no_entrypoint_override';
    public const STATUS_INVALID_ENTRYPOINT_RESULT_IGNORED = 'invalid_entrypoint_result_ignored';
    public const STATUS_LEGACY_INVOKABLE_FAILED = 'legacy_invokable_failed';

    /** @param array<string, mixed> $diagnostics */
    private function __construct(
        private ?Response $response,
        private ?CrudResourceContract $viewContract,
        private bool $continueDefault,
        public string $status,
        public array $diagnostics = [],
    ) {
    }

    public static function continueDefault(string $status = self::STATUS_CONTINUE_DEFAULT, array $diagnostics = []): self
    {
        return new self(null, null, true, $status, $diagnostics);
    }

    public static function response(Response $response, string $status = self::STATUS_RESPONSE, array $diagnostics = []): self
    {
        return new self($response, null, false, $status, $diagnostics);
    }

    public static function viewContract(CrudResourceContract $contract, string $status = self::STATUS_VIEW_CONTRACT, array $diagnostics = []): self
    {
        return new self(null, $contract, false, $status, $diagnostics);
    }

    public static function notGrounded(array $diagnostics = []): self
    {
        return new self(null, null, true, self::STATUS_NOT_GROUNDED, $diagnostics);
    }

    /** @param array<string, mixed> $diagnostics */
    public function withDiagnostics(array $diagnostics): self
    {
        if ([] === $diagnostics) {
            return $this;
        }

        return new self(
            $this->response,
            $this->viewContract,
            $this->continueDefault,
            $this->status,
            array_replace_recursive($this->diagnostics, $diagnostics),
        );
    }

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }

    public function shouldContinueDefault(): bool
    {
        return $this->continueDefault;
    }

    public function hasPayload(): bool
    {
        return null !== $this->response || null !== $this->viewContract;
    }

    public function payload(): Response|CrudResourceContract|null
    {
        return $this->response ?? $this->viewContract;
    }
}
