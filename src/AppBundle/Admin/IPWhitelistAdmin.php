<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\CoreBundle\Validator\ErrorElement;

class IPWhitelistAdmin extends AbstractAdmin {
    
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper
            ->add('indirizzo_ip', 'text', array(
                'label' => 'Indirizzo IP'
            ))
			->add('descrizione', 'text', array(
                'label' => 'Descrizione'
            ))
			;

    }
    
        // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('indirizzo_ip')
           ->add('descrizione')
     ;
    }

    // Fields to be shown on show action
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
           ->add('indirizzo_ip')
           ->add('descrizione')

       ;
    }
    
    public function validate(ErrorElement $errorElement, $object)
    {
        
        $valid = preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]|\*|\[([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\-([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]|\*|\[([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\-([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\])$/', $object->getIndirizzoIp());
            
        if(!$valid){
            $errorElement
                ->with('indirizzo_ip')
                ->addViolation('Immettere un indirizzo IP valido')
                ->end()               
            ;
        }
        else {
            //Check sul range di numeri di un gruppo
            $valid = explode('.', $valid);
            foreach ($valid as $num){
                
                if($num[0]=='['){
                    $range = explode('-', substr($num, 1, count($num)-1));
                    if ($range[0]>$range[1]) {
                    
                        $errorElement
                            ->with('indirizzo_ip')
                            ->addViolation('Range di numeri non valido')
                            ->end();
                    }                    
                }
            }
        }

    }
    
    protected function configureRoutes(RouteCollection $collection) {

                
    }

    
}

