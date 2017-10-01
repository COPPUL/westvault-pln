<?php

namespace AppBundle\Utility;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractCommandTestCase extends AbstractTestCase {
	
	protected $commandTester;
	
	protected $command;
	
	protected $application;

	public function setUp() {
		parent::setUp();
		$this->application = new Application();
		$this->application->add($this->getCommand());
		$this->command = $this->application->find($this->getCommandName());
		$this->command->setContainer($this->getContainer());
		$this->commandTester = new CommandTester($this->command);
	}
	
	abstract function getCommand();
	
	abstract function getCommandName();
	
	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
		);
	}
}
