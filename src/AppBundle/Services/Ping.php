<?php

namespace AppBundle\Services;

use AppBundle\Entity\Provider;
use AppBundle\Utility\PingResult;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Monolog\Logger;

/**
 * Send a PING request to a provider, and return the result.
 */
class Ping
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * Set the ORM thing.
     *
     * @param Registry $registry
     */
    public function setManager(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * Set the service logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the HTTP client.
     *
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * Ping a provider, check on it's health, etc.
     *
     * @param Provider $provider
     *
     * @return PingResult
     *
     * @throws Exception
     */
    public function ping(Provider $provider)
    {
        $this->logger->notice("Pinging {$provider}");
        $url = $provider->getGatewayUrl();
        $client = $this->getClient();
        try {
            $response = $client->get($url, array(
                'allow_redirects' => true,
                'headers' => array(
                    'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
                    'Accept' => 'application/xml,text/xml,*/*;q=0.1',
                ),
            ));
            $pingResponse = new PingResult($response);
            if ($pingResponse->getHttpStatus() === 200) {
                $provider->setContacted(new DateTime());
                $provider->setTitle($pingResponse->getProviderTitle('(unknown title)'));
                $provider->setOjsVersion($pingResponse->getOjsRelease());
                $provider->setTermsAccepted($pingResponse->areTermsAccepted() === 'yes');
            } else {
                $provider->setStatus('ping-error');
            }
            $this->em->flush($provider);

            return $pingResponse;
        } catch (RequestException $e) {
            $provider->setStatus('ping-error');
            $this->em->flush($provider);
            if ($e->hasResponse()) {
                return new PingResult($e->getResponse());
            }
            throw $e;
        } catch (XmlParseException $e) {
            $provider->setStatus('ping-error');
            $this->em->flush($provider);

            return new PingResult($e->getResponse());
        } catch (Exception $e) {
            $provider->setStatus('ping-error');
            $this->em->flush($provider);
            throw $e;
        }
    }
}
