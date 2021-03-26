<?php

// src/AppBundle/Admin/FornitoriAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class FornitoriAdmin extends AbstractAdmin
{
    
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        $formMapper
			 ->add('nome', 'text', array(
                'label' => 'Nome'
            ))
			 ->add('ragionesociale', 'text', array(
                'label' => 'Ragione Sociale'
            ))
            ->add('falsemedia', 'text', array(
                'label' => 'Falsemedia',
                'required' => false
            ))
			->add('media', 'text', array(
                'label' => 'Media',
                'required' => false
            ))
            ->add('piva', 'text', array(
                'label' => 'Partita IVA'
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
            ->add('falsemedia')
            ->add('media')
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
            ->add('falsemedia')
            ->add('media')
            ->add('piva')                
            ->add('creationDate')

       ;
    }

    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
           ->add('ragionesociale',null, array('label' => 'Ragione Sociale'))
           ->add('creation_date')
       ;
    }
}