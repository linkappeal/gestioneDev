<?php

// src/AppBundle/Admin/CampagnaAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class BrandAdmin extends AbstractAdmin
{
    
           // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        
        $formMapper
                    //->add('landing','choice', array('choices' => $ret))
                    ->add('name', 'text', array('label' => 'Nome Brand'))   
						->add('creation_date', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd/MM/yyyy H:m:s',
                                                      'label' => 'Data Creazione'))

        ;
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
			 ->add('creation_date')
			
                           

       ;
    }
	
	protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name',null, array('label' => 'Nome Brand'))
        ;
    } 

}