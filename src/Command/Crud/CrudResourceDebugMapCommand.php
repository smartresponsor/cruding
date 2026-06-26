<?php

declare(strict_types=1);

namespace App\Cruding\Command\Crud;

use App\Cruding\Controller\Crud\CrudResourceController;
use App\Cruding\Dto\Resource\CrudRouteContext;
use App\Cruding\Service\Crud\Resource\CrudResourceProviderLocator;
use App\Cruding\Service\Crud\Resource\CrudRouteShapeResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'crud:resource:debug-map', description: 'Display Cruding resource route parsing, provider keys, and diagnostic template hints.')]
final class CrudResourceDebugMapCommand extends Command
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CrudRouteShapeResolver $routeShapeResolver,
        private readonly CrudResourceProviderLocator $providerLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('all', null, InputOption::VALUE_NONE, 'Show all routes instead of only CrudResourceController routes.')
            ->addOption('providers', null, InputOption::VALUE_NONE, 'Show registered resource provider keys.')
            ->addOption('templates', null, InputOption::VALUE_NONE, 'Show diagnostic folder/index template hints for every parsed route.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (true === $input->getOption('providers')) {
            $this->renderProviderKeys($output);
            $output->writeln('');
        }

        $rows = [];
        foreach ($this->router->getRouteCollection()->all() as $nameEntity => $route) {
            if (true !== $input->getOption('all') && !$this->isResourceControllerRoute($route)) {
                continue;
            }

            $request = $this->requestForRoute($nameEntity, $route);
            $context = $this->routeShapeResolver->resolve($request);
            if (!$context instanceof CrudRouteContext) {
                $rows[] = [$nameEntity, $route->getPath(), 'unparsed', '-', '-', '-', '-'];
                continue;
            }

            $provider = $this->providerLocator->locate($context);
            $rows[] = [
                $nameEntity,
                $route->getPath(),
                $this->contextLabel($context),
                $context->primaryProviderKey(),
                null === $provider ? 'missing' : $provider::class,
                $this->genericRoute($route) ? 'generic' : 'concrete',
                true === $input->getOption('templates') ? implode("\n", $context->templateCandidates) : $context->templateCandidates[0],
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Route', 'Path', 'Parsed context', 'Provider key', 'Provider', 'Mode', 'Template hint']);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }

    private function renderProviderKeys(OutputInterface $output): void
    {
        $entries = $this->providerLocator->entries();
        if ([] === $entries) {
            $output->writeln('<comment>No resource provider services are registered.</comment>');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Provider key', 'Provider class']);
        foreach ($entries as $key => $className) {
            $table->addRow([$key, $className]);
        }
        $table->render();
    }

    private function isResourceControllerRoute(Route $route): bool
    {
        $controller = $route->getDefault('_controller');
        if (!is_string($controller)) {
            return false;
        }

        return CrudResourceController::class === $controller || str_starts_with($controller, CrudResourceController::class.'::');
    }

    private function requestForRoute(string $nameEntity, Route $route): Request
    {
        $path = $route->getPath();
        $attributes = ['_route' => $nameEntity];

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
            'resource' => 'alpha',
            'subject' => 'sample-subject',
            'view' => 'compliance',
            'item' => 'sample-item',
            'token', 'ViewToken', 'widgetToken' => 'show',
            'action' => 'briefing',
            default => str_ends_with($lower, 'slug') ? $this->sampleSlug($lower) : 'demo-'.$this->tokenize($variable),
        };
    }

    private function sampleSlug(string $lower): string
    {
        $stem = substr($lower, 0, -4);

        return '' === $stem ? 'demo-slug' : 'demo-'.$this->tokenize($stem);
    }

    private function contextLabel(CrudRouteContext $context): string
    {
        $parts = [$context->resource];
        if (null !== $context->subjectValue) {
            $parts[] = '{'.$context->subjectIdentifierField().'}';
        }
        if (null !== $context->viewPath) {
            $parts[] = $context->viewPath;
        }
        if (null !== $context->ViewToken) {
            $parts[] = $context->ViewToken;
        }
        if (null !== $context->itemValue) {
            $parts[] = '{'.$context->itemIdentifierField().'}';
        }
        $parts[] = $context->operation;

        return implode(' / ', $parts);
    }

    private function genericRoute(Route $route): bool
    {
        foreach (['resource', 'view', 'token', 'action'] as $variable) {
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
