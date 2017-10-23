<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Harvest a deposit from a provider. Attempts to check file sizes via HTTP HEAD
 * before downloading, and checks that there will be sufficient disk space.
 */
class HarvestCommand extends AbstractProcessingCmd
{
    /**
     * File sizes reported via HTTP HEAD must this close to to the file size
     * as reported in the deposit. Threshold = 0.02 is 2%.
     */
    const FILE_SIZE_THRESHOLD = 0.08;

    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var integer
     */
    private $maxAttempts;

    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->maxAttempts = $this->container->getParameter('max_harvest_attempts');
    }
    
    /**
     * Set the HTTP client, usually based on Guzzle.
     *
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get a configured client, creating one if one hasn't been set by
     * setClient().
     *
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
            $headers = $this->client->getDefaultOption('headers');
            $headers['User-Agent'] = 'PkpPlnBot 1.0; http://pkp.sfu.ca';
            $this->client->setDefaultOption('headers', $headers);
        }

        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:harvest');
        $this->setDescription('Harvest OJS deposits.');
        parent::configure();
    }

    /**
     * Write a deposit's data to the filesystem at $path. Returns true on
     * success and false on failure.
     *
     * @param string   $path
     * @param Response $response
     *
     * @return bool
     */
    protected function writeDeposit($path, Response $response)
    {
        $this->logger->info("Writing deposit to {$path}");
        try {
            $fh = fopen($path, 'wb');
            $body = $response->getBody();
            if (!$body) {
                throw new Exception('Response body was empty.');
            }
            // 64k chunks.
            while ($bytes = $body->read(64 * 1024)) {
                fwrite($fh, $bytes);
            }
            fclose($fh);
        } catch (Exception $ex) {
            $this->logger->error("Cannot write data to {$path}.");
            throw $ex;
        }

        return true;
    }

    /**
     * Fetch a deposit URL with Guzzle. Returns the data on success or false
     * on failure.
     *
     * @param string $url
     *
     * @return Response|false
     */
    protected function fetchDeposit($url, $expected)
    {
        $client = $this->getClient();
        try {
            $response = $client->get($url);
            $this->logger->info("Harvest - {$url} - HTTP {$response->getStatusCode()} - {$response->getHeader('Content-Length')}");
            if ($response->getStatusCode() !== 200) {
                $this->logger->error("Harvest - {$url} - HTTP {$response->getHttpStatus()} - {$url} - {$response->getError()}");
            }
        } catch (Exception $e) {
            $this->logger->error($e);
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode().' '.$e->getResponse()->getReasonPhrase());
            } else {
                $this->logger->error("Harvest - {$url} - {$e->getMessage()}");
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Send an HTTP HEAD request to get the deposit's host to get an estimate
     * of the download size.
     *
     * @param type $deposit
     *
     * @throws Exception
     */
    protected function checkSize(Deposit $deposit)
    {
        $client = $this->getClient();
        try {
            $head = $client->head($deposit->getUrl());
            if ($head->getStatusCode() !== 200) {
                throw new Exception("HTTP HEAD request cannot check file size: HTTP {$head->getStatusCode()} - {$head->getReasonPhrase()} - {$deposit->getUrl()}");
            }
            $reportedSize = $head->getHeader('Content-Length');
            if ($reportedSize === null || $reportedSize === '') {
                $message = "HTTP HEAD response does not include file size\n{$deposit->getUrl()}";
                $message .= "\n" . $head->getStatusCode() . " " . $head->getReasonPhrase() . " " . $head->getProtocolVersion();
                foreach($head->getHeaders() as $key => $value) {
                    $message .= "\n{$key}: " . implode('; ', $value);
                }
                throw new Exception($message);
            }
            
            $difference = abs($reportedSize - $deposit->getSize()) / max([$reportedSize, $deposit->getSize()]);
            if ($difference > self::FILE_SIZE_THRESHOLD) {
                $deposit->addErrorLog("Expected file size {$deposit->getSize()} is not close to reported size {$reportedSize}");
                $this->logger->warning("Harvest - {$deposit->getUrl()} - Expected file size {$deposit->getSize()} is not close to reported size {$reportedSize}");
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response !== null) {
                $this->logger->critical($e->getResponse()->getStatusCode().' '.$e->getResponse()->getReasonPhrase());
            } else {
                $this->logger->critical($e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Get an estimate of the file size for the deposits being processed. Throws
     * an exception if the harvest would exhaust available disk space.
     *
     * @param Deposit[] $deposits
     */
    protected function preprocessDeposits($deposits = array())
    {
        $harvestSize = 0;
        foreach ($deposits as $deposit) {
            $harvestSize += $deposit->getSize();
        }
        // deposits report their sizes in 1000-byte units.
        $harvestSize *= 1000;
        $this->logger->notice("Harvest expected to consume {$harvestSize} bytes.");
        $harvestPath = $this->filePaths->getHarvestDir();

        $remaining = (disk_free_space($harvestPath) - $harvestSize) / disk_total_space($harvestPath);
        if ($remaining < 0.10) {
            // less than 10% remaining
            $p = round($remaining * 100, 1);
            $this->logger->critical("Harvest - Harvest would leave less than {$p}% disk space remaining.");
            throw new Exception("Harvest would leave {$p}% disk space remaining.");
        }
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    protected function processDeposit(Deposit $deposit)
    {
        $this->logger->notice("harvest - {$deposit->getDepositUuid()}");
        if($deposit->getHarvestAttempts() > $this->maxAttempts) {
            $this->logger->notice("skipping - {$deposit->getDepositUuid()} - too many failed harvests.");
            return;
        }
        $deposit->setHarvestAttempts($deposit->getHarvestAttempts() + 1);
        $this->checkSize($deposit);
        $response = $this->fetchDeposit($deposit->getUrl(), $deposit->getSize());
        $deposit->setFileType($response->getHeader('Content-Type'));
        $filePath = $this->filePaths->getHarvestFile($deposit);

        return $this->writeDeposit($filePath, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'harvested';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'harvest-error';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'depositedByProvider';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Deposit harvest failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Deposit harvest succeeded.';
    }
}
