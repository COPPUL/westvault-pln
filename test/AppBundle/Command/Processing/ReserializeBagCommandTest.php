<?php

namespace AppBundle\Command\Processing;

require_once('vendor/scholarslab/bagit/lib/bagit.php');

use AppBundle\Utility\AbstractCommandTestCase;
use BagIt;
use Exception;
use ZipArchive;

class ReserializeBagCommandTest extends AbstractCommandTestCase {

	public function getCommand() {
		return new ReserializeBagCommand();
	}

	public function getCommandName() {
		return 'pln:reserialize';
	}

	public function dataFiles() {
		return array(
			'bag-harvested.zip' => 'received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip',
			'.processing' => 'processing',
			'.received' => 'received',
			'.staged' => 'staged',
		);
	}

	public function testReserialize() {
		// extract the bag.
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('virus-checked');
		$this->em->flush();
		$this->em->clear();

		$harvestedPath = 'test/data/received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip';
		$extractedPath = 'test/data/processing/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2';
		$zipFile = new ZipArchive();
		$zipFile->open($harvestedPath);
		if ($zipFile->extractTo(dirname($extractedPath)) === false) {
			throw new Exception("Cannot extract to {$extractedPath} " . $zipFile->getStatusString());
		}
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
		));
		$this->em->clear();
		$processedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('reserialized', $processedDeposit->getState());
		
		$bag = new BagIt('test/data/received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip');
		foreach($this->infoKeys() as $key => $value) {
			$this->assertEquals($value, $bag->getBagInfoData($key), "{$key} matches");
		}
	}

	private function infoKeys() {
		return array(
			'External-Identifier' => '87A27F05-DE80-4858-8B54-CAB5FCB30307',
			'PKP-PLN-Deposit-UUID' => '87A27F05-DE80-4858-8B54-CAB5FCB30307',
			'PKP-PLN-Deposit-Received' => '2016-04-26T13:00:22-07:00',
			'PKP-PLN-Deposit-Volume' => '3',
			'PKP-PLN-Deposit-Issue' => '1',
			'PKP-PLN-Deposit-PubDate' => '2014-07-13T00:00:00-07:00',
			'PKP-PLN-Journal-UUID' => 'AC4E7490-E8C6-44D1-A611-26F7B60738E1',
			'PKP-PLN-Journal-Title' => 'Intl J Test',
			'PKP-PLN-Journal-ISSN' => '9876-5432',
			'PKP-PLN-Journal-URL' => 'http://ojs.dv/index.php/ijt',
			'PKP-PLN-Journal-Email' => 'ubermichael@gmail.com',
			'PKP-PLN-Publisher-Name' => 'TestPress',
			'PKP-PLN-Publisher-URL' => 'http://text.example.com',
		);
	}
}
