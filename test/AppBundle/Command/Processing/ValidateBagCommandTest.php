<?php

namespace AppBundle\Command\Processing;

use AppBundle\Utility\AbstractCommandTestCase;

class ValidateBagCommandTest extends AbstractCommandTestCase {
	
	public function getCommand() {
		return new ValidateBagCommand();
	}

	public function getCommandName() {
		return 'pln:validate-bag';
	}
	
	public function dataFiles() {
		return array(
			'bag-harvested.zip' => 'received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip',
			'.processing' => 'processing',
			'.received' => 'received',
			'.staged' => 'staged',
		);
	}
	
	public function testValidate() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('payload-validated');
		$this->em->flush();
		$this->em->clear();
		
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
		));
		$this->em->clear();
		
		$validatedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('bag-validated', $validatedDeposit->getState());
		
		$root = self::DSTDIR . '/processing/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2';
		foreach($this->expectedFiles() as $path) {
			$this->assertFileExists($root . '/' . $path);
		}
	}
		
	public function expectedFiles() {
		return array(
			'bag-info.txt',
			'bagit.txt',
			'fetch.txt',
			'manifest-sha1.txt',
			'tagmanifest-sha1.txt',
			'data/Issue87A27F05-DE80-4858-8B54-CAB5FCB30307.xml',
			'data/terms87A27F05-DE80-4858-8B54-CAB5FCB30307.xml'
		);
	}
}