<?php

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminUserControllerTest extends AbstractTestCase {

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
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
	
	public function testIndex() {
		$this->client->request('GET', '/admin/user/');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$this->assertContains('admin@example.com', $this->client->getResponse()->getContent());
		$this->assertContains('user@example.com', $this->client->getResponse()->getContent());
	}
	
	public function testCreate() {
		$this->client->request('GET', 'admin/user/new');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Create');
		$form = $formCrawler->form(array(
			'appbundle_user[email]' => 'bob@example.com',
			'appbundle_user[fullname]' => 'Bob Terwilliger',
			'appbundle_user[institution]' => 'Springfield State Penn',
			'appbundle_user[notify]' => 1,
			'appbundle_user[enabled]' => 1,
			'appbundle_user[roles]' => array('ROLE_ADMIN'),			
		));
		$this->client->submit($form);
		
		$this->em->clear();
		$user = $this->em->getRepository('AppUserBundle:User')->findOneBy(array(
			'email' => 'bob@example.com',
		));
		$this->assertInstanceOf('AppUserBundle\Entity\User', $user);
		$this->assertEquals('bob@example.com', $user->getUsername());
		$this->assertEquals('bob@example.com', $user->getEmail());
		$this->assertEquals('Bob Terwilliger', $user->getFullname());
		$this->assertEquals('Springfield State Penn', $user->getInstitution());
	}
	
	public function testEdit() {
		$this->client->request('GET', 'admin/user/2/edit');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->getCrawler();
        $formCrawler = $crawler->selectButton('Update');
		$form = $formCrawler->form(array(
			'appbundle_user[email]' => 'bart@example.com',
			'appbundle_user[fullname]' => 'Bart Simpson',
			'appbundle_user[institution]' => 'Springfield Elementary',
			'appbundle_user[notify]' => 1,
			'appbundle_user[enabled]' => 1,
			'appbundle_user[roles]' => array('ROLE_ADMIN'),			
		));
		$this->client->submit($form);
		
		$this->em->clear();
		$user = $this->em->getRepository('AppUserBundle:User')->findOneBy(array(
			'email' => 'bart@example.com',
		));
		$this->assertInstanceOf('AppUserBundle\Entity\User', $user);
		$this->assertEquals('bart@example.com', $user->getUsername());
		$this->assertEquals('bart@example.com', $user->getEmail());
		$this->assertEquals('Bart Simpson', $user->getFullname());
		$this->assertEquals('Springfield Elementary', $user->getInstitution());
	}

	public function testDelete() {
		$this->client->followRedirects(true);
		$this->client->request('GET', 'admin/user/2/delete');
		$this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
		$this->em->clear();
		$user = $this->em->getRepository('AppUserBundle:User')->findOneBy(array(
			'email' => 'bart@example.com',
		));
		$this->assertNull($user);
	}
}
