<?php

namespace AppBundle\Entity;

use PHPUnit_Framework_TestCase;

class JournalTest extends PHPUnit_Framework_TestCase {
	
	protected $journal;
	
	public function setUp() {
		$this->journal = new Journal();
	}
	
	public function testSetUuidLowercase() {
		$uuid = 'abcd1234';
		$this->journal->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->journal->getUuid());
	}
	
	public function testSetUuidUppercase() {
		$uuid = 'ABCD1234';
		$this->journal->setUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->journal->getUuid());
	}
	
	public function testGetEmptyTitle() {
		$this->assertEquals('(unknown)', $this->journal->getTitle());
	}
	
	public function testGetTitle() {
		$this->journal->setTitle('Hello');
		$this->assertEquals('Hello', $this->journal->getTitle());
	}
	
	public function testGetGatewayUrl() {
		$this->journal->setUrl('http://foo');
		$this->assertEquals('http://foo/gateway/plugin/PLNGatewayPlugin', $this->journal->getGatewayUrl());
	}
	
	public function testGetCompletedDeposits() {
		$d1 = new Deposit();
		$d1->setState('completed');
		$this->journal->addDeposit($d1);
		$d2 = new Deposit();
		$this->journal->addDeposit($d2);
		$completed = $this->journal->getCompletedDeposits();
		$this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $completed);
		$this->assertCount(1, $completed);
	}
	
	public function testToStringEmptyTitle() {
		$this->assertEquals('(unknown)', (string)$this->journal);
	}
	
	public function testToStringTitle() {
		$this->journal->setTitle('Hi there');
		$this->assertEquals('Hi there', (string)$this->journal);
	}
}