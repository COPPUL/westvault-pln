<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuContainerControllerTest extends AbstractTestCase {

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
            'AppBundle\DataFixtures\ORM\test\LoadDocs',
            'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
            'AppBundle\DataFixtures\ORM\test\LoadAuContainers',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testIndex() {
        $this->client->request('GET', '/aucontainer/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('Displaying 3 records of 3 total.', $response->getContent());
        $this->assertContains('2 (0 deposits/0kb)', $this->client->getResponse()->getContent());
    }
}
