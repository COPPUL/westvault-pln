<?php

namespace AppBundle\Command\Processing;

use AppBundle\Utility\AbstractCommandTestCase;

class ValidatePayloadCommandTest extends AbstractCommandTestCase {
	
	public function getCommand() {
		return new ValidatePayloadCommand();
	}

	public function getCommandName() {
		return 'pln:validate-payload';
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
		$deposit->setState('harvested');
		$this->em->flush();
		$this->em->clear();
		
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
			'--limit' => 1,
		));
		$this->em->clear();
		$this->assertCount(1, $this->em->getRepository('AppBundle:Deposit')->findBy(array(
			'state' => 'payload-validated',
		)));
		$this->assertCount(0, $this->em->getRepository('AppBundle:Deposit')->findBy(array(
			'state' => 'payload-error',
		)));
	}
	
	public function testValidateFail() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('harvested');
		$deposit->setChecksumValue('fooooo');
		$this->em->flush();
		$this->em->clear();
		
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
			'--limit' => 1,
		));
		$this->em->clear();
		$this->assertCount(0, $this->em->getRepository('AppBundle:Deposit')->findBy(array(
			'state' => 'payload-validated',
		)));
		
		$processedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertContains('Deposit checksum does not match.', implode(' ', $processedDeposit->getErrorLog()));
	}
}
