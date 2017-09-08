<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\DepositRepository;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * List all deposits in a particular state.
 */
class ListDepositsCommand extends ContainerAwareCommand
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
        $this->setName('pln:list');
        $this->setDescription('List deposits.');
        $this->addArgument(
            'state',
            InputArgument::REQUIRED,
            'List deposits in this state'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var DepositRepository $repo */
        $repo = $this->em->getRepository('AppBundle:Deposit');

        $state = $input->getArgument('state');
        $deposits = $repo->findBy(array('state' => $state));
        foreach ($deposits as $deposit) {
            $output->writeln("{$deposit->getInstitution()->getUuid()}/{$deposit->getDepositUuid()}");
        }
    }
}
