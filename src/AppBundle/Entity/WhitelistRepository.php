<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * WhitelistRepository makes searching whitelisted entities easier.
 */
class WhitelistRepository extends EntityRepository
{
    /**
     * Search for whitelist entries by uuid or comment.
     *
     * @param string $q
     *
     * @return Container|Whitelist[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->where(
            $qb->expr()->like(
                new Func(
                    'CONCAT',
                    array(
                        'w.uuid',
                        'w.comment',
                    )
                ),
                "'%$q%'"
            )
        );
        $query = $qb->getQuery();
        $listed = $query->getResult();

        return $listed;
    }
}
