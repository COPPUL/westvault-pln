<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * DepositRepository provides some useful functions for querying deposits.
 */
class DepositRepository extends EntityRepository
{
    /**
     * Find deposits by state.
     *
     * @param string $state
     *
     * @return Collection|Deposit[]
     */
    public function findByState($state)
    {
        return $this->findBy(array(
            'state' => $state,
        ));
    }

    /**
     * Summarize deposits by counting them by state.
     *
     * @return array
     */
    public function stateSummary()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.state, count(e) as ct')
                ->groupBy('e.state')
                ->orderBy('e.state');

        return $qb->getQuery()->getResult();
    }

    /**
     * Search for deposits by UUID or part of a UUID.
     *
     * @param string $q
     *
     * @return Collection|Deposit[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.depositUuid LIKE :q');
		$qb->orWhere('d.depositUuid LIKE :q');
        $qb->setParameter('q', '%'.strtoupper($q).'%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Return some recent deposits.
     *
     * @todo this should be called findRecent
     *
     * @param type $limit
     *
     * @return Collection|Deposit[]
     */
    public function findNew($limit = 5)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->orderBy('d.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
