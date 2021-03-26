<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Domini
 *
 * @ORM\Table(name="domini")
 * @ORM\Entity
 */
class Domini
{
     /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    
    private $id;
    
     /**
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    private $description;

     /**
     * @ORM\Column(name="dbname", type="string", length=100, nullable=true)
     */
    private $dbname;
    

     /**
     * @ORM\Column(name="dbhost", type="string", length=100, nullable=true)
     */
    private $dbhost;

     /**
     * @ORM\Column(name="dbpass", type="string", length=100, nullable=true)
     */
    private $dbpass;    
    
	/**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true)
     */
    private $creationDate;

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
     * Set dbname
     *
     * @param string $dbname
     *
     * @return Domini
     */
    public function setDbname($dbname)
    {
        $this->dbname = $dbname;

        return $this;
    }

    /**
     * Get dbname
     *
     * @return string
     */
    public function getDbname()
    {
        return $this->dbname;
    }

	
	/**
     * Set description
     *
     * @param string $description
     *
     * @return Domini
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
	

	/**
     * Set dbhost
     *
     * @param string $dbhost
     *
     * @return Domini
     */
    public function setDbhost($dbhost)
    {
        $this->dbhost = $dbhost;

        return $dbhost;
    }

    /**
     * Get dbhost
     *
     * @return string
     */
    public function getDbhost()
    {
        return $this->dbhost;
    }

	/**
     * Set dbpass
     *
     * @param string $dbpass
     *
     * @return Domini
     */
    public function setDbpass($dbpass)
    {
        $this->dbpass = $dbpass;

        return $dbpass;
    }

    /**
     * Get dbpass
     *
     * @return string
     */
    public function getDbpass()
    {
        return $this->dbpass;
    }
	
	    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Domini
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

}
