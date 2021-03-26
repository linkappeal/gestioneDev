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

class ConcorsoAdmin extends AbstractAdmin {

    protected $baseRouteName = 'concorso';
    protected $baseRoutePattern = 'concorso';
    
    protected function configureRoutes(RouteCollection $collection) {
        
        $collection->add('conteggi', 'conteggi');
        $collection->add('getConteggioCustomer', 'getConteggioCustomer');
        $collection->add('getMediaInfo', 'getMediaInfo');
        $collection->add('getConteggioConcorso', 'getConteggioConcorso');
        $collection->add('getClientiConcorsoByGiorno', 'getClientiConcorsoByGiorno');
        $collection->add('getClientiConcorsoByMese', 'getClientiConcorsoByMese');
        $collection->add('getMeseRightContent', 'getMeseRightContent');

        $collection->remove('list');
        $collection->remove('create');
    }
}