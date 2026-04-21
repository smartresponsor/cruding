<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\ObjectMeta;

use App\Cruding\Service\ObjectMeta\ObjectVisibilityManager;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ObjectVisibilityManagerTest extends TestCase
{
    private CrudFormHandlerInterface&MockObject $formHandler;

    protected function setUp(): void
    {
        $this->formHandler = $this->createMock(CrudFormHandlerInterface::class);
    }

    public function testInspectReadsVisibilityStateFromKnownMethods(): void
    {
        $manager = new ObjectVisibilityManager($this->formHandler, []);
        $object = new class {
            public function isVisible(): bool
            {
                return true;
            }

            public function isPublished(): bool
            {
                return true;
            }

            public function isArchived(): bool
            {
                return false;
            }

            public function isDraft(): bool
            {
                return false;
            }
        };

        $context = $manager->inspect($object);

        self::assertTrue($context->visible);
        self::assertTrue($context->published);
        self::assertFalse($context->archived);
        self::assertFalse($context->draft);
    }

    public function testApplyUsesConfigDrivenTransitionAndFlushes(): void
    {
        $manager = new ObjectVisibilityManager($this->formHandler, [
            'publish' => [
                'writes' => [
                    'setPublished' => true,
                    'setVisible' => true,
                ],
            ],
        ]);

        $object = new class {
            public bool $published = false;
            public bool $visible = false;

            public function setPublished(bool $published): void
            {
                $this->published = $published;
            }

            public function setVisible(bool $visible): void
            {
                $this->visible = $visible;
            }

            public function isVisible(): bool
            {
                return $this->visible;
            }

            public function isPublished(): bool
            {
                return $this->published;
            }

            public function isArchived(): bool
            {
                return false;
            }

            public function isDraft(): bool
            {
                return false;
            }
        };

        $this->formHandler->expects(self::once())->method('flush')->with($object);

        $context = $manager->apply($object, 'publish');

        self::assertTrue($context->published);
        self::assertTrue($context->visible);
    }

    public function testApplyThrowsForUnsupportedTransition(): void
    {
        $manager = new ObjectVisibilityManager($this->formHandler, []);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported visibility transition');

        $manager->apply(new \stdClass(), 'publish');
    }
}
