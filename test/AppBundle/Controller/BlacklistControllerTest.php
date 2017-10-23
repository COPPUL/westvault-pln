<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class BlacklistControllerTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadBlacklist',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }

    public function testIndex() {
        $this->client->request('GET', '/blacklist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('E10E1C62-80AB-4D62-8336-C13C7BE73ED8', $this->client->getResponse()->getContent());
    }

    public function testCreate() {
        $this->client->request('GET', '/blacklist/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Create');
        $form = $formCrawler->form(array(
            "appbundle_blacklist[uuid]" => '6923756D-FBB8-47B1-89CB-4A7434775956',
            'appbundle_blacklist[comment]' => "Test comment",
        ));
        $this->client->submit($form);
        $this->client->request('GET', '/blacklist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains('E10E1C62-80AB-4D62-8336-C13C7BE73ED8', $this->client->getResponse()->getContent());
        $this->assertContains('6923756D-FBB8-47B1-89CB-4A7434775956', $this->client->getResponse()->getContent());
    }

    public function testEdit() {
        $this->client->request('GET', '/blacklist/1/edit');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Update');
        $form = $formCrawler->form(array(
            "appbundle_blacklist[uuid]" => '6923756D-FBB8-47B1-89CB-4A7434775956',
            'appbundle_blacklist[comment]' => "Test comment",
        ));
        $this->client->submit($form);
        $this->client->request('GET', '/blacklist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertNotContains('E10E1C62-80AB-4D62-8336-C13C7BE73ED8', $this->client->getResponse()->getContent());
        $this->assertContains('6923756D-FBB8-47B1-89CB-4A7434775956', $this->client->getResponse()->getContent());
    }

    public function testDelete() {
        $this->client->request('GET', '/blacklist/1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $link = $crawler->selectLink('Delete')->link();
        $this->client->click($link);
        
        $this->client->request('GET', '/blacklist/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertNotContains('E10E1C62-80AB-4D62-8336-C13C7BE73ED8', $this->client->getResponse()->getContent());
    }
}
