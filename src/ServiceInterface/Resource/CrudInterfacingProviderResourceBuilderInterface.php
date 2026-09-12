<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Resource;

use App\Cruding\DTO\CrudPageDefinitionDTO;
use Symfony\Component\Form\FormView;

/**
 * Builds a neutral Cruding view payload for Interfacing/provider rendering.
 */
interface CrudInterfacingProviderResourceBuilderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function build(CrudPageDefinitionDTO $page, ?object $object = null, ?FormView $form = null): array;
}
