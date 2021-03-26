<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pixels
 *
 * @ORM\Table(name="pixels", uniqueConstraints={@ORM\UniqueConstraint(name="id", columns={"id"})})
 * @ORM\Entity
 */
class Pixels
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Descrizione", type="string", length=255, nullable=true)
     */
    private $descrizione ='';

    /**
     * @var string
     *
     * @ORM\Column(name="id_campagna", type="string", length=255, nullable=true)
     */
    private $idCampagna;

    /**
     * @var string
     *
     * @ORM\Column(name="id_agenzia", type="string", length=255, nullable=true)
     */
    private $idAgenzia;

    /**
     * @var string
     *
     * @ORM\Column(name="cpl_number", type="string", length=255, nullable=true)
     */
    private $cplNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="codebase", type="string", length=255, nullable=true)
     */
    private $codebase;
    
    /**
     * @var string
     *
     * @ORM\Column(name="payout_code", type="string", length=255, nullable=true)
     */
    private $payout_code;    

    /**
     * @var string
     *
     * @ORM\Column(name="pixel", type="text", length=65535, nullable=true)
     */
    private $pixel;

    /**
     * @var integer
     *
     * @ORM\Column(name="attivo", type="integer", nullable=true)
     */
    private $attivo = '1';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data_creazione", type="datetime", nullable=false)
     */
    private $dataCreazione;
	
	private $url;

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
     * Set descrizione
     *
     * @param string $descrizione
     *
     * @return Pixels
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
     * Set idCampagna
     *
     * @param string $idCampagna
     *
     * @return Pixels
     */
    public function setIdCampagna($idCampagna)
    {
        $this->idCampagna = $idCampagna;

        return $this;
    }

    /**
     * Get idCampagna
     *
     * @return string
     */
    public function getIdCampagna()
    {
        return $this->idCampagna;
    }

    /**
     * Set idAgenzia
     *
     * @param string $idAgenzia
     *
     * @return Pixels
     */
    public function setIdAgenzia($idAgenzia)
    {
        $this->idAgenzia = $idAgenzia;

        return $this;
    }

    /**
     * Get idAgenzia
     *
     * @return string
     */
    public function getIdAgenzia()
    {
        return $this->idAgenzia;
    }

    /**
     * Set cplNumber
     *
     * @param string $cplNumber
     *
     * @return Pixels
     */
    public function setCplNumber($cplNumber)
    {
        $this->cplNumber = str_replace(' ', '', $cplNumber);

        return $this;
    }

    /**
     * Get cplNumber
     *
     * @return string
     */
    public function getCplNumber()
    {
        return $this->cplNumber;
    }

    /**
     * Set codebase
     *
     * @param string $codebase
     *
     * @return Pixels
     */
    public function setCodebase($codebase)
    {
        $this->codebase = $codebase;

        return $this;
    }

    /**
     * Get codebase
     *
     * @return string
     */
    public function getCodebase()
    {
        return $this->codebase;
    }

    /**
     * Set pixel
     *
     * @param string $pixel
     *
     * @return Pixels
     */
    public function setPixel($pixel)
    {
        $this->pixel = $pixel;
		$this->cleanPixel();
        return $this;
    }

    /**
     * Get pixel
     *
     * @return string
     */
    public function getPixel()
    {
        return $this->pixel;
    }

    /**
     * Set attivo
     *
     * @param integer $attivo
     *
     * @return Pixels
     */
    public function setAttivo($attivo)
    {
        $this->attivo = $attivo;

        return $this;
    }

    /**
     * Get attivo
     *
     * @return integer
     */
    public function getAttivo()
    {
        return $this->attivo;
    }

    /**
     * Set dataCreazione
     *
     * @param \DateTime $dataCreazione
     *
     * @return Pixels
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
	
	public function setUrl($url){
		
		$this->url = $url;
		
		return $this;
	}
	
	/*
	* Ripulisce il codice del pixel da eventuali commenti
	*/
	private function cleanPixel(){
		if(isset($this->pixel)){
			$_pixelcode = $this->pixel;
			$_pixelcode = trim($_pixelcode);
			$_pixelcode = preg_replace('/<!--(.*)-->/Uis', '', 	$_pixelcode);
			$_pixelcode = trim(preg_replace('/\s+/', ' ', 		$_pixelcode));
			$this->pixel = $_pixelcode;
		}
	}
	
	/*
	* restituisce il false media prelevato dall'URL principale
	*/
	public function parseFalseMedia(){
		$queryString = $this->getQueryUrl(); 
		if(isset($queryString['query'])){
			$query = $queryString['query'];
			parse_str($query, $vars);
			$_falsemedia = $vars['media'];
			$this->setCodebase($_falsemedia);
			return $_falsemedia;
		}
	}
	
	/*
	* Restituisce la query dall'URL passato al pixel
	*/ 
	private function getQueryUrl(){
		if(isset($this->url)){
			$queryurl = parse_url($this->url);
			return $queryurl;
		}
	}
	/*
	* Genera l'ID univoco della campagna basato sull'URL passato
	*/
	public function generateIdCampagna(){
		if(isset($this->url)){
			try{
				$query_par = $this->getQueryUrl();
				if(isset($query_par['host'])){
					$dominio = $query_par['host']; 
					$path_arr = explode('/',$query_par['path']); 
					$this->idCampagna = strtoupper($dominio ."_". $path_arr[2]);
				}
			}catch(Exception $e){
				echo $e;
			}
		}
	}

    /**
     * Set payoutCode
     *
     * @param string $payoutCode
     *
     * @return Pixels
     */
    public function setPayoutCode($payoutCode)
    {
        $this->payout_code = $payoutCode;

        return $this;
    }

    /**
     * Get payoutCode
     *
     * @return string
     */
    public function getPayoutCode()
    {
        return $this->payout_code;
    }
}
