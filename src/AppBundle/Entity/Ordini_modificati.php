<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ordini_modificati
 *
 * @ORM\Table(name="ordini_modificati")
 * @ORM\Entity
 */
class Ordini_modificati
{
     /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;
    
     /**
     * @ORM\Column(type="integer", length=11)
     */
    private $id_ordine;
    
     /**
     * @ORM\Column(type="integer", length=11)
     */
    private $id_payout;
    
    /**
     * @ORM\Column(type="integer", length=11)
    */
    private $target;   
	
	/**
     * @ORM\Column(type="integer", length=11)
	 */
    private $ordine_mese;   
	/**
     * @ORM\Column(type="integer", length=11)
	 */
    private $ordine_anno;  

	/**
     * @ORM\Column(type="integer", length=11)
    */
    private $base_lorde;  
	
	/**
     * @ORM\Column(type="integer", length=11)
    */
    private $base_trash;
    
	/**
     * @ORM\Column(type="integer", length=11)
    */
    private $differenza_lorde;       
 
    /**
     * @ORM\Column(type="integer", length=11)
    */
    private $differenza_trash;    

 
    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
    */
    private $trash_modificato;    

	/**
     * @ORM\Column(type="datetime")
	 */
    private $creation_date;   

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
     * Set id_ordine
     *
     * @param integer $id_ordine
     *
     * @return Ordini_modificati
     */
    public function setIdOrdine($id_ordine)
    {
        $this->id_ordine = $id_ordine;

        return $this;
    }
	
	/**
     * Get id_ordine
     *
     * @return integer
     */
    public function getIdOrdine()
    {
        return $this->id_ordine;
    }

    /**
     * Set id_payout
     *
     * @param integer $id_payout
     *
     * @return Ordini_modificati
     */
    public function setIdPayout($id_payout)
    {
        $this->id_payout = $id_payout;

        return $this;
    }
	
	/**
     * Get id_payout
     *
     * @return integer
     */
    public function getIdPayout()
    {
        return $this->id_payout;
    }

    /**
     * Set target
     *
     * @param integer $target
     *
     * @return Ordini_modificati
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }
	
   /**
     * Get target
     *
     * @return integer
     */
    public function getTarget()
    {
        return $this->target;
    }
   
   /**
     * Set ordine_mese
     *
     * @param integer $ordine_mese
     *
     * @return Ordini_modificati
     */
    public function setOrdineMese($ordine_mese)
    {
        $this->ordine_mese = $ordine_mese;

        return $this;
    }

    /**
     * Get ordine_mese
     *
     * @return integer
     */
    public function getOrdineMese()
    {
        return $this->ordine_mese;
    }
	
	
	/**
     * Set ordine_anno
     *
     * @param integer $ordine_anno
     *
     * @return Ordini_modificati
     */
    public function setOrdineAnno($ordine_anno)
    {
        $this->ordine_anno = $ordine_anno;

        return $this;
    }

    /**
     * Get ordine_anno
     *
     * @return integer
     */
    public function getOrdineAnno()
    {
        return $this->ordine_anno;
    }
	

    /**
     * Set differenza_lorde
     *
     * @param integer $differenza_lorde
     *
     * @return Ordini_modificati
     */
    public function setDifferenzaLorde($differenza_lorde)
    {
        $this->differenza_lorde = $differenza_lorde;

        return $this;
    }

    /**
     * Get differenza_lorde
     *
     * @return integer
     */
    public function getDifferenzaLorde()
    {
        return $this->differenza_lorde;
    }
	
	/**
     * Set base_lorde
     *
     * @param integer $base_lorde
     *
     * @return Ordini_modificati
     */
    public function setBaseLorde($base_lorde)
    {
        $this->base_lorde = $base_lorde;

        return $this;
    }

    /**
     * Get base_lorde
     *
     * @return integer
     */
    public function getBaseLorde()
    {
        return $this->base_lorde;
    }
	
	    /**
     * Set differenza_trash
     *
     * @param integer $differenza_trash
     *
     * @return Ordini_modificati
     */
    public function setDifferenzaTrash($differenza_trash)
    {
        $this->differenza_trash = $differenza_trash;

        return $this;
    }

    /**
     * Get differenza_trash
     *
     * @return integer
     */
    public function getDifferenzaTrash()
    {
        return $this->differenza_trash;
    }
	
	/**
     * Set base_trash
     *
     * @param integer $base_trash
     *
     * @return Ordini_modificati
     */
    public function setBaseTrash($base_trash)
    {
        $this->base_trash = $base_trash;

        return $this;
    }

    /**
     * Get base_trash
     *
     * @return integer
     */
    public function getBaseTrash()
    {
        return $this->base_trash;
    }
	
	/**
     * Set trash_modificato
     *
     * @param integer $trash_modificato
     *
     * @return Ordini_modificati
     */
    public function setTrashModificato($trash_modificato)
    {
        $this->trash_modificato = $trash_modificato;

        return $this;
    }

    /**
     * Get trash_modificato
     *
     * @return integer
     */
    public function getTrashModificato()
    {
        return $this->trash_modificato;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Ordini_modificati
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
}
