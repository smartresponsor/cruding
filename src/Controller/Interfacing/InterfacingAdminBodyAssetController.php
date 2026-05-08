<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Interfacing;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Serves canonical Interfacing admin-body provider assets from the active host runtime.
 *
 * Cruding may be the running Symfony application for local/host integration tests, but
 * it is not the UI owner. The provider document references the canonical Interfacing
 * asset URLs, so this controller publishes those assets through the current runtime
 * without reintroducing Cruding-owned Twig/CSS rendering.
 */
final class InterfacingAdminBodyAssetController
{
    private const MIME_TYPES = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'mjs' => 'text/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
    ];

    public function __construct(private readonly string $projectDir)
    {
    }

    public function __invoke(string $assetPath): Response
    {
        if ('' === $assetPath || str_contains($assetPath, '..') || str_starts_with($assetPath, '/') || str_contains($assetPath, '\\')) {
            return new Response('Invalid Interfacing admin-body asset path.', Response::HTTP_BAD_REQUEST);
        }

        $root = $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'interfacing'.DIRECTORY_SEPARATOR.'admin-body';
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $assetPath);
        $realRoot = realpath($root);
        $realPath = realpath($path);

        if (false === $realRoot || false === $realPath || !str_starts_with($realPath, $realRoot.DIRECTORY_SEPARATOR) || !is_file($realPath)) {
            return new Response('Interfacing admin-body asset not found.', Response::HTTP_NOT_FOUND);
        }

        $extension = strtolower((string) pathinfo($realPath, PATHINFO_EXTENSION));
        $response = new BinaryFileResponse($realPath);
        $response->headers->set('Content-Type', self::MIME_TYPES[$extension] ?? 'application/octet-stream');
        $response->headers->set('Cache-Control', 'public, max-age=60, must-revalidate');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($realPath));

        return $response;
    }
}
