<?php

declare(strict_types=1);

namespace App\Cruding\Command\Runtime;

use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'crud:runtime:route-match-smoke',
    description: 'Smoke-test Cruding runtime route requirements against Symfony router matching.',
)]
final class CrudRuntimeRouteMatchSmokeCommand extends Command
{
    private const DEFAULT_RESERVED_PATHS = [
        '/admin',
        '/login',
        '/logout',
        '/profile',
        '/dashboard',
        '/viewing',
        '/interfacing',
        '/accessing',
        '/administering',
        '/cruding',
        '/api/admin',
        '/api/login',
        '/api/viewing',
        '/api/interfacing',
    ];

    public function __construct(
        private readonly CrudRuntimeRouteGuard $routeGuard,
        private readonly RouterInterface $router,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'reserved-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Reserved path that must not match a Cruding route. Can be repeated.',
            )
            ->addOption(
                'cruding-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path that must match a Cruding route. Can be repeated.',
            )
            ->addOption(
                'skip-defaults',
                null,
                InputOption::VALUE_NONE,
                'Do not add default reserved/entity/surface smoke paths.',
            )
            ->addOption(
                'fail-on-empty-entity',
                null,
                InputOption::VALUE_NONE,
                'Fail when APP_RUNTIME_ENTITY produced no allowed resource tokens.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $policy = $this->routeGuard->policy();
        $errors = [];

        $reservedPaths = $this->stringList($input->getOption('reserved-path'));
        $crudingPaths = $this->stringList($input->getOption('cruding-path'));
        $skipDefaults = (bool) $input->getOption('skip-defaults');

        if (!$skipDefaults) {
            $reservedPaths = array_values(array_unique([...self::DEFAULT_RESERVED_PATHS, ...$reservedPaths]));
            $crudingPaths = array_values(array_unique([...$this->defaultCrudingPaths($policy->allowedResourceTokens, $policy->surfaceTokens), ...$crudingPaths]));
        }

        $output->writeln('<info>Cruding runtime route match smoke</info>');
        $this->writeList($output, 'allowed resource', $policy->allowedResourceTokens);
        $this->writeList($output, 'reserved smoke paths', $reservedPaths);
        $this->writeList($output, 'cruding smoke paths', $crudingPaths);

        if ((bool) $input->getOption('fail-on-empty-entity') && [] === $policy->allowedResourceTokens) {
            $errors[] = 'APP_RUNTIME_ENTITY produced no allowed resource tokens.';
        }

        foreach ($reservedPaths as $path) {
            $match = $this->match($path);
            if (null === $match) {
                $output->writeln(sprintf('<info>OK</info> reserved path %-40s did not match any route', $path));
                continue;
            }

            $routeName = $this->routeName($match);
            if (!$this->isCrudingRoute($routeName)) {
                $output->writeln(sprintf('<info>OK</info> reserved path %-40s matched non-Cruding route %s', $path, $routeName));
                continue;
            }

            $errors[] = sprintf('Reserved path "%s" matched Cruding route "%s".', $path, $routeName);
            $output->writeln(sprintf('<error>FAIL</error> reserved path %-40s matched Cruding route %s', $path, $routeName));
        }

        foreach ($crudingPaths as $path) {
            $match = $this->match($path);
            if (null === $match) {
                $errors[] = sprintf('Expected Cruding path "%s" did not match any route.', $path);
                $output->writeln(sprintf('<error>FAIL</error> Cruding path %-40s did not match any route', $path));
                continue;
            }

            $routeName = $this->routeName($match);
            if ($this->isCrudingRoute($routeName)) {
                $output->writeln(sprintf('<info>OK</info> Cruding path %-40s matched %s', $path, $routeName));
                continue;
            }

            $errors[] = sprintf('Expected Cruding path "%s" matched non-Cruding route "%s".', $path, $routeName);
            $output->writeln(sprintf('<error>FAIL</error> Cruding path %-40s matched non-Cruding route %s', $path, $routeName));
        }

        if ([] !== $errors) {
            $output->writeln('');
            foreach ($errors as $error) {
                $output->writeln('<error>ERROR</error> '.$error);
            }

            return Command::FAILURE;
        }

        $output->writeln('<info>Cruding runtime route match smoke passed.</info>');

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $allowedResourceTokens
     * @param list<string> $surfaceTokens
     *
     * @return list<string>
     */
    private function defaultCrudingPaths(array $allowedResourceTokens, array $surfaceTokens): array
    {
        if ([] === $allowedResourceTokens) {
            return [];
        }

        $resource = $allowedResourceTokens[0];
        $surfaceToken = $surfaceTokens[0] ?? 'show';

        return [
            '/'.$resource,
            '/'.$resource.'/',
            '/api/'.$resource,
            '/api/'.$resource.'/',
            '/'.$resource.'/attachment/media/'.$surfaceToken.'/123',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function match(string $path): ?array
    {
        try {
            return $this->router->match($path);
        } catch (ResourceNotFoundException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $match
     */
    private function routeName(array $match): string
    {
        $route = $match['_route'] ?? '<unknown>';

        return is_string($route) ? $route : '<unknown>';
    }

    private function isCrudingRoute(string $routeName): bool
    {
        return str_starts_with($routeName, 'cruding_');
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];
        foreach ($value as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $strings[] = trim($item);
            }
        }

        return $strings;
    }

    /**
     * @param list<string> $values
     */
    private function writeList(OutputInterface $output, string $label, array $values): void
    {
        $output->writeln(sprintf('%s: %s', $label, [] === $values ? '<none>' : implode(', ', $values)));
    }
}
