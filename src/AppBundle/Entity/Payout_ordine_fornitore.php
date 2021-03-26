<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payout_ordine_fornitore
 *
 * @ORM\Table(name="payout_ordine_fornitore")
 * @ORM\Entity
 */
class Payout_ordine_fornitore
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;
    
    /**
     * @ORM\Column(type="string", length=250)
    */
    private $campo;
 
	/**
     * @ORM\Column(type="integer", length=11)
    */
    private $tipo_campo;  
	
	/**
     * @ORM\Column(type="string", length=250)
    */
    private $campo_valore;  

	/**
     * @ORM\Column(type="decimal", precision=8, scale=2)
	*/
	
    private $payout;   
	
	/**
     * @ORM\Column(type="string", length=250)
    */
    private $descrizione; 

     /**
     * @ORM\Column(type="datetime")
     */
    private $data_creazione;


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
     * Set campo
     *
     * @param string $campo
     *
     * @return Payout_ordine_fornitore
     */
    public function setCampo($campo)
    {
        $this->campo = $campo;

        return $this;
    }

    /**
     * Get campo
     *
     * @return string
     */
    public function getCampo()
    {
        return $this->campo;
    }
	
    /**
     * Set tipo_campo
     *
     * @param integer $tipo_campo
     *
     * @return Payout_ordine_fornitore
     */
    public function setTipoCampo($tipo_campo)
    {
        $this->tipo_campo = $tipo_campo;

        return $this;
    }

    /**
     * Get tipo_campo
     *
     * @return string
     */
    public function getTipoCampo()
    {
        return $this->tipo_campo;
    }
	
    /**
     * Set campo_valore
     *
     * @param string $campo_valore
     *
     * @return Payout_ordine_fornitore
     */
    public function setCampoValore($campo_valore)
    {
        $this->campo_valore = $campo_valore;

        return $this;
    }

    /**
     * Get campo_valore
     *
     * @return string
     */
    public function getCampoValore()
    {
        return $this->campo_valore;
    }

	
    /**
     * Get payout
     *
     * @return decimal
     */
    public function getPayout()
    {
        return $this->payout;
    }
	
    /**
     * Set payout
     *
     * @param decimal $payout
     *
     * @return Payout_ordine_fornitore
     */
    public function setPayout($payout)
    {
        $this->payout = $payout;

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
	
    /**
     * Set descrizione
     *
     * @param decimal $descrizione
     *
     * @return Payout_ordine_fornitore
     */
	 public function setDescrizione($descrizione)
    {
        $this->descrizione = $descrizione;

        return $this;
    }
	
    /**
     * Set data_creazione
     *
     * @param \DateTime $data_creazione
     *
     * @return Payout_ordine_fornitore
     */
    public function setDataCreazione($data_creazione)
    {
        $this->data_creazione = $data_creazione;

        return $this;
    }

	/**
     * Get data_creazione
     *
     * @return \DateTime
     */
    public function getDataCreazione()
    {
        return $this->data_creazione;
    }
}
