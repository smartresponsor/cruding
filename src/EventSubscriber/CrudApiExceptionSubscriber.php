<?php

declare(strict_types=1);

namespace App\Cruding\EventSubscriber;

use App\Cruding\Service\Crud\Api\CrudApiProblemResponseFactory;
use App\Cruding\Value\Api\CrudApiResponseTitle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CrudApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private CrudApiProblemResponseFactory $problemResponseFactory)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();
        $extra = ['resourcePath' => $request->attributes->get('resourcePath')];

        if (!$exception instanceof HttpExceptionInterface) {
            error_log(sprintf('Unhandled API exception: %s: %s', $exception::class, $exception->getMessage()));
        }

        $response = match (true) {
            $exception instanceof NotFoundHttpException => $this->problemResponseFactory->notFound($exception->getMessage() ?: 'Resource not found.', $extra),
            $exception instanceof AccessDeniedHttpException => $this->problemResponseFactory->forbidden($exception->getMessage() ?: 'Access denied.', $extra),
            $exception instanceof BadRequestHttpException => $this->problemResponseFactory->badRequest($exception->getMessage() ?: 'Bad request.', $extra),
            $exception instanceof HttpExceptionInterface => $this->problemResponseFactory->create(
                $exception->getStatusCode(),
                CrudApiResponseTitle::fromStatusCode($exception->getStatusCode()),
                $exception->getMessage() ?: 'HTTP error.',
                $extra,
            ),
            default => $this->problemResponseFactory->create(500, 'Internal Server Error', 'Unexpected API error.', $extra),
        };

        $event->setResponse($response);
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }
}
