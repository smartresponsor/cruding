<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use Symfony\Component\Form\FormView;

/**
 * Builds the Bridge/Interfacing provider-surface view model for Cruding pages.
 *
 * Cruding remains the owner of resource metadata, objects, operations, and form
 * handling. It must not render the primary CRUD page through its own Twig shell;
 * the final visual document is delegated to Interfacing through the bridge
 * provider-surface contract.
 */
interface CrudInterfacingProviderSurfaceBuilderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): array;
}
