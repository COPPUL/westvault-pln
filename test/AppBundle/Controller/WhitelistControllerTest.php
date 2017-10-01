<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class WhitelistControllerTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }

    public function testIndex() {
        $this->client->request('GET', '/whitelist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6', $this->client->getResponse()->getContent());
    }

    public function testCreate() {
        $this->client->request('GET', '/whitelist/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Create');
        $form = $formCrawler->form(array(
            "appbundle_whitelist[uuid]" => '6923756D-FBB8-47B1-89CB-4A7434775956',
            'appbundle_whitelist[comment]' => "Test comment",
        ));
        $this->client->submit($form);
        $this->client->request('GET', '/whitelist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6', $this->client->getResponse()->getContent());
        $this->assertContains('6923756D-FBB8-47B1-89CB-4A7434775956', $this->client->getResponse()->getContent());
    }

    public function testEdit() {
        $this->client->request('GET', '/whitelist/1/edit');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Update');
        $form = $formCrawler->form(array(
            "appbundle_whitelist[uuid]" => '6923756D-FBB8-47B1-89CB-4A7434775956',
            'appbundle_whitelist[comment]' => "Test comment",
        ));
        $this->client->submit($form);
        $this->client->request('GET', '/whitelist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertNotContains('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6', $this->client->getResponse()->getContent());
        $this->assertContains('6923756D-FBB8-47B1-89CB-4A7434775956', $this->client->getResponse()->getContent());
    }

    public function testDelete() {
        $this->client->request('GET', '/whitelist/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $link = $crawler->selectLink('Delete')->link();
        $this->client->click($link);
        
        $this->client->request('GET', '/whitelist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertNotContains('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6', $this->client->getResponse()->getContent());
    }
}
