<?php

namespace AppBundle\Command\Processing\HarvestCommand;

use AppBundle\Utility\AbstractCommandTestCase;
use AppBundle\Command\Processing\HarvestCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class Head500ErrorTest extends AbstractCommandTestCase {
	
	/**
	 * @var History
	 */
	protected $history;
	
	protected $command;
	
	public function setUp() {		
		$this->command = new HarvestCommand();
		
		$client = new Client();		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		$mock = new Mock([
			new Response(
				500, 
				array(''),
				$this->getResponseBody()
            )]);
		$client->getEmitter()->attach($mock);
		$this->command->setClient($client);
		
		parent::setUp();
	}
	
	public function testHarvest() {
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
		));
		$this->assertCount(1, $this->em->getRepository('AppBundle:Deposit')->findBy(array(
			'state' => 'harvest-error'
		)));
	}

	public function getCommand() {
		return $this->command; // use the injected Guzzle client.
	}

	public function getCommandName() {
		return 'pln:harvest';
	}
	
	protected function getResponseBody() {
		return null;
	}
}