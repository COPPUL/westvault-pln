<?php

namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Service to check a UUID for whitelist/blacklist status.
 */
class BlackWhitelist
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * Construct the service.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     * Return true if the uuid is whitelisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isWhitelisted($uuid)
    {
        $repo = $this->em->getRepository('AppBundle:Whitelist');

        return $repo->findOneBy(array(
            'uuid' => strtoupper($uuid), )) !== null;
    }

    /**
     * Return true if the uuid is blacklisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isBlacklisted($uuid)
    {
        $repo = $this->em->getRepository('AppBundle:Blacklist');

        return $repo->findOneBy(array(
            'uuid' => strtoupper($uuid), )) !== null;
    }
}
