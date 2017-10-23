<?php

namespace AppBundle\Entity;

use PHPUnit_Framework_TestCase;

class WhitelistTest extends PHPUnit_Framework_TestCase {
	
	protected $whitelist;
	
	public function setUp() {
		$this->whitelist = new Whitelist();
	}
	
	public function testSetUuidLowercase() {
		$uuid = 'abc123';
		$this->whitelist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->whitelist->getUuid());
	}
	
	public function testSetUuidUppercase() {
		$uuid = 'ABC123';
		$this->whitelist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->whitelist->getUuid());
	}
	
	public function testToString() {
		$uuid = 'abc123';
		$this->whitelist->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), (string)$this->whitelist);
	}
}