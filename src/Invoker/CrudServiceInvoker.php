<?php

declare(strict_types=1);

namespace App\Cruding\Invoker;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\ServiceInterface\Entrypoint\CrudDeleteServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudGetServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudGroundedServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPatchServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPostServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPutServiceInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the service invoker responsibility within the Cruding component.
 */
final class CrudServiceInvoker
{
    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    public function invoke(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics = []): CrudServiceResultDTO
    {
        $grounding = $this->groundingDecision($entrypoint, $context, $resolutionDiagnostics);
        if ($grounding instanceof CrudServiceResultDTO) {
            return $grounding;
        }

        if (!$grounding) {
            return CrudServiceResultDTO::notGrounded([
                'entrypoint' => $entrypoint::class,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        $method = $context->httpMethod();
        $result = match ($method) {
            CrudServiceContextDTO::HTTP_GET => $this->callGet($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContextDTO::HTTP_POST => $this->callPost($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContextDTO::HTTP_PUT => $this->callPut($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContextDTO::HTTP_PATCH => $this->callPatch($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContextDTO::HTTP_DELETE => $this->callDelete($entrypoint, $context, $resolutionDiagnostics),
            default => null,
        };
        $dispatchedMethod = $method;

        if (null === $result && $this->isPublicCallable($entrypoint, '__invoke')) {
            $result = $this->callLegacyInvokable($entrypoint, $context, $resolutionDiagnostics);
            $dispatchedMethod = '__invoke';
        }

        return $this->normalizeResult($result, $entrypoint, $dispatchedMethod, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function groundingDecision(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): bool|CrudServiceResultDTO
    {
        if (!$entrypoint instanceof CrudGroundedServiceInterface && !$this->isPublicCallable($entrypoint, 'isGrounded')) {
            return true;
        }

        try {
            if ($entrypoint instanceof CrudGroundedServiceInterface) {
                return $entrypoint->isGrounded($context);
            }

            $method = new \ReflectionMethod($entrypoint, 'isGrounded');

            return (bool) $method->invoke($entrypoint, $context);
        } catch (\Throwable $exception) {
            return CrudServiceResultDTO::continueDefault(CrudServiceResultDTO::STATUS_ENTRYPOINT_GROUNDING_FAILED, [
                'entrypoint' => $entrypoint::class,
                'method' => 'isGrounded',
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'resolution' => $resolutionDiagnostics,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callGet(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudGetServiceInterface && !$this->isPublicCallable($entrypoint, 'get')) {
            return null;
        }

        return $this->callHook($entrypoint, 'get', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPost(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPostServiceInterface && !$this->isPublicCallable($entrypoint, 'post')) {
            return null;
        }

        return $this->callHook($entrypoint, 'post', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPut(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPutServiceInterface && !$this->isPublicCallable($entrypoint, 'put')) {
            return null;
        }

        return $this->callHook($entrypoint, 'put', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPatch(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPatchServiceInterface && !$this->isPublicCallable($entrypoint, 'patch')) {
            return null;
        }

        return $this->callHook($entrypoint, 'patch', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callDelete(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudDeleteServiceInterface && !$this->isPublicCallable($entrypoint, 'delete')) {
            return null;
        }

        return $this->callHook($entrypoint, 'delete', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callHook(object $entrypoint, string $method, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        try {
            return $entrypoint->{$method}($context);
        } catch (\Throwable $exception) {
            return CrudServiceResultDTO::continueDefault(CrudServiceResultDTO::STATUS_ENTRYPOINT_HOOK_FAILED, [
                'entrypoint' => $entrypoint::class,
                'method' => $method,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'resolution' => $resolutionDiagnostics,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callLegacyInvokable(object $entrypoint, CrudServiceContextDTO $context, array $resolutionDiagnostics): mixed
    {
        if (!is_callable($entrypoint)) {
            return null;
        }

        try {
            return $entrypoint($context->request);
        } catch (\Throwable $exception) {
            return CrudServiceResultDTO::continueDefault(CrudServiceResultDTO::STATUS_LEGACY_INVOKABLE_FAILED, [
                'entrypoint' => $entrypoint::class,
                'method' => '__invoke',
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'resolution' => $resolutionDiagnostics,
            ]);
        }
    }

    private function isPublicCallable(object $entrypoint, string $method): bool
    {
        return is_callable([$entrypoint, $method]);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function normalizeResult(mixed $result, object $entrypoint, string $method, array $resolutionDiagnostics): CrudServiceResultDTO
    {
        if ($result instanceof CrudServiceResultDTO) {
            return $result;
        }

        if ($result instanceof Response) {
            return CrudServiceResultDTO::response($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if ($result instanceof CrudResourceContract) {
            return CrudServiceResultDTO::viewContract($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if (null === $result) {
            return CrudServiceResultDTO::continueDefault(CrudServiceResultDTO::STATUS_NO_ENTRYPOINT_OVERRIDE, [
                'entrypoint' => $entrypoint::class,
                'method' => $method,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        return CrudServiceResultDTO::continueDefault(CrudServiceResultDTO::STATUS_INVALID_ENTRYPOINT_RESULT_IGNORED, [
            'entrypoint' => $entrypoint::class,
            'method' => $method,
            'resultType' => get_debug_type($result),
            'resolution' => $resolutionDiagnostics,
        ]);
    }
}
