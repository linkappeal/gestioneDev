<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class DominiAdmin extends AbstractAdmin
{
    
           // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        
        $formMapper
                    //->add('landing','choice', array('choices' => $ret))
                    ->add('dbname', 'text', array('label' => 'Nome Database'))   
                    ->add('dbhost', 'text', array('label' => 'Host Database', 'required' => false))   
                    ->add('dbpass', 'text', array('label' => 'Pass Database', 'required' => false))   
                    ->add('description', 'text', array('label' => 'Descrizione'))   
					->add('creationDate', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd.MM.yyyy',
                                                      'label' => 'Data Creazione'
            ))
        ;
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('dbname')
			 ->add('description')
			 ->add('creationDate')
       ;
    }

}