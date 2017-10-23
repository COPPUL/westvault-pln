<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * ProviderRepository.
 *
 * This class adds a simple provider search and a few other easy queries.
 */
class ProviderRepository extends EntityRepository
{
    /**
     * Search for a provider by title, uuid, issn, url, email, or publisher.
     *
     * @param string $q
     *
     * @return Collection|Provider[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('j');
        $qb->where(
            $qb->expr()->like(
                new Func(
                    'CONCAT',
                    array(
                        'j.name',
                    )
                ),
                "'%$q%'"
            )
        );
        $query = $qb->getQuery();
        $providers = $query->getResult();

        return $providers;
    }
}
