<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractCommandTestCase;
use Closure;
use Exception;

class EmptyCommand extends AbstractProcessingCmd {

	protected $callback;

	public function setCallback(Closure $callback) {
		$this->callback = $callback;
	}

	public function getCallback() {
		if (!$this->callback) {
			$this->callback = function(Deposit $deposit) {
				return true;
			};
		}
		return $this->callback;
	}
	
	protected function configure() {
		$this->setName('test:empty:cmd');
		$this->setDescription('PLN Processing Command that does nothing.');
		parent::configure();
	}

	protected function processDeposit(Deposit $deposit) {
		$callback = $this->getCallback();
		return $callback($deposit);
	}

	public function errorState() {
		return "test0-error";
	}

	public function failureLogMessage() {
		return "test failed.";
	}

	public function nextState() {
		return "test1";
	}

	public function processingState() {
		return "test0";
	}

	public function successLogMessage() {
		return "test passed.";
	}
}

class AbstractProcessingCmdTest extends AbstractCommandTestCase {

	public function getCommand() {
		return new EmptyCommand();
	}

	public function getCommandName() {
		return 'test:empty:cmd';
	}	

	public function testGetDepositsEmpty() {
		$this->assertCount(0, $this->command->getDeposits());
	}

	public function testGetDepositsNonEmpty() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0');
		$this->em->flush();
		$this->em->clear();

		$this->assertCount(1, $this->command->getDeposits());
	}

	public function testGetDepositsRetry() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0-error');
		$this->em->flush();
		$this->em->clear();

		$this->assertCount(1, $this->command->getDeposits(true));
	}

	public function testExecuteSuccess() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0');
		$this->em->flush();
		$this->em->clear();

		$this->commandTester->execute(array(
			'command' => $this->command->getName(),
		));

		$updatedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('test1', $updatedDeposit->getState());
	}

	public function testExecuteException() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0');
		$this->em->flush();
		$this->em->clear();

		$this->command->setCallback(function(Deposit $deposit) {
			throw new Exception("Booooo.");
		});
		$this->commandTester->execute(array(
			'command' => $this->command->getName(),
		));

		$updatedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('test0-error', $updatedDeposit->getState());
	}

	public function testExecuteFalse() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0');
		$this->em->flush();
		$this->em->clear();

		$this->command->setCallback(function(Deposit $deposit) {
			return false;
		});
		$this->commandTester->execute(array(
			'command' => $this->command->getName(),
		));

		$updatedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('test0-error', $updatedDeposit->getState());
	}

	public function testExecuteDryRun() {
		$deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$deposit->setState('test0');
		$this->em->flush();
		$this->em->clear();

		$this->commandTester->execute(array(
			'command' => $this->command->getName(),
			'--dry-run' => 1,
		));

		$updatedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
		$this->assertEquals('test0', $updatedDeposit->getState());
	}
	
	public function testExecuteLimit() {
		foreach($this->em->getRepository('AppBundle:Deposit')->findAll() as $deposit) {
			$deposit->setState('test0');
		}
		$this->em->flush();
		$this->em->clear();

		$this->commandTester->execute(array(
			'command' => $this->command->getName(),
			'--limit' => 1,
		));

		$this->assertCount(
			1, 
			$this->em->getRepository('AppBundle:Deposit')->findBy(array(
				'state' => 'test0'
			))
		);
		$this->assertCount(
			1, 
			$this->em->getRepository('AppBundle:Deposit')->findBy(array(
				'state' => 'test1'
			))
		);
	}
}
