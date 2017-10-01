<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class TermOfUseRepositoryTest extends AbstractTestCase {
	
	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:TermOfUse');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
		);
	}
	
	public function testGetTerms() {
		$r = $this->repository->getTerms();
		$this->assertEquals(3, count($r));
		
		$this->assertEquals(0, $r[0]->getWeight());
		$this->assertEquals(1, $r[1]->getWeight());
		$this->assertEquals(2, $r[2]->getWeight());
		
		$this->assertEquals(2, $r[0]->getId());
		$this->assertEquals(1, $r[1]->getId());
		$this->assertEquals(3, $r[2]->getId());
	}
}
