<?php

namespace AppBundle\Builder;
use Symfony\Bundle\DoctrineBundle\Registry;

class LeadCountersBuilder  {
    
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $orm;
    public  function __construct($orm)
    {
        $this->orm = $orm;
    }
    public function getCounters()
    {
        $repo = $this->orm->getRepository('AppBundle:Lead_uni');
        
        
//        $items = $repo->findAll();
//        $ret = array();
//        foreach($items as $item)
//        {
//            $ret[$item->getName()] = $item->getId();
//        }
        
        $cnt = 1234;
        
//        $datagrid = $this->admin->getDatagrid();
//        
//        $queryBuilder = $datagrid->getQuery();
        
       // var_dump($query);
        
        return $cnt;
    }
}