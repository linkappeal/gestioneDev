<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Affiliati
 *
 * @ORM\Table(name="affiliati")
 * @ORM\Entity
 */
class Affiliati
{
	
	/**
     * @var integer
     *
     * @ORM\Column(name="id_fornitore", type="integer", length=11, nullable=true)
     */
    private $id_fornitore = 41;

    /**
     * @var string
     *
     * @ORM\Column(name="nome", type="string", length=250, nullable=false)
     */
    private $nome = '';

    /**
     * @var string
     *
     * @ORM\Column(name="ragionesociale", type="string", length=250, nullable=false)
     */
    private $ragionesociale = '';

    /**
     * @var string
     *
     * @ORM\Column(name="piva", type="string", length=100, nullable=true)
     */
    private $piva = '';

    /**
     * @var string
     *
     * @ORM\Column(name="refid", type="string", length=100, nullable=true)
     */
    private $refid = '';

    /**
     * @var string
     *
     * @ORM\Column(name="descrizione", type="text", length=65535, nullable=false)
     */
    private $descrizione;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set id_fornitore
     *
     * @param integer $id_fornitore
     *
     * @return Affiliati
     */
    public function setIdFornitore($id_fornitore)
    {
        $this->id_fornitore = $id_fornitore;

        return $this;
    }

    /**
     * Get id_fornitore
     *
     * @return integer
     */
    public function getIdFornitore()
    {
        return $this->id_fornitore;
    }
	


    /**
     * Set nome
     *
     * @param string $nome
     *
     * @return Affiliati
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
     * Set ragionesociale
     *
     * @param string $ragionesociale
     *
     * @return Affiliati
     */
    public function setRagionesociale($ragionesociale)
    {
        $this->ragionesociale = $ragionesociale;

        return $this;
    }

    /**
     * Get ragionesociale
     *
     * @return string
     */
    public function getRagionesociale()
    {
        return $this->ragionesociale;
    }

    /**
     * Set piva
     *
     * @param string $piva
     *
     * @return Affiliati
     */
    public function setPiva($piva)
    {
        $this->piva = $piva;

        return $this;
    }

    /**
     * Get piva
     *
     * @return string
     */
    public function getPiva()
    {
        return $this->piva;
    }

    /**
     * Set refid
     *
     * @param string $refid
     *
     * @return Affiliati
     */
    public function setRefid($refid)
    {
        $this->refid = $refid;

        return $this;
    }

    /**
     * Get refid
     *
     * @return string
     */
    public function getRefid()
    {
        return $this->refid;
    }

    /**
     * Set descrizione
     *
     * @param string $descrizione
     *
     * @return Affiliati
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

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return Affiliati
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
