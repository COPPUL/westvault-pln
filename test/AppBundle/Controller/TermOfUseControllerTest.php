<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class TermOfUseControllerTest extends AbstractTestCase {

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
		$this->client->request('GET', '/termofuse/');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$crawler = $this->client->getCrawler();

		$this->assertCount(1, $crawler->selectLink('test.a'));
		$this->assertCount(1, $crawler->selectLink('test.b'));
		$this->assertCount(1, $crawler->selectLink('test.c'));
		$this->assertCount(4, $crawler->selectLink('History'));
	}

	public function testShow() {
		$this->client->request('GET', '/termofuse/');
		$indexCrawler = $this->client->getCrawler();
		$this->assertCount(1, $indexCrawler->selectLink('test.a'));
		$this->client->click($indexCrawler->selectLink('test.a')->link());

		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertContains('first term', $response->getContent());
		$this->assertContains('test.a', $response->getContent());
	}

	public function testEdit() {
		$this->client->request('GET', '/termofuse/1');
		$indexCrawler = $this->client->getCrawler();
		$this->assertCount(1, $indexCrawler->selectLink('Edit'));
		$this->client->click($indexCrawler->selectLink('Edit')->link());
		
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$buttonCrawler = $this->client->getCrawler()->selectButton('Update');
		$form = $buttonCrawler->form(array(
			'appbundle_termofuse[weight]' => 99,
			'appbundle_termofuse[keyCode]' => 'abc123',
			'appbundle_termofuse[langCode]' => 'fr_FR',
			'appbundle_termofuse[content]' => 'Content is for winners',
		));
		$this->client->submit($form);
		$this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
		$this->client->followRedirect();
		
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertContains('The terms of use entry has been updated.', $response->getContent());
		$this->assertContains('99', $response->getContent());
		$this->assertContains('abc123', $response->getContent());
		$this->assertContains('fr_FR', $response->getContent());
		$this->assertContains('Content is for winners', $response->getContent());
	}

	public function testNew() {
		$this->client->request('GET', '/termofuse/');
		$indexCrawler = $this->client->getCrawler();
		$this->assertCount(2, $indexCrawler->selectLink('New'));
		$this->client->click($indexCrawler->selectLink('New')->eq(1)->link());
		
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$buttonCrawler = $this->client->getCrawler()->selectButton('Create');
		$form = $buttonCrawler->form(array(
			'appbundle_termofuse[weight]' => 99,
			'appbundle_termofuse[keyCode]' => 'abc123',
			'appbundle_termofuse[langCode]' => 'fr_FR',
			'appbundle_termofuse[content]' => 'Content is for winners',
		));
		$this->client->submit($form);
		$this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
		$this->client->followRedirect();
		
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertContains('The terms of use entry has been saved.', $response->getContent());
		$this->assertContains('99', $response->getContent());
		$this->assertContains('abc123', $response->getContent());
		$this->assertContains('fr_FR', $response->getContent());
		$this->assertContains('Content is for winners', $response->getContent());
	}
	
	public function testDelete() {
		$this->client->request('GET', '/termofuse/1');
		$indexCrawler = $this->client->getCrawler();
		$this->assertCount(1, $indexCrawler->selectLink('Delete'));
		$this->client->click($indexCrawler->selectLink('Delete')->link());
		
		$this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
		$this->client->followRedirect();
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$crawler = $this->client->getCrawler();

		$this->assertCount(0, $crawler->selectLink('test.a'));
		$this->assertCount(1, $crawler->selectLink('test.b'));
		$this->assertCount(1, $crawler->selectLink('test.c'));
	}
}
