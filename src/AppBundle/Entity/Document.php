<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Help Document.
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Document
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
    private $id;

    /**
     * Document title.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $title;

    /**
     * The URL slug for the document.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $path;

    /**
     * A brief summary to display on the list of documents.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $summary;

    /**
     * The content.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $content;

    /**
     * Date when the document was updated.
     *
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $updated;

    /**
     * Automatically called to update the timestamps before insert/update
     * operations.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setUpdated()
    {
        $this->updated = new DateTime();
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
     * Set title.
     *
     * @param string $title
     *
     * @return Document
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return Document
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Document
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get updated.
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set summary.
     *
     * @param string $summary
     *
     * @return Document
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary.
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }
}
