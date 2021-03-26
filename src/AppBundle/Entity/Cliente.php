<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Cliente
 *
 * @ORM\Table(name="cliente")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Cliente
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
     * @ORM\Column(type="string", length=255)
     */
    private $account;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

	/**
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private $username;

	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password_ic;

	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;
    
     /**
     * @ORM\Column(type="datetime")
     */
    private $creation_date;
    
    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $partita_iva;     

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
     * @return Cliente
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
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
	
	 /**
     * Set username
     *
     * @param string $username
     *
     * @return Cliente
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }
	
	 /**
     * Get password_ic
     *
     * @return string
     */
    public function getPasswordIc()
    {
        return $this->password_ic;
    }
	
	/**
     * Set password_ic
     *
     * @param string $password_ic
     *
     * @return Cliente
     */
    public function setPasswordIc($password_ic)
    {
        $this->password_ic = $password_ic;
		$pass_md5 = md5($this->password_ic);
		$this->setPassword($pass_md5);
        return $this;
    }
	
	/**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
	
	 /**
     * Set password
     *
     * @param string $password
     *
     * @return Cliente
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }
	
	 

    /**
     * Set account
     *
     * @param string $account
     *
     * @return Cliente
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }
	
    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Cliente
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
    
    public function genClienteCode($cliente_name){
        
        $trimmed = trim($cliente_name);
    
        $output = preg_replace('/\s+/', '', $trimmed);
        
        $output = strtoupper($output);
        
        $first_ch  = $output[0];
        $last_ch   = $output[strlen($output)-1];
        $middle_ch =  $output[intdiv(strlen($output)-1, 2)];
        
        $date = new \DateTime("now");
        $hours = $date->format('H');
        $year = $date->format('y');
        $month = $date->format('m');
        $day = $date->format('d');
        
        $code = $first_ch.$day.$month.$middle_ch.$year.$hours.$last_ch;
        
        return $code;
 
    }
	
	public function random_password( $length = 12 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
		$password = substr( str_shuffle( $chars ), 0, $length );
		return $password;
	}


    /**
     * Set code
     *
     * @param string $code
     *
     * @return Cliente
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set partitaIva
     *
     * @param string $partitaIva
     *
     * @return Cliente
     */
    public function setPartitaIva($partitaIva)
    {
        $this->partita_iva = $partitaIva;

        return $this;
    }

    /**
     * Get partitaIva
     *
     * @return string
     */
    public function getPartitaIva()
    {
        return $this->partita_iva;
    }
}
