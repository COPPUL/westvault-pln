<?php

namespace AppBundle\Command\Shell;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run all the processing commands in order.
 */
class RunAllCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:run-all');
        $this->setDescription('Run all processing commands.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the processing state to be updated'
        );
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Only process $limit deposits.'
        );
        parent::configure();
    }

    /**
     * List of commands to run.
     *
     * @return string[]
     */
    private static function commandList()
    {
        return array(
            'pln:harvest',
            'pln:validate-payload',
            'pln:virus-scan',
            'pln:deposit',
            'pln:status',
        );
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (self::commandList() as $cmd) {
            $output->writeln("Running {$cmd}");
            $command = $this->getApplication()->find($cmd);
            $command->run($input, $output);
        }
    }
}
