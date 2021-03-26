<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Brand
 *
 * @ORM\Table(name="a_lead_extra_values")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class A_lead_extra_values
{
    /**
     * @ORM\ManyToOne(targetEntity="Lead_uni")
     * @ORM\Id
     */
    private $lead;
    
    /**
     * @ORM\ManyToOne(targetEntity="Lead_uni_extra_values")
     * @ORM\Id
     */
    private $value;   
        
     /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $creation_date;        


    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return A_lead_extra_values
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
     * Set lead
     *
     * @param \AppBundle\Entity\Lead_uni $lead
     *
     * @return A_lead_extra_values
     */
    public function setLead(\AppBundle\Entity\Lead_uni $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return \AppBundle\Entity\Lead_uni
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set value
     *
     * @param \AppBundle\Entity\Lead_uni_extra_values $value
     *
     * @return A_lead_extra_values
     */
    public function setValue(\AppBundle\Entity\Lead_uni_extra_values $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return \AppBundle\Entity\Lead_uni_extra_values
     */
    public function getValue()
    {
        return $this->value;
    }
}
