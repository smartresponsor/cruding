<?php

declare(strict_types=1);

namespace App\Cruding\Command\Surface;

use App\Cruding\Controller\Surface\CrudSurfaceController;
use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Service\Surface\CrudRouteShapeResolver;
use App\Cruding\Service\Surface\CrudSurfaceProviderLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Shows how Cruding parses resource-bound surface routes.
 */
#[AsCommand(
    name: 'crud:surface:debug-map',
    description: 'Display Cruding surface route parsing, provider keys, and template candidates.',
)]
final class CrudSurfaceDebugMapCommand extends Command
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CrudRouteShapeResolver $routeShapeResolver,
        private readonly CrudSurfaceProviderLocator $providerLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Show all routes instead of only CrudSurfaceController routes.')
            ->addOption('providers', null, InputOption::VALUE_NONE, 'Show registered surface provider keys.')
            ->addOption('templates', null, InputOption::VALUE_NONE, 'Show template candidates for every parsed route.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (true === $input->getOption('providers')) {
            $this->renderProviderKeys($output);
            $output->writeln('');
        }

        $rows = [];
        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if (true !== $input->getOption('all') && !$this->isSurfaceControllerRoute($route)) {
                continue;
            }

            $request = $this->requestForRoute($name, $route);
            $context = $this->routeShapeResolver->resolve($request);
            if (!$context instanceof CrudRouteContext) {
                $rows[] = [$name, $route->getPath(), 'unparsed', '-', '-', '-', '-'];
                continue;
            }

            $provider = $this->providerLocator->locate($context);
            $rows[] = [
                $name,
                $route->getPath(),
                $this->contextLabel($context),
                $context->primaryProviderKey(),
                null === $provider ? 'missing' : $provider::class,
                $this->genericRoute($route) ? 'generic' : 'concrete',
                true === $input->getOption('templates') ? implode("\n", $context->templateCandidates) : $context->templateCandidates[0],
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Route', 'Path', 'Parsed context', 'Provider key', 'Provider', 'Mode', 'Template']);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }

    private function renderProviderKeys(OutputInterface $output): void
    {
        $entries = $this->providerLocator->entries();
        if ([] === $entries) {
            $output->writeln('<comment>No cruding.surface_provider services are registered.</comment>');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Provider key', 'Provider class']);
        foreach ($entries as $key => $className) {
            $table->addRow([$key, $className]);
        }
        $table->render();
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
            'action' => 'briefing',
            default => str_ends_with($lower, 'slug') ? $this->sampleSlug($lower) : 'demo-'.$this->tokenize($variable),
        };
    }

    private function sampleSlug(string $lower): string
    {
        $stem = substr($lower, 0, -4);
        if ('' === $stem) {
            return 'demo-slug';
        }

        return 'demo-'.$this->tokenize($stem);
    }

    private function contextLabel(CrudRouteContext $context): string
    {
        $parts = [$context->resource];
        if (null !== $context->subjectValue) {
            $parts[] = '{'.$context->subjectIdentifierField().'}';
        }
        if (null !== $context->surfacePath) {
            $parts[] = $context->surfacePath;
        }
        if (null !== $context->itemValue) {
            $parts[] = '{'.$context->itemIdentifierField().'}';
        }
        $parts[] = $context->operation;

        return implode(' / ', $parts);
    }

    private function genericRoute(Route $route): bool
    {
        foreach (['resource', 'surface', 'action'] as $variable) {
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
