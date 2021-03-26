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

class ClickcommandAdmin extends AbstractAdmin {

    protected $baseRouteName = 'clickcommand';
    protected $baseRoutePattern = 'clickcommand';
    
    protected function configureRoutes(RouteCollection $collection) {
        
        $collection->add('lista', 'lista');
        $collection->add('insertclickcommand', 'insertclickcommand');
        $collection->add('deleteclickcommand', 'deleteclickcommand');
        
        $collection->remove('list');
        $collection->remove('create');
    }
    

}