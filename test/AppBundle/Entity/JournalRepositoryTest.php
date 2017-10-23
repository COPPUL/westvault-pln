<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class JournalRepositoryTest extends AbstractTestCase {

	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:Journal');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals'
		);
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
			array('Testing'),
			array('c0a65967'),
			array('1234-1234'),
			array('journal.example.com'),
			array('@example.com'),
			array('Test Publisher'),
		);
	}

	public function testFindByStatus() {
		$r = $this->repository->findByStatus('healthy');
		$this->assertEquals(1, count($r));
	}
	
	public function testStatusSummary() {
		$expected = Array(
			Array(
				'status' => 'healthy',
				'ct' => '1',
			),
			Array(
				'status' => 'new',
				'ct' => '1',
			)
		);
		$r = $this->repository->statusSummary();
		$this->assertEquals($expected, $r);
	}

	public function testFindSilent() {
		$this->em->clear();
		$r = $this->repository->findSilent(9);
		$this->assertEquals(1, count($r));
	}

	public function testFindOverdue() {
		$r = $this->repository->findOverdue(4);
		$this->assertEquals(1, count($r));
	}

	public function testFindNew() {
		$r = $this->repository->findNew();
		$this->assertEquals(2, count($r));
	}
}
