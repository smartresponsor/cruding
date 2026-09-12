<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Operation\Create;

use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\Service\Operation\Create\CrudCreateWorkItem;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\Form\FormInterface;

/**
 * Builds create view builder values used by Cruding workflows.
 */
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
