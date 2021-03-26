<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Lead_uni_extra_values
 *
 * @ORM\Table(name="lead_uni_extra_values")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Lead_uni_extra_values
{
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;
    
     /**
     * @ORM\ManyToOne(targetEntity="Lead_uni_extra_fields")
     */
    private $field;    
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    
     /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $creation_date;    
    
     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;    
    

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
     * @return Lead_uni_extra_values
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
     * @return Lead_uni_extra_values
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
     * Set type
     *
     * @param string $type
     *
     * @return Lead_uni_extra_values
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set field
     *
     * @param \AppBundle\Entity\Lead_uni_extra_fields $field
     *
     * @return Lead_uni_extra_values
     */
    public function setField(\AppBundle\Entity\Lead_uni_extra_fields $field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return \AppBundle\Entity\Lead_uni_extra_fields
     */
    public function getField()
    {
        return $this->field;
    }
}
