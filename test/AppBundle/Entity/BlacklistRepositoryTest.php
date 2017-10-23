<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class BlacklistRepositoryTest extends AbstractTestCase {
	
	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:Blacklist');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadBlacklist',
		);
	}
	
	public function testSearchUuid() {
		$bl = $this->repository->search('e10e1c62');
		$this->assertEquals(1, count($bl));
	}

	public function testSearchComment() {
		$bl = $this->repository->search('entry');
		$this->assertEquals(1, count($bl));
	}
	
	public function testSearchNoResults() {
		$bl = $this->repository->search('cheeses');
		$this->assertEquals(0, count($bl));
	}
}
