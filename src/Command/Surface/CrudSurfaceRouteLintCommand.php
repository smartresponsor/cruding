<?php

declare(strict_types=1);

namespace App\Cruding\Command\Surface;

use App\Cruding\Controller\Surface\CrudSurfaceController;
use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Service\Surface\CrudRouteShapeResolver;
use App\Cruding\Service\Surface\CrudSurfaceProviderLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Validates that resource-bound surface routes remain convention parsable.
 */
#[AsCommand(
    name: 'crud:surface:lint-routes',
    description: 'Validate Cruding surface routes against the route-token convention.',
)]
final class CrudSurfaceRouteLintCommand extends Command
{
    private const FALLBACK_OPERATION_LIST = ['index', 'detail', 'show', 'view'];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly CrudRouteShapeResolver $routeShapeResolver,
        private readonly CrudSurfaceProviderLocator $providerLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'strict-provider',
            null,
            InputOption::VALUE_NONE,
            'Fail concrete non-fallback routes when no surface provider is registered.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $strictProvider = true === $input->getOption('strict-provider');
        $errorList = [];
        $warningList = [];
        $checked = 0;

        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if (!$this->isSurfaceControllerRoute($route)) {
                continue;
            }

            ++$checked;
            $request = $this->requestForRoute($name, $route);
            $context = $this->routeShapeResolver->resolve($request);
            if (!$context instanceof CrudRouteContext) {
                $errorList[] = sprintf('%s: route path "%s" cannot be parsed into a Cruding route context.', $name, $route->getPath());
                continue;
            }

            if ('' === $context->resource || '' === $context->operation || [] === $context->providerKeys) {
                $errorList[] = sprintf('%s: parsed route context is incomplete.', $name);
                continue;
            }

            $provider = $this->providerLocator->locate($context);
            if (null !== $provider) {
                continue;
            }

            if ($this->genericRoute($route)) {
                $warningList[] = sprintf(
                    '%s: generic route resolves providers at runtime; sample key is "%s"%s.',
                    $name,
                    $context->primaryProviderKey(),
                    $strictProvider ? '; strict-provider is intentionally skipped for generic declarations' : ''
                );
                continue;
            }

            if (in_array($context->operation, self::FALLBACK_OPERATION_LIST, true)) {
                $warningList[] = sprintf('%s: no provider for "%s"; generic Doctrine fallback may handle it if the entity exists.', $name, $context->primaryProviderKey());
                continue;
            }

            $message = sprintf('%s: concrete route "%s" needs a provider for "%s".', $name, $route->getPath(), $context->primaryProviderKey());
            if ($strictProvider) {
                $errorList[] = $message;
            } else {
                $warningList[] = $message;
            }
        }

        foreach ($warningList as $warning) {
            $output->writeln('<comment>WARN</comment> '.$warning);
        }

        foreach ($errorList as $error) {
            $output->writeln('<error>ERROR</error> '.$error);
        }

        if ([] !== $errorList) {
            $output->writeln(sprintf('<error>Cruding surface route lint failed: %d checked, %d error(s), %d warning(s).</error>', $checked, count($errorList), count($warningList)));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Cruding surface route lint passed: %d checked, %d warning(s).</info>', $checked, count($warningList)));

        return Command::SUCCESS;
    }

    private function isSurfaceControllerRoute(Route $route): bool
    {
        $controller = $route->getDefault('_controller');
        if (!is_string($controller)) {
            return false;
        }

        return CrudSurfaceController::class === $controller
            || str_starts_with($controller, CrudSurfaceController::class.'::');
    }

    private function requestForRoute(string $name, Route $route): Request
    {
        $path = $route->getPath();
        $attributes = ['_route' => $name];

        foreach ($route->compile()->getVariables() as $variable) {
            $value = $this->sampleValue($variable);
            $attributes[$variable] = $value;
            $path = str_replace('{'.$variable.'}', (string) $value, $path);
        }

        $request = Request::create($path);
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $request;
    }

    private function sampleValue(string $variable): string|int
    {
        $lower = strtolower($variable);
        if ('id' === $lower || str_ends_with($lower, 'id')) {
            return 123;
        }

        return match ($lower) {
            'resource' => 'vendor',
            'subject' => 'acme-inc',
            'surface' => 'compliance',
            'item' => 'w9-form',
            'token' => 'show',
            'surfaceToken' => 'show',
            'widgetToken' => 'show',
            'action' => 'briefing',
            default => str_ends_with($lower, 'slug') ? $this->sampleSlug($lower) : 'demo-'.$this->tokenize($variable),
        };
    }

    private function sampleSlug(string $lower): string
    {
        $stem = substr($lower, 0, -4);

        return '' === $stem ? 'demo-slug' : 'demo-'.$this->tokenize($stem);
    }

    private function genericRoute(Route $route): bool
    {
        foreach (['resource', 'surface', 'token', 'action'] as $variable) {
            if (in_array($variable, $route->compile()->getVariables(), true)) {
                return true;
            }
        }

        return false;
    }

    private function tokenize(string $value): string
    {
        $token = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?: $value;
        $token = strtolower(str_replace('_', '-', $token));
        $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?: $token;

        return trim(preg_replace('/-+/', '-', $token) ?: $token, '-');
    }
}
