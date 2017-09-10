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
                        'j.title',
                        'j.uuid',
                        'j.issn',
                        'j.url',
                        'j.email',
                        'j.publisherName',
                    )
                ),
                "'%$q%'"
            )
        );
        $query = $qb->getQuery();
        $providers = $query->getResult();

        return $providers;
    }

    /**
     * Find providers by status.
     *
     * @param string $status
     *
     * @return Collection|Provider[]
     */
    public function findByStatus($status)
    {
        return $this->findBy(array(
            'status' => $status,
        ));
    }

    /**
     * Summarize the provider statuses, counting them by status.
     *
     * @return array
     */
    public function statusSummary()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.status, count(e) as ct')
            ->groupBy('e.status')
            ->orderBy('e.status');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find providers that haven't contacted the PLN in $days.
     *
     * @param int $days
     *
     * @return Collection|Provider[]
     */
    public function findSilent($days)
    {
        $dt = new DateTime("-{$days} day");

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.contacted < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find providers that have gone silent and that notifications have been sent
     * for, but they have not been updated yet.
     *
     * @param int $days
     *
     * @return Collection|Provider[]
     */
    public function findOverdue($days)
    {
        $dt = new DateTime("-{$days} day");
        $qb = $this->createQueryBuilder('e');
        $qb->Where('e.notified < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQUery()->getResult();
    }

    /**
     * @todo This method should be called findRecent(). It does not find
     * providers with status=new
     *
     * @param type $limit
     *
     * @return Collection|Provider[]
     */
    public function findNew($limit = 5)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
