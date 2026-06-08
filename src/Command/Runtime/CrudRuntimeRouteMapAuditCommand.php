<?php

declare(strict_types=1);

namespace App\Cruding\Command\Runtime;

use App\Cruding\Service\Surface\CrudRouteMapMatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'crud:runtime:route-map-audit',
    description: 'Inspect host platform route-map entries loaded by Cruding.',
)]
final class CrudRuntimeRouteMapAuditCommand extends Command
{
    public function __construct(
        private readonly CrudRouteMapMatcher $matcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entries = $this->matcher->entryList();
        $missingResolver = 0;
        $missingService = 0;
        $missingParser = 0;

        foreach ($entries as $entry) {
            if (null === $entry->identifierResolver()) {
                ++$missingResolver;
            }
            if (null === $entry->service) {
                ++$missingService;
            }
            if (null === $entry->parser) {
                ++$missingParser;
            }
        }

        $output->writeln('<info>Cruding route-map audit</info>');
        $output->writeln('entries: '.count($entries));
        $output->writeln('without parser binding: '.$missingParser);
        $output->writeln('without resolver metadata: '.$missingResolver);
        $output->writeln('without service target: '.$missingService);

        if ([] === $entries) {
            $output->writeln('<comment>WARN</comment> No route-map entries found under config/platform/routes.');
        }

        return Command::SUCCESS;
    }
}
