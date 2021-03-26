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

class EstrattoreAdmin extends AbstractAdmin {

    protected $baseRouteName = 'estrattore';
    protected $baseRoutePattern = 'leads';
    
    protected function configureRoutes(RouteCollection $collection) {
        
        $collection->add('estrattore', 'estrattore');
		
        $collection->add('getfilter', 'getfilter');
		
        $collection->add('filtralead', 'filtralead');
		
        $collection->add('getCampagnaField', 'getCampagnaField');
        
        $collection->remove('list');
        $collection->remove('create');
    }
    

}