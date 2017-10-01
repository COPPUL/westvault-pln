<?php

namespace AppBundle\Command\Processing;

// autoloading doesn't work for bagit. 
require_once 'vendor/scholarslab/bagit/lib/bagit.php';

use AppBundle\Entity\Deposit;
use BagIt;
use CL\Tissue\Adapter\ClamAv\ClamAvAdapter;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
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

    private function loadXml($filename, &$report) {
        $dom = new DOMDocument();
        try {
            $dom->load($filename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
            return $dom;
        } catch (Exception $e) {
            $report .= "{$filename} cannot be parsed: {$e->getMessage()}\n";
        }
        
        $filteredFilename = "{$filename}-filtered.xml";
        $in = fopen($filename, 'rb');
        $out = fopen($filteredFilename, 'wb');
        $blockSize = 64 * 1024; // 64k blocks
        $changes = 0;
        while ($buffer = fread($in, $blockSize)) {
            $filtered = iconv('UTF-8', 'UTF-8//IGNORE', $buffer);
            $changes += strlen($buffer) - strlen($filtered);
            fwrite($out, $filtered);
        }
        
        try {
            $dom->load($filteredFilename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
            $report .= "{$filteredFilename} will be used instead.\n";
            return $dom;
        } catch (Exception $e) {
            $report .= "Filtering out invalid UTF-8 characters failed. Cannot parse XML at all: {$e->getMessage()}\n";
            return null;
        }
    }
    
    private function scanEmbed(DOMElement $embed, DOMXPath $xp, &$report) {
        $attrs = $embed->attributes;
        if(!$attrs) {
            return;
        }
        $filename = $attrs->getNamedItem('filename')->nodeValue;
        $tmpPath = tempnam(sys_get_temp_dir(), 'pln-vs-');
        $fh = fopen($tmpPath, 'wb');
        if(!$fh) {
            throw new Exception("Cannot open {$tmpPath} for write.");
        }
        $chunkSize = 1024*64; // 64kb chunks
        $length = $xp->evaluate('string-length(./text())', $embed);
        $offset = 1; //xpath starts at 1
        while($offset < $length) {
            $end = $offset + $chunkSize;
            $chunk = $xp->evaluate("substring(./text(), {$offset}, {$chunkSize})", $embed);
            fwrite($fh, base64_decode($chunk));
            $offset = $end;
        }
        $result = $this->scanner->scan([$tmpPath]);
        if($result->hasVirus()) {
            $report .= "Virus infections found in embedded file:\n";
            foreach($result->getDetections() as $d) {
                $report .= "{$filename} - {$d->getDescription()}\n";
            }
        }
        unlink($tmpPath);
    }
    
    private function scanEmbeddedData($filename, &$report) {
        $dom = $this->loadXml($filename, $report);
        if($dom === null) {
            return;
        }
        $xp = new DOMXPath($dom);
        foreach($xp->query('//embed') as $embed) {
            $this->scanEmbed($embed, $xp, $report);
        }
    }
    
    protected function processDeposit(Deposit $deposit) {
        $report = '';
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        
        $result = $this->scanner->scan([$extractedPath]);
        if($result->hasVirus()) {
            $report .= "Virus infections found in bag files.\n";
            foreach($result->getDetections() as $d) {
                $report .= "{$d->getPath()} - {$d->getDescription()}\n";
            }
        }
        
        $bag = new BagIt($extractedPath);
        foreach($bag->getBagContents() as $filename) {
            if (substr($filename, -4) !== '.xml') {
                continue;
            }
            $this->scanEmbeddedData($filename, $report);
        }
        print $report;
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
        return 'xml-validated';
    }

    public function successLogMessage() {
        return 'Virus check passed. No infections found.';
    }

}