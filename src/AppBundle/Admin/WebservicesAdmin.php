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

class WebservicesAdmin extends AbstractAdmin
{

    protected $baseRouteName = 'webservices';
    protected $baseRoutePattern = 'webservices';

    protected function configureRoutes(RouteCollection $collection)
    {
        //webservices
        $collection->add('webservices', 'webservices');
        $collection->add('crea', 'crea');
        $collection->add('salvaWebservice', 'salvaWebservice');
        $collection->add('modifica', 'modifica');
        $collection->add('editWebservice', 'editWebservice');
        $collection->add('reportWebservice', 'reportWebservice');
        $collection->add('getWebserviceReport', 'getWebserviceReport');
        $collection->add('generateToken', 'generateToken');
        $collection->add('get_fornitori_data', 'get_fornitori_data');
        $collection->add('get_standard_fields', 'get_standard_fields');
        $collection->add('get_specific_fields', 'get_specific_fields');

        //campi specifici
        $collection->add('specificFields', 'specificFields');
        $collection->add('editSpecificField', 'editSpecificField');
        $collection->add('saveSpecificField', 'saveSpecificField');

        // Routes
        $collection->add('routes', 'routes');
        $collection->add('editRoute', 'editRoute');
        $collection->add('saveRoute', 'saveRoute');
        $collection->add('mapping', 'mapping');
        $collection->add('saveMapping', 'saveMapping');

        // Unrouted
        $collection->add('unrouted', 'unrouted');

        // Clean up
        $collection->remove('list');
        $collection->remove('create');
    }


}