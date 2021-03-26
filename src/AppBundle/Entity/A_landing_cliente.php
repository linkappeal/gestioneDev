<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A_giacliente_brand
 *
 * @ORM\Table(name="a_landing_cliente")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LeadRepository")
 */
class A_landing_cliente  {
     
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Landing")
     */
    private $landing;
    
    /**
     * @ORM\ManyToOne(targetEntity="Cliente")
     */
    private $cliente;
	
	/**
     * @ORM\ManyToOne(targetEntity="Campagna")
     */
    private $campagna;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $indiretta = '0';
	
    /**
     * @ORM\Column(type="integer", nullable=true, options={"comment":" 0: intarget, 1:offtarget"})
     */
    private $offtarget = '0';

	/**
     * @ORM\Column(type="string", length=150, nullable=true, options={"Condizioni Offtarget: segnare nome campo=valore,nomecampo2=valore2"})
     */
    private $offtargetCond;
	
	/**
     * @ORM\Column(type="string", length=256, nullable=true, options={"Campi Db per salvataggio su leadout off target"})
     */
    private $campiExtOfftarget;
	
	/**
     * @ORM\Column(type="string", length=600, nullable=true)
     */
    private $campiExtLeadout = "SURNAME,NAME,CODICEFISCALE,PHONEFISSO,PHONE1,EMAIL,CLIENTE,TIPOAZIENDA,DATA,IP,URL,ID,MEDIA,SUBMEDIA,CODE,REFID,BANNERID,DOWNLOAD,TOKEN_VERIFIED,EMAIL_VERIFIED";
	
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $budgetCliente = '0';

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $clienteAttivo = '1';

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $clienteDefault = '0';

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idPrivacy = '1';

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $dbCliente;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $mailoperationCliente;

    /**
     * @ORM\Column(type="string", length=50, nullable=true, options={"Se offtarget, questo campo indicherà la tabella di prelievo delle lead"})
     */
    private $mailCliente;
	
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $data_start;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $data_end;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
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
     * Set indiretta
     *
     * @param integer $indiretta
     *
     * @return A_landing_cliente
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
	
    /**
     * Set offtarget
     *
     * @param integer $offtarget
     *
     * @return A_landing_cliente
     */
    public function setOfftarget($offtarget)
    {
        $this->offtarget = $offtarget;

        return $this;
    }

    /**
     * Get offtarget
     *
     * @return integer
     */
    public function getOfftarget()
    {
        return $this->offtarget;
    }
	
	/**
     * Set offtargetCond
     *
     * @param string $offtargetCond
     *
     * @return A_landing_cliente
     */
    public function setOfftargetCond($offtargetCond)
    {
        $this->offtargetCond = $offtargetCond;

        return $this;
    }

    /**
     * Get offtargetCond
     *
     * @return string
     */
    public function getOfftargetCond()
    {
        return $this->offtargetCond;
    }
	
	/**
     * Set campiExtOfftarget
     *
     * @param string $campiExtOfftarget
     *
     * @return A_landing_cliente
     */
    public function setCampiExtOfftarget($campiExtOfftarget)
    {
        $this->campiExtOfftarget = $campiExtOfftarget;

        return $this;
    }

    /**
     * Get campiExtOfftarget
     *
     * @return string
     */
    public function getCampiExtOfftarget()
    {
        return $this->campiExtOfftarget;
    }
	
	/**
     * Set campiExtLeadout
     *
     * @param string $campiExtLeadout
     *
     * @return A_landing_cliente
     */
    public function setCampiExtLeadout($campiExtLeadout)
    {
        $this->campiExtLeadout = $campiExtLeadout;

        return $this;
    }

    /**
     * Get campiExtLeadout
     *
     * @return string
     */
    public function getCampiExtLeadout()
    {
        return $this->campiExtLeadout;
    }
	
    /**
     * Set budgetCliente
     *
     * @param integer $budgetCliente
     *
     * @return A_landing_cliente
     */
    public function setBudgetCliente($budgetCliente)
    {
        $this->budgetCliente = $budgetCliente;

        return $this;
    }

