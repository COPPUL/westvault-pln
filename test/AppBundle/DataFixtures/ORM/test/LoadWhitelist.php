<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Whitelist;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a many whitelist entries for testing.
 */
class LoadWhitelist extends AbstractDataFixture {

    /**
     * {@inheritDoc}
     */
    protected function doLoad(ObjectManager $manager) {
        $e0 = new Whitelist();
        $e0->setComment('Test entry');
        $e0->setUuid('6646afaa-beba-40c8-a286-c64a3e90d0f6');
        $manager->persist($e0);
		
		$e1 = new Whitelist();
		$e1->setComment('Test journal');
		$e1->setUuid('c0a65967-32bd-4ee8-96de-c469743e563a');
		$manager->persist($e1);
		
        $manager->flush();
        $this->setReference('wl', $e0);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}
