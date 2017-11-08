<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\FilePaths;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Commands that process the deposits should extend this class, which
 * provides common functions for updating status, and will automatically
 * fetch the deposits needing to be processed.
 */
abstract class AbstractProcessingCmd extends ContainerAwareCommand
{
    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $depositDir;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->container = $container;
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
        $this->filePaths = $container->get('filepaths');
        $this->fs = new Filesystem();
    }

    /**
     * Set the command-line options for the processing commands.
     */
    protected function configure()
    {
        $this->addOption(
            'retry',
            'r',
            InputOption::VALUE_NONE,
            'Retry failed deposits'
        );
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update processing status'
        );
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Only process $limit deposits.'
        );
        $this->addArgument('deposit-id', InputArgument::IS_ARRAY, 'One or more deposit database IDs to process');
    }

    /**
     * Preprocess the list of deposits.
     *
     * @param Deposit[] $deposits
     */
    protected function preprocessDeposits($deposits = array())
    {
        // do nothing by default.
    }

    /**
     * Process one deposit return true on success and false on failure.
     *
     * @param Deposit $deposit
     *
     * @return bool
     */
    abstract protected function processDeposit(Deposit $deposit);

    /**
     * Deposits in this state will be processed by the commands.
     */
    abstract public function processingState();

    /**
     * Successfully processed deposits will be given this state.
     */
    abstract public function nextState();

    /**
     * Deposits which generate errors will be given this state.
     */
    abstract public function errorState();

    /**
     * Successfully processed deposits will be given this log message.
     */
    abstract public function successLogMessage();

    /**
     * Failed deposits will be given this log message.
     */
    abstract public function failureLogMessage();

    /**
     * Code to run before executing the command.
     */
    protected function preExecute()
    {
        // do nothing, let subclasses override if needed.
    }

    /**
     * @param bool  $retry      retry failed deposits
     * @param int[] $depositIds zero or more deposit Ids to filter
     *
     * @return Deposit[]
     */
    final public function getDeposits($retry = false, $depositIds = array())
    {
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $state = $this->processingState();
        if ($retry) {
            $state = $this->errorState();
        }
        $query = array('state' => $state);
        if (count($depositIds) > 0) {
            $query['id'] = $depositIds;
        }

        return $repo->findBy($query);
    }

    /**
     * Execute the command. Get all the deposits needing to be harvested. Each
     * deposit will be passed to the commands processDeposit() function.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute();
        $deposits = $this->getDeposits(
            $input->getOption('retry'),
            $input->getArgument('deposit-id')
        );

        if ($input->hasOption('limit')) {
            $deposits = array_slice($deposits, 0, $input->getOption('limit'));
        }

        $count = count($deposits);

        $this->logger->info("Processing {$count} deposits.");
        $this->preprocessDeposits($deposits);

        foreach ($deposits as $deposit) {
            try {
                $result = $this->processDeposit($deposit);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $deposit->setState($this->errorState());
                $deposit->addToProcessingLog($this->failureLogMessage());
                $deposit->addErrorLog(get_class($e).$e->getMessage());
                $this->em->flush($deposit);
                continue;
            }

            if ($input->getOption('dry-run')) {
                continue;
            }

            if ($result === true) {
                $deposit->setState($this->nextState());
                $deposit->addToProcessingLog($this->successLogMessage());
            } elseif($result === false) {
                $deposit->setState($this->errorState());
                $deposit->addToProcessingLog($this->failureLogMessage());
            } elseif($result === null) {
                // dunno, do nothing I guess.
            }
            $this->em->flush($deposit);
        }
    }
}
