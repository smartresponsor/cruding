<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudApiInputHandlerInterface
{
    /**
     * @return FormInterface<mixed>
     */
    public function submit(string $formTypeClass, object $object, Request $request, bool $clearMissing): FormInterface;
}
