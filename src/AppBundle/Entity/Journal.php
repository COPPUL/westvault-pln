<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Journal
 *
 * Any OJS journal may make deposits to the PLN.
 *
 * @ORM\Table(name="journal")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="JournalRepository")
 */
class Journal {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Journal UUID, as generated by the PLN plugin.
     *
     * @var string
     * 
     * @Assert\Uuid
     * @ORM\Column(type="string", length=36, nullable=false)
     */
    private $uuid;

    /**
     * When the journal last contacted the staging server
     *
     * @var string
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $contacted;

    /**
     * When the journal manager was notified.
     *
     * @var string
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $notified;

    /**
     * The title of the journal.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $title;

    /**
     * Journal's ISSN
     *
     * @var string
     * @ORM\Column(type="string", length=9, nullable=false)
     */
    private $issn;

    /**
     *
     * @var string
     * 
     * @Assert\Url
     * @ORM\Column(type="string", nullable=false)
     */
    private $url;

    /**
     * The status of the journal's health.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $status = 'healthy';

    /**
     * Email address to contact the journal manager.
     *
     * @var string
     * @Assert\Email
     * @ORM\Column(type="string", nullable=false)
     */
    private $email;

    /**
     * Name of the publisher
     *
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $publisherName;

    /**
     * Publisher's website.
     *
     * @var string
     * @Assert\Url
     * @ORM\Column(type="string", nullable=true)
     */
    private $publisherUrl;

    /**
     * The journal's deposits.
     *
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="journal")
     */
    private $deposits;

    public function __construct() {
        $this->deposits = new ArrayCollection();
    }

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
     * @return Journal
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
     * Set contacted
     *
     * @param DateTime $contacted
     * @return Journal
     */
    public function setContacted(DateTime $contacted)
    {
        $this->contacted = $contacted;

        return $this;
    }

    /**
     * Get contacted
     *
     * @return DateTime
     */
    public function getContacted()
    {
        return $this->contacted;
    }

    /**
     * Set notified
     *
     * @param DateTime $notified
     * @return Journal
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;

        return $this;
    }

    /**
     * Get notified
     *
     * @return DateTime
     */
    public function getNotified()
    {
        return $this->notified;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Journal
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set issn
     *
     * @param string $issn
     * @return Journal
     */
    public function setIssn($issn)
    {
        $this->issn = $issn;

        return $this;
    }

    /**
     * Get issn
     *
     * @return string 
     */
    public function getIssn()
    {
        return $this->issn;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Journal
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    public function getGatewayUrl() {
        return $this->url . '/gateway/plugin/PLNGatewayPlugin';
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Journal
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Journal
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set publisherName
     *
     * @param string $publisherName
     * @return Journal
     */
    public function setPublisherName($publisherName)
    {
        $this->publisherName = $publisherName;

        return $this;
    }

    /**
     * Get publisherName
     *
     * @return string 
     */
    public function getPublisherName()
    {
        return $this->publisherName;
    }

    /**
     * Set publisherUrl
     *
     * @param string $publisherUrl
     * @return Journal
     */
    public function setPublisherUrl($publisherUrl)
    {
        $this->publisherUrl = $publisherUrl;

        return $this;
    }

    /**
     * Get publisherUrl
     *
     * @return string 
     */
    public function getPublisherUrl()
    {
        return $this->publisherUrl;
    }

    /**
     * @ORM\PrePersist
     */
    public function setTimestamp() {
        $this->contacted = new DateTime();
    }

    /**
     * Add deposits
     *
     * @param Deposit $deposits
     * @return Journal
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

    /**
     * Get the deposits which have been sent to LOCKSSOMatic.
     * 
     * @return Deposit[]
     */
    public function getCompletedDeposits() {
        $completed = [];
        foreach($this->deposits as $deposit) {
            //if($deposit->getState() === 'deposited') {
                $completed[] = $deposit;
            //}
        }
        return $completed;
    }

    /**
     * Count the deposits for a journal.
     *
     * @return int
     */
    public function countDeposits() {
        return $this->deposits->count();
    }

    /**
     * The title of the journal is it's stringified representation.
     *
     * @return string
     */
    public function __toString() {
        return $this->title;
    }

}
