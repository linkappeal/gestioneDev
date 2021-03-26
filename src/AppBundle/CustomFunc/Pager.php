<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\CustomFunc;

use Sonata\DoctrineORMAdminBundle\Datagrid\Pager as SonataPager;

class Pager extends SonataPager
{
    public $nbResult;
    
    private $limit_results;
    
    public function computeNbResult()
    {
        $countQuery = clone $this->getQuery();

        if (count($this->getParameters()) > 0) {
            $countQuery->setParameters($this->getParameters());
        }

        if (empty($this->limit_results)){
            $countQuery->select(sprintf('count(DISTINCT %s.%s) as cnt', $countQuery->getRootAlias(), current($this->getCountColumn())));

            return $countQuery->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        }
        else return $this->limit_results;
    }
    
    public function setLimit($limit){
        
        $this->limit_results = $limit;
        
    }
}



