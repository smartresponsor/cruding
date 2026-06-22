<?php

declare(strict_types=1);

namespace App\Cruding\Invoker\Crud;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudDeleteEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGetEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGroundedEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPatchEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPostEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPutEntrypointInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Response;

final class CrudServiceInvoker
{
    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    public function invoke(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics = []): CrudEntrypointResult
    {
        $grounding = $this->groundingDecision($entrypoint, $context, $resolutionDiagnostics);
        if ($grounding instanceof CrudEntrypointResult) {
            return $grounding;
        }

        if (!$grounding) {
            return CrudEntrypointResult::notGrounded([
                'entrypoint' => $entrypoint::class,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        $method = $context->httpMethod();
        $result = match ($method) {
            CrudEntrypointContext::HTTP_GET => $this->callGet($entrypoint, $context, $resolutionDiagnostics),
            CrudEntrypointContext::HTTP_POST => $this->callPost($entrypoint, $context, $resolutionDiagnostics),
            CrudEntrypointContext::HTTP_PUT => $this->callPut($entrypoint, $context, $resolutionDiagnostics),
            CrudEntrypointContext::HTTP_PATCH => $this->callPatch($entrypoint, $context, $resolutionDiagnostics),
            CrudEntrypointContext::HTTP_DELETE => $this->callDelete($entrypoint, $context, $resolutionDiagnostics),
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
    private function groundingDecision(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): bool|CrudEntrypointResult
    {
        if (!$entrypoint instanceof CrudGroundedEntrypointInterface && !$this->isPublicCallable($entrypoint, 'isGrounded')) {
            return true;
        }

        try {
            return (bool) $entrypoint->isGrounded($context);
        } catch (\Throwable $exception) {
            return CrudEntrypointResult::continueDefault(CrudEntrypointResult::STATUS_ENTRYPOINT_GROUNDING_FAILED, [
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
    private function callGet(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudGetEntrypointInterface && !$this->isPublicCallable($entrypoint, 'get')) {
            return null;
        }

        return $this->callHook($entrypoint, 'get', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPost(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPostEntrypointInterface && !$this->isPublicCallable($entrypoint, 'post')) {
            return null;
        }

        return $this->callHook($entrypoint, 'post', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPut(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPutEntrypointInterface && !$this->isPublicCallable($entrypoint, 'put')) {
            return null;
        }

        return $this->callHook($entrypoint, 'put', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callPatch(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudPatchEntrypointInterface && !$this->isPublicCallable($entrypoint, 'patch')) {
            return null;
        }

        return $this->callHook($entrypoint, 'patch', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callDelete(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        if (!$entrypoint instanceof CrudDeleteEntrypointInterface && !$this->isPublicCallable($entrypoint, 'delete')) {
            return null;
        }

        return $this->callHook($entrypoint, 'delete', $context, $resolutionDiagnostics);
    }

    /**
     * @param array<string, mixed> $resolutionDiagnostics
     */
    private function callHook(object $entrypoint, string $method, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        try {
            return $entrypoint->{$method}($context);
        } catch (\Throwable $exception) {
            return CrudEntrypointResult::continueDefault(CrudEntrypointResult::STATUS_ENTRYPOINT_HOOK_FAILED, [
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
    private function callLegacyInvokable(object $entrypoint, CrudEntrypointContext $context, array $resolutionDiagnostics): mixed
    {
        try {
            return $entrypoint($context->request);
        } catch (\Throwable $exception) {
            return CrudEntrypointResult::continueDefault(CrudEntrypointResult::STATUS_LEGACY_INVOKABLE_FAILED, [
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
    private function normalizeResult(mixed $result, object $entrypoint, string $method, array $resolutionDiagnostics): CrudEntrypointResult
    {
        if ($result instanceof CrudEntrypointResult) {
            return $result;
        }

        if ($result instanceof Response) {
            return CrudEntrypointResult::response($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if ($result instanceof CrudSurfaceContract) {
            return CrudEntrypointResult::surfaceContract($result, diagnostics: ['resolution' => $resolutionDiagnostics]);
        }

        if (null === $result) {
            return CrudEntrypointResult::continueDefault(CrudEntrypointResult::STATUS_NO_ENTRYPOINT_OVERRIDE, [
                'entrypoint' => $entrypoint::class,
                'method' => $method,
                'resolution' => $resolutionDiagnostics,
            ]);
        }

        return CrudEntrypointResult::continueDefault(CrudEntrypointResult::STATUS_INVALID_ENTRYPOINT_RESULT_IGNORED, [
            'entrypoint' => $entrypoint::class,
            'method' => $method,
            'resultType' => get_debug_type($result),
            'resolution' => $resolutionDiagnostics,
        ]);
    }
}
