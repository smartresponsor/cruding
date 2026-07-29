<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudCreateFormProcessor
{
    public function __construct(
        private CrudFormHandlerInterface $formHandler,
        private CrudMutationLifecycleDispatcher $mutationLifecycleDispatcher,
        private CrudCreateRedirectResolver $redirectResolver,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    /**
     * @return FormInterface<mixed>|Response
     */
    public function process(Request $request, CrudCreateWorkItem $workItem): FormInterface|Response
    {
        if (null === $workItem->context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle($workItem->context->formTypeClass, $workItem->object, $request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $form;
        }

        $lifecycleContext = new CrudMutationLifecycleContext($workItem->context, $workItem->object, $request, 'create');
        $this->mutationLifecycleDispatcher->execute(
            $lifecycleContext,
            function () use ($workItem): void {
                $this->formHandler->persist($workItem->object);
            },
        );

        return $this->redirectResolver->afterCreate($workItem);
    }
}
