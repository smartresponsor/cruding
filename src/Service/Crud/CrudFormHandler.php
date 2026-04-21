<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudFormHandler implements CrudFormHandlerInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @return FormInterface<mixed>
     */
    public function createAndHandle(AbstractController $controller, string $formTypeClass, object $object, Request $request): FormInterface
    {
        $form = $controller->createForm($formTypeClass, $object);
        $form->handleRequest($request);

        return $form;
    }

    public function persist(object $object): void
    {
        $manager = $this->managerRegistry->getManagerForClass($object::class) ?? $this->managerRegistry->getManager();
        $manager->persist($object);
        $manager->flush();
    }

    public function flush(object $object): void
    {
        $manager = $this->managerRegistry->getManagerForClass($object::class) ?? $this->managerRegistry->getManager();
        $manager->flush();
    }

    public function delete(object $object): void
    {
        $manager = $this->managerRegistry->getManagerForClass($object::class) ?? $this->managerRegistry->getManager();
        $manager->remove($object);
        $manager->flush();
    }
}
