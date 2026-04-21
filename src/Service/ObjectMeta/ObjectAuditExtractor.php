<?php

declare(strict_types=1);

namespace App\Cruding\Service\ObjectMeta;

use App\Cruding\Dto\ObjectMeta\ObjectAuditContext;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectAuditExtractorInterface;

final class ObjectAuditExtractor implements ObjectAuditExtractorInterface
{
    public function extract(object $object): ObjectAuditContext
    {
        return new ObjectAuditContext(
            $this->readDate($object, ['getCreatedAt', 'createdAt']),
            $this->readDate($object, ['getUpdatedAt', 'updatedAt']),
            $this->readActor($object, ['getCreatedBy', 'createdBy', 'getAuthor', 'author']),
            $this->readActor($object, ['getUpdatedBy', 'updatedBy', 'getEditor', 'editor']),
        );
    }

    /** @param list<string> $candidates */
    private function readDate(object $object, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!method_exists($object, $candidate)) {
                continue;
            }

            $value = $object->{$candidate}();
            if ($value instanceof \DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            if (is_scalar($value) && '' !== (string) $value) {
                return (string) $value;
            }
        }

        return null;
    }

    /** @param list<string> $candidates */
    private function readActor(object $object, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!method_exists($object, $candidate)) {
                continue;
            }

            $value = $object->{$candidate}();
            if (null === $value) {
                return null;
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            foreach (['getUserIdentifier', 'getEmail', 'getSlug', 'getId', '__toString'] as $reader) {
                if (method_exists($value, $reader)) {
                    $resolved = $value->{$reader}();

                    return is_scalar($resolved) ? (string) $resolved : null;
                }
            }
        }

        return null;
    }
}
