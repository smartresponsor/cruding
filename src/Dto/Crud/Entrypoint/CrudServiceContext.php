<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud\Entrypoint;

use App\Cruding\Dto\Crud\CrudContext;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudServiceContext
{
    public const HTTP_GET = 'get';
    public const HTTP_POST = 'post';
    public const HTTP_PUT = 'put';
    public const HTTP_PATCH = 'patch';
    public const HTTP_DELETE = 'delete';

    /**
     * @var list<string>
     */
    public const SUPPORTED_HTTP_METHODS = [
        self::HTTP_GET,
        self::HTTP_POST,
        self::HTTP_PUT,
        self::HTTP_PATCH,
        self::HTTP_DELETE,
    ];

    public function __construct(
        public Request $request,
        public CrudContext $crudContext,
        public ?object $object = null,
    ) {
    }

    public function httpMethod(): string
    {
        return strtolower($this->request->getMethod());
    }

    public function isHttpMethod(string $method): bool
    {
        return $this->httpMethod() === strtolower($method);
    }

    public function isGet(): bool
    {
        return $this->isHttpMethod(self::HTTP_GET);
    }

    public function isPost(): bool
    {
        return $this->isHttpMethod(self::HTTP_POST);
    }

    public function isPut(): bool
    {
        return $this->isHttpMethod(self::HTTP_PUT);
    }

    public function isPatch(): bool
    {
        return $this->isHttpMethod(self::HTTP_PATCH);
    }

    public function isDelete(): bool
    {
        return $this->isHttpMethod(self::HTTP_DELETE);
    }

    public function routeName(): ?string
    {
        $route = $this->request->attributes->get('_route');

        return is_scalar($route) ? (string) $route : null;
    }

    public function path(): string
    {
        return $this->request->getPathInfo();
    }

    public function resourcePath(): string
    {
        return $this->crudContext->resourcePath;
    }

    public function isActorGrounded(): bool
    {
        return true === $this->request->attributes->get('_crud_actor_grounded', false);
    }

    public function actorUserId(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_user_id');

        return is_scalar($value) ? $value : null;
    }

    public function actorUserSlug(): ?string
    {
        $value = $this->request->attributes->get('_crud_actor_user_slug');

        return is_scalar($value) ? (string) $value : null;
    }

    public function actorUserIdentifier(): ?string
    {
        $value = $this->request->attributes->get('_crud_actor_user_identifier');

        return is_scalar($value) ? (string) $value : null;
    }

    public function actorIdentityField(): ?string
    {
        $field = $this->request->attributes->get('_crud_actor_identity_field');

        return is_string($field) && '' !== $field ? $field : null;
    }

    public function actorIdentityValue(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_identity_value');

        return is_scalar($value) ? $value : null;
    }

    public function actorAdminIdentityField(): ?string
    {
        $field = $this->request->attributes->get('_crud_actor_admin_identity_field');

        return is_string($field) && '' !== $field ? $field : null;
    }

    public function actorAdminIdentityValue(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_admin_identity_value');

        return is_scalar($value) ? $value : null;
    }

    public function actorIsAdmin(): bool
    {
        return true === $this->request->attributes->get('_crud_actor_is_admin', false);
    }

    public function view(): string
    {
        return $this->crudContext->view;
    }

    public function operation(): string
    {
        return $this->crudContext->operation;
    }

    public function isOperation(string $operation): bool
    {
        return $this->operation() === strtolower($operation);
    }

    public function identifierField(): string
    {
        return $this->crudContext->identifierField;
    }

    public function identifierValue(): string|int|null
    {
        return $this->crudContext->identifierValue;
    }

    public function hasIdentity(): bool
    {
        return null !== $this->crudContext->identifierValue && '' !== (string) $this->crudContext->identifierValue;
    }

    public function hasResource(): bool
    {
        return null !== $this->object;
    }

    public function hasObject(): bool
    {
        return $this->hasResource();
    }
}
