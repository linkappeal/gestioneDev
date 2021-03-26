<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;

use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class OrdiniAdmin extends AbstractAdmin {

    protected $baseRouteName = 'ordini';
    protected $baseRoutePattern = 'ordini';

	protected function configureRoutes(RouteCollection $collection) {
        
		// CLIENTI
        $collection->add('clienti', 'clienti');
        $collection->add('crea', 'crea');
        $collection->add('modifica', 'modifica');
        $collection->add('conteggiClienti', 'conteggiClienti'); // azione di richiamo render conteggi cliente
        $collection->add('conteggiaCliente', 'conteggiaCliente'); // azione di conteggio per campagna cliente
        $collection->add('getLandingCampagneFromCliente', 'getLandingCampagneFromCliente');
        $collection->add('exportConteggio', 'exportConteggio');
        $collection->add('deleteOrder', 'deleteOrder');
	    $collection->add('checkOrdine', 'checkOrdine');
	    $collection->add('salvaOrdine', 'salvaOrdine');
	    $collection->add('editOrdine', 	'editOrdine');
		$collection->add('getCampaignColumns', 'getCampaignColumns');
		$collection->add('removeSingleGroup', 'removeSingleGroup');
		$collection->add('salvaPay', 'salvaPay');
		$collection->add('getStornoTable', 'getStornoTable');
		$collection->add('updateTableAfterStorni', 'updateTableAfterStorni');
		$collection->add('getStornoTableIndiretta', 'getStornoTableIndiretta');
		
		// funzione screenshot landings
		$collection->add('getLandingScreenshot', 'getLandingScreenshot'); // ajax call for screenshots
		$collection->add('updateTableAfterLeadAdd', 'updateTableAfterLeadAdd');
		
		
		// FORNITORI
	    $collection->add('fornitori', 'fornitori');
        $collection->add('creaFornitore', 'creaFornitore');
        $collection->add('modificaFornitore', 'modificaFornitore');
        $collection->add('conteggiFornitori', 'conteggiFornitori'); // azione di richiamo render conteggi cliente
        $collection->add('conteggiaFornitore', 'conteggiaFornitore'); // azione di conteggio per campagna cliente
        $collection->add('exportConteggioFornitore', 'exportConteggioFornitore');
        $collection->add('deleteOrderFornitore', 'deleteOrderFornitore');
	    $collection->add('checkOrdineFornitore', 'checkOrdineFornitore');
	    $collection->add('salvaOrdineFornitore', 'salvaOrdineFornitore');
	    $collection->add('getFornitoreColumns', 'getFornitoreColumns');
	    $collection->add('editFornitoreOrdine', 'editFornitoreOrdine');
	    $collection->add('getStornoFornitoriTable', 'getStornoFornitoriTable');
	    $collection->add('exportConteggioFornitori', 'exportConteggioFornitori');
	    $collection->add('getCampagneByFornitore', 'getCampagneByFornitore');
	    $collection->add('getAffilitatiFornitore', 'getAffilitatiFornitore');
	    $collection->add('getCampagneByFornitoreAndAffiliato', 'getCampagneByFornitoreAndAffiliato');
		$collection->add('getListFornitoriOrdini', 'getListFornitoriOrdiniAction');
		//$collection->add('getStornoTable', 'getStornoTable');
		//$collection->add('updateTableAfterStorni', 'updateTableAfterStorni');
		
		// funzione custom per serializzazione database payout clienti
		$collection->add('serializza', 'serializza');
		
		// funzione custom per l'update della tabella clienti ordini con l'id del cliente
		$collection->add('updateClienti', 'updateClienti');
		
        $collection->remove('list');
        $collection->remove('create');
    }
    

}