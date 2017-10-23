<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Document;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a deposit for testing.
 */
class LoadDocs extends AbstractDataFixture implements OrderedFixtureInterface {

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
        $doc = new Document();
        $doc->setContent("<p>Content is good and cheesy.</p>");
        $doc->setPath('test1');
        $doc->setSummary("summarized");
        $doc->setTitle("title1");        
        $manager->persist($doc);
        $this->setReference('doc', $doc);
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}