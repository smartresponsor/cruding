<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Api;

use App\Cruding\EventSubscriber\CrudApiExceptionSubscriber;
use App\Cruding\Service\Api\CrudApiProblemResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class CrudApiExceptionSubscriberTest extends TestCase
{
    public function testSubscriberTransformsApiNotFoundIntoProblemJson(): void
    {
        $subscriber = new CrudApiExceptionSubscriber(new CrudApiProblemResponseFactory());
        $kernel = $this->createStub(KernelInterface::class);
        $request = Request::create('/api/product/missing');
        $request->attributes->set('resourcePath', 'product');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException('Missing product.'));

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Not Found', $payload['title']);
        self::assertSame('product', $payload['resourcePath']);
    }

    public function testSubscriberIgnoresNonApiRequests(): void
    {
        $subscriber = new CrudApiExceptionSubscriber(new CrudApiProblemResponseFactory());
        $kernel = $this->createStub(KernelInterface::class);
        $request = Request::create('/product/demo');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new NotFoundHttpException('Missing product.'));

        $subscriber->onKernelException($event);

        self::assertNull($event->getResponse());
    }
}
