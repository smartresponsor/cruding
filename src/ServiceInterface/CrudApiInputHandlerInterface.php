<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the contract for api input handler within the Cruding component.
 */
interface CrudApiInputHandlerInterface
{
    /**
     * @return FormInterface<mixed>
     */
    public function submit(string $formTypeClass, object $object, Request $request, bool $clearMissing): FormInterface;
}
