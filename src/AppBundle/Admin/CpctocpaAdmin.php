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

class CpctocpaAdmin extends AbstractAdmin {

    protected $baseRouteName = 'cpctocpa';
    protected $baseRoutePattern = 'cpctocpa';

	protected function configureRoutes(RouteCollection $collection) {
         $collection->add('lista', 'lista');
		 $collection->add('crea', 'crea');
		 $collection->add('salvaCpctocpa', 'salvaCpctocpa');
		 $collection->add('deleteCpctocpa', 'deleteCpctocpa');
		 $collection->add('modifica', 'modifica');
		 $collection->add('editCpctocpa', 	'editCpctocpa');
		 $collection->add('reportCpctocpa', 	'reportCpctocpa');
		 $collection->add('getCpctocpaReport', 'getCpctocpaReport');
		
        $collection->remove('list');
        $collection->remove('create');
    }
    

}