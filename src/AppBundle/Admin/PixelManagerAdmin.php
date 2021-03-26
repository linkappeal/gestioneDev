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

class PixelManagerAdmin extends AbstractAdmin {

    protected $baseRouteName = 'pixel_manager_counters';
    protected $baseRoutePattern = 'lead_uni';
    
    protected function configureRoutes(RouteCollection $collection) {
        
        $collection->add('pixelmanager', 'pixelmanager');
        
        $collection->add('addpixel', 'addpixel');
        
        $collection->add('deletepixel','deletepixel');

        $collection->add('insertpixel','insertpixel');
        
        $collection->add('checkpixel','checkpixel');
        
        $collection->add('editpixel','editpixel');
        
        $collection->remove('list');
        $collection->remove('create');
    }
    

}