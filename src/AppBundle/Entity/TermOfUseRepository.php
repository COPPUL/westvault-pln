<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * TermOfUseRepository makes fetching the terms in weight order easy.
 */
class TermOfUseRepository extends EntityRepository
{
    /**
     * Get the terms, ordered by weight.
     *
     * @return Collection|TermOfUse[]
     */
    public function getTerms()
    {
        $qb = $this->createQueryBuilder('t')
                ->orderBy('t.weight', 'ASC')
                ->getQuery();

        return $qb->getResult();
    }
}
