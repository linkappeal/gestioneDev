<?php

// src/AppBundle/Admin/AffiliatiAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class AffiliatiAdmin extends AbstractAdmin
{
    
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        $formMapper
			->add('id_fornitore', 'text', array(
                'label' => 'Id Fornitore'
            ))
			 ->add('nome', 'text', array(
                'label' => 'Nome'
            ))
			->add('refid', 'text', array(
                'label' => 'Refid',
            ))
			 ->add('ragionesociale', 'text', array(
                'label' => 'Ragione Sociale',
				'required' => false,
            ))
            ->add('piva', 'text', array(
                'label' => 'Partita IVA',
				'required' => false,
            ))                
            ->add('descrizione', 'textarea', array(
                'label' => 'Descrizione',
				'required' => false,
            ))                
            ->add('creationDate', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd.MM.yyyy',
                                                      'label' => 'Data Creazione'
            ))
    ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
       $datagridMapper
            ->add('id')
            ->add('ragionesociale',null, array('label' => 'Ragione Sociale'))
            ->add('piva')
            ->add('nome',null, array('label' => 'Nome'))
            ->add('refid')
            ->add('creationDate')
       ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
		->addIdentifier('id')
		->addIdentifier('ragionesociale',null, array('label' => 'Ragione Sociale'))
			->add('nome')
            ->add('refid')
            ->add('piva')                
            ->add('creationDate')

       ;
    }

    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
           ->add('ragionesociale',null, array('label' => 'Ragione Sociale'))
           ->add('refid',null, array('label' => 'Reference ID'))
           ->add('creation_date')
       ;
    }
}