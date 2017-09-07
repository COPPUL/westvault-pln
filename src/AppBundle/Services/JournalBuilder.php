<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Institution;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Router;

/**
 * Construct a institution, and save it to the database.
 */
class InstitutionBuilder
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
     * @todo why does the institution builder need a router?
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
     * Build and persist a institution from XML.
     *
     * @param SimpleXMLElement $xml
     * @param string           $institution_uuid
     *
     * @return Institution
     */
    public function fromXml(SimpleXMLElement $xml, $institution_uuid)
    {
        $institution = $this->em->getRepository('AppBundle:Institution')->findOneBy(array(
            'uuid' => $institution_uuid,
        ));
        if ($institution === null) {
            $institution = new Institution();
        }
        $institution->setUuid($institution_uuid);
        $institution->setTitle($this->getXmlValue($xml, '//atom:title'));
        $institution->setUrl(html_entity_decode($this->getXmlValue($xml, '//pkp:institution_url'))); // &amp; -> &
        $institution->setEmail($this->getXmlValue($xml, '//atom:email'));
        $institution->setIssn($this->getXmlValue($xml, '//pkp:issn'));
        $institution->setPublisherName($this->getXmlValue($xml, '//pkp:publisherName'));
        $institution->setPublisherUrl(html_entity_decode($this->getXmlValue($xml, '//pkp:publisherUrl'))); // &amp; -> &
        $this->em->persist($institution);
        $this->em->flush($institution);

        return $institution;
    }
}
