<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudTokenizedRouteIntentDTO;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves actor scope context resolver for Cruding request processing.
 */
final readonly class CrudActorScopeContextResolver
{
    public function __construct(private Security $security)
    {
    }

    /**      * Executes the apply operation.      */
    public function apply(Request $request, CrudTokenizedRouteIntentDTO $intent): void
    {
        $request->attributes->set('_crud_actor_grounded', false);
        $request->attributes->set('_crud_actor_user_id', null);
        $request->attributes->set('_crud_actor_user_slug', null);
        $request->attributes->set('_crud_actor_user_identifier', null);
        $request->attributes->set('_crud_actor_is_admin', false);
        $request->attributes->set('_crud_actor_identity_field', null);
        $request->attributes->set('_crud_actor_identity_value', null);
        $request->attributes->set('_crud_actor_admin_identity_field', null);
        $request->attributes->set('_crud_actor_admin_identity_value', null);

        $user = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN');

        $request->attributes->set('_crud_actor_grounded', 'page' === $intent->operation && null !== $user);
        $request->attributes->set('_crud_actor_is_admin', $isAdmin);

        if (null === $user) {
            return;
        }

        $userId = $this->readScalarByMethods($user, ['getId', 'id']);
        $userSlug = $this->readScalarByMethods($user, ['getObjectSlug', 'getSlug', 'slug']);
        $userIdentifier = $user->getUserIdentifier();

        $request->attributes->set('_crud_actor_user_id', $userId);
        $request->attributes->set('_crud_actor_user_slug', $userSlug);
        $request->attributes->set('_crud_actor_user_identifier', $userIdentifier);

        $frontendIdentity = $userSlug ?? $userIdentifier;
        $request->attributes->set('_crud_actor_identity_field', null !== $userSlug ? 'user_slug' : 'user_identifier');
        $request->attributes->set('_crud_actor_identity_value', $frontendIdentity);

        if ($isAdmin && null !== $userId) {
            $request->attributes->set('_crud_actor_admin_identity_field', 'user_id');
            $request->attributes->set('_crud_actor_admin_identity_value', $userId);
        }
    }

    /**
     * @param list<string> $methods
     */
    private function readScalarByMethods(object $object, array $methods): string|int|null
    {
        foreach ($methods as $method) {
            if (!method_exists($object, $method)) {
                continue;
            }

            $value = $object->{$method}();
            if (is_scalar($value)) {
                return is_int($value) ? $value : (string) $value;
            }
        }

        return null;
    }
}
