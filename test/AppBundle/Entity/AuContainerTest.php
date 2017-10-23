<?php

namespace AppBundle\Entity;

use PHPUnit_Framework_TestCase;

class AuContainerTest extends PHPUnit_Framework_TestCase {
	
	protected $auContainer;
	
	public function setUp() {
		$this->auContainer = new AuContainer();
	}
	
	public function setOpenClosed() {
		$this->auContainer->setOpen(false);
		$this->assertEquals(false, $this->auContainer->getOpen());
	}
	
	public function setClosedOpen() {
		$this->auContainer->setOpen(false);
		$this->auContainer->setOpen(true);
		$this->assertEquals(false, $this->auContainer->getOpen());
	}
	
	public function testGetSizeEmpty() {
		$this->assertEquals(0, $this->auContainer->getSize());
	}

	public function testGetSizeSingle() {
		$deposit = new Deposit();
		$deposit->setPackageSize(1234);
		$this->auContainer->addDeposit($deposit);
		$this->assertEquals(1234, $this->auContainer->getSize());
	}
	
	public function testGetSizeMultiple() {
		$d1 = new Deposit();
		$d1->setPackageSize(1234);
		$this->auContainer->addDeposit($d1);
		$d2 = new Deposit();
		$d2->setPackageSize(4321);
		$this->auContainer->addDeposit($d2);
		$this->assertEquals(5555, $this->auContainer->getSize());
	}

	public function testCountDepositsEmpty() {
		$this->assertEquals(0, $this->auContainer->countDeposits());
	}

	public function testCountDepositsSingle() {
		$deposit = new Deposit();
		$this->auContainer->addDeposit($deposit);
		$this->assertEquals(1, $this->auContainer->countDeposits());
	}
	
	public function testCountDepositsMultiple() {
		$d1 = new Deposit();
		$this->auContainer->addDeposit($d1);
		$d2 = new Deposit();
		$this->auContainer->addDeposit($d2);
		$this->assertEquals(2, $this->auContainer->countDeposits());
	}
}
