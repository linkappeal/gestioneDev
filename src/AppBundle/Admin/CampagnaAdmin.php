<?php

// src/AppBundle/Admin/CampagnaAdmin.php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class CampagnaAdmin extends AbstractAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $date = new \DateTime();
        $strDate = $date->format('d.m.Y');

		$em = $this->modelManager->getEntityManager('AppBundle\Entity\Brand');
        
		$query = $em->createQueryBuilder('b')
               ->select('b')
               ->from('AppBundle:Brand', 'b')
               ->where('b.name IS NOT NULL')
               ->orderBy('b.name', 'ASC');
			
		$scelteTipoCampagna = array(
						'Mobile' => 'Mobile',
						'Adsl' => 'Adsl',
						'Fisso' => 'Fisso',
						'Energia' => 'Energia',
						'Luce e Gas' => 'Luce e Gas',
						'Finanziaria' => 'Finanziaria',
						'Investimenti' => 'Investimenti',
						'Noleggio' => 'Noleggio',
						'Automobili' => 'Automobili',
						'Turismo' => 'Turismo',
						'Viaggi' => 'Viaggi',
						'Mix' => 'Mix',
						'Food' => 'Food',
						'Vini' => 'Vini',
						'Beverage' => 'Beverage',
						'Altro' => 'Altro',
						);
        $formMapper
                    //->add('landing','choice', array('choices' => $ret))
					->add('brand', 'sonata_type_model', array('property'=> 'name','query' => $query))
                    ->add('nome_offerta', 'text', array('label' => 'Nome offerta'))    
					->add('indiretta','choice',array(
						'choices' => array(
							'No' => 0,
							'Si' => 1,
							),
							'label'=> 'Campagna In diretta' ))
                    ->add('settore', 'text', array('label' => 'Settore'))    
                    ->add('tipo_campagna','choice',array(
						'choices' => $scelteTipoCampagna,
							'label'=> 'Target Campagna' ))  
					->add('target_campagna','choice',array(
						'choices' => array(
							'Business' => 'business',
							'Consumer' => 'consumer',
							'Mixed' => 'mixed',
							),
							'label'=> 'Target Campagna' ))

        ;
    }
    
	protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
		$em = $this->modelManager->getEntityManager('AppBundle\Entity\Cliente');

      /*  $query = $em->createQueryBuilder('b')
                ->select('b')
                ->from('AppBundle:Brand', 'b')
                ->where('b.name IS NOT NULL')
                ->orderBy('b.name', 'ASC'); */
				
        $datagridMapper
            ->add('id')
            ->add('nome_offerta')
			->add('indiretta', 'doctrine_orm_string', array(), 'choice', array('choices' => array('No' => 0,'Si'  => 1)))
			->add('brand.name')
        ;
    }
	
    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('nome_offerta')
			->add('brand.name')
			->add('indiretta','choice',array(
						'choices' => array(
							0 => '',
							1 => 'IN DIRETTA',
							),
							'label'=> 'In diretta'
            ))
			->add('_action', 'actions', array(
							'actions' => array(
								'edit' => array(),
							)
						))
                           

       ;
    }

}