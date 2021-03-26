<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Leadout
 *
 * @ORM\Table(name="leadout")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Leadout
{
     /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;
    
     /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    
     /**
     * @ORM\Column(type="datetime")
     */
    private $creation_date;
    
    /**
    * @ORM\ManyToOne(targetEntity="Brand")
    */
    private $parent;   

    

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
     * Set name
     *
     * @param string $name
     *
     * @return Leadout
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
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Leadout
     */
    public function setCreationDate($creationDate)
    {
        $this->creation_date = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creation_date;
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\Brand $parent
     *
     * @return Leadout
     */
    public function setParent(\AppBundle\Entity\Brand $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Brand
     */
    public function getParent()
    {
        return $this->parent;
    }
}
