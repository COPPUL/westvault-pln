<?php

namespace AppBundle\Services\Ping;

use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\PingResult;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

/**
 * Test the ping behaviour when the pinged server responds with HTTP 300. Nothing
 * should change.
 */
class Ping500Test extends AbstractTestCase {
	
	/**
	 * @var Ping
	 */
	protected $ping;

	/**
	 * @var PingResult
	 */
	protected $response;

	/**
	 * @var History
	 */
	protected $history;
	
	/**
	 * @var array
	 */
	protected $prePing;
	
	public function setUp() {
		parent::setUp();
		$journal = $this->references->getReference('journal');
		$this->prePing['contacted'] = $journal->getContacted();
		$this->prePing['status'] = $journal->getstatus();
		$this->prePing['ojsVersion'] = $journal->getOjsVersion();
		$this->prePing['title'] = $journal->getTitle();
		$this->prePing['url'] = $journal->getUrl();
		
		$this->ping = $this->getContainer()->get('ping');
		$client = new Client();
		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		
		$mock = new Mock([
			new Response(
				500, 
				array('Location' => 'http://example.com')
            )]);
		$client->getEmitter()->attach($mock);
		
		$this->ping->setClient($client);
		$this->response = $this->ping->ping($journal);
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
		);
	}
	
	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\Ping', $this->ping);
	}
	
	public function testPing() {
		$this->assertInstanceOf('AppBundle\Utility\PingResult', $this->response);
	}
	
	public function testPingStatus() {
		$this->assertEquals(500, $this->response->getHttpStatus());
	}	
		
	public function testRequestHeaders() {
		$request = $this->history->getLastRequest();
		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals('PkpPlnBot 1.0; http://pkp.sfu.ca', $request->getHeader('User-Agent'));
	}
	
	public function testUpdatedContacted() {
		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertEquals($this->prePing['contacted'], $journal->getContacted());
	}
	
	public function testUpdatedStatus() {
		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertEquals('ping-error', $journal->getStatus());
	}
		
	public function testUpdatedTitle() {
		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertEquals($this->prePing['title'], $journal->getTitle());
	}
	
	public function testupdatedOjsVersion() {
		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertEquals($this->prePing['ojsVersion'], $journal->getOjsVersion());
	}
		
	public function testupdatedTermsAccepted() {
		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertFalse($journal->getTermsAccepted());
	}
}