<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Journal;
use AppBundle\Utility\AbstractDataFixture;
use DateInterval;
use DateTime;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a journal for testing.
 */
class LoadJournals extends AbstractDataFixture implements OrderedFixtureInterface {

    /**
     * {@inheritDoc}
     */
    public function getOrder() {
        return 1;		
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoad(ObjectManager $manager) {
        $j0 = new Journal();
        $j0->setEmail('test@example.com');
        $j0->setIssn('1234-1234');
        $j0->setPublisherName('Test Publisher');
        $j0->setPublisherUrl('http://example.com');
        $j0->setTitle('I J Testing');
        $j0->setUrl('http://journal.example.com');
        $j0->setStatus('healthy');
        $j0->setUuid('c0a65967-32bd-4ee8-96de-c469743e563a');
		$j0->setOjsVersion('2.4.8.0');
        $manager->persist($j0);
		$j0->setContacted(new DateTime('-10 days'));
		$j0->setNotified(new DateTime('-5 days'));
		
        $j1 = new Journal();
        $j1->setEmail('foo@bar.com');
        $j1->setIssn('4321-4321');
        $j1->setPublisherName('Orange Inc');
        $j1->setPublisherUrl('http://orangula.dev');
        $j1->setTitle('J Oranges');
        $j1->setUrl('http://journal.orangula.dev');
        $j1->setStatus('new');
        $j1->setUuid('A556CBF2-B674-444F-87B7-23DEE36F013D');
        $j1->setContacted(new DateTime());
        $manager->persist($j1);
		
        $manager->flush();
        $this->setReference('journal', $j0);
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}