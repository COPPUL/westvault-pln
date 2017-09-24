<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Provider.
 *
 * Any OJS provider may make deposits to the PLN.
 *
 * @ORM\Table(name="provider")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="ProviderRepository")
 */
class Provider
{
    /**
     * List of states where a deposit has been sent to LOCKSSOMatic.
     *
     * This should be a constant array, but those aren't supported in PHP 5.4.
     *
     * @var array
     */
    public static $SENTSTATES = array(
            'deposited',
            'complete',
            'status-error',
        );

    /**
     * Database ID.
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Provider UUID, as generated by the PLN plugin.
     *
     * @var string
     * @ORM\Column(type="string", length=36, nullable=false)
     */
    private $uuid;

    /**
     * The title of the provider.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * The provider's deposits.
     * @var Deposit[]|Collection
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="provider")
     */
    private $deposits;

    /**
     * Construct a new Provider.
     */
    public function __construct()
    {
        $this->deposits = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     * @return Provider
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string 
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Provider
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add deposits
     *
     * @param Deposit $deposits
     * @return Provider
     */
    public function addDeposit(Deposit $deposits)
    {
        $this->deposits[] = $deposits;

        return $this;
    }

    /**
     * Remove deposits
     *
     * @param Deposit $deposits
     */
    public function removeDeposit(Deposit $deposits)
    {
        $this->deposits->removeElement($deposits);
    }

    /**
     * Get deposits
     *
     * @return Collection 
     */
    public function getDeposits()
    {
        return $this->deposits;
    }
    
    public function countDeposits() {
        return $this->deposits->count();
    }
}
