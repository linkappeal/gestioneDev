<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace AppBundle\Filter;

use AppBundle\Form\Type\MultiSelectType;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\CoreBundle\Form\Type\BooleanType;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;

class MultiSelectFilter extends Filter
{
    protected $entityManager;
    
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {

        // check data sanity
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }
        
        $query = $this->entityManager->createQueryBuilder();
        $id_campagne = $query->select('cmp')
                             ->from('AppBundle:Campagna', 'cmp');
        
        if(!empty($data['value']['settore'])) {
                                                $query->andWhere($query->expr()->in('cmp.settore', $data['value']['settore']));
//                                                $query->where('cmp.settore = :settore');
//                                                $query->setParameter('settore', $data['value']['settore']);
                                              }
        else return;
        
        if(!empty($data['value']['tipo_campagna'])) {
            
            $query->andWhere($query->expr()->in('cmp.tipo_campagna', $data['value']['tipo_campagna']));
                                                          
            if(!empty($data['value']['brand'])) {
                $query->andWhere($query->expr()->in('cmp.brand', $data['value']['brand']));
                
                if(!empty($data['value']['B2b_B2c'])) {
                    $query->andWhere($query->expr()->in('cmp.target_campagna', $data['value']['B2b_B2c']));
                    
                    if(!empty($data['value']['nome_offerta'])) {
                        $query->andWhere($query->expr()->in('cmp.nome_offerta', $data['value']['nome_offerta']));
                    }
                }
            }
        }
                                              
        $id_campagne = $query->getQuery()->getArrayResult();
        
        $id_campagne = array_map(function($val) {
            return $val['id'];
        }, $id_campagne);

        $queryBuilder->andWhere($queryBuilder->expr()->In($alias . '.campagna', $id_campagne));
        
        $this->active = true;

    }
    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return array();
    }
    /**
     * {@inheritdoc}
     */
    public function getRenderSettings()
    {
        return array('sonata_type_filter_default', array(
            'field_type' => MultiSelectType::class,
            'field_options' => $this->getFieldOptions(),
            'operator_type' => 'hidden',
            'operator_options' => array(),
            'label' => $this->getLabel()
        ));
    }
}