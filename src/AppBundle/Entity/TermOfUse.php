<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TermOfUse.
 *
 * A single term of use that the institution managers must agree to.
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="TermOfUseRepository")
 */
class TermOfUse
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
     * The "weight" of the term. Heavier terms are sorted lower.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $weight;

    /**
     * The date the term was created.
     *
     * @var string
     *
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * The date the term was updated.
     *
     * @var string
     *
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * A term key code, something unique to all versions and translations
     * of a term.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $keyCode;

    /**
     * ISO language code.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $langCode;

    /**
     * The content of the term, in the language in $langCode.
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $content;

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
     * Set weight.
     *
     * @param int $weight
     *
     * @return TermOfUse
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Get created.
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set keyCode.
     *
     * @param string $keyCode
     *
     * @return TermOfUse
     */
    public function setKeyCode($keyCode)
    {
        $this->keyCode = $keyCode;

        return $this;
    }

    /**
     * Get keyCode.
     *
     * @return string
     */
    public function getKeyCode()
    {
        return $this->keyCode;
    }

    /**
     * Set langCode.
     *
     * @param string $langCode
     *
     * @return TermOfUse
     */
    public function setLangCode($langCode)
    {
        $this->langCode = $langCode;

        return $this;
    }

    /**
     * Get langCode.
     *
     * @return string
     */
    public function getLangCode()
    {
        return $this->langCode;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return TermOfUse
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
     * Called automatically before the database entry is updated or created to
     * set the timestamps.
     *
     * @ORM\PrePersist
     */
    public function setCreatedTimestamp()
    {
        $this->created = new DateTime();
        $this->updated = new DateTime();
    }

    /**
     * Called automatically before the database entry is updated or created to
     * set the timestamps.
     *
     * @ORM\PreUpdate
     */
    public function setUpdatedTimestamp()
    {
        $this->updated = new DateTime();
    }

    /**
     * The term's content is a stringified representation. Returns the content.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * Get updated.
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
