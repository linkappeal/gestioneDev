<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Lead_uni
 *
 * @ORM\Table(name="lead_uni")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class Lead_uni
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
    private $source_db;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $source_tbl;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $source_id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Campagna")
     */
    private $campagna;    

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nome;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cognome;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ragione_sociale;    
    
	/**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $sesso;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cellulare;   
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $operatore;  
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tel_fisso;   
    
    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $anno_nascita; 

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $eta;       
           
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $editore;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $submedia;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $citta;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $provincia;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $indirizzo;    
    
        /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nazione;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $quartiere;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $regione;   
    
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $cap;    

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $forma_giuridica;   
    
    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $partita_iva; 
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $tipo_partita_iva; 

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $cliente; 
    
    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $codice_fiscale; 
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $importo_richiesto;     
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $data;     
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $indirizzo_ip;  
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference_id;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $banner_id;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $download;   
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $token_verified;   
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $latitudine; 
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $longitudine; 
    
    /**
     * @ORM\Column(type="integer")
     */
    private $parent_id;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;  
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $privacy;  
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $privacy_version;     
    
    /**
     * @ORM\Column(type="boolean", options={"default" : 1}) )
     */
    private $privacy_terzi;      
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $email_verified;   
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iban;  
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $professione;    

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cabina;    
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $titolo_di_studio;   
    

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
     * Set sourceDb
     *
     * @param string $sourceDb
     *
     * @return Lead_uni
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
     * Set sourceTbl
     *
     * @param string $sourceTbl
     *
     * @return Lead_uni
     */
    public function setSourceTbl($sourceTbl)
    {
        $this->source_tbl = $sourceTbl;

        return $this;
    }

    /**
     * Get sourceTbl
     *
     * @return string
     */
    public function getSourceTbl()
    {
        return $this->source_tbl;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return Lead_uni
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
     * Set campagna
     *
     * @param \AppBundle\Entity\Campagna $campagna
     *
     * @return Campagna
     */
    public function setCampagna(\AppBundle\Entity\Campagna $campagna)
    {
        $this->campagna = $campagna;

        return $this;
    }

    /**
     * Get campagna
     *
     * @return \AppBundle\Entity\Campagna
     */
    public function getCampagna()
    {
        return $this->campagna;
    }

    /**
     * Set nome
     *
     * @param string $nome
     *
     * @return Lead_uni
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
     * Get cognome
     *
     * @return string
     */
    public function getCognome()
    {
        return $this->cognome;
    } 
    
    /**
     * Set cognome
     *
     * @param string $cognome
     *
     * @return Lead_uni
     */
    public function setCognome($cognome)
    {
        $this->cognome = $cognome;

        return $this;
    }

    /**
     * Get ragioneSociale
     *
     * @return string
     */
    public function getRagioneSociale()
    {
        return $this->ragione_sociale;
    }      

    /**
     * Set ragioneSociale
     *
     * @param string $ragioneSociale
     *
     * @return Lead_uni
     */
    public function setRagioneSociale($ragioneSociale)
    {
        $this->ragione_sociale = $eragioneSociale;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set cellulare
     *
     * @param string $cellulare
     *
     * @return Lead_uni
     */
    public function setCellulare($cellulare)
    {
        $this->cellulare = $cellulare;

        return $this;
    }

    /**
     * Get cellulare
     *
     * @return string
     */
    public function getCellulare()
    {
        return $this->cellulare;
    }

    /**
     * Set telFisso
     *
     * @param string $telFisso
     *
     * @return Lead_uni
     */
    public function setTelFisso($telFisso)
    {
        $this->tel_fisso = $telFisso;

        return $this;
    }

    /**
     * Get telFisso
     *
     * @return string
     */
    public function getTelFisso()
    {
        return $this->tel_fisso;
    }

    /**
     * Set et??
     *
     * @param string $et??
     *
     * @return Lead_uni
     */
    public function setEt??($et??)
    {
        $this->et?? = $et??;

        return $this;
    }

    /**
     * Get et??
     *
     * @return string
     */
    public function getEt??()
    {
        return $this->et??;
    }


    /**
     * Set editore
     *
     * @param string $editore
     *
     * @return Lead_uni
     */
    public function setEditore($editore)
    {
        $this->editore = $editore;

        return $this;
    }

    /**
     * Get editore
     *
     * @return string
     */
    public function getEditore()
    {
        return $this->editore;
    }
    
    /**
     * Set submedia
     *
     * @param string $submedia
     *
     * @return Lead_uni
     */
    public function setSubmedia($submedia)
    {
        $this->submedia = $submedia;

        return $this;
    }

    /**
     * Get submedia
     *
     * @return string
     */
    public function getSubmedia()
    {
        return $this->submedia;
    }

    /**
     * Set citta
     *
     * @param string $citta
     *
     * @return Lead_uni
     */
    public function setCitta($citta)
    {
        $this->citta = $citta;

        return $this;
    }

    /**
     * Get citta
     *
     * @return string
     */
    public function getCitta()
    {
        return $this->citta;
    }

    /**
     * Set provincia
     *
     * @param string $provincia
     *
     * @return Lead_uni
     */
    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;

        return $this;
    }

    /**
     * Get provincia
     *
     * @return string
     */
    public function getProvincia()
    {
        return $this->provincia;
    }

    /**
     * Set indirizzo
     *
     * @param string $indirizzo
     *
     * @return Lead_uni
     */
    public function setIndirizzo($indirizzo)
    {
        $this->indirizzo = $indirizzo;

        return $this;
    }

    /**
     * Get indirizzo
     *
     * @return string
     */
    public function getIndirizzo()
    {
        return $this->indirizzo;
    }
    
    /**
     * Set regione
     *
     * @param string $regione
     *
     * @return Lead_uni
     */
    public function setRegione($regione)
    {
        $this->regione = $regione;

        return $this;
    }

    /**
     * Get regione
     *
     * @return string
     */
    public function getRegione()
    {
        return $this->regione;
    }
    
    /**
     * Set quartiere
     *
     * @param string $quartiere
     *
     * @return Lead_uni
     */
    public function setQuartiere($quartiere)
    {
        $this->quartiere = $quartiere;

        return $this;
    }

    /**
     * Get quartiere
     *
     * @return string
     */
    public function getQuartiere()
    {
        return $this->quartiere;
    }

    /**
     * Set nazione
     *
     * @param string $nazione
     *
     * @return Lead_uni
     */
    public function setNazione($nazione)
    {
        $this->nazione = $nazione;

        return $this;
    }

    /**
     * Get nazione
     *
     * @return string
     */
    public function getNazione()
    {
        return $this->nazione;
    }
    
    /**
     * Set cap
     *
     * @param string $cap
     *
     * @return Lead_uni
     */
    public function setCap($cap)
    {
        $this->cap = $cap;

        return $this;
    }

    /**
     * Get cap
     *
     * @return string
     */
    public function getCap()
    {
        return $this->cap;
    }

    /**
     * Set formaGiuridica
     *
     * @param boolean $formaGiuridica
     *
     * @return Lead_uni
     */
    public function setFormaGiuridica($formaGiuridica)
    {
        $this->forma_giuridica = $formaGiuridica;

        return $this;
    }

    /**
     * Get formaGiuridica
     *
     * @return boolean
     */
    public function getFormaGiuridica()
    {
        return $this->forma_giuridica;
    }

    /**
     * Set partitaIva
     *
     * @param string $partitaIva
     *
     * @return Lead_uni
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

    /**
     * Set tipoPartitaIva
     *
     * @param string $tipoPartitaIva
     *
     * @return Lead_uni
     */
    public function setTipoPartitaIva($tipoPartitaIva)
    {
        $this->tipo_partita_iva = $tipoPartitaIva;

        return $this;
    }

    /**
     * Get tipoPartitaIva
     *
     * @return string
     */
    public function getTipoPartitaIva()
    {
        return $this->tipo_partita_iva;
    }

	/**
     * Set cliente
     *
     * @param string $cliente
     *
     * @return Lead_uni
     */
    public function setCliente($cliente)
    {
        $this->cliente = $cliente;

        return $this;
    }

    /**
     * Get cliente
     *
     * @return string
     */
    public function getCliente()
    {
        return $this->cliente;
    }
	
    /**
     * Set codiceFiscale
     *
     * @param string $codiceFiscale
     *
     * @return Lead_uni
     */
    public function setCodiceFiscale($codiceFiscale)
    {
        $this->codice_fiscale = $codiceFiscale;

        return $this;
    }

    /**
     * Get codiceFiscale
     *
     * @return string
     */
    public function getCodiceFiscale()
    {
        return $this->codice_fiscale;
    }

    /**
     * Set importoRichiesto
     *
     * @param string $importoRichiesto
     *
     * @return Lead_uni
     */
    public function setImportoRichiesto($importoRichiesto)
    {
        $this->importo_richiesto = $importoRichiesto;

        return $this;
    }

    /**
     * Get importoRichiesto
     *
     * @return string
     */
    public function getImportoRichiesto()
    {
        return $this->importo_richiesto;
    }

    /**
     * Set data
     *
     * @param \DateTime $data
     *
     * @return Lead_uni
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return \DateTime
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set indirizzoIp
     *
     * @param string $indirizzoIp
     *
     * @return Lead_uni
     */
    public function setIndirizzoIp($indirizzoIp)
    {
        $this->indirizzo_ip = $indirizzoIp;

        return $this;
    }

    /**
     * Get indirizzoIp
     *
     * @return string
     */
    public function getIndirizzoIp()
    {
        return $this->indirizzo_ip;
    }

    /**
     * Set referenceId
     *
     * @param string $referenceId
     *
     * @return Lead_uni
     */
    public function setReferenceId($referenceId)
    {
        $this->reference_id = $referenceId;

        return $this;
    }

    /**
     * Get referenceId
     *
     * @return string
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * Set bannerId
     *
     * @param string $bannerId
     *
     * @return Lead_uni
     */
    public function setBannerId($bannerId)
    {
        $this->banner_id = $bannerId;

        return $this;
    }

    /**
     * Get bannerId
     *
     * @return string
     */
    public function getBannerId()
    {
        return $this->banner_id;
    }

    /**
     * Set download
     *
     * @param boolean $download
     *
     * @return Lead_uni
     */
    public function setDownload($download)
    {
        $this->download = $download;

        return $this;
    }

    /**
     * Get download
     *
     * @return boolean
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * Set tokenVerified
     *
     * @param boolean $tokenVerified
     *
     * @return Lead_uni
     */
    public function setTokenVerified($tokenVerified)
    {
        $this->token_verified = $tokenVerified;

        return $this;
    }

    /**
     * Get tokenVerified
     *
     * @return boolean
     */
    public function getTokenVerified()
    {
        return $this->token_verified;
    }

    /**
     * Set operatore
     *
     * @param string $operatore
     *
     * @return Lead_uni
     */
    public function setOperatore($operatore)
    {
        $this->operatore = $operatore;

        return $this;
    }

    /**
     * Get operatore
     *
     * @return string
     */
    public function getOperatore()
    {
        return $this->operatore;
    }
    
    /**
     * Set parentId
     *
     * @param string $parentId
     *
     * @return Lead_uni
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
    
    /**
     * Set latitudine
     *
     * @param string $latitudine
     *
     * @return Lead_uni
     */
    public function setLatitudine($latitudine)
    {
        $this->latitudine = $latitudine;

        return $this;
    }

    /**
     * Get latitudine
     *
     * @return string
     */
    public function getLatitudine()
    {
        return $this->latitudine;
    }

    /**
     * Set longitudine
     *
     * @param string $longitudine
     *
     * @return Lead_uni
     */
    public function setLongitudine($longitudine)
    {
        $this->longitudine = $longitudine;

        return $this;
    }

    /**
     * Get longitudine
     *
     * @return string
     */
    public function getLongitudine()
    {
        return $this->longitudine;
    }
    
    /**
     * Set url
     *
     * @param string $url
     *
     * @return Lead_uni
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
     * Set code
     *
     * @param string $code
     *
     * @return Lead_uni
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
     * Set privacy
     *
     * @param string $privacy
     *
     * @return Lead_uni
     */
    public function setPrivacy($privacy)
    {
        $this->privacy = $privacy;

        return $this;
    }

    /**
     * Get privacy
     *
     * @return string
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }    
    
    /**
     * Set privacyVersion
     *
     * @param string $privacyVersion
     *
     * @return Lead_uni
     */
    public function setPrivacyVersion($privacyVersion)
    {
        $this->privacy_version = $privacyVersion;

        return $this;
    }

    /**
     * Get privacyVersion
     *
     * @return string
     */
    public function getPrivacyVersion()
    {
        return $this->privacy_version;
    }    
    
    /**
     * Set emailVerified
     *
     * @param boolean $emailVerified
     *
     * @return Lead_uni
     */
    public function setEmailVerified($emailVerified)
    {
        $this->email_verified = $emailVerified;

        return $this;
    }

    /**
     * Get emailVerified
     *
     * @return boolean
     */
    public function getEmailVerified()
    {
        return $this->email_verified;
    }    
    
    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return Lead_uni
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }      

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Lead_uni
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set annoNascita
     *
     * @param \DateTime $annoNascita
     *
     * @return Lead_uni
     */
    public function setAnnoNascita($annoNascita)
    {
        $this->anno_nascita = $annoNascita;

        return $this;
    }

    /**
     * Get annoNascita
     *
     * @return \DateTime
     */
    public function getAnnoNascita()
    {
        return $this->anno_nascita;
    }

    /**
     * Set professione
     *
     * @param string $professione
     *
     * @return Lead_uni
     */
    public function setProfessione($professione)
    {
        $this->professione = $professione;

        return $this;
    }

    /**
     * Get professione
     *
     * @return string
     */
    public function getProfessione()
    {
        return $this->professione;
    }

    /**
     * Set eta
     *
     * @param string $eta
     *
     * @return Lead_uni
     */
    public function setEta($eta)
    {
        $this->eta = $eta;

        return $this;
    }

    /**
     * Get eta
     *
     * @return string
     */
    public function getEta()
    {
        return $this->eta;
    }

    /**
     * Set privacyTerzi
     *
     * @param boolean $privacyTerzi
     *
     * @return Lead_uni
     */
    public function setPrivacyTerzi($privacyTerzi)
    {
        $this->privacy_terzi = $privacyTerzi;

        return $this;
    }

    /**
     * Get privacyTerzi
     *
     * @return boolean
     */
    public function getPrivacyTerzi()
    {
        return $this->privacy_terzi;
    }

    /**
     * Set cabina
     *
     * @param string $cabina
     *
     * @return Lead_uni
     */
    public function setCabina($cabina)
    {
        $this->cabina = $cabina;

        return $this;
    }

    /**
     * Get cabina
     *
     * @return string
     */
    public function getCabina()
    {
        return $this->cabina;
    }

    /**
     * Set titoloDiStudio
     *
     * @param string $titoloDiStudio
     *
     * @return Lead_uni
     */
    public function setTitoloDiStudio($titoloDiStudio)
    {
        $this->titolo_di_studio = $titoloDiStudio;

        return $this;
    }

    /**
     * Get titoloDiStudio
     *
     * @return string
     */
    public function getTitoloDiStudio()
    {
        return $this->titolo_di_studio;
    }

    /**
     * Set sesso
     *
     * @param string $sesso
     *
     * @return Lead_uni
     */
    public function setSesso($sesso)
    {
        $this->sesso = $sesso;

        return $this;
    }

    /**
     * Get sesso
     *
     * @return string
     */
    public function getSesso()
    {
        return $this->sesso;
    }
}
