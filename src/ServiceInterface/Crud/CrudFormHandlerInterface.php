<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface CrudFormHandlerInterface
{
    /**
     * @return FormInterface<mixed>
     */
    public function createAndHandle(string $formTypeClass, object $object, Request $request): FormInterface;

    public function persist(object $object): void;

    public function flush(object $object): void;

    public function delete(object $object): void;
}
