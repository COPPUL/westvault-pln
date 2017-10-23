<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class DepositContainerControllerTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadAuContainers',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testIndex() {
        $this->client->request('GET', '/deposit/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $crawler = $this->client->getCrawler();
        $depositCrawler = $crawler->selectLink('578205CB-0947-4CD3-A384-CDF186F5E86B');
        $this->assertCount(1, $depositCrawler);
        $this->assertEquals('http://localhost/deposit/2', $depositCrawler->link()->getUri());
        
        $linkCrawler = $crawler->selectLink('I J Testing');
        $this->assertCount(2, $linkCrawler);
        $this->assertEquals('http://localhost/journal/1', $linkCrawler->link()->getUri());
    }
    
    public function testShow() {
        $this->client->request('GET', '/deposit/2');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $this->assertContains('578205CB-0947-4CD3-A384-CDF186F5E86B', $response->getContent());
        
        $crawler = $this->client->getCrawler();
        $linkCrawler = $crawler->selectLink('I J Testing');
        $this->assertCount(1, $linkCrawler);
        $this->assertEquals('http://localhost/journal/1', $linkCrawler->link()->getUri());
    }
    
    public function testSearchLink() {
        $this->client->request('GET', '/deposit/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $linkCrawler = $this->client->getCrawler()->selectLink('Search');
        
        // one for search, one for search journals in menu bar.
        $this->assertCount(2, $linkCrawler);
        
        $link = $linkCrawler->eq(1)->link();
        $this->assertEquals('http://localhost/deposit/search', $link->getUri());
    }
    
    public function testSearchUuid() {
        $this->client->request('GET', '/deposit/search');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $formCrawler = $this->client->getCrawler();
        $buttonCrawler = $formCrawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => '578205CB'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('578205CB-0947-4CD3-A384-CDF186F5E86B', $this->client->getResponse()->getContent());
    }
}
