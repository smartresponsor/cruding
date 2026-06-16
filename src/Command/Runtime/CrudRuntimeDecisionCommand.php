<?php

declare(strict_types=1);

namespace App\Cruding\Command\Runtime;

use App\Cruding\Service\Crud\Runtime\CrudRuntimeDecisionGuard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'crud:runtime:decision',
    description: 'Validate Cruding runtime decision against env, composer inventory, and runtime scope lock files.',
)]
final class CrudRuntimeDecisionCommand extends Command
{
    public function __construct(
        private readonly CrudRuntimeDecisionGuard $decisionGuard,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->decisionGuard->report();
        $policy = $report->routePolicy;
        $lock = $report->runtimeLock;
        $composer = $report->composerInventory;

        $output->writeln('<info>Cruding runtime decision</info>');
        $output->writeln('app env: '.$lock->appEnv);
        $output->writeln('project dir: '.$composer->projectDir);
        $output->writeln('runtime lock: '.($lock->found ? $lock->path : '<none>'));
        $output->writeln('composer.json: '.($composer->composerJsonPath ?? '<none>'));
        $output->writeln('composer.lock: '.($composer->composerLockPath ?? '<none>'));

        $this->writeList($output, 'env runtime scope', $policy->scopeTokens);
        $this->writeList($output, 'env runtime entity', $policy->entityTokens);
        $this->writeList($output, 'allowed resource', $policy->allowedResourceTokens);
        $this->writeList($output, 'lock runtime scope', $lock->scopeTokens);
        $this->writeList($output, 'lock runtime entity', $lock->entityTokens);
        $this->writeList($output, 'lock packages', $lock->packageNames);
        $this->writeList($output, 'composer declared packages', $composer->declaredPackageNames);
        $this->writeList($output, 'composer installed packages', $composer->installedPackageNames);

        foreach ($report->warnings as $warning) {
            $output->writeln('<comment>WARN</comment> '.$warning);
        }

        foreach ($report->errors as $error) {
            $output->writeln('<error>ERROR</error> '.$error);
        }

        if (!$report->passed()) {
            return Command::FAILURE;
        }

        $output->writeln('<info>Cruding runtime decision passed.</info>');

        return Command::SUCCESS;
    }

    /** @param list<string> $values */
    private function writeList(OutputInterface $output, string $label, array $values): void
    {
        $output->writeln(sprintf('%s: %s', $label, [] === $values ? '<none>' : implode(', ', $values)));
    }
}
