<?php

namespace AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Doctrine\Common\Util\Debug;
use Doctrine\DBAL\DriverManager;
use Exporter\Source\DoctrineORMQuerySourceIterator;

use DoctrineORMEntityManager;

use Symfony\Component\HttpFoundation\StreamedResponse;

use AppBundle\CustomFunc\Handler as Handler;
use AppBundle\Entity\Extraction as Extraction;
use AppBundle\Entity\Extraction_history as Extraction_history;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Cliente as Cliente;

use Sonata\AdminBundle\Route\RouteCollection;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CRUDTwoCountController extends Controller
{
    
    
    public function twocountersAction(){
        
            $date = new \DateTime(); 
            $date->setTime(0, 0);
            
			
			// oggi
			$OggiCntIntarget 	= $this->getCounter(0, $date);
			$OggiCntOfftarget 	= $this->getCounter(1, $date);
			$OggiIndirette		= $this->getCounterIndirette($date);
			
			$todayLeadsNum 					= $OggiCntIntarget['totali'];
            $todayLeadsNumNotPassed 		= $OggiCntOfftarget['totali'];
            $todayLeadsNumIndir 			= $OggiIndirette['totali'];
            $todayLeadsCampaign 			= $OggiCntIntarget['per_campagna'];
            $todayLeadsOffTargetCampaign 	= $OggiCntOfftarget['per_campagna'];
            $todayLeadsNumIndirCampaign 	= $OggiIndirette['per_campagna'];
           
           
			// ieri
            $date->modify('-1 days');
			$IeriCntIntarget	= $this->getCounter(0, $date);
			$IeriCntOfftarget 	= $this->getCounter(1, $date);
			$IeriIndirette		= $this->getCounterIndirette($date);
			
            $yesterdayLeadsNum 					= $IeriCntIntarget['totali'];
            $yesterdayLeadsNumNotPassed 		= $IeriCntOfftarget['totali'];
            $yesterdayLeadsNumIndir 			= $IeriIndirette['totali'];
            $yesterdayLeadsCampaign				= $IeriCntIntarget['per_campagna'];
            $yesterdayLeadsOffTargetCampaign 	= $IeriCntOfftarget['per_campagna'];
            $yesterdayLeadsNumIndirCampaign		= $IeriIndirette['per_campagna'];
            
			// l'altro ieri
			$date->modify('-1 days');
            $AltroIeriCntIntarget	= $this->getCounter(0, $date);
			$AltroIeriCntOfftarget 	= $this->getCounter(1, $date);
			$AltroIeriIndirette		= $this->getCounterIndirette($date);
			
			
            $postYesterdayLeadsNum 					= $AltroIeriCntIntarget['totali'];
            $postYesterdayLeadsNumNotPassed 		= $AltroIeriCntOfftarget['totali'];
            $postYesterdayLeadsNumIndir 			= $AltroIeriIndirette['totali'];
            $postYesterdayLeadsCampaign				= $AltroIeriCntIntarget['per_campagna'];
            $postYesterdayLeadsOffTargetCampaign 	= $AltroIeriCntOfftarget['per_campagna'];
            $postYesterdayLeadsNumIndirCampaign		= $AltroIeriIndirette['per_campagna'];
			
            $dataScript = $this->getLastScriptLaunch();
        
            return $this->render('two_counters_lead.html.twig', array(
                'action' 							=> 'counters',
                'csrf_token' 						=> $this->getCsrfToken('sonata.batch'),
                'data_script' 						=> $dataScript,
                'today'								=> $todayLeadsNum,
                'today_notpassed' 					=> $todayLeadsNumNotPassed,
                'today_indirette' 					=> $todayLeadsNumIndir,
                'today_campaign' 					=> $todayLeadsCampaign,
                'today_offtarget' 					=> $todayLeadsOffTargetCampaign,
                'yesterday' 						=> $yesterdayLeadsNum,
                'yesterday_offtarget' 				=> $yesterdayLeadsOffTargetCampaign,
                'yesterday_campaign' 				=> $yesterdayLeadsCampaign,
                'yesterday_notpassed' 				=> $yesterdayLeadsNumNotPassed,
                'yesterday_indirette'				=> $yesterdayLeadsNumIndir,
                'postyesterday' 					=> $postYesterdayLeadsNum,
                'postyesterday_notpassed' 			=> $postYesterdayLeadsNumNotPassed,
                'postyesterday_indirette' 			=> $postYesterdayLeadsNumIndir,
				'postyesterday_campaign' 			=> $postYesterdayLeadsCampaign,
                'postyesterday_offtarget' 			=> $postYesterdayLeadsOffTargetCampaign,
                'today_indirette_campaign' 			=> $todayLeadsNumIndirCampaign,
                'yesterday_indirette_campaign' 		=> $yesterdayLeadsNumIndirCampaign,
                'postyesterday_indirette_campaign' 	=> $postYesterdayLeadsNumIndirCampaign,
            ), null); 
    }
	
	public function getCounterIndirette($date, $date2=null){
		//$count['per_campagna'] = array();
		$count['totali'] = 0;
		$count['per_campagna']=array();
		if(empty($date2)){
			$date2 = new \DateTime($date->format('Y-m-d H:i:s'));
			$date2->add(new \DateInterval('P1D'));
		}
		
		$config = new \Doctrine\DBAL\Configuration();
        $conn = $this->get('doctrine.dbal.pixel_connection');
		
        $sql  = "SELECT id_campagna as campagna, count(*) as tot FROM pixel_trace ";
        $sql .= " WHERE indiretta = 1 ";
        $sql .= " AND dt >= '"	.$date->format('Y-m-d H:i:s')."'";
        $sql .= " AND dt < '"	.$date2->format('Y-m-d H:i:s')."'";
        $sql .= " GROUP BY id_campagna";
        
        $stmt = $conn->query($sql);
        $results =  $stmt->fetchAll();
		
		foreach($results as $key => $val){
		if(empty($val['campagna'])){ $val['campagna'] = 0; }
			$count['per_campagna'][$val['campagna']][] = array('num' => $val['tot']  );
			$count['totali']+=$val['tot'];
        }
		return $count;
		
	}
	
	public function getCounter($target=0, $date, $date2=null){
		$count['per_campagna'] = array();
		$count['totali'] = 0;
		if(empty($date2)){
			$date2 = new \DateTime($date->format('Y-m-d H:i:s'));
			$date2->add(new \DateInterval('P1D'));
		}
		$sql_not_linkappeal = " AND cli.id !='76' ";
		// edit 24/07/2017 per campagna mono campo che salva nome, cognome ed email come NULL: aggiunto ifnull nei filtri antitest
		$sql = "SELECT
				ca.nome_offerta as campagna, 
				cli.name as Cliente, count(cnt.cliente_id) as tot
				from contatore cnt 
				left join campagna as ca on cnt.campagna_id = ca.id
				left join cliente as cli on cnt.cliente_id = cli.id
				left join lead_uni as lu on cnt.lead_id = lu.id
				where cnt.offtarget=".$target."
				AND cnt.data_lead >= '".$date->format('Y-m-d H:i:s')."'
				AND cnt.data_lead < '".$date2->format('Y-m-d H:i:s')."'
				AND IFNULL(lu.email, '') not like '%prova%' 
				AND IFNULL(lu.email, '') not like '%test%' 
				AND IFNULL(lu.nome, '') not like 'test%' 
				AND IFNULL(lu.nome, '') not like 'prova%'"  .	
				$sql_not_linkappeal // rimuovo il cliente Linkappeal in quanto non deve essere conteggiato
				. "group by cli.name, ca.nome_offerta
				order by ca.nome_offerta ASC";

		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$results =  $stmt->fetchAll();
		
		foreach($results as $key => $val){
			if(empty($val['campagna'])){ $val['campagna'] = 0; }
			$count['per_campagna'][$val['campagna']][] = array('cliente' => $val['Cliente'], 'num' => $val['tot']  );
			$count['totali']+=$val['tot'];
        }
		return $count;

	}
    
    
	public function countbyrangedataAction(Request $request){
            
		$data = $request->get('data');
		$target = $request->get('target');
		
		$dates = explode(' - ', $data);
		$date_min = \DateTime::createFromFormat('d/m/y', $dates[0]);
		$date_min->setTime(0, 0, 0);
		$date_max = \DateTime::createFromFormat('d/m/y', $dates[1]);
		$date_max->setTime(0, 0, 0);
		
		if($date_min==$date_max){
			$date_max = new \DateTime($date_max->format('Y-m-d H:i:s'));
			$date_max->add(new \DateInterval('P1D'));
		}else{
			$date_max = new \DateTime($date_max->format('Y-m-d H:i:s'));
			$date_max->add(new \DateInterval('P1D'));
		}
				
		
		//echo "min: " . $date_min->format('Y-m-d H:i:s');
		//echo "<br>MAX: " . $date_max->format('Y-m-d H:i:s');
		$result = $this->getRangeDateNumberLeads($date_min, $date_max, $target);
		$result['stato'] = true;
		
		$response = new Response();
		$response->setContent(json_encode($result));

		$response->headers->set('Content-Type', 'application/json');

		return  $response;
        
	}
        
        function getLastScriptLaunch(){
            
            $qb= $this->getDoctrine()->getManager()->createQueryBuilder();
            
            $qb->select('max(s.launch_date)')
            ->from('AppBundle:Scripts_history', 's');
        
            $retval = $qb->getQuery()->getSingleScalarResult(); 
            
            return $retval;
            
        }
        


    public function getRangeDateNumberLeads($date1, $date2, $target=null){
        
        $count = array();
        $count['totali'] 				= 0; //totale intarget
        $count['per_campagna'] 			= array(); //dettaglio campagne intarget
        $count['totaleOfftarget']		= 0; //totale offtarget
		$count['indirette']				= 0; // totali indiretta da aggiungere alle intarget
        $count['per_campagnaOfftarget'] = array(); //dettaglio campagne offtarget

		if($target=='intarget'){
			$count_off	= $this->getCounter(1,$date1, $date2);
			$count_in 	= $this->getCounter(0,$date1, $date2);

			$count['totaleOfftarget'] 		= $count_off['totali'];
			$count['per_campagnaOfftarget'] = $count_off['per_campagna'];
			$count['totali'] 				= $count_in['totali'];
			$count['per_campagna'] 			= $count_in['per_campagna'];
			
			//$count['totali']+=$count_in['tot'] + $count_off['totali'];
		}else{
			$count = $this->getCounterIndirette($date1,$date2);
		}
        return $count;
    } 
}