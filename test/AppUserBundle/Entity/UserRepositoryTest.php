<?php


namespace AppUserBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class UserRepositoryTest extends AbstractTestCase {

	protected $userRepo;
	
	public function setUp() {
		parent::setUp();
		$this->userRepo = $this->em->getRepository('AppUserBundle:User');
	}
	
	public function fixtures() {
		return array(
			'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
		);
	}
	
	public function testFindNotify() {
		$this->assertCount(1, $this->userRepo->findUserToNotify());
	}
}
