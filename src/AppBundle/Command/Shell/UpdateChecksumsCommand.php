<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositRepository;
use AppBundle\Services\FilePaths;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Update the checksums for the deposits. This shouldn't be necessary, but
 * was useful during development.
 *
 * This isn't particularly useful once a deposit has been sent to LOCKSS, unless
 * the deposit's status is reset to reserialized or something like it.
 */
class UpdateChecksumsCommand extends ContainerAwareCommand
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
        $this->logger = $container->get('logger');
        $this->em = $container->get('doctrine')->getManager();
        $this->filePaths = $container->get('filepaths');
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pln:update-checksums');
        $this->setDescription('Update checksums.');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update checksum.'
        );
    }

    /**
     * Get the checksum for a harvested deposit file.
     *
     * @param Deposit $deposit
     *
     * @return string
     */
    private function getChecksum(Deposit $deposit)
    {
        $filePath = $this->filePaths->getHarvestFile($deposit);
        switch (strtoupper($deposit->getChecksumType())) {
            case 'SHA-1':
            case 'SHA1':
                return sha1_file($filePath);
            case 'MD5':
                return md5_file($filePath);
            default:
                $this->logger->error("Deposit checksum type {$deposit->getChecksumType()} unknown.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var DepositRepository $repo */
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $deposits = $repo->findAll();
        foreach ($deposits as $deposit) {
            $this->logger->notice("{$deposit->getDepositUuid()}");
            $checksum = strtoupper($this->getChecksum($deposit));
            if ($checksum !== $deposit->getChecksumValue()) {
                $this->logger->warning("Updating checksum for {$deposit->getDepositUuid()}");
                $deposit->setChecksumValue($checksum);
            }
        }
        if (!$input->getOption('dry-run')) {
            $this->em->flush();
        }
    }
}
