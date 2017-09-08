<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\DepositRepository;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Summarize the processing status for the deposits.
 */
class SummarizeDepositsCommand extends ContainerAwareCommand
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
        $this->setName('pln:summary');
        $this->setDescription('Summarize deposits.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var DepositRepository $repo */
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $summary = $repo->stateSummary();
        $count = 0;
        foreach ($summary as $row) {
            $output->writeln(sprintf('%6d - %s', $row['ct'], $row['state']));
            $count += $row['ct'];
        }
        $output->writeln(sprintf('%6d - total', $count));
    }
}
