<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Blacklist;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a black list entry for testing.
 */
class LoadBlacklist extends AbstractDataFixture {

    /**
     * {@inheritDoc}
     */
    protected function doLoad(ObjectManager $manager) {
        $entry = new Blacklist();
        $entry->setComment('Test entry');
        $entry->setUuid('e10e1c62-80ab-4d62-8336-c13c7be73ed8');
        $manager->persist($entry);
        $manager->flush();
        $this->setReference('bl', $entry);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}
