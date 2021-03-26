<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Cliente
 *
 * @ORM\Table(name="extraction")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Extraction
{
        
     /**
     * @ORM\OneToOne(targetEntity="Lead_uni")
     * @ORM\Id
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
     * @return Extraction
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
     * Set tipoEstrazione
     *
     * @param \DateTime $tipoEstrazione
     *
     * @return Extraction
     */
    public function setTipoEstrazione($tipoEstrazione)
    {
        $this->tipo_estrazione = $tipoEstrazione;

        return $this;
    }

    /**
     * Get tipoEstrazione
     *
     * @return \DateTime
     */
    public function getTipoEstrazione()
    {
        return $this->tipo_estrazione;
    }

    /**
     * Set dataSblocco
     *
     * @param \DateTime $dataSblocco
     *
     * @return Extraction
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
     * Set lead
     *
     * @param \AppBundle\Entity\Lead $lead
     *
     * @return Extraction
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
     * @return Extraction
     */
    public function setCliente(\AppBundle\Entity\Cliente $cliente)
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
}
