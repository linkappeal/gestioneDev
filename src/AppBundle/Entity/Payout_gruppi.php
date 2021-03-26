<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payout_gruppi
 *
 * @ORM\Table(name="payout_gruppi")
 * @ORM\Entity
 */
class Payout_gruppi
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;

	/**
     * @ORM\Column(type="integer")
	*/
	
    private $budget; 	
	
	/**
     * @ORM\Column(type="decimal", precision=8, scale=2)
	*/
	
    private $tetto_trash;   
	
	/**
     * @ORM\Column(type="string")
     */
    
	private $data_inizio;
	
	/**
     * @ORM\Column(type="string")
     */
    
	private $data_fine;
    
	/**
     * @ORM\Column(type="string")
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
     * Set tetto_trash
     *
     * @param decimal $tetto_trash
     *
     * @return Payout_gruppi
     */
    public function setTettoTrash($tetto_trash)
    {
        $this->tetto_trash = $tetto_trash;

        return $this;
    }

	/**
     * Get tetto_trash
     *
     * @return decimal
     */
    public function getTettoTrash()
    {
        return $this->tetto_trash;
    }

	/**
     * Set budget
     *
     * @param integer $budget
     *
     * @return Payout_gruppi
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

	/**
     * Get budget
     *
     * @return integer
     */
    public function getBudget()
    {
        return $this->budget;
    }
	
	
    /**
     * Set data_inizio
     *
     * @param \DateTime $data_inizio
     *
     * @return Payout_gruppi
     */
    public function setDataInizio($data_inizio)
    {
        $this->data_inizio = $data_inizio;

        return $this;
    }

	/**
     * Get data_inizio
     *
     * @return \DateTime
     */
    public function getDataInizio()
    {
        return $this->data_inizio;
    }

	/**
     * Set data_fine
     *
     * @param \DateTime $data_fine
     *
     * @return Payout_gruppi
     */
    public function setDataFine($data_fine)
    {
        $this->data_fine = $data_fine;

        return $this;
    }

	/**
     * Get data_fine
     *
     * @return \DateTime
     */
    public function getDataFine()
    {
        return $this->data_fine;
    }
   /**
     * Set data_creazione
     *
     * @param \DateTime $data_creazione
     *
     * @return Payout_gruppi
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
