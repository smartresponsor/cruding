<?php

declare(strict_types=1);

namespace App\Cruding\Routing;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class CrudingRouteManifest extends Loader
{
    public const GRAMMAR_VERSION = 'cruding-route-manifest-v1';

    private const API_CONTROLLER = 'App\\Cruding\\Controller\\Api\\Crud\\CrudApiController';
    private const CRUD_CONTROLLER = 'App\\Cruding\\Controller\\Crud\\CrudController';
    private const RESOURCE_CONTROLLER = 'App\\Cruding\\Controller\\Crud\\CrudResourceController';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%cruding.app_env%')]
        private readonly string $appEnv,
        #[Autowire('%cruding.view_token_requirement%')]
        private readonly string $viewTokenRequirement,
    ) {
        parent::__construct();
    }

    public function path(): string
    {
        return $this->projectDir.'/var/cruding/route_manifest.yaml';
    }

    public function metaPath(): string
    {
        return $this->projectDir.'/var/cruding/route_manifest.meta.json';
    }

    public function exists(): bool
    {
        return is_file($this->path()) && '' !== trim((string) file_get_contents($this->path()));
    }

    public function loadManifest(): RouteCollection
    {
        return $this->loader(dirname($this->path()))->load(basename($this->path()));
    }

    public function loadLive(): RouteCollection
    {
        $routes = new RouteCollection();

        $this->addRoute($routes, 'cruding_api_read', '/api/{crudPath}', self::API_CONTROLLER, ['GET'], ['crudPath' => '[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?(?:/(?:[1-9][0-9]*|[a-z0-9][a-z0-9_-]{17,}))?'], ['_crud_view' => 'public']);
        $this->addRoute($routes, 'cruding_api_create', '/api/{crudPath}', self::API_CONTROLLER, ['POST'], ['crudPath' => '[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?'], ['_crud_view' => 'public']);
        $this->addRoute($routes, 'cruding_api_member_mutation', '/api/{crudPath}', self::API_CONTROLLER, ['PUT', 'PATCH', 'DELETE'], ['crudPath' => '[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?/(?:[1-9][0-9]*|[a-z0-9][a-z0-9_-]{17,})'], ['_crud_view' => 'public']);
        $this->addRoute($routes, 'cruding_tokenized_catch_all', '/{crudPath}', self::CRUD_CONTROLLER, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], ['crudPath' => '(?:[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?/(?:index|new|create|import|bulk|show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)|[a-z][a-z0-9_-]*(?:/[a-z][a-z0-9_-]*)?/(?:show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)/(?:[1-9][0-9]*|[a-z0-9][a-z0-9_-]{17,}))'], ['_crud_view' => 'public']);

        $resource = '[a-z][a-z0-9_-]*';
        $subject = '(?!show$|index$|new$|create$|edit$|update$|delete$|bulk$|import$|export$|archive$|restore$|duplicate$|card$|table$|gallery$|compact$|full$|detail$|list$)[A-Za-z0-9][A-Za-z0-9_-]*';
        $view = '[a-z0-9][a-z0-9_-]*';
        $item = '[A-Za-z0-9][A-Za-z0-9_-]*';
        $action = '(?!request(?:/)?$)[a-z][a-z0-9_-]*';

        $base = ['resource' => $resource, 'subject' => $subject, 'view' => $view];
        $this->addRoute($routes, 'cruding_view_token_item_action', '/{resource}/{subject}/{view}/{token}/{item}/{action}', self::RESOURCE_CONTROLLER, ['GET'], $base + ['token' => $this->viewTokenRequirement, 'item' => $item, 'action' => $action]);
        $this->addRoute($routes, 'cruding_view_token_item', '/{resource}/{subject}/{view}/{token}/{item}', self::RESOURCE_CONTROLLER, ['GET'], $base + ['token' => $this->viewTokenRequirement, 'item' => $item]);
        $this->addRoute($routes, 'cruding_resource_item_action', '/{resource}/{subject}/{view}/{item}/{action}', self::RESOURCE_CONTROLLER, ['GET'], $base + ['item' => $item, 'action' => $action]);
        $this->addRoute($routes, 'cruding_resource_action', '/{resource}/{subject}/{view}/{action}', self::RESOURCE_CONTROLLER, ['GET'], $base + ['action' => $action]);
        $this->addRoute($routes, 'cruding_resource_index', '/{resource}/{subject}/{view}', self::RESOURCE_CONTROLLER, ['GET'], $base);

        return $routes;
    }

    public function dump(): void
    {
        $routes = $this->loadLive();
        $directory = dirname($this->path());

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($this->path(), Yaml::dump($this->export($routes), 6, 4));
        file_put_contents($this->metaPath(), json_encode([
            'grammarVersion' => self::GRAMMAR_VERSION,
            'appEnv' => $this->appEnv,
            'routeCount' => count($routes),
            'hash' => $this->hash($routes),
            'generatedAt' => gmdate('c'),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    public function liveHash(): string
    {
        return $this->hash($this->loadLive());
    }

    public function manifestHash(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->hash($this->loadManifest());
        } catch (\Throwable) {
            return null;
        }
    }

    public function export(RouteCollection $collection): array
    {
        $data = [];

        foreach ($collection->all() as $name => $route) {
            $data[$name] = $this->exportRoute($route);
        }

        return $data;
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->exists()) {
            try {
                $routes = $this->loadManifest();

                if (0 < count($routes)) {
                    return $routes;
                }
            } catch (\Throwable) {
            }
        }

        return $this->loadLive();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'cruding' === $type;
    }

    private function addRoute(RouteCollection $routes, string $name, string $path, string $controller, array $methods, array $requirements, array $defaults = []): void
    {
        $routes->add($name, new Route($path, ['_controller' => $controller] + $defaults, $requirements, [], '', [], $methods));
    }

    private function loader(string $directory): YamlFileLoader
    {
        return new YamlFileLoader(new FileLocator($directory));
    }

    private function hash(RouteCollection $collection): string
    {
        return hash('sha256', json_encode($this->export($collection), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function exportRoute(Route $route): array
    {
        $defaults = $route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        unset($defaults['_controller']);

        $data = ['path' => $route->getPath()];
        if (is_string($controller)) {
            $data['controller'] = $controller;
        }
        if ([] !== $route->getMethods()) {
            $data['methods'] = array_values($route->getMethods());
        }
        if ([] !== $defaults) {
            ksort($defaults);
            $data['defaults'] = $defaults;
        }
        $requirements = $route->getRequirements();
        if ([] !== $requirements) {
            ksort($requirements);
            $data['requirements'] = $requirements;
        }

        return $data;
    }
}
