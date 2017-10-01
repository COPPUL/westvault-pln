<?php

namespace AppBundle\Services;

use AppBundle\Utility\AbstractTestCase;

class BlackWhitelistTest extends AbstractTestCase {

	/**
	 * @var BlackWhitelist
	 */
	protected $list;
	
	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadBlacklist',
			'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
		);
	}
	
	public function setUp() {
		parent::setUp();
		$this->list = $this->getContainer()->get('blackwhitelist');				
	}
	
	public function testGet() {
		$this->assertInstanceOf('AppBundle\Services\BlackWhitelist', $this->list);
	}
	
	public function testIsWhitelistedLowercase() {
		$this->assertTrue($this->list->isWhitelisted('6646afaa-beba-40c8-a286-c64a3e90d0f6'));
		$this->assertFalse($this->list->isWhitelisted('6646afaa-beba-a286-c64a3e90d0f6'));
	}
	
	public function testIsWhitelistedUppercase() {
		$this->assertTrue($this->list->isWhitelisted('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6'));
		$this->assertFalse($this->list->isWhitelisted('BEBA-40C8-A286-C64A3E90D0F6'));
	}
	
	public function testIsBlacklistedLowercase() {
		$this->assertTrue($this->list->isBlacklisted('e10e1c62-80ab-4d62-8336-c13c7be73ed8'));
		$this->assertFalse($this->list->isBlacklisted('e10e1c62-c13c7be73ed8'));		
	}
	
	public function testIsBlacklistedUppercase() {
		$this->assertTrue($this->list->isBlacklisted('E10E1C62-80AB-4D62-8336-C13C7BE73ED8'));
		$this->assertFalse($this->list->isBlacklisted('E10E1C62-C13C7BE73ED8'));
	}
}