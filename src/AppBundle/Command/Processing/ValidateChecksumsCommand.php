<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use Exception;

/**
 * Validate the size and checksum of a downloaded deposit.
 */
class ValidateChecksumsCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:validate-checksums');
        $this->setDescription('Validate PLN deposit files.');
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

        $checksumValue = hash($deposit->getChecksumType(), file_get_contents($depositPath));
        if ($checksumValue !== $deposit->getChecksumValue()) {
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
