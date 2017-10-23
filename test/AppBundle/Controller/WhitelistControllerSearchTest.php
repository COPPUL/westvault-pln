<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class WhitelistControllerSearchTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadBigWhitelist',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testSearchForm() {
        $this->client->request('GET', '/whitelist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $link = $crawler->selectLink('Search')->link();
        $this->client->click($link);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());        
    }

    public function testSearchUuid() {
        $this->client->request('GET', '/whitelist/search');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $formCrawler = $this->client->getCrawler();
        $buttonCrawler = $formCrawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'FC1EFBBA'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('FC1EFBBA-BFA4-4505-A398-006FDBE6A9D7', $this->client->getResponse()->getContent());
    }
    
    public function testSearchComments() {
        $this->client->request('GET', '/whitelist/search');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $formCrawler = $this->client->getCrawler();
        $buttonCrawler = $formCrawler->selectButton('Search');
        $form = $buttonCrawler->form(array(
            'q' => 'cheese'
        ));
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('9B6DDEFD-F74F-47FC-A6F2-4CDC549637C9', $this->client->getResponse()->getContent());
    }
}
