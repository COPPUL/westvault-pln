<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractDataFixture;
use DateTime;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a deposit for testing.
 */
class LoadDeposits extends AbstractDataFixture implements OrderedFixtureInterface {

	/**
	 * {@inheritDoc}
	 */
	public function getOrder() {
		return 2; // must be after LoadJournals.
	}

	/**
	 * {@inheritDoc}
	 */
	protected function doLoad(ObjectManager $manager) {
		$d0 = new Deposit();
		$d0->setAction('add');
		$d0->setChecksumType('SHA1');
		$d0->setChecksumValue('b4b0bfbaadf33f8e678ad9cd0c1da2c9a6e7d204');
		$d0->setDepositUuid('d38e7ecb-7d7e-408d-94b0-b00d434fdbd2');
		$d0->setDepositReceipt('http://example.com/path/to/receipt');
		$d0->setFileType('application/zip');
		$d0->setIssue(2);
		$d0->setJournal($this->getReference('journal'));
		$d0->setPubDate(new DateTime());
		$d0->setSize(100);
		$d0->setState('depositedByJournal');
		$d0->setUrl('http://journal.example.com/path/to/deposit');
		$d0->setVolume(1);
		$manager->persist($d0);

		$d1 = new Deposit();
		$d1->setAction('add');
		$d1->setChecksumType('SHA1');
		$d1->setChecksumValue('f1d2d2f924e986ac86fdf7b36c94bcdf32beec15');
		$d1->setDepositUuid('578205CB-0947-4CD3-A384-CDF186F5E86B');
		$d1->setDepositReceipt('http://example2.com/path/to/receipt');
		$d1->setFileType('application/zip');
		$d1->setIssue(4);
		$d1->setJournal($this->getReference('journal'));
		$d1->setPubDate(new DateTime());
		$d1->setSize(1000);
		$d1->setState('harvested');
		$d1->setUrl('http://journal.example2.com/path/to/deposit');
		$d1->setVolume(2);
		$manager->persist($d1);

		$manager->flush();
		$this->setReference('deposit', $d0);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getEnvironments() {
		return array('test');
	}
}
