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

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Institution;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Build a deposit and persist it to the database.
 */
class DepositBuilder
{
    /**
     * ORM entity manager.
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Service logger.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Set the router for the builder.
     *
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
     * Set the ORM.
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
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Get a single XML value as a string.
     *
     * @param SimpleXMLElement $xml
     * @param type             $xpath
     *
     * @return string
     *
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath)
    {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return trim((string) $data[0]);
        }
        if (count($data) === 0) {
            return;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Build the URL for the deposit receipt.
     *
     * @param Deposit $deposit
     *
     * @return string
     */
    public function buildDepositReceiptUrl(Deposit $deposit)
    {
        return $this->router->getGenerator()->generate(
            'statement',
            array(
                'institution_uuid' => $deposit->getInstitution()->getUuid(),
                'deposit_uuid' => $deposit->getDepositUuid(),
                ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get the licensing info from the XML and add it to the deposit object.
     *
     * @todo getLicensingInfo() is a terrible name
     *
     * @param Deposit          $deposit
     * @param SimpleXMLElement $xml
     */
    public function getLicensingInfo(Deposit $deposit, SimpleXMLElement $xml)
    {
        $item = $xml->xpath('//pkp:license/node()');
        foreach ($item as $child) {
            $deposit->addLicense($child->getName(), (string) $child);
        }
    }

    /**
     * Build a deposit from XML.
     *
     * @param Institution          $institution
     * @param SimpleXMLElement $xml
     * @param string           $action
     *
     * @return Deposit
     */
    public function fromXml(Institution $institution, SimpleXMLElement $xml)
    {
        $id = $this->getXmlValue($xml, '//atom:id');
        $deposit_uuid = strtoupper(substr($id, 9, 36));

        $deposit = $this->em->getRepository('AppBundle:Deposit')->findOneBy(array(
            'depositUuid' => $deposit_uuid,
        ));
        $action = 'edit';
        if (!$deposit) {
            $action = 'add';
            $deposit = new Deposit();
        }
        $deposit->setAction($action);
        $deposit->setState('depositedByInstitution');
        $deposit->setChecksumType($this->getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue($this->getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setDepositUuid($deposit_uuid);
        $deposit->setFileType('');
        $deposit->setIssue($this->getXmlValue($xml, 'pkp:content/@issue'));
        $deposit->setVolume($this->getXmlValue($xml, 'pkp:content/@volume'));
        $deposit->setPubDate(new DateTime($this->getXmlValue($xml, 'pkp:content/@pubdate')));
        $deposit->setInstitution($institution);
        $deposit->setSize($this->getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl(html_entity_decode($this->getXmlValue($xml, 'pkp:content')));
        $deposit->setDepositReceipt($this->buildDepositReceiptUrl($deposit));
        $ojsVersion = $this->getXmlValue($xml, 'pkp:content/@ojsVersion');
        if($ojsVersion) {
            $deposit->setInstitutionVersion($ojsVersion);
        } else {
            $deposit->setInstitutionVersion(Deposit::DEFAULT_JOURNAL_VERSION);
        }
        
        $this->getLicensingInfo($deposit, $xml);

        if ($action === 'add') {
            $deposit->addToProcessingLog('Deposit received.');
        } else {
            $deposit->addToProcessingLog('Deposit edited or reset by institution manager.');
        }

        $this->em->persist($deposit);
        $this->em->flush();

        return $deposit;
    }
}
