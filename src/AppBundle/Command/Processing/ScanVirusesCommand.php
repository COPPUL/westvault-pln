<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use CL\Tissue\Adapter\ClamAv\ClamAvAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScanVirusesCommand extends AbstractProcessingCmd {
    
    /**
     * @var ClamAvAdapter
     */
    protected $scanner;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $scannerPath = $container->getParameter('clamdscan_path');
        $this->scanner = new ClamAvAdapter($scannerPath);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:scan-viruses');
        $this->setDescription('Scan deposit packages for viruses.');
        parent::configure();
    }

    protected function processDeposit(Deposit $deposit) {        
        $report = '';
        $extractedPath = $this->filePaths->getHarvestFile($deposit);
        
        $result = $this->scanner->scan([$extractedPath]);
        if($result->hasVirus()) {
            $report .= "Virus infections found in file.\n";
            foreach($result->getDetections() as $d) {
                $report .= "{$d->getPath()} - {$d->getDescription()}\n";
            }
            return false;
        }
        return true;
    }

    public function errorState() {
        return 'virus-error';
    }

    public function failureLogMessage() {
        return 'Virus check failed.';
    }

    public function nextState() {
        return 'virus-checked';
    }

    public function processingState() {
        return 'payload-validated';
    }

    public function successLogMessage() {
        return 'Virus check passed. No infections found.';
    }

}