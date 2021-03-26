<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Campagna
 *
 * @ORM\Table(name="campagna")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Campagna {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    
    /**
     * @ORM\Column(type="integer")
    */
    private $indiretta =  0;
	
    /**
     * @ORM\ManyToOne(targetEntity="Brand")
     */
    private $brand;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $settore;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nome_offerta;
    
    /**     
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $target_campagna;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tipo_campagna;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $source_db;   

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $source_id;       
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbtabmo;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dbtab;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $data_start;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $data_end;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $leadout_path;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shot_path;  
    
    /**
     * @ORM\Column(type="boolean", nullable=false, nullable=true)
     */
    private $is_active;   
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $optin;     
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $disable_js_validation;     
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $disable_php_validation;     
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_published;  
    
    /**
     * @ORM\Column(type="integer")
    */
    
    private $id_privacy =  1;
    
    /**
     * @@ORM\OneToOne(targetEntity="Landing", mappedBy="campagna")
     */
    private $landing;    
    
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
     * Set brand
     *
     * @param string $brand
     *
     * @return Campagna
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }
    
    public function getBrandId()
    {
        return $this->brand->getId();
    }

    public function getBrandName()
    {
        return $this->brand->getName();
    }

    /**
     * Set settore
     *
     * @param string $settore
     *
     * @return Campagna
     */
    public function setSettore($settore)
    {
        $this->settore = $settore;

        return $this;
    }

    /**
     * Get settore
     *
     * @return string
     */
    public function getSettore()
    {
        return $this->settore;
    }

    /**
     * Set nomeOfferta
     *
     * @param string $nomeOfferta
     *
     * @return Campagna
     */
    public function setNomeOfferta($nomeOfferta)
    {
        $this->nome_offerta = $nomeOfferta;

        return $this;
    }

    /**
     * Get nomeOfferta
     *
     * @return string
     */
    public function getNomeOfferta()
    {
        return $this->nome_offerta;
    }

    /**
     * Set targetCampagna
     *
     * @param string $targetCampagna
     *
     * @return Campagna
     */
    public function setTargetCampagna($targetCampagna)
    {
        $this->target_campagna = $targetCampagna;

        return $this;
    }

    /**
     * Get targetCampagna
     *
     * @return string
     */
    public function getTargetCampagna()
    {
        return $this->target_campagna;
    }

    /**
     * Set tipoCampagna
     *
     * @param string $tipoCampagna
     *
     * @return Campagna
     */
    public function setTipoCampagna($tipoCampagna)
    {
        $this->tipo_campagna = $tipoCampagna;

        return $this;
    }

    /**
     * Get tipoCampagna
     *
     * @return string
     */
    public function getTipoCampagna()
    {
        return $this->tipo_campagna;
    }

    /**
     * Set dbtab
     *
     * @param string $dbtab
     *
     * @return Campagna
     */
    public function setDbtab($dbtab)
    {
        $this->dbtab = $dbtab;

        return $this;
    }

    /**
     * Get dbtab
     *
     * @return string
     */
    public function getDbtab()
    {
        return $this->dbtab;
    }

    /**
     * Set dataStart
     *
     * @param \DateTime $dataStart
     *
     * @return Campagna
     */
    public function setDataStart($dataStart)
    {
        $this->data_start = $dataStart;

        return $this;
    }

    /**
     * Get dataStart
     *
     * @return \DateTime
     */
    public function getDataStart()
    {
        return $this->data_start;
    }

    /**
     * Set dataEnd
     *
     * @param \DateTime $dataEnd
     *
     * @return Campagna
     */
    public function setDataEnd($dataEnd)
    {
        $this->data_end = $dataEnd;

        return $this;
    }

    /**
     * Get dataEnd
     *
     * @return \DateTime
     */
    public function getDataEnd()
    {
        return $this->data_end;
    }

    /**
     * Set leadoutPath
     *
     * @param string $leadoutPath
     *
     * @return Campagna
     */
    public function setLeadoutPath($leadoutPath)
    {
        $this->leadout_path = $leadoutPath;

        return $this;
    }

    /**
     * Get leadoutPath
     *
     * @return string
     */
    public function getLeadoutPath()
    {
        return $this->leadout_path;
    }

    /**
     * Set shotPath
     *
     * @param string $shotPath
     *
     * @return Campagna
     */
    public function setShotPath($shotPath)
    {
        $this->shot_path = $shotPath;

        return $this;
    }

    /**
     * Get shotPath
     *
     * @return string
     */
    public function getShotPath()
    {
        return $this->shot_path;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Campagna
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set optin
     *
     * @param boolean $optin
     *
     * @return Campagna
     */
    public function setOptin($optin)
    {
        $this->optin = $optin;

        return $this;
    }

    /**
     * Get optin
     *
     * @return boolean
     */
    public function getOptin()
    {
        return $this->optin;
    }

    /**
     * Set disableJsValidation
     *
     * @param boolean $disableJsValidation
     *
     * @return Campagna
     */
    public function setDisableJsValidation($disableJsValidation)
    {
        $this->disable_js_validation = $disableJsValidation;

        return $this;
    }

    /**
     * Get disableJsValidation
     *
     * @return boolean
     */
    public function getDisableJsValidation()
    {
        return $this->disable_js_validation;
    }

    /**
     * Set disablePhpValidation
     *
     * @param boolean $disablePhpValidation
     *
     * @return Campagna
     */
    public function setDisablePhpValidation($disablePhpValidation)
    {
        $this->disable_php_validation = $disablePhpValidation;

        return $this;
    }

    /**
     * Get disablePhpValidation
     *
     * @return boolean
     */
    public function getDisablePhpValidation()
    {
        return $this->disable_php_validation;
    }

    /**
     * Set isPublished
     *
     * @param boolean $isPublished
     *
     * @return Campagna
     */
    public function setIsPublished($isPublished)
    {
        $this->is_published = $isPublished;

        return $this;
    }

    /**
     * Get isPublished
     *
     * @return boolean
     */
    public function getIsPublished()
    {
        return $this->is_published;
    }

    /**
     * Set sourceDb
     *
     * @param string $sourceDb
     *
     * @return Campagna
     */
    public function setSourceDb($sourceDb)
    {
        $this->source_db = $sourceDb;

        return $this;
    }

    /**
     * Get sourceDb
     *
     * @return string
     */
    public function getSourceDb()
    {
        return $this->source_db;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return Campagna
     */
    public function setSourceId($sourceId)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set dbtabmo
     *
     * @param string $dbtabmo
     *
     * @return Campagna
     */
    public function setDbtabmo($dbtabmo)
    {
        $this->dbtabmo = $dbtabmo;

        return $this;
    }

    /**
     * Get dbtabmo
     *
     * @return string
     */
    public function getDbtabmo()
    {
        return $this->dbtabmo;
    }

    /**
     * Set idPrivacy
     *
     * @param integer $idPrivacy
     *
     * @return Campagna
     */
    public function setIdPrivacy($idPrivacy)
    {
        $this->id_privacy = $idPrivacy;

        return $this;
    }

    /**
     * Get idPrivacy
     *
     * @return integer
     */
    public function getIdPrivacy()
    {
        return $this->id_privacy;
    } 
	
	/**
     * Set indiretta
     *
     * @param integer $indiretta
     *
     * @return Campagna
     */
    public function setIndiretta($indiretta)
    {
        $this->indiretta = $indiretta;

        return $this;
    }

    /**
     * Get indiretta
     *
     * @return integer
     */
    public function getIndiretta()
    {
        return $this->indiretta;
    }
}
