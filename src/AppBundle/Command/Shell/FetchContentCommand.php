<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Provider;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Exception;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fetch all the content of one or more providers from LOCKSS via LOCKSSOMatic.
 */
class FetchContentCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * @var SwordClient
     */
    private $swordClient;

    /**
     * @var HttpClient
     */
    private $httpClient;

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
        $this->filePaths = $container->get('filepaths');
        $this->swordClient = $container->get('sword_client');
        $this->fs = new Filesystem();
    }

    /**
     * Configure the command.
     */
    public function configure()
    {
        $this->setName('pln:fetch');
        $this->setDescription('Download the archived content for one or more providers.');
        $this->addArgument('providers', InputArgument::IS_ARRAY, 'The database ID of one or more providers.');
    }

    /**
     * Set the HTTP client for contacting LOCKSSOMatic.
     *
     * @param Client $httpClient
     */
    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Build and configure and return an HTTP client. Uses the client set
     * from setHttpClient() if available.
     *
     * @return Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * Download all the content from one provider.
     *
     * Requests a SWORD deposit statement from LOCKSSOMatic, and uses the
     * sword:originalDeposit element to fetch the content.
     *
     * @param Provider $provider
     */
    public function downloadProvider(Provider $provider)
    {
        foreach ($provider->getDeposits() as $deposit) {
            $this->swordClient->fetch($deposit);
        }
    }

    /**
     * Get a list of providers to download.
     *
     * @param array $providerIds
     *
     * @return Collection|Provider[]
     */
    public function getProviders($providerIds)
    {
        return $this->em->getRepository('AppBundle:Provider')->findBy(array('id' => $providerIds));
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $providerIds = $input->getArgument('providers');
        $providers = $this->getProviders($providerIds);
        foreach ($providers as $provider) {
            $this->downloadProvider($provider);
        }
    }
}
