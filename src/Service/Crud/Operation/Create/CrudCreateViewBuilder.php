<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\Form\FormInterface;

final readonly class CrudCreateViewBuilder
{
    public function __construct(
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
    ) {
    }

    /** @param FormInterface<mixed> $form */
    public function build(CrudCreateWorkItem $workItem, FormInterface $form): CrudResourceContract
    {
        $formView = $form->createView();
        $page = $this->pageDefinitionProvider->provideNew($workItem->context, $workItem->object, $formView);

        return $this->viewContractFactory->create($page, $workItem->object, $formView);
    }
}
