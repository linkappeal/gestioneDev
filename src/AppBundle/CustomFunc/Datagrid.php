<?php
namespace AppBundle\CustomFunc;

use Sonata\AdminBundle\Datagrid\Datagrid as SonataDatagrid;
use Sonata\AdminBundle\Admin\FieldDescriptionCollection;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Filter\FilterInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class Datagrid extends SonataDatagrid
{

    private $lead_limit;
       
    public function setLeadLimit($limit){
        
        $this->lead_limit = $limit;
        
    }
    
    public function buildPager()
    {
        if ($this->bound) {
            return;
        }

        foreach ($this->getFilters() as $name => $filter) {
            list($type, $options) = $filter->getRenderSettings();

            $this->formBuilder->add($filter->getFormName(), $type, $options);
        }

        // NEXT_MAJOR: Remove BC trick when bumping Symfony requirement to 2.8+
        $hiddenType = method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\HiddenType'
            : 'hidden';

        $this->formBuilder->add('_sort_by', $hiddenType);
        $this->formBuilder->get('_sort_by')->addViewTransformer(new CallbackTransformer(
            function ($value) {
                return $value;
            },
            function ($value) {
                return $value instanceof FieldDescriptionInterface ? $value->getName() : $value;
            }
        ));

        $this->formBuilder->add('_sort_order', $hiddenType);
        $this->formBuilder->add('_page', $hiddenType);
        $this->formBuilder->add('_per_page', $hiddenType);

        $this->form = $this->formBuilder->getForm();
        $this->form->submit($this->values);

        $data = $this->form->getData();

        foreach ($this->getFilters() as $name => $filter) {
            $this->values[$name] = isset($this->values[$name]) ? $this->values[$name] : null;
            $filter->apply($this->query, $data[$filter->getFormName()]);
        }
        
//        if (isset($this->values['_sort_by'])) {
//            if (!$this->values['_sort_by'] instanceof FieldDescriptionInterface) {
//                throw new UnexpectedTypeException($this->values['_sort_by'], 'FieldDescriptionInterface');
//            }
//
//            if ($this->values['_sort_by']->isSortable()) {
//                $this->query->setSortBy($this->values['_sort_by']->getSortParentAssociationMapping(), $this->values['_sort_by']->getSortFieldMapping());
//                $this->query->setSortOrder(isset($this->values['_sort_order']) ? $this->values['_sort_order'] : null);
//            }
//        }
        
          $arrSort = array(
            "fieldName" => "data",
            "type" => "datetime",
            "scale" => 0,
            "length" => NULL,
            "unique" => false,
            "nullable" =>false,
            "precision" => 0,
            "columnName" => "data"
        );
        
//        var_dump($this->values['_sort_by']->getSortParentAssociationMapping());
//        var_dump($this->values['_sort_by']->getSortFieldMapping());
         $this->query->resetDQLPart('orderBy');
         $this->query->setSortBy(array(), $arrSort);
         $this->query->setSortOrder('DESC');

        $maxPerPage = 50;

        $this->pager->setMaxPerPage($maxPerPage);

        $page = 1;
//        if (isset($this->values['_page'])) {
//            // check for `is_array` can be safely removed if php 5.3 support will be dropped
//            if (is_array($this->values['_page'])) {
//                if (isset($this->values['_page']['value'])) {
//                    $page = $this->values['_page']['value'];
//                }
//            } else {
//                $page = $this->values['_page'];
//            }
//        }

        $this->pager->setPage($page);

        $this->pager->setQuery($this->query);
        if(!empty($this->lead_limit)) $this->pager->setLimit($this->lead_limit);
        $this->pager->init();

        $this->bound = true;
    }
}