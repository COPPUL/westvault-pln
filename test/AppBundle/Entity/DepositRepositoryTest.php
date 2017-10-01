<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class DepositRepositoryTest extends AbstractTestCase {

	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:Deposit');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
		);
	}

	public function testFindByState() {
		$r = $this->repository->findByState('depositedByJournal');
		$this->assertEquals(1, count($r));
	}

	public function testStateSummary() {
		$expected = Array(Array(
				'state' => 'depositedByJournal',
				'ct' => '1',
			), Array(
				'state' => 'harvested',
				'ct' => '1',
			)
		);

		$r = $this->repository->stateSummary();
		$this->assertEquals($expected, $r);
	}
	
	/**
	 * @dataProvider termProvider
	 * @param string $term
	 */
	public function testSearch($term) {
		$r = $this->repository->search($term);
		$this->assertEquals(1, count($r));
	}
	
	public function termProvider() {
		return array(
			array('d38e7ecb')
		);
	}

	public function testFindNew() {
		$r = $this->repository->findNew();
		$this->assertEquals(2, count($r));
	}
}
