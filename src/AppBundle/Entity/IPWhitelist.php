<?php
// src/AppBundle/Entity/IPWhitelist.php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ip_whitelist")
 */
class IPWhitelist
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $indirizzo_ip;  

    /**
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    private $descrizione;  

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
     * Set indirizzoIp
     *
     * @param string $indirizzoIp
     *
     * @return IPWhitelist
     */
    public function setIndirizzoIp($indirizzoIp)
    {
        $this->indirizzo_ip = $indirizzoIp;

        return $this;
    }

    /**
     * Get indirizzoIp
     *
     * @return string
     */
    public function getIndirizzoIp()
    {
        return $this->indirizzo_ip;
    }
	
	   /**
     * Set descrizione
     *
     * @param string $descrizione
     *
     * @return IPWhitelist
     */
    public function setDescrizione($descrizione)
    {
        $this->descrizione = $descrizione;

        return $this;
    }

    /**
     * Get descrizione
     *
     * @return string
     */
    public function getDescrizione()
    {
        return $this->descrizione;
    }
}
