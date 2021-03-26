<?php 
// src/AppBundle/Twig/AppExtension.php
namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('pricers', array($this, 'priceFilter')),
        );
    }
	
	public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('genera_filtri', array($this, 'generate_filter')),
        );
    }

    public function generate_filter($filtri)
    {
		$output = '';
		if(!is_array($filtri)){
			return '';
		}
		foreach($filtri as $filtroId => $filtro){
			$output .= 	'<li>
							<a href="#" onclick="getFilter(\''.$filtroId.'\')" filter-target="filter-'.$filtroId.'" data-filtroid="' . $filtroId . '" class="sonata-toggle-filter">
								<i class="fa fa-square-o"></i>' 
								. $filtro["frontname"] 
								.
							'</a>
						</li>';
		}
        return $output;
		
		
        //return print_r($filtri, true);
    }
	/*
	    public function generateFilters($arrayFiltri = array())
    {
		$output = '';
		foreach($arrayFiltri as $filtro){
			
			$output = 	'<li>' .
							'<a href="#" class="sonata-toggle-filter" filter-target="" filter-container="">' .
						'<i class="fa fa-square-o"></i>Cliente'.
					'</a>'.
				'</li>';
		}
        return $output;
    }
	*/
}
?>