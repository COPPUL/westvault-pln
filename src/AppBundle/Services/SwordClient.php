<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Provider;
use AppBundle\Utility\Namespaces;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Stream\Stream;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Bespoke sword client, for use with LOCKSSOMatic.
 */
class SwordClient {

    /**
     * IRI for the service document.
     *
     * @var string
     */
    private $sdIri;

    /**
     * The UUID for the LOCKSSOMatic server.
     *
     * @var string
     */
    private $serverUuid;

    /**
     * Maximum upload file size, as reported by the service document.
     *
     * @var int
     */
    private $maxUpload;

    /**
     * Checksum of the deposit package.
     *
     * @var string
     */
    private $uploadChecksum;

    /**
     * Name of the site, as reported by the service document.
     *
     * @var string
     */
    private $siteName;

    /**
     * The collectin IRI, as reported by the service document.
     *
     * @var string
     */
    private $colIri;

    /**
     * Mapping of prefix => URIs for XML namespaces.
     *
     * @var Namespaces
     */
    private $namespaces;

    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * All PKP PLN providers are given the same title in LOCKSS/LOCKSSOMatic to
     * enable use of the LOCKSS subscription manager.
     *
     * @var type
     */
    private $plnProviderTitle;

    /**
     * @var FilePaths
     */
    private $filePaths;

    /**
     * If true, save the deposit XML in a file in the same directory as the
     * serialized deposit bag.
     *
     * @var bool
     */
    private $saveDepositXml;

    /**
     * Construct a sword client.
     *
     * @param string $sdIri
     * @param string $serverUuid
     * @param bool   $saveDepositXml
     */
    public function __construct($sdIri, $serverUuid, $saveDepositXml) {
        $this->sdIri = $sdIri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
        $this->namespaces = new Namespaces();
        $this->saveDepositXml = $saveDepositXml;
    }

