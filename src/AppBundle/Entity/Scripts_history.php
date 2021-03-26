<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scripts_history
 *
 * @ORM\Table(name="scripts_history")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Scripts_history
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
    private $launch_date;    
    
     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $output;    
    
    
    
    

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
     * @return Scripts_history
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
     * Set launchDate
     *
     * @param \DateTime $launchDate
     *
     * @return Scripts_history
     */
    public function setLaunchDate($launchDate)
    {
        $this->launch_date = $launchDate;

        return $this;
    }

    /**
     * Get launchDate
     *
     * @return \DateTime
     */
    public function getLaunchDate()
    {
        return $this->launch_date;
    }

    /**
     * Set output
     *
     * @param string $output
     *
     * @return Scripts_history
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }
}
