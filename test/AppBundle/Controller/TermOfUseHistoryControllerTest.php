<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class TermOfUseHistoryControllerTest extends AbstractTestCase {

	protected $client;

	public function setUp() {
		parent::setUp();
		$this->client = static::createClient(array(), array(
					'PHP_AUTH_USER' => 'admin@example.com',
					'PHP_AUTH_PW' => 'supersecret',
		));
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
			'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
		);
	}

	public function testIndex() {
		$this->client->request('GET', '/termhistory/');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$crawler = $this->client->getCrawler();
		$this->assertCount(1, $crawler->selectLink(1));
		$this->assertCount(1, $crawler->selectLink(2));
		$this->assertCount(1, $crawler->selectLink(3));
	}
	
	public function testShow() {
		$this->client->request('GET', '/termhistory/1');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
	}
	
	public function testEditUpatesHistory() {
		$repo = $this->em->getRepository('AppBundle:TermOfUseHistory');
		$this->assertCount(1, $repo->getTermHistory(1));
		
		$this->client->request('GET', '/termofuse/1/edit');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		
		$buttonCrawler = $this->client->getCrawler()->selectButton('Update');
		$form = $buttonCrawler->form(array(
			'appbundle_termofuse[weight]' => 99,
			'appbundle_termofuse[keyCode]' => 'abc123',
			'appbundle_termofuse[langCode]' => 'fr_FR',
			'appbundle_termofuse[content]' => 'Content is for winners',
		));
		$this->client->submit($form);
		$this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
		
		$this->client->request('GET', '/termhistory/1');
		$historyResponse = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $historyResponse->getStatusCode());
		
		$this->assertContains('Content is for winners', $historyResponse->getContent());
		$this->assertContains('abc123', $historyResponse->getContent());
		$this->assertCount(2, $repo->getTermHistory(1));
	}

	public function testNewCreatesHistory() {
		$repo = $this->em->getRepository('AppBundle:TermOfUseHistory');
		
		$this->client->request('GET', '/termofuse/new');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		
		$buttonCrawler = $this->client->getCrawler()->selectButton('Create');
		$form = $buttonCrawler->form(array(
			'appbundle_termofuse[weight]' => 99,
			'appbundle_termofuse[keyCode]' => 'abc123',
			'appbundle_termofuse[langCode]' => 'fr_FR',
			'appbundle_termofuse[content]' => 'Content is for winners',
		));
		$this->client->submit($form);
		$this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
		
		$this->client->request('GET', '/termhistory/4');
		$historyResponse = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $historyResponse->getStatusCode());
		
		$this->assertContains('Content is for winners', $historyResponse->getContent());
		$this->assertContains('abc123', $historyResponse->getContent());
		$this->assertCount(1, $repo->getTermHistory(4));
	}

	public function testDeleteUpdatesHistory() {
		$repo = $this->em->getRepository('AppBundle:TermOfUseHistory');
		$this->assertCount(1, $repo->getTermHistory(1));

		$this->client->request('GET', '/termofuse/1/delete');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
		
		$this->assertCount(2, $repo->getTermHistory(1));
	}
}
