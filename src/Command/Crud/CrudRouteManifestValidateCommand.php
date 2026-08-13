<?php

declare(strict_types=1);

namespace App\Cruding\Command\Crud;

use App\Cruding\Routing\CrudingRouteManifest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'crud:route:validate', description: 'Validate the optional Cruding route manifest against live grammar.')]
final class CrudRouteManifestValidateCommand extends Command
{
    public function __construct(private readonly CrudingRouteManifest $manifest)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $manifestHash = $this->manifest->manifestHash();
        if (null === $manifestHash) {
            $output->writeln('<comment>Cruding route manifest is missing, empty, or unreadable.</comment>');
            $output->writeln('<comment>Symfony will use live Cruding route grammar.</comment>');

            return Command::SUCCESS;
        }

        $liveHash = $this->manifest->liveHash();
        if ($liveHash !== $manifestHash) {
            $output->writeln('<error>Cruding route manifest is stale.</error>');
            $output->writeln(sprintf('Manifest: %s', $manifestHash));
            $output->writeln(sprintf('Live:     %s', $liveHash));

            return Command::FAILURE;
        }

        $output->writeln('<info>Cruding route manifest is fresh.</info>');

        return Command::SUCCESS;
    }
}
