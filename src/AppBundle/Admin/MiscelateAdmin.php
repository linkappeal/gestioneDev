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

class MiscelateAdmin extends AbstractAdmin {

    protected $baseRouteName = 'miscelate';
    protected $baseRoutePattern = 'miscelate';

	protected function configureRoutes(RouteCollection $collection) {
         $collection->add('miscelate', 'miscelate');
		 $collection->add('crea', 'crea');
		 $collection->add('salvaMiscelata', 'salvaMiscelata');
		 $collection->add('deleteMiscelata', 'deleteMiscelata');
		 $collection->add('modifica', 'modifica');
		 $collection->add('editMiscelata', 	'editMiscelata');
		 $collection->add('reportMiscelata', 	'reportMiscelata');
		 $collection->add('getMiscelataReport', 'getMiscelataReport');
		 $collection->add('getCplGranulo', 'getCplGranulo');
		
       
      
       
		
        $collection->remove('list');
        $collection->remove('create');
    }
    

}