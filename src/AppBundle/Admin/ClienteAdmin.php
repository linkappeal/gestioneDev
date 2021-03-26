<?php

// src/AppBundle/Admin/ClienteAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class ClienteAdmin extends AbstractAdmin
{
    
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');
        
        $formMapper
			 ->add('account', 'text', array(
                'label' => 'Account Cliente'
            ))
            ->add('name', 'text', array(
                'label' => 'Ragione Sociale'
            ))
			->add('username', 'text', array(
                'label' => 'Username'
            ))
			->add('password_ic', 'text', array(
                'label' => 'Password',
				'required' => false
            ))
            ->add('creation_date', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd.MM.yyyy',
                                                      'label' => 'Data Creazione'
            ))
            ->add('code', 'text', array(
                'label' => 'Codice Cliente (lasciare vuoto per generare automaticamente)',
                'required' => false
            ))
            ->add('partita_iva', 'text', array(
                'label' => 'Partita IVA'
            ))                
    ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
       $datagridMapper
            ->add('id')
            ->add('account')
            ->add('name',null, array('label' => 'Ragione Sociale'))
            ->add('username',null, array('label' => 'Username'))
            ->add('password_ic',null, array('label' => 'Password'))
            ->add('creation_date')
            ->add('code')
            ->add('partita_iva')
       ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
		->addIdentifier('id')
		->addIdentifier('name',null, array('label' => 'Ragione Sociale'))
			->add('account')
            ->add('creation_date')
            ->add('username')
            ->add('password_ic',null, array('label' => 'Password'))
            ->add('code')
            ->add('partita_iva')                

       ;
    }

    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
           ->add('name',null, array('label' => 'Ragione Sociale'))
           ->add('username',null, array('label' => 'Username'))
           ->add('creation_date')
       ;
    }
    
    
    public function prePersist($cliente)
    {

        if (empty($cliente->getCode())){
            
            $code = $cliente->genClienteCode($cliente->getName());
            
            $cliente->setCode($code);
            
        }  
		if (empty($cliente->getPasswordIc())){
            $pass = $cliente->random_password();
            $cliente->setPasswordIc($pass); // qui viene anche codificata in md5 la password
            
        }
        
    }    
    
}