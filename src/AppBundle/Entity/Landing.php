<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Landing
 * 
 * @ORM\Table(name="landing")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */

class Landing {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Campagna")
     * @ORM\JoinColumn(name="campagna_id", referencedColumnName="id")
     */
   // private $campagna;
    
    /**
     * @ORM\Column(type="string", length=200, options={"comment":"Titolo da mostrare nel browser"})
     */    
    private $titoloLanding = 'Offerta';

    /**
     * @ORM\Column(type="string", length=250)
     */
    private $slugLanding;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"comment":"Colore in formato #XXXXXX"})
     */
    private $coloreButton;    
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $logo;     
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $font = "trebucbd.ttf";   
    
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $font_size = '10.3';  
    
    /**
     * @ORM\Column(type="string", length=10, nullable=true, options={"comment":"Colore in formato #XXXXXX"})
     */
    private $font_color;     
    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dataCreazione;
	
	/**
     * @ORM\Column(type="text", nullable=true)
     */
    private $testoTankyou = "La tua richiesta &egrave; stata inoltrata con successo.<br /><br />Ti ricontatteremo al pi&ugrave; presto per darti tutte le informazioni che desideri sulla promozione in corso.<br /><br />Cordialmente<br />{THANKS_BY}";  


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
     * Set titoloLanding
     *
     * @param string $titoloLanding
     *
     * @return Landing
     */
    public function setTitoloLanding($titoloLanding)
    {
        $this->titoloLanding = $titoloLanding;

        return $this;
    }

    /**
     * Get titoloLanding
     *
     * @return string
     */
    public function getTitoloLanding()
    {
        return $this->titoloLanding;
    }

    /**
     * Set slugLanding
     *
     * @param string $slugLanding
     *
     * @return Landing
     */
    public function setSlugLanding($slugLanding)
    {
        $this->slugLanding = $slugLanding;

        return $this;
    }

    /**
     * Get slugLanding
     *
     * @return string
     */
    public function getSlugLanding()
    {
        return $this->slugLanding;
    }

    /**
     * Set coloreButton
     *
     * @param string $coloreButton
     *
     * @return Landing
     */
    public function setColoreButton($coloreButton)
    {
        $this->coloreButton = $coloreButton;

        return $this;
    }

    /**
     * Get coloreButton
     *
     * @return string
     */
    public function getColoreButton()
    {
        return $this->coloreButton;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return Landing
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set font
     *
     * @param string $font
     *
     * @return Landing
     */
    public function setFont($font)
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get font
     *
     * @return string
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set fontSize
     *
     * @param string $fontSize
     *
     * @return Landing
     */
    public function setFontSize($fontSize)
    {
        $this->font_size = $fontSize;

        return $this;
    }

    /**
     * Get fontSize
     *
     * @return string
     */
    public function getFontSize()
    {
        return $this->font_size;
    }

    /**
     * Set fontColor
     *
     * @param string $fontColor
     *
     * @return Landing
     */
    public function setFontColor($fontColor)
    {
        $this->font_color = $fontColor;

        return $this;
    }

    /**
     * Get fontColor
     *
     * @return string
     */
    public function getFontColor()
    {
        return $this->font_color;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Landing
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set dataCreazione
     *
     * @param \DateTime $dataCreazione
     *
     * @return Landing
     */
    public function setDataCreazione($dataCreazione)
    {
        $this->dataCreazione = $dataCreazione;

        return $this;
    }

    /**
     * Get dataCreazione
     *
     * @return \DateTime
     */
    public function getDataCreazione()
    {
        return $this->dataCreazione;
    }

    /**
     * Set campagna
     *
     * @param \AppBundle\Entity\Campagna $campagna
     *
     * @return Landing
     */
    //public function setCampagna(\AppBundle\Entity\Campagna $campagna = null)
    //{
    //    $this->campagna = $campagna;
    //
    //    return $this;
    //}

    /**
     * Get campagna
     *
     * @return \AppBundle\Entity\Campagna
     */
    //public function getCampagna()
    //{
    //    return $this->campagna;
    //}
	
	
	 /**
     * Set testoTankyou
     *
     * @param string $testoTankyou
     *
     * @return Landing
     */
    public function setTestoTankyou($testoTankyou)
    {
        $this->testoTankyou = $testoTankyou;

        return $this;
    }

    /**
     * Get testoTankyou
     *
     * @return string
     */
    public function getTestoTankyou()
    {
        return $this->testoTankyou;
    }
}
