<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class WhitelistRepositoryTest extends AbstractTestCase {
	
	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:Whitelist');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
		);
	}
	
	public function testSearchUuid() {
		$bl = $this->repository->search('6646afaa');
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
