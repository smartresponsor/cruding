<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the contract for form handler within the Cruding component.
 */
interface CrudFormHandlerInterface
{
    /**
     * @return FormInterface<mixed>
     */
    public function createAndHandle(string $formTypeClass, object $object, Request $request): FormInterface;

    /**      * Executes the persist operation.      */
    public function persist(object $object): void;

    /**      * Executes the flush operation.      */
    public function flush(object $object): void;

    /**      * Executes the delete operation.      */
    public function delete(object $object): void;
}
