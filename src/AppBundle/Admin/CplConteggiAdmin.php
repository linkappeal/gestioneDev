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

class CplConteggiAdmin extends AbstractAdmin {

    protected $baseRouteName = 'cpl';
    protected $baseRoutePattern = 'cpl';
    
    protected function configureRoutes(RouteCollection $collection) {
        
        $collection->add('conteggi', 'conteggi');
        $collection->add('getCampagneByClienteId', 'getCampagneByClienteId');
        $collection->add('getConteggiCampagna', 'getConteggiCampagna');
        $collection->add('exportTable', 'exportTable');
        $collection->add('getClientiConcorsoByGiorno', 'getClientiConcorsoByGiorno');
        $collection->add('getClientiConcorsoByMese', 'getClientiConcorsoByMese');
        $collection->add('getGiorniniTotal', 'getGiorniniTotal');
        $collection->add('getMeseRightContent', 'getMeseRightContent');

        $collection->remove('list');
        $collection->remove('create');
    }
}