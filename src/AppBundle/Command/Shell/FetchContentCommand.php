<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Institution;
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
 * Fetch all the content of one or more institutions from LOCKSS via LOCKSSOMatic.
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
        $this->setDescription('Download the archived content for one or more institutions.');
        $this->addArgument('institutions', InputArgument::IS_ARRAY, 'The database ID of one or more institutions.');
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
     * Fetch one deposit from LOCKSSOMatic.
     *
     * @param Deposit $deposit
     * @param string  $href
     */
    public function fetch(Deposit $deposit, $href)
    {
        $client = $this->getHttpClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getInstitution()).'/'.basename($href);
        $this->logger->notice("Saving {$deposit->getInstitution()->getTitle()} vol. {$deposit->getVolume()} no. {$deposit->getIssue()} to {$filepath}");
        try {
            $client->get($href, array(
                'allow_redirects' => false,
                'decode_content' => false,
                'save_to' => $filepath,
            ));
            $hash = strtoupper(hash_file($deposit->getPackageChecksumType(), $filepath));
            if ($hash !== $deposit->getPackageChecksumValue()) {
                $this->logger->warning("Package checksum failed. Expected {$deposit->getPackageChecksumValue()} but got {$hash}");
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    /**
     * Download all the content from one institution.
     *
     * Requests a SWORD deposit statement from LOCKSSOMatic, and uses the
     * sword:originalDeposit element to fetch the content.
     *
     * @param Institution $institution
     */
    public function downloadInstitution(Institution $institution)
    {
        foreach ($institution->getDeposits() as $deposit) {
            $statement = $this->swordClient->statement($deposit);
            $originals = $statement->xpath('//sword:originalDeposit');

            foreach ($originals as $element) {
                $this->fetch($deposit, $element['href']);
            }
        }
    }

    /**
     * Get a list of institutions to download.
     *
     * @param array $institutionIds
     *
     * @return Collection|Institution[]
     */
    public function getInstitutions($institutionIds)
    {
        return $this->em->getRepository('AppBundle:Institution')->findBy(array('id' => $institutionIds));
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $institutionIds = $input->getArgument('institutions');
        $institutions = $this->getInstitutions($institutionIds);
        foreach ($institutions as $institution) {
            $this->downloadInstitution($institution);
        }
    }
}
