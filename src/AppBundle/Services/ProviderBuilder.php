<?php

namespace AppBundle\Services;

use AppBundle\Entity\Provider;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Router;

/**
 * Construct a provider, and save it to the database.
 */
class ProviderBuilder
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
     * @var Router
     */
    private $router;

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
     * Set the ORM thing.
     *
     * @param Registry $registry
     */
    public function setManager(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * Set the router.
     *
     * @todo why does the provider builder need a router?
     *
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Fetch a single XML value from a SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml
     * @param string           $xpath
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath)
    {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return (string) $data[0];
        }
        if (count($data) === 0) {
            return;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Build and persist a provider from XML.
     *
     * @param SimpleXMLElement $xml
     * @param string           $provider_uuid
     *
     * @return Provider
     */
    public function fromXml(SimpleXMLElement $xml, $provider_uuid)
    {
        $provider = $this->em->getRepository('AppBundle:Provider')->findOneBy(array(
            'uuid' => $provider_uuid,
        ));
        if ($provider === null) {
            $provider = new Provider();
        }
        $provider->setUuid($provider_uuid);
        $provider->setEmail($this->getXmlValue($xml, '//atom:email'));
        $this->em->persist($provider);
        $this->em->flush($provider);

        return $provider;
    }
}