    /**
     * Set the HTTP client.
     *
     * @param Client $client
     */
    public function setClient(Client $client) {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient() {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * Set the PLN Provider Title.
     *
     * @param string $plnProviderTitle
     */
    public function setPlnProviderTitle($plnProviderTitle) {
        $this->plnProviderTitle = $plnProviderTitle;
    }

    /**
     * Set the logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the templating engine.
     *
     * @param TwigEngine $templating
     */
    public function setTemplating(TwigEngine $templating) {
        $this->templating = $templating;
    }

    /**
     * Set the router for the PLN.
     *
     * @param Router $router
     */
    public function setRouter(Router $router) {
        $this->router = $router;
    }

    /**
     * Set the FilePaths service.
     *
     * @param FilePaths $filePaths
     */
    public function setFilePaths(FilePaths $filePaths) {
        $this->filePaths = $filePaths;
    }

    /**
     * Convenience method to log a message.
     *
     * @param string $message
     * @param array  $context
     * @param string $level
     */
    private function log($message, $context = array(), $level = 'info') {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Fetch the service document by HTTP.
     *
     * @param Provider $provider
     *
     * @throws RequestException
     */
    public function serviceDocument(Provider $provider) {
        $client = $this->getClient();
        $headers = array(
            'On-Behalf-Of' => $this->serverUuid,
            'Provider-Name' => $provider->getName(),
        );
        try {
            $response = $client->get($this->sdIri, ['headers' => $headers]);
        } catch (RequestException $e) {
            $this->logger->critical($e->getMessage());
            if ($e->hasResponse()) {
                $xml = $e->getResponse()->xml();
                $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $this->logger->critical((string) $xml->xpath('//atom:summary')[0]);
            }
            throw $e;
        }
        $xml = new SimpleXMLElement($response->getBody());
        $this->namespaces->registerNamespaces($xml);
        $this->maxUpload = (string) ($xml->xpath('sword:maxUploadSize')[0]);
        $this->uploadChecksum = (string) ($xml->xpath('lom:uploadChecksumType')[0]);
        $this->siteName = (string) ($xml->xpath('.//atom:title')[0]);
        $this->colIri = (string) ($xml->xpath('.//app:collection/@href')[0]);
    }

    /**
     * Send a deposit to LOM via HTTP.
     *
     * @param Deposit $deposit
     *
     * @return bool true on success
     */
    public function createDeposit(Deposit $deposit) {
        $this->serviceDocument($deposit->getProvider());
        $xml = $this->templating->render('AppBundle:SwordClient:deposit.xml.twig', array(
            'title' => 'Deposit from WestVault part ' . $deposit->getAuContainer()->getId(),
            'publisher' => 'WestVault Staging Server',
            'deposit' => $deposit,
            'baseUri' => $this->router->generate('home', array(), UrlGeneratorInterface::ABSOLUTE_URL),
            'plnProviderTitle' => $this->plnProviderTitle,
        ));
        if ($this->saveDepositXml) {
            $atomPath = $this->filePaths->getHarvestDir($deposit->getProvider()) . '/' . $deposit->getDepositUuid() . '.xml';
            file_put_contents($atomPath, $xml);
        }
        try {
            $client = $this->getClient();
            $request = $client->createRequest('POST', $this->colIri);
            $request->setBody(Stream::factory($xml));
            $response = $client->send($request);
            $responseXml = new SimpleXMLElement($response->getBody());
        } catch (RequestException $e) {
            $this->logger->critical($e->getMessage());
            if ($e->hasResponse()) {
                $xml = $e->getResponse()->xml();
                $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $xml->registerXPathNamespace('sword', 'http://purl.org/net/sword/');
                $this->logger->critical('Summary: ' . (string) $xml->xpath('//atom:summary')[0]);
                $this->logger->warning('Detail: ' . (string) $xml->xpath('//sword:verboseDescription')[0]);
            }

            return false;
        } catch (Exception $e) {
            $this->logger->critical("Error parsing deposit response from server: {$e->getMessage()}");

            return false;
        }

        $deposit->setDepositReceipt($response->getHeader('Location'));
        $deposit->setDepositDate(new DateTime());
        // TODO should I do something wtih responseXML here?
        $this->namespaces->registerNamespaces($responseXml);

        return true;
    }

    public function receipt(Deposit $deposit) {
        $client = $this->getClient();
        $receiptRequest = $client->createRequest('GET', $deposit->getDepositReceipt());
        $receiptResponse = $client->send($receiptRequest);
        $receiptXml = new SimpleXMLElement($receiptResponse->getBody());
        $this->namespaces->registerNamespaces($receiptXml);

        return $receiptXml;
    }

    /**
     * Fetch the SWORD statement.
     *
     * @todo complete this stub
     *
     * @param Deposit $deposit
     *
     * @return \SimpleXMLElement
     */
    public function statement(Deposit $deposit) {
        $receipt = $this->receipt($deposit);
        $statementUrl = $receipt->xpath('atom:link[@rel="http://purl.org/net/sword/terms/statement"]/@href')[0];
        $client = $this->getClient();
        $statementRequest = $client->createRequest('GET', $statementUrl);
        $statementResponse = $client->send($statementRequest);
        $statementXml = new \SimpleXMLElement($statementResponse->getBody());
        $this->namespaces->registerNamespaces($statementXml);

        return $statementXml;
    }

    protected function hashFile($algorithm, $filepath) {
        $handle = fopen($filepath, "r");
        $context = hash_init($algorithm);
        while(($data = fread($handle, 64 * 1024))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle); 
        return $hash;
    }
    
    public function fetch(Deposit $deposit) {
        $statement = $this->statement($deposit);
        $originals = $statement->xpath('//sword:originalDeposit');
        if (count($originals) > 1) {
            throw new Exception("Deposits with multiple content URLs are not supported.");
        }
        $element = $originals[0];        
        $href = $element['href'];        
        $client = $this->getClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getProvider()) . '/' . $deposit->getDepositUuid();
        try {
            $response = $client->get($href, [
                'save_to' => $filepath,
            ]);
        } catch(\Exception $e) {
            $this->logger->critical("Cannot download content from {$href}: {$e->getMessage()}");
            return null;
        }
        $hash = $this->hashFile($deposit->getChecksumType(), $filepath);
        if ($hash !== $deposit->getChecksumValue()) {
            $this->logger->warning("Package checksum failed. Expected {$deposit->getChecksumValue()} but got {$hash}");
        }
        $this->logger->critical("Saved to {$filepath}");
        return $filepath;
    }

    /**
     * Get the site name, as used in deposits.
     *
     * @return string
     */
    public function getSiteName() {
        return $this->siteName;
    }

    /**
     * Get the collection IRI.
     *
     * @return type
     */
    public function getColIri() {
        return $this->colIri;
    }

    /**
     * Get the maximum upload size.
     *
     * @return int
     */
    public function getMaxUpload() {
        return $this->maxUpload;
    }

    /**
     * Get the checksum type expected by LOCKSSOMatic for uploads.
     *
     * @return string
     */
    public function getUploadChecksum() {
        return $this->uploadChecksum;
    }

}
