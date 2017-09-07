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

namespace AppBundle\Command\Processing;

require_once 'vendor/scholarslab/bagit/lib/bagit.php';

use AppBundle\Entity\AuContainer;
use AppBundle\Entity\Deposit;
use BagIt;

/**
 * Take a processed bag and reserialize it.
 */
class ReserializeBagCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:reserialize');
        $this->setDescription('Reserialize the deposit bag.');
        parent::configure();
    }

    /**
     * Add the metadata from the database to the bag-info.txt file.
     *
     * @param BagIt   $bag
     * @param Deposit $deposit
     */
    protected function addMetadata(BagIt $bag, Deposit $deposit)
    {
        $bag->bagInfoData = array(); // @todo this is very very bad. Once BagItPHP is updated it should be $bag->clearAllBagInfo();
        $bag->setBagInfoData('External-Identifier', $deposit->getDepositUuid());
        $bag->setBagInfoData('PKP-PLN-Deposit-UUID', $deposit->getDepositUuid());
        $bag->setBagInfoData('PKP-PLN-Deposit-Received', $deposit->getReceived()->format('c'));
        $bag->setBagInfoData('PKP-PLN-Deposit-Volume', $deposit->getVolume());
        $bag->setBagInfoData('PKP-PLN-Deposit-Issue', $deposit->getIssue());
        $bag->setBagInfoData('PKP-PLN-Deposit-PubDate', $deposit->getPubDate()->format('c'));

        $institution = $deposit->getInstitution();
        $bag->setBagInfoData('PKP-PLN-Institution-UUID', $institution->getUuid());
        $bag->setBagInfoData('PKP-PLN-Institution-Title', $institution->getTitle());
        $bag->setBagInfoData('PKP-PLN-Institution-ISSN', $institution->getIssn());
        $bag->setBagInfoData('PKP-PLN-Institution-URL', $institution->getUrl());
        $bag->setBagInfoData('PKP-PLN-Institution-Email', $institution->getEmail());
        $bag->setBagInfoData('PKP-PLN-Publisher-Name', $institution->getPublisherName());
        $bag->setBagInfoData('PKP-PLN-Publisher-URL', $institution->getPublisherUrl());

        foreach ($deposit->getLicense() as $key => $value) {
            $bag->setBagInfoData('PKP-PLN-'.$key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit)
    {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        $this->logger->info("Reserializing {$extractedPath}");

        $temp = tempnam(sys_get_temp_dir(), 'deposit_processing_log');
        if (file_exists($temp)) {
            unlink($temp);
        }
        file_put_contents($temp, $deposit->getProcessingLog());

        $bag = new BagIt($extractedPath);
        $bag->addFile($temp, 'data/processing-log.txt');
        $this->addMetadata($bag, $deposit);
        $bag->update();
        unlink($temp);

        $path = $this->filePaths->getStagingBagPath($deposit);

        if (file_exists($path)) {
            $this->logger->warning("{$path} already exists. Removing it.");
            unlink($path);
        }

        $bag->package($path, 'zip');
        $deposit->setPackagePath($path);
        $deposit->setPackageSize(ceil(filesize($path) / 1000)); // bytes to kb.
        $deposit->setPackageChecksumType('sha1');
        $deposit->setPackageChecksumValue(hash_file('sha1', $path));

        $auContainer = $this->em->getRepository('AppBundle:AuContainer')->getOpenContainer();
        if ($auContainer === null) {
            $auContainer = new AuContainer();
            $this->em->persist($auContainer);
        }
        $deposit->setAuContainer($auContainer);
        $auContainer->addDeposit($deposit);
        if ($auContainer->getSize() > $this->container->getParameter('pln_maxAuSize')) {
            $auContainer->setOpen(false);
            $this->em->flush($auContainer);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Bag Reserialize failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'reserialized';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'virus-checked';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Bag Reserialize succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'reserialize-error';
    }
}
