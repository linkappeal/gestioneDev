<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Miscelata
 *
 * @ORM\Table(name="miscelate_light")
 * @ORM\Entity
 */
class Miscelata
{
     /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
  
    private $id;
    
     /**
     * @ORM\Column(type="string", length=50)
     */
    private $nome;
    
     /**
     * @ORM\Column(type="string", length=2550)
     */
    private $hot_sources;
    
    /**
     * @ORM\Column(type="string", length=2550)
    */
    private $cold_source;   
	
	/**
     * @ORM\Column(type="string", length=2550)
	 */
    private $mixed_table;   

	/**
     * @ORM\Column(type="integer", length=3)
	 */
    private $percentuale_fredde;  

    /**
     * @ORM\Column(type="integer", length=7)
    */
    private $limite;       
	
	/**
     * @ORM\Column(type="integer", length=1)
	 */
    private $attiva;

	/**
     * @ORM\Column(type="integer", length=5)
	 */
    private $cliente_id; 

	/**
     * @ORM\Column(type="integer", length=5)
	 */
    private $campagna_id;

	/**
     * @ORM\Column(type="integer", length=5)
	 */
    private $landing_id;

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
     * Set nome
     *
     * @param string $nome
     *
     * @return Nome
     */
    public function setNome($nome)
    {
        $this->nome = $nome;

        return $this;
    }
	
	/**
     * Get nome
     *
     * @return string
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     * Set hot_sources
     *
     * @param array $hot_sources
     *
     * @return string
     */
    public function sethot_sources($hot_sources)
    {
        $this->hot_sources = serialize($hot_sources);

        return $this;
    }
	
	/**
     * Get hot_sources
     *
     * @return array
     */
    public function gethot_sources()
    {
        return unserialize($this->hot_sources);
    }

    /**
     * Set cold_source
     *
     * @param array $cold_source
     *
     * @return string
     */
    public function setcold_source($cold_source)
    {
        $this->cold_source = serialize($cold_source);

        return $this;
    }
	
   /**
     * Get cold_source
     *
     * @return array
     */
    public function getcold_source()
    {
        return unserialize($this->cold_source);
    }
   
   /**
     * Set mixed_table
     *
     * @param array $mixed_table
     *
     * @return string
     */
    public function setmixed_table($mixed_table)
    {
        $this->mixed_table = serialize($mixed_table);

        return $this;
    }

    /**
     * Get mixed_table
     *
     * @return array
     */
    public function getmixed_table()
    {
        return unserialize($this->mixed_table);
    }
	
	
	/**
     * Set percentuale_fredde
     *
     * @param integer $percentuale_fredde
     *
     * @return integer
     */
    public function setpercentuale_fredde($percentuale_fredde)
    {
        $this->percentuale_fredde = $percentuale_fredde;

        return $this;
    }

    /**
     * Get percentuale_fredde
     *
     * @return integer
     */
    public function getpercentuale_fredde()
    {
        return $this->percentuale_fredde;
    }
	

    /**
     * Set limite
     *
     * @param integer $limite
     *
     * @return integer
     */
    public function setLimite($limite)
    {
        $this->limite = $limite;

        return $this;
    }

    /**
     * Get limite
     *
     * @return integer
     */
    public function getLimite()
    {
        return $this->limite;
    }

    /**
     * Set attiva
     *
     * @param integer $attiva
     *
     * @return integer
     */
    public function setAttiva($attiva)
    {
        $this->attiva = $attiva;

        return $this;
    }

    /**
     * Get attiva
     *
     * @return integer
     */
    public function getAttiva()
    {
        return $this->attiva;
    }
	
	/**
     * Set cliente_id
     *
     * @param integer $cliente_id
     *
     * @return integer
     */
    public function setcliente_id($cliente_id)
    {
        $this->cliente_id = $cliente_id;

        return $this;
    }

    /**
     * Get cliente_id
     *
     * @return integer
     */
    public function getcliente_id()
    {
        return $this->cliente_id;
    }
	
    /**
     * Set campagna_id
     *
     * @param integer $campagna_id
     *
     * @return integer
     */
    public function setcampagna_id($campagna_id)
    {
        $this->campagna_id = $campagna_id;

        return $this;
    }

    /**
     * Get campagna_id
     *
     * @return integer
     */
    public function getcampagna_id()
    {
        return $this->campagna_id;
    }
	
	/**
     * Set landing_id
     *
     * @param integer $landing_id
     *
     * @return integer
     */
    public function setlanding_id($landing_id)
    {
        $this->landing_id = $landing_id;

        return $this;
    }

    /**
     * Get landing_id
     *
     * @return integer
     */
    public function getlanding_id()
    {
        return $this->landing_id;
    }
}
