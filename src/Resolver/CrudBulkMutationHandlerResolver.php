<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\CrudBulkMutationHandlerInterface;

/**
 * Resolves exactly one host-provided bulk mutation handler.
 */
final readonly class CrudBulkMutationHandlerResolver
{
    /** @param iterable<CrudBulkMutationHandlerInterface> $handlers */
    public function __construct(private iterable $handlers)
    {
    }

    /** Resolves the single backend handler responsible for the requested bulk action. */
    public function resolve(string $action, CrudContextDTO $context): CrudBulkMutationHandlerInterface
    {
        $matched = null;

        foreach ($this->handlers as $handler) {
            if (!$handler->supports($action, $context)) {
                continue;
            }

            if (null !== $matched) {
                throw new \LogicException(sprintf('Multiple bulk mutation handlers support action "%s".', $action));
            }

            $matched = $handler;
        }

        if (null === $matched) {
            throw new \InvalidArgumentException(sprintf('Unsupported bulk mutation action "%s".', $action));
        }

        return $matched;
    }
}
