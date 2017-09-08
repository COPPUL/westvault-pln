<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * InstitutionRepository.
 *
 * This class adds a simple institution search and a few other easy queries.
 */
class InstitutionRepository extends EntityRepository
{
    /**
     * Search for a institution by title, uuid, issn, url, email, or publisher.
     *
     * @param string $q
     *
     * @return Collection|Institution[]
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
        $institutions = $query->getResult();

        return $institutions;
    }

    /**
     * Find institutions by status.
     *
     * @param string $status
     *
     * @return Collection|Institution[]
     */
    public function findByStatus($status)
    {
        return $this->findBy(array(
            'status' => $status,
        ));
    }

    /**
     * Summarize the institution statuses, counting them by status.
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
     * Find institutions that haven't contacted the PLN in $days.
     *
     * @param int $days
     *
     * @return Collection|Institution[]
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
     * Find institutions that have gone silent and that notifications have been sent
     * for, but they have not been updated yet.
     *
     * @param int $days
     *
     * @return Collection|Institution[]
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
     * institutions with status=new
     *
     * @param type $limit
     *
     * @return Collection|Institution[]
     */
    public function findNew($limit = 5)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
