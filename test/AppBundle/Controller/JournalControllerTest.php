<?php

namespace AppBundle\Controller;

use AppBundle\Services\Ping;
use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class JournalControllerTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppBundle\DataFixtures\ORM\test\LoadDeposits',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testIndex() {
        $this->client->request('GET', '/journal/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        
        $linkCrawler = $crawler->selectLink('I J Testing');
        $this->assertCount(1, $linkCrawler);
        $this->assertEquals('http://localhost/journal/1', $linkCrawler->link()->getUri());
		
		$this->assertCount(1, $crawler->selectLink('healthy'));
		$this->assertCount(1, $crawler->selectLink('new'));
    }
    
    public function testIndexStatusNew() {
        $this->client->request('GET', '/journal/', array('status' => 'new'));
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $this->assertCount(1, $crawler->selectLink('J Oranges'));
        $this->assertCount(0, $crawler->selectLink('I J Testing'));				
    }
    
    public function testIndexStatusHealthy() {
        $this->client->request('GET', '/journal/', array('status' => 'healthy'));
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $this->assertCount(0, $crawler->selectLink('J Oranges'));
        $this->assertCount(1, $crawler->selectLink('I J Testing'));				
    }
    
    public function testShow() {
        $this->client->request('GET', '/journal/1');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('C0A65967-32BD-4EE8-96DE-C469743E563A', $response->getContent());
    }
    
    public function testDelete() {
        $this->client->followRedirects(true);
        $this->client->request('GET', '/journal/1/delete');        
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('Journals which have made deposits cannot be deleted.', $response->getContent());
    }
    
    public function testDeleteUnchecked() {
        $this->client->followRedirects(true);
        $this->client->request('GET', '/journal/2/delete');        
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        // we can delete
        $this->assertNotContains('Journals which have made deposits cannot be deleted.', $response->getContent());
        $formCrawler = $this->client->getCrawler()->selectButton('Delete');
        $form = $formCrawler->form();
        $this->client->submit($form);
        $this->assertNotContains('Journal deleted', $this->client->getResponse()->getContent());
    }
    
    public function testDeleteChecked() {
        $this->client->followRedirects(true);
        $this->client->request('GET', '/journal/2/delete');        
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        // we can delete
        $this->assertNotContains('Journals which have made deposits cannot be deleted.', $response->getContent());
        $formCrawler = $this->client->getCrawler()->selectButton('Delete');
        $form = $formCrawler->form(array(
            'form[confirm]' => 'yes'
        ));
        $this->client->submit($form);
        $this->assertContains('Journal deleted', $this->client->getResponse()->getContent());
    }
        
    public function testPing() {
		$ping = $this->getContainer()->get('ping');
		$this->assertInstanceOf('AppBundle\Services\Ping', $ping);
    }
    
    public function testSearchPage() {
        $this->client->request('GET', '/journal/', array('status' => 'new'));
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $linkCrawler = $this->client->getCrawler()->selectLink('Search');
		$this->assertCount(2, $linkCrawler);		
        $link = $linkCrawler->eq(1)->link();
        $this->assertEquals('http://localhost/journal/search', $link->getUri());
    }
    
    public function testSearchTitle() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'orange'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));
    }
    
    public function testSearchUuid() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'A556CBF2'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));
    }
    
    public function testSearchIssn() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => '4321'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));
    }
    
    public function testSearchUrl() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'orangula'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));
    }
    
    public function testSearchEmail() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => '@bar.com'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));
    }
    
    public function testSearchPublisherName() {
        $this->client->request('GET', '/journal/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        $buttonCrawler = $crawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'Orange Inc'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->client->getCrawler()->selectLink('Orange Inc'));
        $this->assertCount(0, $this->client->getCrawler()->selectLink('I J Testing'));		
    }
}
