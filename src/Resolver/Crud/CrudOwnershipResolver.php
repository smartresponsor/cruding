<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Crud\CrudOwnership;
use App\Cruding\Dto\Crud\CrudOwnershipResolutionContext;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CrudOwnershipResolver implements CrudOwnershipResolverInterface
{
    /** @param iterable<CrudOwnershipProviderInterface> $providerList */
    public function __construct(
        private Security $security,
        private iterable $providerList,
    ) {
    }

    public function resolve(?object $object): CrudOwnership
    {
        $actor = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN');
        $isAuthenticated = is_object($actor);

        if (null === $object) {
            return new CrudOwnership(false, $isAuthenticated, false, $isAdmin, null);
        }

        $context = new CrudOwnershipResolutionContext($object, $isAuthenticated ? $actor : null, $isAdmin);
        foreach ($this->providerList as $provider) {
            if ($provider->supports($context)) {
                return $provider->resolve($context);
            }
        }

        return new CrudOwnership(false, $isAuthenticated, false, $isAdmin, null);
    }
}
