<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fornitori
 *
 * @ORM\Table(name="fornitori")
 * @ORM\Entity
 */
class Fornitori
{
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
     * @ORM\Column(name="piva", type="string", length=100, nullable=false)
     */
    private $piva = '';

    /**
     * @var string
     *
     * @ORM\Column(name="falsemedia", type="string", length=100, nullable=false)
     */
    private $falsemedia = '';

    /**
     * @var string
     *
     * @ORM\Column(name="media", type="string", length=100, nullable=false)
     */
    private $media = '';

    /**
     * @var string
     *
     * @ORM\Column(name="descrizione", type="text", length=65535, nullable=false)
     */
    private $descrizione = '';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
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
     * Set nome
     *
     * @param string $nome
     *
     * @return Fornitori
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
     * @return Fornitori
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
     * @return Fornitori
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
     * Set falsemedia
     *
     * @param string $falsemedia
     *
     * @return Fornitori
     */
    public function setFalsemedia($falsemedia)
    {
        $this->falsemedia = $falsemedia;

        return $this;
    }

    /**
     * Get falsemedia
     *
     * @return string
     */
    public function getFalsemedia()
    {
        return $this->falsemedia;
    }

    /**
     * Set media
     *
     * @param string $media
     *
     * @return Fornitori
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media
     *
     * @return string
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set descrizione
     *
     * @param string $descrizione
     *
     * @return Fornitori
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
     * @return Fornitori
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
