<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\TermOfUse;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Load terms of use for testing.
 */
class LoadTermsOfUse extends AbstractDataFixture {

    /**
     * @var ObjectManager
     */
    private $manager;

    private $terms = array(
        [1, 'en-US', 'test.a', 'first term.'],
        [0, 'en-US', 'test.b', 'second term.'],
        [2, 'en-US', 'test.c', 'third term.'],
    );

    /**
     * {@inheritDoc}
     */
    private function createTerm($weight, $langCode, $key, $content) {
        $term = new TermOfUse();
        $term->setWeight($weight);
        $term->setLangCode($langCode);
        $term->setKeyCode($key);
        $term->setContent($content);
        $this->manager->persist($term);
    }

    /**
     * {@inheritDoc}
     */
    public function doLoad(ObjectManager $manager) {
        $this->manager = $manager;
        foreach($this->terms as $data) {
            $this->createTerm($data[0], $data[1], $data[2], $data[3]);
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
