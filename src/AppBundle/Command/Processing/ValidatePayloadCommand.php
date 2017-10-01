<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use Exception;

/**
 * Validate the size and checksum of a downloaded deposit.
 */
class ValidatePayloadCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:validate-payload');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit)
    {
        $depositPath = $this->filePaths->getHarvestFile($deposit);

        if (!$this->fs->exists($depositPath)) {
            throw new Exception("Cannot find deposit bag {$depositPath}");
        }

        $checksumValue = null;
        switch (strtoupper($deposit->getChecksumType())) {
            case 'SHA-1':
            case 'SHA1':
                $checksumValue = sha1_file($depositPath);
                break;
            case 'MD5':
                $checksumValue = md5_file($depositPath);
                break;
            default:
                throw new Exception("Deposit checksum type {$deposit->getChecksumType()} unknown.");
        }
        if (strtoupper($checksumValue) !== $deposit->getChecksumValue()) {
            $deposit->addErrorLog("Deposit checksum does not match. Expected {$deposit->getChecksumValue()} != Actual ".strtoupper($checksumValue));
            $this->logger->warning("Deposit checksum does not match for deposit {$deposit->getDepositUuid()}");

            return false;
        }

        $this->logger->info("Deposit {$depositPath} validated.");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'harvested';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Payload checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Payload checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'payload-error';
    }
}
