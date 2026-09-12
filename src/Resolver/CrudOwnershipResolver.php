<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudOwnershipResolutionContextDTO;
use App\Cruding\ServiceInterface\CrudOwnershipProviderInterface;
use App\Cruding\ServiceInterface\CrudOwnershipResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Resolves ownership resolver for Cruding request processing.
 */
final readonly class CrudOwnershipResolver implements CrudOwnershipResolverInterface
{
    /** @param iterable<CrudOwnershipProviderInterface> $providerList */
    public function __construct(
        private Security $security,
        private iterable $providerList,
    ) {
    }

    /**      * Executes the resolve operation.      */
    public function resolve(?object $object): CrudOwnershipDTO
    {
        $actor = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN');
        $isAuthenticated = is_object($actor);

        if (null === $object) {
            return new CrudOwnershipDTO(false, $isAuthenticated, false, $isAdmin, null);
        }

        $context = new CrudOwnershipResolutionContextDTO($object, $isAuthenticated ? $actor : null, $isAdmin);
        foreach ($this->providerList as $provider) {
            if ($provider->supports($context)) {
                return $provider->resolve($context);
            }
        }

        return new CrudOwnershipDTO(false, $isAuthenticated, false, $isAdmin, null);
    }
}
