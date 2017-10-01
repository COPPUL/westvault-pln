<?php

namespace AppUserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User.
 *
 * Adds fullname and institution. Overrides functionality to make username
 * and email synonymous.
 *
 * @ORM\Table(name="appuser")
 * @ORM\Entity(repositoryClass="AppUserBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * Database ID.
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="fullname", type="string", nullable=true)
     */
    private $fullname;

    /**
     * @var string
     *
     * @ORM\Column(name="institution", type="string", nullable=true)
     */
    private $institution;

    /**
     * Should this user get notification emails when a institution goes silent?
     *
     * @var bool
     * @ORM\Column(name="notify", type="boolean")
     */
    private $notify;

    /**
     * Construct a user.
     */
    public function __construct()
    {
        parent::__construct();
        $this->notify = false;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the email and username.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        parent::setUsername($email);

        return parent::setEmail($email);
    }

    /**
     * Set the canonical email address.
     *
     * @param string $emailCanonical
     *
     * @return User
     */
    public function setEmailCanonical($emailCanonical)
    {
        parent::setUsernameCanonical($emailCanonical);

        return parent::setEmailCanonical($emailCanonical);
    }

    /**
     * Set institution.
     *
     * @param string $institution
     *
     * @return User
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;

        return $this;
    }

    /**
     * Get institution.
     *
     * @return string
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * Set fullname.
     *
     * @param string $fullname
     *
     * @return User
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set notify.
     *
     * @param bool $notify
     *
     * @return User
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;

        return $this;
    }

    /**
     * Get notify.
     *
     * @return bool
     */
    public function getNotify()
    {
        return $this->notify;
    }
}
