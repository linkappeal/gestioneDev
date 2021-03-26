<?php

namespace AppBundle\Builder;
use Symfony\Bundle\DoctrineBundle\Registry;

class ClienteListBuilder {
    
    /** @var \Symfony\Bundle\DoctrineBundle\Registry */
    private $orm;
    public  function __construct($orm)
    {
        $this->orm = $orm;
    }
    public function getList()
    {
        $repo = $this->orm->getRepository('AppBundle:Cliente');
        $items = $repo->findAll();
        $ret = array();
        foreach($items as $item)
        {
            $ret[$item->getName()] = $item->getId();
        }
        return $ret;
    }
    
    public function getClientefromId($id)
    {
        $repo = $this->orm->getRepository('AppBundle:Cliente');
        $item = $repo->findById($id);
        
        $ret = $item[0]->getName();
        
        return $ret;
    }
}