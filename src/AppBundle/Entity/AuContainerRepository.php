<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * AuContainerRepository makes it easy to find AuContainers.
 */
class AuContainerRepository extends EntityRepository
{
    /**
     * Find the open container with the lowest database ID. There should only
     * ever be one open container, but finding the one with lowest database ID
     * guarantees it.
     *
     * @return Collection|AuContainer[]
     */
    public function getOpenContainer()
    {
        return $this->findOneBy(
            array('open' => true),
            array('id' => 'ASC')
        );
    }
}
