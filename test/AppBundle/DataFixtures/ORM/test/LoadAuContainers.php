<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\AuContainer;
use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractDataFixture;
use DateTime;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a deposit for testing.
 */
class LoadAuContainers extends AbstractDataFixture implements OrderedFixtureInterface {

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
		$c1 = new AuContainer();
		$c1->setOpen(false);
        $this->setReference('aucontainer', $c1);
		$manager->persist($c1);
		$c2 = new AuContainer();
		$manager->persist($c2);
		$c3 = new AuContainer();
		$manager->persist($c3);
		$manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}