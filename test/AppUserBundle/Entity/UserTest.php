<?php


namespace AppUserBundle\Entity;

use AppUserBundle\Entity\User;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase {

	protected $user;
	
	public function setUp() {
		$this->user = new User();
	}
	
	public function testConstructor() {
		$this->assertFalse($this->user->getNotify());
	}
	
	public function testUsername() {
		$this->user->setEmail('u@example.com');
		$this->assertEquals('u@example.com', $this->user->getUsername());
	}
}
