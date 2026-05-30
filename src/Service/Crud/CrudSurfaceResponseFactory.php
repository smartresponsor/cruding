<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class CrudSurfaceResponseFactory
{
    public function __construct(private Environment $twig)
    {
    }

    public function render(CrudSurfaceContract $surface): Response
    {
        try {
            return new Response($this->twig->render($surface->templatePath(), $surface->toTemplateContext()));
        } catch (\Throwable $exception) {
            if ('@Cruding/crud/index.html.twig' !== $surface->templatePath()) {
                try {
                    return new Response($this->twig->render('@Cruding/crud/index.html.twig', $surface->toTemplateContext()));
                } catch (\Throwable) {
                    // Fall through to the explicit JSON diagnostic response below.
                }
            }

            return new JsonResponse([
                'ok' => false,
                'component' => 'cruding',
                'reason' => 'crud_surface_render_failed',
                'diagnostics' => [
                    'templatePath' => $surface->templatePath(),
                    'exceptionClass' => $exception::class,
                    'message' => $exception->getMessage(),
                ],
                'fallback' => $surface->toFallbackData(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
