<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Blacklist
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="BlacklistRepository")
 */
class Blacklist {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Journal UUID, as generated by the PLN plugin. This cannot be part
     * of a relationship - a journal may be listed before we have
     * a record of it.
     *
     * @var string
     * @Assert\Uuid
     * @ORM\Column(type="string", length=36, nullable=false)
     */
    private $uuid;

    /**
     * Short message describing why the journal was listed.
     *
     * @var type
     * @ORM\Column(type="text")
     */
    private $comment;

    /**
     * The date the blacklist entry was created.
     *
     * @var string
     * 
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }


    /**
     * Set uuid
     *
     * @param string $uuid
     * @return Blacklist
     */
    public function setUuid($uuid)
    {
        $this->uuid = strtoupper($uuid);

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
     * Set created
     *
     * @param \DateTime $created
     * @return Blacklist
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @ORM\PrePersist
     */
    public function setTimestamp() {
        $this->created = new DateTime();
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Blacklist
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }
    
        
    public function __toString() {
        return $this->uuid;
    }

}
