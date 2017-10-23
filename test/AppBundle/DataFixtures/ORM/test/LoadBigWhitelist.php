<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Whitelist;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load a white list entry for testing.
 */
class LoadBigWhitelist extends AbstractDataFixture {

    
    
    /**
     * {@inheritDoc}
     */
    protected function doLoad(ObjectManager $manager) {
        $data = array(
            array('FC1EFBBA-BFA4-4505-A398-006FDBE6A9D7', 'this is a comment that is searchable'),
            array('9B6DDEFD-F74F-47FC-A6F2-4CDC549637C9', 'cheese is good for you'),
            array('4B84F713-7167-4F3F-A2CD-028E5C9F2A05', 'very model of a modern major'),
            array('A5ABCD90-4226-4B9A-849B-C78A856008B1', 'something about a general here'),
        );
        
        foreach($data as $d) {
            $entry = new Whitelist();
            $entry->setComment($d[1]);
            $entry->setUuid($d[0]);
            $manager->persist($entry);
        }
        
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEnvironments() {
        return array('test');
    }
}
