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
use Sonata\CoreBundle\Form\Type\BooleanType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class LeadAdmin extends AbstractAdmin {

    public $cliente_list;
    public $counters = array();
    
//    protected $datagridValues = array(
//
//    // display the first page (default = 1)
//    '_page' => 1,
//        
//    // reverse order (default = 'ASC')
//    '_sort_order' => 'DESC',
//
//    // name of the ordered field (default = the model's id field, if any)
//    '_sort_by' => 'data',
//    );
    
    protected $list_fields;

    public function getCounters() {

        if (empty($this->counters)) {

            $query = $this->datagrid->getQuery();
            
            
            $date_now = new \DateTime();
           // $queryn = $this->modelManager->getEntityManager('AppBundle\Entity\Extraction')->createQueryBuilder();
//            $nots = $queryn->select('ext')
//                    ->from('AppBundle:Extraction', 'ext')
//                    ->where('ext.data_sblocco >= :date_now')
//                    ->setParameter('date_now', $date_now->format('Y\-m\-d\ h:i:s'))
//                    ->getQuery()
//                    ->getArrayResult();
//
//
//            $nots_val = array_map(function($val) {
//                return $val['lead_id'];
//            }, $nots);

            $queryc = clone $query;
            $queryc->select('(case when o.cellulare IS NOT NULL AND o.cellulare!=\'\' then \'cellulare\' when o.tel_fisso IS NOT NULL AND o.tel_fisso !=\'\' then \'fisso\' else \'null\' end) as numero');
            //$queryc->addSelect('(case when o.data <= DATE_ADD(CURRENT_TIMESTAMP(),3, \'month\') THEN \'Vendibili\' ELSE \'Noleggiabili\' END) as tipo');
            $queryc->addSelect('(case when (e.data_sblocco <= CURRENT_TIMESTAMP() or e.data_sblocco is null) then \'oggi\' when (e.data_sblocco > CURRENT_TIMESTAMP() and e.data_sblocco <= DATE_ADD(CURRENT_TIMESTAMP(), 30,\'day\')) then \'trenta\' when (e.data_sblocco > DATE_ADD(CURRENT_TIMESTAMP(), 30,\'day\') and e.data_sblocco <= DATE_ADD(CURRENT_TIMESTAMP(), 60,\'day\'))  then \'sessanta\' else \'oltre\' END) AS disponib');
            $queryc->addSelect('COUNT(o.id) as counter');
            
            //$queryc->andWhere($query->expr()->notIn('o.id', array_unique($nots_val)));
            // $queryc->andWhere('o.data < :date');
            $queryc->groupBy('numero');
            //$queryc->addGroupBy('tipo');
            $queryc->addGroupBy('disponib');
            $queryc->setMaxResults('100');

            $result = $queryc->getQuery()->getArrayResult();

            foreach ($result as $key => $val) {

                $counters[$val['disponib']][$val['numero']] = $val['counter'];

                if (($val['numero'] == 'cellulare') ||
                        ($val['numero'] == 'fisso')) {

                    if (empty($counters[$val['disponib']]['totali'])) {
                        $counters[$val['disponib']]['totali'] = $val['counter'];
                    } else {
                        $counters[$val['disponib']]['totali'] += $val['counter'];
                    }


                    if (empty($counters[$val['numero']]['totali'])) {
                        $counters[$val['numero']]['totali'] = $val['counter'];
                    } else {
                        $counters[$val['numero']]['totali'] += $val['counter'];
                    }

                    if (empty($counters['totali'])) {
                        $counters['totali'] = $val['counter'];
                    } else {
                        $counters['totali'] += $val['counter'];
                    }
                }
            }

            $qb= $this->modelManager->getEntityManager('AppBundle\Entity\Lead_uni')->createQueryBuilder();
        
            //Conteggio per le lead con privacy terzi non accettata
            $res =  $qb->select('count(o.id) as cnt')
                ->from('AppBundle:Lead_uni', 'o')
                ->where('o.privacy_terzi = 0');

            $retval = $qb->getQuery()->getSingleScalarResult();
            if(!empty($retval)) $counters['privacy_not_accepted'] = $retval;
            
        if (!empty($counters))
                $this->counters = $counters;
        } else
            $counters = $this->counters;

        if (!empty($counters))
            return $counters;
        else
            return array();
    }

    public function setClienteListBuilder(\AppBundle\Builder\ClienteListBuilder $ext) {
        $this->cliente_list = $ext;
    }

    public function setLeadCounters(\AppBundle\Builder\LeadCountersBuilder $cnt) {
        //$this->counters = $cnt;
    }
	
	
    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('type',
                      'doctrine_orm_callback',
                        array( 'callback' => array($this, 'handleNoleggioVendita'),
                               'label' => 'Tipologia estrazione',
                               'field_type' => 'choice',
                               'field_options' => array('choices' => array(
                                                                    'Noleggio' => 'nol', // The key (value1) will contain the actual value that you want to filter on
                                                                    'Vendita' => 'ven' // The 'Name Two' is the "display" name in the filter
                                                                    ),
                                                                    'expanded' => true,
                                                                    'multiple' => false,
                                                                    'placeholder' => 'Tutte'
                                                                    
                                                        ),
                               'show_filter' => true))
                        
                ->add('gia_estratte', 
                      'doctrine_orm_callback', 
                       array( 'callback' => array($this, 'handleAlreadyExtractedFilter'),
                              'label' => 'Ricerca tra lead',
                              'show_filter' => true), 
                       'choice', 
                        array(    'choices' => array(
                                  'Estraibili' => '0', // The key (value1) will contain the actual value that you want to filter on
                                  'Estratte' => '1', // The 'Name Two' is the "display" name in the filter
                                   ),
                                  'placeholder' => 'Tutte',
                                  'expanded' => true,
                                  'multiple' => false))
                ->add('cliente', 
                      'doctrine_orm_callback', 
                       array(
                            'callback' => array($this, 'handleClienteFilter'),
                            'field_type' => 'choice',
                            'field_options' => array('choices' => $this->cliente_list->getList(),'multiple' => true)
                       )
                      )
                ->add('campagna',
                      'doctrine_orm_multiselect',
                       array('label'=>'Campagna',
                  ))
//            ->add('numero_leads', 'doctrine_orm_callback',
//                  array(
//                      'callback' => array($this, 'handleNumLeadsFilter'),
//                      'field_type' => 'number',
//                      //'field_options' => array('choices' => $this->cliente_list->getList()),
//                      'show_filter' => true
//                      )
//                      
//                )  
                ->add('id')
                ->add('nome')
                ->add('cognome')
                ->add('email')
                ->add('data_range', 'doctrine_orm_callback', array(
                    'callback' => array($this, 'getDateTimeFilter'),
                    'field_type' => 'sonata_type_date_range_picker',
                    'field_options' => [
                        'field_options' => [
                            'format' => 'yyyy-MM-dd'
                        ]
            ]))
                ->add('editore')
                ->add('operatore',
                      'doctrine_orm_string',
                       array('label'=>'Operatore Telefonico'))
                ->add('forma_giuridica',
                      'doctrine_orm_choice',
                       array(),
                       'choice',
                        array(    'choices' => array(
                                  'Persona fisica' => '0',
                                  'Persona giuridica' => '1', 
                                   ),
                                  //'placeholder' => 'Tutte',
                                  'expanded' => false,
                                  'multiple' => false))
                ->add('gia_cliente', 
                      'doctrine_orm_callback', 
                       array( 'callback' => array($this, 'handleGiaClienteFilter'),
                              'label' => 'Già cliente di',
                              'show_filter' => false), 
                       'choice', 
                       array('choices'=>$this->getAllClientiValues(),
                            'expanded' => false,
                            'multiple' => true))                 
                ->add('tel_fisso')
                ->add('cellulare')
                ->add('indirizzo')
                ->add('cap',
                      'doctrine_orm_choice',
                      array('label' => 'CAP'),
                      'choice',
                      array('choices'=>$this->getAllValues('cap'),
                            'expanded' => false,
                            'multiple' => true))   
                ->add('provincia',
                      'doctrine_orm_choice',
                      array('label' => 'Provincia'),
                      'choice',
                      array('choices'=>$this->getAllValues('provincia'),
                            'expanded' => false,
                            'multiple' => true))                      
//                ->add('citta',
//                      'doctrine_orm_string',
//                      array('label'=>'Città'),
//                      'choice',
//                      array(  'expanded' => false,
//                              'multiple' => true))
                ->add('regione')
                ->add('nazione')
                ->add('quartiere')

          ->add('nome_vuoto', 'doctrine_orm_callback', array(
                    'label' => 'Lead senza nome',
                    'translation_domain' => 'SonataCoreBundle',
                    'callback' => function($queryBuilder, $alias, $field, $value) {
                            if (!isset($value['value'])) {
                                return;
                            }
                            if ($value['value'] == BooleanType::TYPE_NO) {
                                $queryBuilder->andWhere(sprintf('%s.nome IS NOT NULL', $alias));
                            }
                            return true;
                        }
                    ), 'choice', array(
                        'translation_domain' => 'SonataCoreBundle',
                        'choices' => array(
							'label_type_yes' => BooleanType::TYPE_YES,
							'label_type_no'  => BooleanType::TYPE_NO
                        )
                    )
                )
				->add('cognome_vuoto', 'doctrine_orm_callback', array(
                    'label' => 'Lead senza cognome',
                    'translation_domain' => 'SonataCoreBundle',
                    'callback' => function($queryBuilder, $alias, $field, $value) {
                            if (!isset($value['value'])) {
                                return;
                            }
                            if ($value['value'] == BooleanType::TYPE_NO) {
                                $queryBuilder->andWhere(sprintf('%s.cognome IS NOT NULL', $alias));
                            }
                            return true;
                        }
                    ), 'choice', array(
                        'translation_domain' => 'SonataCoreBundle',
                        'choices' => array(
							'label_type_yes' => BooleanType::TYPE_YES,
							'label_type_no'  => BooleanType::TYPE_NO
                        )
                    )
                )
			->add('email_vuoto', 'doctrine_orm_callback', array(
				'label' => 'Lead senza email',
				'translation_domain' => 'SonataCoreBundle',
				'callback' => function($queryBuilder, $alias, $field, $value) {
						if (!isset($value['value'])) {
							return;
						}
						if ($value['value'] == BooleanType::TYPE_NO) {
							$queryBuilder->andWhere(sprintf('%s.email IS NOT NULL', $alias));
						}
						return true;
					}
				), 'choice', array(
					'translation_domain' => 'SonataCoreBundle',
					'choices' => array(
						'label_type_yes' => BooleanType::TYPE_YES,
						'label_type_no'  => BooleanType::TYPE_NO
					)
				)
			)
			->add('cellulare_vuoto', 'doctrine_orm_callback', array(
				'label' => 'Lead senza cellulare',
				'translation_domain' => 'SonataCoreBundle',
				'callback' => function($queryBuilder, $alias, $field, $value) {
						if (!isset($value['value'])) {
							return;
						}
						if ($value['value'] == BooleanType::TYPE_NO) {
							$queryBuilder->andWhere(sprintf('%s.cellulare IS NOT NULL', $alias));
							$queryBuilder->andWhere(sprintf('LENGTH(%s.cellulare) > 7', $alias));
						}
						return true;
					}
				), 'choice', array(
					'translation_domain' => 'SonataCoreBundle',
					'choices' => array(
						'label_type_yes' => BooleanType::TYPE_YES,
						'label_type_no'  => BooleanType::TYPE_NO
					)
				)
			)
            ;
			
    }

    public function handleClienteFilter($queryBuilder, $alias, $field, $value) {

        //var_dump($value);
        if (empty($value['value']))                
            return;

        //$queryBuilder->leftJoin('AppBundle:Extraction','e', \Doctrine\ORM\Query\Expr\Join::WITH, 'o.id = e.lead');
//         $queryBuilder->setFirstResult(0);
//         $queryBuilder->setMaxResults( 10 );
//         if (0){
             $queryBuilder->andWhere($queryBuilder->expr()->In('e.cliente', $value['value']));
             //Solo lead estratte
             //$queryBuilder->andWhere('e.data_sblocco is not null and e.data_sblocco >= CURRENT_TIMESTAMP()');
             
             //$queryBuilder->setParameter('id_cliente', $value['value']);
//             
//         $queryBuilder->setFirstResult(0);
//         $queryBuilder->setMaxResults( 10 );
//         }
//         else {
//             $queryBuilder->andWhere('e.cliente != :id_cliente');
//             $queryBuilder->orWhere('e.cliente is NULL');
//             $queryBuilder->setParameter('id_cliente', $value['value']);
//             
//         $queryBuilder->setFirstResult(0);
//         $queryBuilder->setMaxResults( 10 );
//         }
        
        return true;
    }

    public function handleAlreadyExtractedFilter($queryBuilder, $alias, $field, $value) {


        $query = $this->modelManager->getEntityManager('AppBundle\Entity\Extraction')->createQueryBuilder();

        $nots = $query->select('ext')
                ->from('AppBundle:Extraction', 'ext')
                ->where('ext.data_sblocco >= CURRENT_TIMESTAMP()')
                ->getQuery()
                ->getArrayResult();


        $nots_val = array_map(function($val) {
            return $val['lead_id'];
        }, $nots);

        if($value['value'] == null){
            $queryBuilder->leftJoin('AppBundle:Extraction', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'o.id = e.lead');
        }
        else if ($value['value'] == 0) {
            $queryBuilder->leftJoin('AppBundle:Extraction', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'o.id = e.lead');
            //$queryBuilder->andWhere($queryBuilder->expr()->notIn($alias . '.id', array_unique($nots_val)));
            $queryBuilder->andWhere("o.privacy_terzi = 1");
        } else if ($value['value'] == 1){
            $queryBuilder->leftJoin('AppBundle:Extraction', 'e', \Doctrine\ORM\Query\Expr\Join::WITH, 'o.id = e.lead');
            if (!empty($nots_val)) { $queryBuilder->andWhere($queryBuilder->expr()->In($alias . '.id', array_unique($nots_val))); }
        }
        
    }
    
    public function handleGiaClienteFilter($queryBuilder, $alias, $field, $value){
        
        if (empty($value['value']))                
        return;
        
        $queryBuilder->leftJoin('AppBundle:A_giacliente_brand','a', \Doctrine\ORM\Query\Expr\Join::WITH, 'o.id = a.lead');
        $queryBuilder->andWhere($queryBuilder->expr()->In('a.brand', $value['value']));
                
    }

    public function handleNumLeadsFilter($queryBuilder, $alias, $field, $value) {
        //var_dump($value);
        if ($value['value'] == null)
            return;
//
//        $queryBuilder->setFirstResult(0);
//        $queryBuilder->setMaxResults(10);
    }

    public function handleNoleggioVendita($queryBuilder, $alias, $field, $value) {
        //var_dump($value);

        //$queryBuilder->addSelect('e.data_sblocco as data_sblocco');
        //$queryBuilder->addSelect('e.data_sblocco');
         //$queryBuilder->addSelect('min(o.data)');
         //$queryBuilder->addSelect('max(o.data)');
       // $queryBuilder->expr()->min('o.data');
                
        if ($value['value'] == 'nol') {

            $queryBuilder->andWhere('CURRENT_TIMESTAMP() > DATE_ADD(o.data,3, \'month\') and (e.tipo_estrazione is NULL or e.tipo_estrazione =\'noleggio\') ');
        } else if ($value['value'] == 'ven') {

            $queryBuilder->andWhere('CURRENT_TIMESTAMP() <= DATE_ADD(o.data,3, \'month\') and ((e.tipo_estrazione is NULL and o.download = 0) or e.tipo_estrazione =\'vendita\') ');
            $queryBuilder->orderBy('o.data', 'DESC');
        } else {
            
        }
      
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        
        if(empty($this->list_fields)){
            $listMapper
                    ->add('nome')
                    ->add('cognome')
                    ->add('cellulare')
                    ->add('campagna.nome_offerta')
                    ->add('data')

            ;
        }
        else {
            
            foreach($this->list_fields as $field){
                $listMapper->add($field);                
                
            }
            
        }
    }
    
    

    public function getDateTimeFilter($queryBuilder, $alias, $field, $value) {

        /*
          ["value"]=>
          array(2) {
          ["start"]=>
          object(DateTime)#1005 (3) {
          ["date"]=>
          string(26) "2017-01-03 00:00:00.000000"
          ["timezone_type"]=>
          int(3)
          ["timezone"]=>
          string(11) "Europe/Rome"
          }
          ["end"]=>
          object(DateTime)#1002 (3) {
          ["date"]=>
          string(26) "2017-01-26 00:00:00.000000"
          ["timezone_type"]=>
          int(3)
          ["timezone"]=>
          string(11) "Europe/Rome"
         */

        if (empty($value['value']['start']) &&
                empty($value['value']['end'])) {
            return;
        }
        
        //$date_end = \DateTime::createFromFormat('Y-m-d', $value['value']['end']);
        $value['value']['end']->setTime(24, 0);

        $queryBuilder->andWhere('o.data>= :data_start');
        $queryBuilder->andWhere('o.data<= :data_end');   
        $queryBuilder->setParameter('data_start', $value['value']['start']);
        $queryBuilder->setParameter('data_end', $value['value']['end']);
        

//        var_dump($value);
//        var_dump($field);
//        var_dump($alias);

        return true;
    }

    public function getExportFormats() {
        return array(
            'csv'
        );
    }

    public function getExportFieldsCust($campi_estrazione)
    {
        //var_dump($campi_estrazione);
        
        $exp_fields[] = 'id';
        
        foreach($campi_estrazione as $key => $val){
            
            switch($val){

                case 1:
                    $exp_fields[] = 'nome_cognome';
                    break;

                case 2:
                    $exp_fields[] = 'cellulare';
                    break;

                case 3:
                    $exp_fields[] = 'email';
                    break;    
                
                case 4:
                    $exp_fields[] = 'campagna.brand';
                    break;  
                
                case 5:
                    $exp_fields[] = 'data';
                    break;   
                
                case 6:
                    $exp_fields[] = 'tel_fisso';
                    break; 

                default: break;

            }
        }
        //var_dump($exp_fields);
        return $exp_fields;

        //return array('id');
    }
    
    public function getAllValues($strValue){
        
        $qb = $this->modelManager->getEntityManager('AppBundle\Entity\Lead_uni')->createQueryBuilder();
        
//        $items = $repo->findAll();
//        
//        $ret = array();
//        $function = 'get'.ucfirst($strValue);
//        foreach($items as $item)
//        {
//            $prov = $item->$function();
//            
//            if (!empty($prov) && !array_key_exists($prov, $ret)){
//                $ret[$prov] = $prov;
//            }
//            
//        }
        
        $ret =  $qb->select('DISTINCT o.'.$strValue)
                    ->from('AppBundle:Lead_uni', 'o')
                    ->where('o.'.$strValue.' is not null AND o.'.$strValue.' not like \'\'')
                    ->orderBy('o.'.$strValue, 'ASC')
                    ->getQuery()
                    ->getArrayResult();
        
        //var_dump($ret);  
        $ret = array_column($ret, $strValue);
        $ret = array_combine($ret,$ret);
        
        return $ret;
    }
    
    public function getAllClientiValues(){
        
        $qb = $this->modelManager->getEntityManager('AppBundle\Entity\Lead_uni')->createQueryBuilder();
        
        $ret =  $qb->select('DISTINCT b.id, b.name')
            ->from('AppBundle:A_giacliente_brand', 'a')
            ->leftJoin('AppBundle:Brand','b', \Doctrine\ORM\Query\Expr\Join::WITH, 'a.brand = b.id')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
        
        $ret = array_combine(array_column($ret, 'name'),array_column($ret, 'id'));
        
        return $ret;
        
    }

    protected function configureRoutes(RouteCollection $collection) {
       
        $collection->add('exportcust', $this->getRouterIdParameter() . '/exportcust');

        $collection->add('exportdo', '/exportdo');
        
        $collection->add('extractdo', '/extractdo');
        
        $collection->add('getdates', '/getdates');
        
        $collection->add('addcliente', '/addcliente');
        
        $collection->add('getsettori', '/getsettori');
        
        $collection->add('gettipocampagna', '/gettipocampagna');
        
        $collection->add('getbrand', '/getbrand');
               
        $collection->add('getb2bb2c', '/getb2bb2c');
                        
        $collection->add('getnomeofferta', '/getnomeofferta');
        
        $collection->add('warningleads', '/warningleads');
        
        $collection->add('accessdenied', '/accessdenied');

        
        $collection->remove('create');
        $collection->remove('delete');

                        
    }
    
    public function setListFields($list_fields){
        
        $this->list_fields = $list_fields;
        
    }
    
    
}
