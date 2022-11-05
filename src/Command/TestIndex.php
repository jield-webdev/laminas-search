<?php

declare(strict_types=1);

namespace Jield\Search\Command;

use Jield\Search\Service\ConsoleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TestIndex extends Command
{
    /** @var string */
    protected static $defaultName = 'search:test-index';

    public function __construct(private readonly ConsoleService $consoleService)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset index');

        $this->setName(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reset = $input->getOption('reset');

        $output->writeln('<info>Testing the search engine index</info>');

        $this->consoleService->testIndex(output: $output, clearIndex: $reset);

        $output->writeln('');
        $output->writeln('<info>Test the search engine index completed</info>');

        return Command::SUCCESS;
    }
}
