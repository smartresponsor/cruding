<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Service;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\CrudMutationLifecycleSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudMutationLifecycleDispatcherTest extends TestCase
{
    public function testAfterFailureRemainsInsideDoctrineTransactionBoundary(): void
    {
        $object = new \stdClass();
        $events = [];

        $subscriber = $this->createStub(CrudMutationLifecycleSubscriberInterface::class);
        $subscriber->method('supports')->willReturn(true);
        $subscriber->method('before')->willReturnCallback(static function () use (&$events): void {
            $events[] = 'before';
        });
        $subscriber->method('after')->willReturnCallback(static function () use (&$events): void {
            $events[] = 'after';
            throw new \RuntimeException('after failed');
        });

        $dispatcher = new CrudMutationLifecycleDispatcher([$subscriber], $this->registry($object));

        try {
            $dispatcher->execute($this->context($object), static function () use (&$events): void {
                $events[] = 'mutation';
            });
            self::fail('Expected after lifecycle failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('after failed', $exception->getMessage());
        }

        self::assertSame(['before', 'mutation', 'after'], $events);
    }

    public function testMutationFailureAfterBeforeRemainsInsideDoctrineTransactionBoundary(): void
    {
        $object = new \stdClass();
        $events = [];

        $subscriber = $this->createMock(CrudMutationLifecycleSubscriberInterface::class);
        $subscriber->method('supports')->willReturn(true);
        $subscriber->method('before')->willReturnCallback(static function () use (&$events): void {
            $events[] = 'before';
        });
        $subscriber->expects(self::never())->method('after');

        $dispatcher = new CrudMutationLifecycleDispatcher([$subscriber], $this->registry($object));

        try {
            $dispatcher->execute($this->context($object), static function () use (&$events): void {
                $events[] = 'mutation';
                throw new \RuntimeException('mutation failed');
            });
            self::fail('Expected mutation failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('mutation failed', $exception->getMessage());
        }

        self::assertSame(['before', 'mutation'], $events);
    }

    private function registry(object $object): ManagerRegistry
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $operation): mixed => $operation());

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($object::class)
            ->willReturn($entityManager);

        return $registry;
    }

    private function context(object $object): CrudMutationLifecycleContextDTO
    {
        return new CrudMutationLifecycleContextDTO(
            new CrudContextDTO('api', 'update', 'fixture', $object::class, 'id', 1, null),
            $object,
            new Request(),
            'update',
        );
    }
}
