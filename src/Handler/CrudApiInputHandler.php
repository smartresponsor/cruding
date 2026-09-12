<?php

declare(strict_types=1);

namespace App\Cruding\Handler;

use App\Cruding\ServiceInterface\CrudApiInputHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles api input handler processing for Cruding operations.
 */
final readonly class CrudApiInputHandler implements CrudApiInputHandlerInterface
{
    public function __construct(private FormFactoryInterface $formFactory)
    {
    }

    /**      * Executes the submit operation.      */
    public function submit(string $formTypeClass, object $object, Request $request, bool $clearMissing): FormInterface
    {
        $form = $this->formFactory->create($formTypeClass, $object, [
            'csrf_protection' => false,
        ]);

        $payload = $request->getContent();
        $data = '' === trim($payload) ? [] : json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            $data = [];
        }

        $form->submit($data, $clearMissing);

        return $form;
    }
}