    /**
     * Get budgetCliente
     *
     * @return integer
     */
    public function getBudgetCliente()
    {
        return $this->budgetCliente;
    }

    /**
     * Set clienteAttivo
     *
     * @param integer $clienteAttivo
     *
     * @return A_landing_cliente
     */
    public function setClienteAttivo($clienteAttivo)
    {
        $this->clienteAttivo = $clienteAttivo;

        return $this;
    }

    /**
     * Get clienteAttivo
     *
     * @return integer
     */
    public function getClienteAttivo()
    {
        return $this->clienteAttivo;
    }

    /**
     * Set clienteDefault
     *
     * @param integer $clienteDefault
     *
     * @return A_landing_cliente
     */
    public function setClienteDefault($clienteDefault)
    {
        $this->clienteDefault = $clienteDefault;

        return $this;
    }

    /**
     * Get clienteDefault
     *
     * @return integer
     */
    public function getClienteDefault()
    {
        return $this->clienteDefault;
    }

    /**
     * Set idPrivacy
     *
     * @param integer $idPrivacy
     *
     * @return A_landing_cliente
     */
    public function setIdPrivacy($idPrivacy)
    {
        $this->idPrivacy = $idPrivacy;

        return $this;
    }

    /**
     * Get idPrivacy
     *
     * @return integer
     */
    public function getIdPrivacy()
    {
        return $this->idPrivacy;
    }

    /**
     * Set dbCliente
     *
     * @param string $dbCliente
     *
     * @return A_landing_cliente
     */
    public function setDbCliente($dbCliente)
    {
        $this->dbCliente = $dbCliente;

        return $this;
    }

    /**
     * Get dbCliente
     *
     * @return string
     */
    public function getDbCliente()
    {
        return $this->dbCliente;
    }

    /**
     * Set mailoperationCliente
     *
     * @param string $mailoperationCliente
     *
     * @return A_landing_cliente
     */
    public function setMailoperationCliente($mailoperationCliente)
    {
        $this->mailoperationCliente = $mailoperationCliente;

        return $this;
    }

    /**
     * Get mailoperationCliente
     *
     * @return string
     */
    public function getMailoperationCliente()
    {
        return $this->mailoperationCliente;
    }

    /**
     * Set mailCliente
     *
     * @param string $mailCliente
     *
     * @return A_landing_cliente
     */
    public function setMailCliente($mailCliente)
    {
        $this->mailCliente = $mailCliente;

        return $this;
    }

    /**
     * Get mailCliente
     *
     * @return string
     */
    public function getMailCliente()
    {
        return $this->mailCliente;
    }

    /**
     * Set dataStart
     *
     * @param \DateTime $dataStart
     *
     * @return A_landing_cliente
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
     * @return A_landing_cliente
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
     * Set dataCreazione
     *
     * @param \DateTime $dataCreazione
     *
     * @return A_landing_cliente
     */
    public function setDataCreazione($dataCreazione)
    {
        $this->data_creazione = $dataCreazione;

        return $this;
    }

    /**
     * Get dataCreazione
     *
     * @return \DateTime
     */
    public function getDataCreazione()
    {
        return $this->data_creazione;
    }

    /**
     * Set landing
     *
     * @param \AppBundle\Entity\Landing $landing
     *
     * @return A_landing_cliente
     */
    public function setLanding(\AppBundle\Entity\Landing $landing)
    {
        $this->landing = $landing;

        return $this;
    }

    /**
     * Get landing
     *
     * @return \AppBundle\Entity\Landing
     */
    public function getLanding()
    {
        return $this->landing;
    }

    /**
     * Set cliente
     *
     * @param \AppBundle\Entity\Cliente $cliente
     *
     * @return A_landing_cliente
     */
    public function setCliente(\AppBundle\Entity\Cliente $cliente)
    {
        $this->cliente = $cliente;

        return $this;
    }

    /**
     * Get cliente
     *
     * @return \AppBundle\Entity\Cliente
     */
    public function getCliente()
    {
        return $this->cliente;
    }
	
	 /**
     * Set campagna
     *
     * @param \AppBundle\Entity\Campagna $campagna
     *
     * @return A_landing_cliente
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
	
}
