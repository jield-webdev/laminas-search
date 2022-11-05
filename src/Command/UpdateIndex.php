<?php

declare(strict_types=1);

namespace Jield\Search\Command;

use Jield\Search\Service\ConsoleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

final class UpdateIndex extends Command
{
    /** @var string */
    protected static $defaultName = 'search:update-index';

    public function __construct(private readonly ConsoleService $consoleService)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset index');

        $cores = implode(', ', array_merge(array_keys($this->consoleService->getCores()), ['all']));

        $this->addArgument(
            'index',
            InputOption::VALUE_REQUIRED,
            $cores,
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $input->getArgument('index');
        $reset = $input->getOption('reset');

        $startMessage = sprintf("<info>%s the index of %s</info>", $reset ? 'Reset' : 'Update', $index);
        $endMessage = sprintf("<info>%s the index of %s completed</info>", $reset ? 'Reset' : 'Update', $index);

        $output->writeln($startMessage);

        $this->consoleService->resetIndex(output: $output, index: $index, clearIndex: $reset);

        $output->writeln($endMessage);

        return Command::SUCCESS;
    }
}
