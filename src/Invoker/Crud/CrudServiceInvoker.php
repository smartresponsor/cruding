<?php

declare(strict_types=1);

namespace App\Cruding\Invoker\Crud;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudDeleteServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGetServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGroundedServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPatchServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPostServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPutServiceInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

final class CrudServiceInvoker
{
    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    public function invoke(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics = []): CrudServiceResult
    {
        $grounding = $this->groundingDecision($entrypoint, $context, $resolutionDiagnostics);
        if ($grounding instanceof CrudServiceResult) {
            return $grounding;
        }

        if (!$grounding) {
            return CrudServiceResult::notGrounded([
                'entrypoint' => $entrypoint::class,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        $method = $context->httpMethod();
        $result = match ($method) {
            CrudServiceContext::HTTP_GET => $this->callGet($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContext::HTTP_POST => $this->callPost($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContext::HTTP_PUT => $this->callPut($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContext::HTTP_PATCH => $this->callPatch($entrypoint, $context, $resolutionDiagnostics),
            CrudServiceContext::HTTP_DELETE => $this->callDelete($entrypoint, $context, $resolutionDiagnostics),
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
    private function groundingDecision(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): bool|CrudServiceResult
    {
        if (!$entrypoint instanceof CrudGroundedServiceInterface && !$this->isPublicCallable($entrypoint, 'isGrounded')) {
            return true;
        }

        try {
            return (bool) $entrypoint->isGrounded($context);
        } catch (\Throwable $exception) {
            return CrudServiceResult::continueDefault(CrudServiceResult::STATUS_ENTRYPOINT_GROUNDING_FAILED, [
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
    private function callGet(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudGetServiceInterface && !$this->isPublicCallable($entrypoint, 'get')) {
            return null;
        }

        return $this->callHook($entrypoint, 'get', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPost(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPostServiceInterface && !$this->isPublicCallable($entrypoint, 'post')) {
            return null;
        }

        return $this->callHook($entrypoint, 'post', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPut(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPutServiceInterface && !$this->isPublicCallable($entrypoint, 'put')) {
            return null;
        }

        return $this->callHook($entrypoint, 'put', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPatch(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPatchServiceInterface && !$this->isPublicCallable($entrypoint, 'patch')) {
            return null;
        }

        return $this->callHook($entrypoint, 'patch', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callDelete(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudDeleteServiceInterface && !$this->isPublicCallable($entrypoint, 'delete')) {
            return null;
        }

        return $this->callHook($entrypoint, 'delete', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callHook(object $entrypoint, string $method, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        try {
            return $entrypoint->{$method}($context);
        } catch (\Throwable $exception) {
            return CrudServiceResult::continueDefault(CrudServiceResult::STATUS_ENTRYPOINT_HOOK_FAILED, [
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
    private function callLegacyInvokable(object $entrypoint, CrudServiceContext $context, array $resolutionDiagnostics): mixed
    {
        try {
            return $entrypoint($context->request);
        } catch (\Throwable $exception) {
            return CrudServiceResult::continueDefault(CrudServiceResult::STATUS_LEGACY_INVOKABLE_FAILED, [
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
    private function normalizeResult(mixed $result, object $entrypoint, string $method, array $resolutionDiagnostics): CrudServiceResult
    {
        if ($result instanceof CrudServiceResult) {
            return $result;
        }

        if ($result instanceof Response) {
            return CrudServiceResult::response($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if ($result instanceof CrudResourceContract) {
            return CrudServiceResult::viewContract($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if (null === $result) {
            return CrudServiceResult::continueDefault(CrudServiceResult::STATUS_NO_ENTRYPOINT_OVERRIDE, [
                'entrypoint' => $entrypoint::class,
                'method' => $method,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        return CrudServiceResult::continueDefault(CrudServiceResult::STATUS_INVALID_ENTRYPOINT_RESULT_IGNORED, [
            'entrypoint' => $entrypoint::class,
            'method' => $method,
            'resultType' => get_debug_type($result),
            'resolution' => $resolutionDiagnostics,
        ]);
    }
}
