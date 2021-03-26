<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * A_giacliente_brand
 *
 * @ORM\Table(name="a_giacliente_brand")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class A_giacliente_brand
{
    /**
     * @ORM\ManyToOne(targetEntity="Lead_uni")
     * @ORM\Id
     */
    private $lead;
    
    /**
     * @ORM\ManyToOne(targetEntity="Brand")
     * @ORM\Id
     */
    private $brand;
    
        

    /**
     * Set lead
     *
     * @param \AppBundle\Entity\Lead_uni $lead
     *
     * @return A_giacliente_brand
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
     * Set brand
     *
     * @param \AppBundle\Entity\Brand $brand
     *
     * @return A_giacliente_brand
     */
    public function setBrand(\AppBundle\Entity\Brand $brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return \AppBundle\Entity\Brand
     */
    public function getBrand()
    {
        return $this->brand;
    }
}
