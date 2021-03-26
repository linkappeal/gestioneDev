<?php

// src/AppBundle/Admin/ALandingClienteAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use AppBundle\Entity\A_landing_cliente;

class ALandingClienteAdmin extends AbstractAdmin
{
	protected $datagridValues = array(

        '_sort_by' => 'data_creazione',
        '_sort_order' => 'DESC',
    );
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d/m/Y H:i:s');
        
        $date->modify('+1 year');
        $strDateEnd = $date->format('d/m/Y H:i:s');
        
        $em = $this->modelManager->getEntityManager('AppBundle\Entity\Cliente');

        $query = $em->createQueryBuilder('c')
                ->select('c')
                ->from('AppBundle:Cliente', 'c')
                ->where('c.name IS NOT NULL')
                ->orderBy('c.name', 'ASC');
        
        $query2 = $em->createQueryBuilder('l')
                ->select('l')
                ->from('AppBundle:Landing', 'l')
                ->where('l.id IS NOT NULL')
                ->orderBy('l.id', 'ASC');
		$query_campagne = $em->createQueryBuilder('cc')
                ->select('cc')
                ->from('AppBundle:Campagna', 'cc')
                ->where('cc.id IS NOT NULL')
                ->orderBy('cc.id', 'ASC');
			
		$qd = $em->createQueryBuilder('d')
                ->select('d')
                ->from('AppBundle:Domini', 'd')
				->where('d.id IS NOT NULL')
                ->orderBy('d.id', 'ASC');
        $query_domini = $qd->getQuery();
		$array_Domini = $query_domini->getArrayResult();  
		
		foreach($array_Domini as $dominio){
			$arrayDomini[$dominio['description']] = $dominio['dbname'];
		}
		
        //$arrayType = $query2->getQuery();->getArrayResult();
        //$ret = array_combine(array_column($arrayType, 'slugLanding'),array_column($arrayType, 'id'));
        
        $formMapper
                    //->add('landing','choice', array('choices' => $ret))
                    ->add('landing', 'sonata_type_model', array('property'=>'slugLanding', 'label'=> 'Identificativo Campagna/Landing', 'query' => $query2))
                    ->add('cliente', 'sonata_type_model', array('property'=>'name', 'query' => $query))
                    ->add('campagna', 'sonata_type_model', array('property' => 'nome_offerta', 'query' => $query_campagne))
                    ->add('indiretta','choice',array(
						'choices' => array(
							'No' => 0,
							'Si' => 1,
							),
							'label'=> 'Campagna in diretta?' ))                    
					->add('offtarget','choice',array(
						'choices' => array(
							'No' => 0,
							'Si' => 1,
							),
							'label'=> 'Richiede OffTarget' ))
					->add('offtargetCond', 'text', array('label' => 'Condizioni Offtarget', 'required' => false))
					->add('campiExtOfftarget', 'text', array('label' => 'Campi da esportare per le Off Target', 'required' => false))
					->add('campiExtLeadout', 'text', array('label' => 'Campi da esportare nel Leadout', 'required' => false))
                    ->add('budgetCliente', 'text', array('label' => 'Budget Cliente'))
                    ->add('clienteAttivo','choice',array(
						'choices' => array(
							'Attivo' => 1,
							'Disattivo' => 0,
							),
							'label'=> 'Stato Cliente' ))
                    ->add('clienteDefault','choice',array(
						'choices' => array(
							'Si' => 1,
							'No' => 0,
							),
							'label'=> 'Cliente di Default' ))
                    ->add('idPrivacy', 'text', array('label' => 'ID Privacy'))
					//->add('dbCliente', 'sonata_type_model', array('property' => 'name', 'query' => $query_domini))
				//	->add('dbCliente', 'choice', array('choices'=>$arrayDomini))
                    ->add('dbCliente',
							'choice',array(
								//'placeholder' => 'Scegli un dominio per il leadout',
								'choices' => $arrayDomini, 
								'label'=> 'Dominio leadout cliente',
								'required' => true,
								)
							) 
                    ->add('mailoperationCliente', 'text', array('label' => 'Mail Operation'))
                    ->add('mailCliente', 'text', array('label' => 'Mail Cliente'))
                    ->add('data_start', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd/MM/yyyy H:m:s',
                                                      'label' => 'Data Inizio'))
                    ->add('data_end', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDateEnd,
                                                      'format' => 'dd/MM/yyyy H:m:s',
                                                      'label' => 'Data Fine'))
                    ->add('data_creazione', 'sonata_type_datetime_picker', array(
                                                      'dp_default_date' => $strDate,
                                                      'format' => 'dd/MM/yyyy H:m:s',
                                                      'label' => 'Data Creazione'))
        ;
    }
    
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('landing.slugLanding','string',array(
			'label'=> 'Slug Landing'))
            ->add('cliente.name','string',array(
			'label'=> 'Nome Cliente'))
			->add('cliente.account','string',array(
			'label'=> 'Account'))
			
			->add('offtarget','choice',array(
						'choices' => array(
							0 => 'IN TARGET',
							1 => 'OFF TARGET',
							),
							'label'=> 'Tipo'
            ))
			->add('indiretta','choice',array(
						'choices' => array(
							0 => 'NO',
							1 => 'IN DIRETTA',
							),
							'label'=> 'In diretta'
            ))
			->add('clienteAttivo','string',array(
							'label'=> 'Stato',
							'template' => 'list_campoAttivoAssociaCliente.html.twig'
            ))				
			->add('mailCliente','string',array(
			'label'=> 'Tabella Mail'))
			->add('mailoperationCliente','string',array(
			'label'=> 'MailOperation'))
            ->add('clienteDefault','choice',array(
						'choices' => array(
							0 => 'No',
							1 => 'DEFAULT',
							),
							'label'=> 'Default'
            ))
            ->add('data_creazione')
            //->add('data_end')   
			->add('_action', 'actions', array(
							'actions' => array(
								'edit' => array(),
							)
						))			

       ;
    }
	protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('landing.slugLanding',null, array('label' => 'Slug Landing'))
            ->add('cliente.name',null, array('label' => 'Nome Cliente'))
            //->add('offtarget', 'choice', array('choices' => array('IN TARGET' => 0,'OFF TARGET'  => 1)))
			->add('offtarget', 'doctrine_orm_string', array(), 'choice', array('choices' => array('IN TARGET' => 0,'OFF TARGET'  => 1)))
			->add('indiretta', 'doctrine_orm_string', array(), 'choice', array('choices' => array('NO' => 0,'SI'  => 1)))
        ;
    }   
	public function toString($object)
    {
        return $object instanceof A_landing_cliente
            ? $object->getLanding()->getTitoloLanding() . " -> " . $object->getCliente()->getName()
            : 'Associa Cliente'; // shown in the breadcrumb on the create view
    }
}