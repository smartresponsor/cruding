<?php

declare(strict_types=1);

namespace App\Cruding;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CrudingBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
