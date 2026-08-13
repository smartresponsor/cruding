<?php

declare(strict_types=1);

namespace App\Cruding\Command\Crud;

use App\Cruding\Routing\CrudingRouteManifest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'crud:route:dump', description: 'Dump the optional Cruding route manifest into var/cruding.')]
final class CrudRouteManifestDumpCommand extends Command
{
    public function __construct(private readonly CrudingRouteManifest $manifest)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $this->manifest->dump();

        $output->writeln(sprintf('<info>Cruding route manifest dumped:</info> %s', $this->manifest->path()));

        return Command::SUCCESS;
    }
}
