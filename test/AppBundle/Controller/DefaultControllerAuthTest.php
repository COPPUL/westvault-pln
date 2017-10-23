<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerAuthTest extends AbstractTestCase {

	protected $client;
	
	public function setUp() {
		parent::setUp();
		$this->client = static::createClient(array(), array(
					'PHP_AUTH_USER' => 'admin@example.com',
					'PHP_AUTH_PW' => 'supersecret',
		));
	}

	public function dataFiles() {
		return array(
			'onix.xml' => 'onix.xml',
			'bag-harvested.zip' => 'staged/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip'
		);
	}
	
	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
			'AppBundle\DataFixtures\ORM\test\LoadDocs',
			'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
			'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
		);
	}

	public function testIndex() {
		$this->client->request('GET', '/');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$this->assertContains('I J Testing', $this->client->getResponse()->getContent());
		$this->assertContains('I J Testing vol. 2 no. 4', $this->client->getResponse()->getContent());
		$this->assertContains('I J Testing vol. 1 no. 2', $this->client->getResponse()->getContent());
	}

	public function testDocsList() {
		$this->client->request('GET', '/docs');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$crawler = $this->client->getCrawler();
		$linkCrawler = $crawler->selectLink('Read more');
		$this->assertEquals(1, $linkCrawler->count());
		$link = $linkCrawler->link();
		$this->assertEquals('http://localhost/docs/test1', $link->getUri());
		$this->assertContains('summarized', $this->client->getResponse()->getContent());
	}

	public function testDocsView() {
		$this->client->request('GET', '/docs/test1');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$this->assertContains('summarized', $this->client->getResponse()->getContent());
		$this->assertContains('Content is good and cheesy.', $this->client->getResponse()->getContent());
	}

	public function testPermission() {
		$this->client->request('GET', '/permission');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$this->assertEquals('LOCKSS system has permission to collect, preserve, and serve this Archival Unit.', $this->client->getResponse()->getContent());
	}

	public function testFetchDepositNotFound() {
		$this->client->request('GET', '/fetch/jid/did.zip');
		$this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
	}

	public function testFetchMismatch() {
		$this->client->request('GET', '/fetch/A556CBF2-B674-444F-87B7-23DEE36F013D/578205CB-0947-4CD3-A384-CDF186F5E86B.zip');
		$this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
	}

	public function testFetchPackageNotFound() {
		$this->client->request('GET', '/fetch/c0a65967-32bd-4ee8-96de-c469743e563a/578205CB-0947-4CD3-A384-CDF186F5E86B.zip');
		$this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
	}

	public function testFetch() {
		$this->client->request('GET', '/fetch/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals('application/zip', $response->headers->get('Content-Type'));
		$this->assertEquals(5350, $response->headers->get('Content-Length'));
	}

	public function testOnyxRedirect() {
		$this->client->request('GET', '/onix.xml');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
		$this->assertEquals('/feeds/onix.xml', $response->headers->get('Location'));
	}

	public function testOnyx() {
		$this->client->request('GET', '/feeds/onix.xml');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals(511, $response->headers->get('Content-Length'));
	}

	public function testTermsJson() {
		$this->client->request('GET', '/feeds/terms.json');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertContains('first term', $response->getContent());
	}

	public function testTermsRss() {
		$this->client->request('GET', '/feeds/terms.rss');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertContains('first term', $response->getContent());
	}

	public function testTermsAtom() {
		$this->client->request('GET', '/feeds/terms.atom');
		$response = $this->client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertContains('first term', $response->getContent());
	}
}
