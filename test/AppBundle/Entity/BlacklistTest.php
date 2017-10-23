<?php

namespace AppBundle\Entity;

use PHPUnit_Framework_TestCase;

class BlacklistTest extends PHPUnit_Framework_TestCase {
	
	protected $blacklist;
	
	public function setUp() {
		$this->blacklist = new Blacklist();
	}
	
	public function testSetUuidLowercase() {
		$uuid = 'abc123';
		$this->blacklist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->blacklist->getUuid());
	}
	
	public function testSetUuidUppercase() {
		$uuid = 'ABC123';
		$this->blacklist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->blacklist->getUuid());
	}
	
	public function testToString() {
		$uuid = 'abc123';
		$this->blacklist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), (string)$this->blacklist);
	}
}