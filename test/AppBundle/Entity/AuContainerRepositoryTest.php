<?php

namespace AppBundle\Entity;

use AppBundle\Utility\AbstractTestCase;

class AuContainerRepositoryTest extends AbstractTestCase {
	
	/**
	 * @var AuContainer
	 */
	protected $repository;

	public function setUp() {
		parent::setUp();
		$this->repository = $this->em->getRepository('AppBundle:AuContainer');
	}

	public function testGetOpenContainer() {
		$c = $this->repository->getOpenContainer();
		$this->assertInstanceOf('AppBundle\Entity\AuContainer', $c);
		$this->assertEquals(true, $c->isOpen());
		$this->assertEquals(2, $c->getId());
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadAuContainers',
		);
	}
}
