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
    
    protected function hashFile($hash, $filepath) {
        $handle = fopen($filepath, "r");
        $context = hash_init($hash);
        while(($data = fread($handle, 64 * 1024))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle); 
        return $hash;
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

        $checksumValue = $this->hashFile($deposit->getChecksumType(),$depositPath);
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
