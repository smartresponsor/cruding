<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Operation\CrudDeleteOperationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Provides the delete operation responsibility within the Cruding component.
 */
final readonly class CrudDeleteOperation implements CrudDeleteOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudFormHandlerInterface $formHandler,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudMutationGuardInterface $mutationGuard,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CrudServiceRunner $entrypointRunner,
        private CrudMutationLifecycleDispatcher $mutationLifecycleDispatcher,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanDelete($access);

        $tokenId = 'delete_'.$context->resourcePath.'_'.$context->identifierValue;
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, (string) $request->request->get('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if ($entrypointResult instanceof Response) {
            return $entrypointResult;
        }

        $lifecycleContext = new CrudMutationLifecycleContextDTO($context, $object, $request, 'delete');
        $this->mutationLifecycleDispatcher->execute(
            $lifecycleContext,
            function () use ($object): void {
                $this->formHandler->delete($object);
            },
        );

        return new RedirectResponse($this->urlGenerator->generate(
            $this->routeNameResolver->resolveIndex($context),
            $this->routeNameResolver->parameters($context, null, null, 'index'),
        ));
    }
}
