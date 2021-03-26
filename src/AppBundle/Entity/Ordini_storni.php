<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ordini_storni
 *
 * @ORM\Table(name="ordini_storni")
 * @ORM\Entity
 */
class Ordini_storni
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
     * @ORM\Column(type="text")
    */
    private $leads_code;       
	
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
     * @return Ordini_storni
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
     * @return Ordini_storni
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
     * @return Ordini_storni
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
     * @return Ordini_storni
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
     * @return Ordini_storni
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
     * Set leads_code
     *
     * @param text $leads_code
     *
     * @return Ordini_storni
     */
    public function setLeadsCode($leads_code)
    {
        $this->leads_code = $leads_code;

        return $this;
    }

    /**
     * Get leads_code
     *
     * @return text
     */
    public function getLeadsCode()
    {
        return $this->leads_code;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Ordini_storni
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
