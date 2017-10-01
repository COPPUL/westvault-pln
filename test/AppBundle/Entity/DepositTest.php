<?php

namespace AppBundle\Entity;

use PHPUnit_Framework_TestCase;

class DepositTest extends PHPUnit_Framework_TestCase {

	protected $deposit;

	public function setUp() {
		$this->deposit = new Deposit();
	}

	public function testDefaults() {
		$this->assertEquals('depositedByJournal', $this->deposit->getState());
	}

	public function testSetDepositUuidLowercase() {
		$uuid = 'abc123';
		$this->deposit->setDepositUuid($uuid);
		$this->assertEquals(strtoupper($uuid), $this->deposit->getDepositUuid());
	}

	public function testSetDepositUuidUppercase() {
		$uuid = 'ABC123';
		$this->deposit->setDepositUuid($uuid);
		$this->assertEquals($uuid, $this->deposit->getDepositUuid());
	}

	public function testSetDepositChecksumValueLowercase() {
		$value = 'abc123';
		$this->deposit->setChecksumValue($value);
		$this->assertEquals(strtoupper($value), $this->deposit->getChecksumValue());
	}

	public function testSetDepositChecksumValueUppercase() {
		$value = 'ABC123';
		$this->deposit->setChecksumValue($value);
		$this->assertEquals($value, $this->deposit->getChecksumValue());
	}

	public function testToString() {
		$uuid = 'abc123';
		$this->deposit->setDepositUuid($uuid);
		$this->assertEquals(strtoupper($uuid), (string) $this->deposit);
	}

	public function testGetFileNameZip() {
		$this->deposit->setFileType('application/zip');
		$uuid = 'abc123';
		$this->deposit->setDepositUuid($uuid);
		$this->assertEquals(strtoupper($uuid) . '.zip', $this->deposit->getFileName());
	}

	public function testGetFileNameTarball() {
		$this->deposit->setFileType('application/x-gzip');
		$uuid = 'abc123';
		$this->deposit->setDepositUuid($uuid);
		$this->assertEquals(strtoupper($uuid) . '.tgz', $this->deposit->getFileName());
	}

	public function testAddToProcessingLog() {
		$this->deposit->addToProcessingLog("hello world.");
		$log = $this->deposit->getProcessingLog();
		$this->assertEquals(4, count(explode("\n", $log)));
		$this->assertStringEndsWith("hello world.\n\n", $log);
	}

	public function testSetPackageChecksumValueLowercase() {
		$value = 'abc123';
		$this->deposit->setPackageChecksumValue($value);
		$this->assertEquals(strtoupper($value), $this->deposit->getPackageChecksumValue());
	}

	public function testSetPackageChecksumValueUppercase() {
		$value = 'ABC123';
		$this->deposit->setPackageChecksumValue($value);
		$this->assertEquals($value, $this->deposit->getPackageChecksumValue());
	}
}
