<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\TermOfUse;
use AppBundle\Entity\TermOfUseHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Doctrine event listener to record term history. Configured as a service in
 * services.yml.
 */
class TermsOfUseListener
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * Set the logger for the event listener.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the token storage for the listener.
     *
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get an array describing the changes.
     *
     * @param UnitOfWork $unitOfWork
     * @param TermOfUse  $entity
     * @param string     $action
     *
     * @return array
     */
    protected function getChangeSet(UnitOfWork $unitOfWork, TermOfUse $entity, $action)
    {
        switch ($action) {
            case 'create':
                return array(
                    'id' => array(null, $entity->getId()),
                    'weight' => array(null, $entity->getWeight()),
                    'keyCode' => array(null, $entity->getKeyCode()),
                    'langCode' => array(null, $entity->getLangCode()),
                    'content' => array(null, $entity->getContent()),
                    'created' => array(null, $entity->getCreated()),
                    'updated' => array(null, $entity->getUpdated()),
                );
            case 'update':
                return $unitOfWork->getEntityChangeSet($entity);
            case 'delete':
                return array(
                    'id' => array($entity->getId(), null),
                    'weight' => array($entity->getWeight(), null),
                    'keyCode' => array($entity->getKeyCode(), null),
                    'langCode' => array($entity->getLangCode(), null),
                    'content' => array($entity->getContent(), null),
                    'created' => array($entity->getCreated(), null),
                    'updated' => array($entity->getUpdated(), null),
                );
        }
    }

    /**
     * Save a history event for a term of use.
     *
     * @param LifecycleEventArgs $args
     * @param string             $action
     */
    protected function saveHistory(LifecycleEventArgs $args, $action)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof TermOfUse) {
            return;
        }

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $this->getChangeSet($unitOfWork, $entity, $action);

        $history = new TermOfUseHistory();
        $history->setTermId($entity->getId());
        $history->setAction($action);
        $history->setChangeSet($changeSet);
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $history->setUser($token->getUsername());
        } else {
            $history->setUser('console');
        }
        $em->persist($history);
        $em->flush($history); // these are post-whatever events, after a flush.
    }

    /**
     * Called automatically after a term entity is persisted.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->saveHistory($args, 'create');
    }

    /**
     * Called automatically after a term entity is updated.
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->saveHistory($args, 'update');
    }

    /**
     * Called automatically before a term entity is removed.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->saveHistory($args, 'delete');
    }
}
