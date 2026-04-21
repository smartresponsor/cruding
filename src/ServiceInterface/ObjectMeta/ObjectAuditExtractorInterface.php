<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\ObjectMeta;

use App\Cruding\Dto\ObjectMeta\ObjectAuditContext;

interface ObjectAuditExtractorInterface
{
    public function extract(object $object): ObjectAuditContext;
}
