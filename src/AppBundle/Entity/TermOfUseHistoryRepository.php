<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * TermOfUseRepository makes it easy to fetch a term.
 */
class TermOfUseHistoryRepository extends EntityRepository
{
    /**
     * Get the complete history for a term.
     *
     * @param int $termId
     *
     * @return Collection|TermOfUseHistory[]
     */
    public function getTermHistory($termId)
    {
        return $this->findBy(array(
            'termId' => $termId,
        ));
    }
}
