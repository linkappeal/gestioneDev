<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Extraction History
 *
 * @ORM\Table(name="extraction_history")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Extraction_history
{
     /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
        
     /**
     * @ORM\ManyToOne(targetEntity="Lead_uni")
     */
    private $lead;
    
    /**
     * @ORM\ManyToOne(targetEntity="Cliente")
     */
    private $cliente;
 
     /**
     * @ORM\Column(type="datetime")
     */
    private $data_estrazione;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $data_sblocco;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $indirizzo_ip;  
    
    /**
     * @ORM\Column(type="integer")
     */
    private $backend_user_id;
    
    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('noleggio', 'vendita','prenotazione')", nullable=false)
     */
    private $tipo_estrazione;

    function __construct()
    {
        $this->data_estrazione = new \DateTime();
    }



    /**
     * Set dataEstrazione
     *
     * @param \DateTime $dataEstrazione
     *
     * @return Extraction_history
     */
    public function setDataEstrazione($dataEstrazione)
    {
        $this->data_estrazione = $dataEstrazione;

        return $this;
    }

    /**
     * Get dataEstrazione
     *
     * @return \DateTime
     */
    public function getDataEstrazione()
    {
        return $this->data_estrazione;
    }

    /**
     * Set dataSblocco
     *
     * @param \DateTime $dataSblocco
     *
     * @return Extraction_history
     */
    public function setDataSblocco($dataSblocco)
    {
        $this->data_sblocco = $dataSblocco;

        return $this;
    }

    /**
     * Get dataSblocco
     *
     * @return \DateTime
     */
    public function getDataSblocco()
    {
        return $this->data_sblocco;
    }

    /**
     * Set indirizzoIp
     *
     * @param string $indirizzoIp
     *
     * @return Extraction_history
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
     * Set backendUserId
     *
     * @param integer $backendUserId
     *
     * @return Extraction_history
     */
    public function setBackendUserId($backendUserId)
    {
        $this->backend_user_id = $backendUserId;

        return $this;
    }

    /**
     * Get backendUserId
     *
     * @return integer
     */
    public function getBackendUserId()
    {
        return $this->backend_user_id;
    }

    /**
     * Set tipoEstrazione
     *
     * @param string $tipoEstrazione
     *
     * @return Extraction_history
     */
    public function setTipoEstrazione($tipoEstrazione)
    {
        $this->tipo_estrazione = $tipoEstrazione;

        return $this;
    }

    /**
     * Get tipoEstrazione
     *
     * @return string
     */
    public function getTipoEstrazione()
    {
        return $this->tipo_estrazione;
    }

    /**
     * Set lead
     *
     * @param \AppBundle\Entity\Lead_uni $lead
     *
     * @return Extraction_history
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
     * Set cliente
     *
     * @param \AppBundle\Entity\Cliente $cliente
     *
     * @return Extraction_history
     */
    public function setCliente(\AppBundle\Entity\Cliente $cliente = null)
    {
        $this->cliente = $cliente;

        return $this;
    }

    /**
     * Get cliente
     *
     * @return \AppBundle\Entity\Cliente
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
