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
        parent::__construct(name: self::$defaultName);
    }

    protected function configure(): void
    {
        $this->setName(name: self::$defaultName);
        $this->addOption(name: 'reset', shortcut: 'r', mode: InputOption::VALUE_NONE, description: 'Reset index');

        $cores = implode(separator: ', ', array: array_merge(array_keys(array: $this->consoleService->getCores()), ['all']));

        $this->addArgument(
            name: 'index',
            mode: InputOption::VALUE_REQUIRED,
            description: $cores,
            default: 'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $input->getArgument(name: 'index');
        $reset = $input->getOption(name: 'reset');

        $startMessage = sprintf("<info>%s the index of %s</info>", $reset ? 'Reset' : 'Update', $index);
        $endMessage = sprintf("<info>%s the index of %s completed</info>", $reset ? 'Reset' : 'Update', $index);

        $output->writeln(messages: $startMessage);

        $this->consoleService->resetIndex(output: $output, index: $index, clearIndex: $reset);

        $output->writeln(messages: $endMessage);

        return Command::SUCCESS;
    }
}
