<?php

declare(strict_types=1);

namespace App\Cruding\Command\Runtime;

use App\Cruding\Service\Crud\Runtime\CrudRuntimeRouteGuard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'crud:runtime:route-guard',
    description: 'Validate Cruding runtime scope/entity route guard policy.',
)]
final class CrudRuntimeRouteGuardCommand extends Command
{
    public function __construct(
        private readonly CrudRuntimeRouteGuard $routeGuard,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $policy = $this->routeGuard->policy();

        $output->writeln('<info>Cruding runtime route guard</info>');
        $this->writeList($output, 'runtime scope', $policy->scopeTokens);
        $this->writeList($output, 'runtime entity', $policy->entityTokens);
        $this->writeList($output, 'reserved root', $policy->reservedRootTokens);
        $this->writeList($output, 'allowed resource', $policy->allowedResourceTokens);
        $this->writeList($output, 'surface token', $policy->surfaceTokens);
        $output->writeln('resource requirement: '.$policy->resourceRequirement);
        $output->writeln('resource path requirement: '.$policy->resourcePathRequirement);
        $output->writeln('surface token requirement: '.$policy->surfaceTokenRequirement);

        if ($policy->hasConflicts()) {
            foreach ($policy->conflictingEntityTokens as $token) {
                $output->writeln(sprintf('<error>ERROR</error> Runtime entity token "%s" conflicts with reserved runtime/root token.', $token));
            }

            return Command::FAILURE;
        }

        if ([] === $policy->allowedResourceTokens) {
            $output->writeln('<comment>WARN</comment> No allowed APP_RUNTIME_ENTITY tokens are configured; root Cruding routes will not match business resources.');
        }

        $output->writeln('<info>Cruding runtime route guard passed.</info>');

        return Command::SUCCESS;
    }

    /** @param list<string> $values */
    private function writeList(OutputInterface $output, string $label, array $values): void
    {
        $output->writeln(sprintf('%s: %s', $label, [] === $values ? '<none>' : implode(', ', $values)));
    }
}
