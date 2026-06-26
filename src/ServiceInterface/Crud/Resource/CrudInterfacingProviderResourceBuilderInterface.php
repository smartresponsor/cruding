<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Resource;

use App\Cruding\Dto\Crud\CrudPageDefinition;
use Symfony\Component\Form\FormView;

/**
 * Builds a neutral Cruding view payload for Interfacing/provider rendering.
 */
interface CrudInterfacingProviderResourceBuilderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(CrudPageDefinition $page, ?object $object = null, ?FormView $form = null): array;
}
