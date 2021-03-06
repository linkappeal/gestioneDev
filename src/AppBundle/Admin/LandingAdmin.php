<?php

// src/AppBundle/Admin/LandingAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class LandingAdmin extends AbstractAdmin
{
       // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        
        $formMapper
                    //->add('landing','choice', array('choices' => $ret))
                    //->add('campagna', 'sonata_type_model', array('property'=>'nome_offerta'))
                    ->add('titoloLanding', 'text', array('label' => 'Titolo Landing'))
                    ->add('slugLanding', 'text', array('label' => 'Slug'))       
                    ->add('url', 'text', array('label' => 'URL'))
                    ->add('logo', 'text', array('label' => 'Logo'))
                    ->add('font', 'text', array('label' => 'Font'))
                    ->add('font_size', 'text', array('label' => 'Grandezza Font'))
                    ->add('font_color', 'text', array('label' => 'Colore Font'))
                    ->add('coloreButton', 'text', array('label' => 'Colore tasto conferma'))
                    ->add('testoTankyou', 'textarea', array('label' => 'Testo Thank You Page'))
                    ->add('data_creazione', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd.MM.yyyy',
                                                      'label' => 'Data Creazione'))
        ;
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('titoloLanding')
            ->add('slugLanding')
            ->add('dataCreazione', null, array('label' => 'Data Creazione'))
            ->add('_action', 'actions', array(
							'actions' => array(
								'edit' => array(),
							)
						));
    }
	
		protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('slugLanding',null, array('label' => 'Slug Landing'))
            ->add('titoloLanding',null, array('label' => 'Nome Offerta'))
            //->add('offtarget', 'choice', array('choices' => array('IN TARGET' => 0,'OFF TARGET'  => 1)))
			//->add('offtarget', 'doctrine_orm_string', array(), 'choice', array('choices' => array('IN TARGET' => 0,'OFF TARGET'  => 1)))
        ;
    }
    
}