<?php

namespace AppBundle\Builder;
use Symfony\Bundle\DoctrineBundle\Registry;

class IPListBuilder  {
    
    public function getIPWhitelist()
    {
        return "127.0.0.1";
    }
}