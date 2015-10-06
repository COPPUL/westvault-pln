<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Journal;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadJournals extends AbstractDataFixture implements OrderedFixtureInterface {

    public function getOrder() {
        return 1;
    }

    protected function doLoad(ObjectManager $manager) {
        $journal = new Journal();
        $journal->setEmail('test@example.com');
        $journal->setIssn('1234-1234');
        $journal->setPublisherName('Test Publisher');
        $journal->setPublisherUrl('http://example.com');
        $journal->setTitle('I J Testing');
        $journal->setUrl('http://journal.example.com');
        $journal->setStatus('healthy');
        $journal->setUuid('c0a65967-32bd-4ee8-96de-c469743e563a');
        $manager->persist($journal);
        $manager->flush();
        $this->setReference('journal', $journal);
    }

    protected function getEnvironments() {
        return array('test');
    }

}