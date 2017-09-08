<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use Doctrine\Common\Collections\ArrayCollection;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Reset the processing status for one or more deposit.
 */
class ResetDepositCommand extends ContainerAwareCommand
{
    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pln:reset');
        $this->setDescription('Reset deposits.');
        $this->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear error log and processing log for deposit.');
        $this->addArgument(
            'state',
            InputArgument::REQUIRED,
            'New state for the deposit(s)'
        );
        $this->addArgument(
            'deposit',
            InputArgument::IS_ARRAY,
            'Deposit UUID(s) to process'
        );
    }

    /**
     * @return Deposit[]|ArrayCollection
     */
    protected function getDeposits($uuids = array())
    {
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $deposits = array();
        if (count($uuids) > 0) {
            $deposits = $repo->findBy(array('depositUuid' => $uuids));
        } else {
            $deposits = $repo->findAll();
        }

        return $deposits;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $input->getArgument('state');
        $uuids = $input->getArgument('deposit');
        $deposits = $this->getDeposits($uuids);
        foreach ($deposits as $deposit) {
            $this->logger->notice("Setting {$deposit->getDepositUuid()} to {$state}");
            $deposit->setState($state);
            if ($input->getOption('clear')) {
                $deposit->setErrorLog(array());
                $deposit->setPackageSize(null);
                $deposit->setPlnState(null);
                $deposit->setProcessingLog('');
                $deposit->addToProcessingLog('Deposit reset.');
                $deposit->setAuContainer(null);
            }
        }
        $this->em->flush();
    }
}
