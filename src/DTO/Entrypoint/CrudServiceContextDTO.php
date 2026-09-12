<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Entrypoint;

use App\Cruding\DTO\CrudContextDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Carries service context data across Cruding processing boundaries.
 */
final readonly class CrudServiceContextDTO
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
        public CrudContextDTO $crudContext,
        public ?object $object = null,
    ) {
    }

    /**      * Executes the http method operation.      */
    public function httpMethod(): string
    {
        return strtolower($this->request->getMethod());
    }

    /**      * Indicates whether http method.      */
    public function isHttpMethod(string $method): bool
    {
        return $this->httpMethod() === strtolower($method);
    }

    /**      * Indicates whether get.      */
    public function isGet(): bool
    {
        return $this->isHttpMethod(self::HTTP_GET);
    }

    /**      * Indicates whether post.      */
    public function isPost(): bool
    {
        return $this->isHttpMethod(self::HTTP_POST);
    }

    /**      * Indicates whether put.      */
    public function isPut(): bool
    {
        return $this->isHttpMethod(self::HTTP_PUT);
    }

    /**      * Indicates whether patch.      */
    public function isPatch(): bool
    {
        return $this->isHttpMethod(self::HTTP_PATCH);
    }

    /**      * Indicates whether delete.      */
    public function isDelete(): bool
    {
        return $this->isHttpMethod(self::HTTP_DELETE);
    }

    /**      * Executes the route name operation.      */
    public function routeName(): ?string
    {
        $route = $this->request->attributes->get('_route');

        return is_scalar($route) ? (string) $route : null;
    }

    /**      * Executes the path operation.      */
    public function path(): string
    {
        return $this->request->getPathInfo();
    }

    /**      * Executes the resource path operation.      */
    public function resourcePath(): string
    {
        return $this->crudContext->resourcePath;
    }

    /**      * Indicates whether actor grounded.      */
    public function isActorGrounded(): bool
    {
        return true === $this->request->attributes->get('_crud_actor_grounded', false);
    }

    /**      * Executes the actor user id operation.      */
    public function actorUserId(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_user_id');

        return is_scalar($value) ? $value : null;
    }

    /**      * Executes the actor user slug operation.      */
    public function actorUserSlug(): ?string
    {
        $value = $this->request->attributes->get('_crud_actor_user_slug');

        return is_scalar($value) ? (string) $value : null;
    }

    /**      * Executes the actor user identifier operation.      */
    public function actorUserIdentifier(): ?string
    {
        $value = $this->request->attributes->get('_crud_actor_user_identifier');

        return is_scalar($value) ? (string) $value : null;
    }

    /**      * Executes the actor identity field operation.      */
    public function actorIdentityField(): ?string
    {
        $field = $this->request->attributes->get('_crud_actor_identity_field');

        return is_string($field) && '' !== $field ? $field : null;
    }

    /**      * Executes the actor identity value operation.      */
    public function actorIdentityValue(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_identity_value');

        return is_scalar($value) ? $value : null;
    }

    /**      * Executes the actor admin identity field operation.      */
    public function actorAdminIdentityField(): ?string
    {
        $field = $this->request->attributes->get('_crud_actor_admin_identity_field');

        return is_string($field) && '' !== $field ? $field : null;
    }

    /**      * Executes the actor admin identity value operation.      */
    public function actorAdminIdentityValue(): string|int|null
    {
        $value = $this->request->attributes->get('_crud_actor_admin_identity_value');

        return is_scalar($value) ? $value : null;
    }

    /**      * Executes the actor is admin operation.      */
    public function actorIsAdmin(): bool
    {
        return true === $this->request->attributes->get('_crud_actor_is_admin', false);
    }

    /**      * Executes the view operation.      */
    public function view(): string
    {
        return $this->crudContext->view;
    }

    /**      * Executes the operation operation.      */
    public function operation(): string
    {
        return $this->crudContext->operation;
    }

    /**      * Indicates whether operation.      */
    public function isOperation(string $operation): bool
    {
        return $this->operation() === strtolower($operation);
    }

    /**      * Executes the identifier field operation.      */
    public function identifierField(): string
    {
        return $this->crudContext->identifierField;
    }

    /**      * Executes the identifier value operation.      */
    public function identifierValue(): string|int|null
    {
        return $this->crudContext->identifierValue;
    }

    /**      * Indicates whether identity.      */
    public function hasIdentity(): bool
    {
        return null !== $this->crudContext->identifierValue && '' !== (string) $this->crudContext->identifierValue;
    }

    /**      * Indicates whether resource.      */
    public function hasResource(): bool
    {
        return null !== $this->object;
    }

    /**      * Indicates whether object.      */
    public function hasObject(): bool
    {
        return $this->hasResource();
    }
}
