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
use Exporter\Source\DoctrineORMQuerySourceIterator;

use DoctrineORMEntityManager;

use Symfony\Component\HttpFoundation\StreamedResponse;

use AppBundle\CustomFunc\Handler as Handler;




use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CpctocpaAdminController extends Controller
{
	
	
	
	// RENDER FUNCIONS 
	//render listato template
	 public function listaAction(Request $message = null){
		$sql = "SELECT * FROM cpc_to_cpa_crons order by attivo DESC";
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_crons =  $stmt->fetchAll();
		$cpctocpa=array();
		foreach($_crons as $_cron){
			$Idcron=$_cron['id'];
			$Nomecron=$_cron['slug_campagna'];
			$Cpccron=$_cron['cpc'];
			$Cpacron=$_cron['cpa_benchmark'];
			$Attivocron=$_cron['attivo'];
			$Cron=array(
				'id'		 => $Idcron,
				'nome'	 	=> $Nomecron,
				'cpc'	 	=> $Cpccron,
				'cpa'	 	=> $Cpacron,
				'attivo'	=> $Attivocron,
				);
			$cpctocpa[]=$Cron;
		}
			
		
		$messageType   	=$message->get('Messaggio');
		$IdMisc		=$message->get('IdMisc');
		if($messageType=='MisCrea'){
			$MessaggioHtml='<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> cron creato (id '.$IdMisc.')</h3>';
		}elseif($messageType=='MisMod'){
			$MessaggioHtml=$MessaggioHtml='<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Cron modificato (id '.$IdMisc.')</h3>';
		}
		if(isset($MessaggioHtml)){
			$message=array($MessaggioHtml,$messageType,$IdMisc);
		}else{
			$message=FALSE;
		}
		
		return $this->render('listato_cpctocpa.html.twig', array(
			'messaggio'		=> $message,
			'crons'				=> $cpctocpa,
        ), null); 
    }
	 
	 
	 // render pagina crea miscelata
	public function creaAction($message = null){
		$sql = "SELECT * FROM cpc_to_cpa_crons order by attivo DESC";
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_crons =  $stmt->fetchAll();
		$cpctocpa=array();
		foreach($_crons as $_cron){
			$Idcron=$_cron['id'];
			$Nomecron=$_cron['slug_campagna'];
			$Cpccron=$_cron['cpc'];
			$Cpacron=$_cron['cpa_benchmark'];
			$Attivocron=$_cron['attivo'];
			$Cron=array(
				'id'		 => $Idcron,
				'nome'	 	=> $Nomecron,
				'cpc'	 	=> $Cpccron,
				'cpa'	 	=> $Cpacron,
				'attivo'	=> $Attivocron,
				);
			$cpctocpa[]=$Cron;
		}
		$sql2 = 'SELECT id_campagna FROM redirect GROUP BY id_campagna';
		$em2 = $this->getDoctrine()->getManager('pixel_man');
		$stmt2 = $em2->getConnection()->prepare($sql2);
		$stmt2->execute();
		$_ids_campagne =  $stmt2->fetchAll();
		$idcampagne=array();
		foreach($_ids_campagne as $_ids_campagna){
			$idcampagne[]=$_ids_campagna['id_campagna'];
		}
		
		
		$questo=FALSE;
		return $this->render('crea_cpctocpa.html.twig', array(
			'questo'		=>$questo,
			'crons'				=> $cpctocpa,
			'idcampagne'	=> $idcampagne,
		), null); 
	}
	
	// render pagina di modifica ordine payout clienti
	public function modificaAction(Request $request, $message = null){
		$questo_id 			= $request->get('cpctocpa_id');
		$sql = "SELECT * FROM cpc_to_cpa_crons order by attivo DESC";
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_crons =  $stmt->fetchAll();
		$cpctocpa=array();
		$questo=FALSE;
		foreach($_crons as $_cron){
			$Idcron=$_cron['id'];
			$Nomecron=$_cron['slug_campagna'];
			$Cpccron=$_cron['cpc'];
			$Cpacron=$_cron['cpa_benchmark'];
			$Attivocron=$_cron['attivo'];
			$Cron=array(
				'id'		 => $Idcron,
				'nome'	 	=> $Nomecron,
				'cpc'	 	=> $Cpccron,
				'cpa'	 	=> $Cpacron,
				'attivo'	=> $Attivocron,
				);
			$cpctocpa[]=$Cron;
			if($Idcron==$questo_id){
				$questo=$Cron;
			}
		}
		$sql2 = 'SELECT id_campagna FROM redirect GROUP BY id_campagna';
		$em2 = $this->getDoctrine()->getManager('pixel_man');
		$stmt2 = $em2->getConnection()->prepare($sql2);
		$stmt2->execute();
		$_ids_campagne =  $stmt2->fetchAll();
		$idcampagne=array();
		foreach($_ids_campagne as $_ids_campagna){
			$idcampagne[]=$_ids_campagna['id_campagna'];
		}
		
		
		
		//pass miscelata to listato template
		
		return $this->render('crea_cpctocpa.html.twig', array(
			'crons'			=> $cpctocpa,
			'idcampagne'	=>$idcampagne,
			'questo'		=>$questo,
		), null); 
	}
	
	//SAVE FUNCTIONS
		public function salvaCpctocpaAction(Request $request){
		$nome		 			= $request->get('nome');
		$cpc					= str_replace(',', '.', $request->get('cpc'));
		$cpa					= str_replace(',', '.', $request->get('cpa'));
		$attiva					= $request->get('attiva');
		$croncount				= 0;
		$lastcron				= date("Y-m-d H:i:s");
		$insertQuery='INSERT INTO cpc_to_cpa_crons (slug_campagna, cpc, cpa_benchmark, attivo, cron_counter, last_cron_date) values ("'.$nome.'", '.$cpc.', '.$cpa.', '.$attiva	.', '.$croncount.', "'.$lastcron.'")';
		
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($insertQuery);
		$stmt->execute();
		$NewId=$em->getConnection()->lastInsertId();
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $NewId,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	
	public function editCpctocpaAction(Request $request){
		$id						= $request->get('id');
		$nome		 			= $request->get('nome');
		$cpc					= str_replace(',', '.', $request->get('cpc'));
		$cpa					= str_replace(',', '.', $request->get('cpa'));
		$attiva					= $request->get('attiva');
		
		
		$updateQuery='UPDATE cpc_to_cpa_crons SET slug_campagna="'.$nome.'", cpc='.$cpc.',cpa_benchmark='.$cpa.', attivo='.$attiva.' WHERE id= '.$id;
		
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($updateQuery);
		$stmt->execute();	
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $id,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}	
		
	public function set_stacco_color($cpa,$diff) {
		//+ o meno 10/ = yellow
		//+10% verde
		//-10% rosso
		$dieci=$cpa/10;
		if($diff>$dieci){
			return '#68dc6d';
		}elseif($diff < (-$dieci)){
			return '#e7796e';
		}else{
			return '#ffe970';
		}
	}
	public function get_true_media($mediaToCheck, $mediaArrayByMedia, $mediaArrayByFalsemedia, $mediaNotFounded){
		if(isset($mediaArrayByMedia[$mediaToCheck])){
			return  array($mediaToCheck,$mediaNotFounded);
		}elseif(isset($mediaArrayByFalsemedia[$mediaToCheck])){
			return array($mediaArrayByFalsemedia[$mediaToCheck]['media'],$mediaNotFounded);
		}else{
			$mediaNotFounded[]=$mediaToCheck;
			return array($mediaToCheck,$mediaNotFounded);
		}
		
	}

	public function getCpctocpaReportAction(Request $request){
		$slug						=$request->get('cpctocpa_slug');
		if($slug AND $slug!=""){
			//select slug del cron
			
			//capiamo in base al periodo l'intervallo da selezionare
			$presetdata				=$request->get('presetdata');
			$QueryDateRestrict=' ';
			$HtmlAlert='';
			if(!$presetdata){ $presetdata="completo";}
			$customstart='';
			$customend='';
			$QueryDateFrontend="completo";
			if($presetdata=='personale'){
				$customstart			=$request->get('start');
				$customend				=$request->get('end');
				if(!$customstart OR !$customend){$presetdata="completo";$HtmlAlert.='<p>La data selezionata non era corretta. risultati mostrati su tutte le date</p>';}else{
					$customstart=$customstart.' 00:00:00';
					$customend=$customend.' 23:59:59';
					$QueryDateRestrict='data_cron >="'.$customstart.'" AND data_cron <="'.$customend.'"';
					$QueryDateFrontend='dal <b>'.str_replace("00:00:00", "", $customstart).'</b> al <b>'.str_replace("23:59:59", "", $customend).'</b>';
				}
				
			}
			//se non abbiamo già stabilito una restrizione sulla query a livello di date procediamo analizzando i preset
			if($QueryDateRestrict==' ' AND $presetdata!='completo'){
				if($presetdata=='sette'){
					//ultimi sette giorni
					$QueryDateRestrict='date_add(data_cron, interval 7 day) >= NOW()';
					$QueryDateFrontend='Ultimi 7 giorni';
				}elseif($presetdata=='trenta'){
					//ultimi 30 giorni
					$QueryDateRestrict='date_add(data_cron, interval 30 day) >= NOW()';
					$QueryDateFrontend='Ultimi 30 giorni';
				}elseif($presetdata=='mese'){
					$QueryDateRestrict='MONTH(data_cron)=MONTH(NOW()) AND YEAR(data_cron)=YEAR(NOW())';
					$QueryDateFrontend='Questo mese';
				}elseif($presetdata=='mesepre'){
					$QueryDateRestrict='MONTH(data_cron)=MONTH(NOW())-1 AND YEAR(data_cron)=YEAR(NOW())';
					$QueryDateFrontend='Mese precedente';
				}
			}
			if($QueryDateRestrict!=' '){
				$QueryDateRestrict=' AND '.$QueryDateRestrict;
			}
			
			//recupero cron
			$sqlC = 'SELECT * FROM cpc_to_cpa_crons WHERE slug_campagna="'.$slug.'"';
			$emC = $this->getDoctrine()->getManager('pixel_man');
			$stmtC = $emC->getConnection()->prepare($sqlC);
			$stmtC->execute();
			$_crons =  $stmtC->fetchAll();
			$Cron=NULL;
			foreach($_crons as $_cron){
				$Idcron=$_cron['id'];
				$Nomecron=$_cron['slug_campagna'];
				$Cpccron=$_cron['cpc'];
				$Cpacron=$_cron['cpa_benchmark'];
				$Attivocron=$_cron['attivo'];
				$CronData=array(
					'id'		 => $Idcron,
					'nome'	 	=> $Nomecron,
					'cpc'	 	=> $Cpccron,
					'cpa_benchmark'	 	=> $Cpacron,
					'attivo'	=> $Attivocron,
					);
			}
			
			
			//creazione array mediaArrayByMedia e mediaArrayByFalsemedia
			$FornitoriQuery= "select media, falsemedia, nome from fornitori where media !='linkappeal_no_cookie'";
			$em3 = $this->getDoctrine()->getManager();
			$stmt3 = $em3->getConnection()->prepare($FornitoriQuery);
			$stmt3->execute();
			$rets =  $stmt3->fetchAll();
			$mediaArrayByMedia=array();
			$mediaArrayByFalsemedia=array();
			foreach($rets as $ret){
				$mediaArrayByMedia [$ret['media']]=array('falsemedia'=>$ret['falsemedia'], 'nome'=>$ret['nome']);
				$mediaArrayByFalsemedia [$ret['falsemedia']]=array('media'=>$ret['media'], 'nome'=>$ret['nome']);
			}
			
			
			$AffiliatiQuery= "select refid, nome from affiliati";
			$em4 = $this->getDoctrine()->getManager();
			$stmt4 = $em4->getConnection()->prepare($AffiliatiQuery);
			$stmt4->execute();
			$rets2 =  $stmt4->fetchAll();
			$affiliatearray=array();
			foreach($rets2 as $ret){
				$affiliatearray[$ret['refid']]=$ret['nome'];
			}
			
			$mediaNotFounded=array();
			//settaggio variabili statiche
			$writetable=0;
			$ReportHTML='';
			$id=26;
			
			//RECUPERO DEI DATI AGGREGATI PRECEDENTI
			//Recuperiamo idati aggregati dei precedenti run... prima per media, poi per affiliato
			$CamapagnaHTMLtable='<table style="border:1px solid #666; background-color:#f2f2f2;"><tr><th>Tipo</th><th>Nome</th><th>Click</th><th>Pixel</th><th>Rapporto <small>(pixel/click)</small></th><th>Costo totale</th><th>Costo per conversione</th><th>Delta cpa</th></tr>';
			$BeforeTable2='<p><b>Dati aggregati per il periodo</b>:  <i>"'.$QueryDateFrontend.'"</i></p>';
			$Alerttables="";
			$writetable2=0;
			$AggCampaignTotalPixels=0;
			$AggCampaignTotalClick=0;
			
			$AggregatedCampaignDataByMediaQuery='select fornitore, sum(click_count) as click, sum(pixel_count) as pixel from cpc_to_cpa_data where slug_campagna ="'.$slug.'" '.$QueryDateRestrict.' and fornitore is not null and fornitore !=""  group by fornitore';
			$em = $this->getDoctrine()->getManager('pixel_man');
			$stmt = $em->getConnection()->prepare($AggregatedCampaignDataByMediaQuery);
			$stmt->execute();
			$AggregatedCampaignDataByMedias =  $stmt->fetchAll();
			
			foreach($AggregatedCampaignDataByMedias as $AggregatedCampaignDataByMedia){
				$writetable++;
				$ThisTrueMediaData=$this->get_true_media($AggregatedCampaignDataByMedia["fornitore"], $mediaArrayByMedia, $mediaArrayByFalsemedia, $mediaNotFounded);
				$ThisTrueMedia=$ThisTrueMediaData[0];
				$mediaNotFounded=$ThisTrueMediaData[1];
				if(in_array($ThisTrueMedia, $mediaNotFounded)){
					$FornitoreName=$ThisTrueMedia.'*';
				}else{
					$FornitoreName=$ThisTrueMedia.' ('.$AggregatedCampaignDataByMedia["fornitore"].')';
				}
				
				//$FornitoreName=$mediaArrayByMedia[$AggregatedCampaignDataByMedia["fornitore"]]["nome"].' ('.$AggregatedCampaignDataByMedia["fornitore"].')';
						//add this to htmlOldTable + calc : rapporto pixel/click, costo, costo conversione , diff
						$AggregatedRapporto=$AggregatedCampaignDataByMedia['pixel']/$AggregatedCampaignDataByMedia['click']*100;
						$AggregatedCosto=$AggregatedCampaignDataByMedia['click']*$CronData['cpc'];
						if($AggregatedCampaignDataByMedia['pixel']>0){
							$AggregatedCostoConversione=$AggregatedCosto/$AggregatedCampaignDataByMedia['pixel'];
							$AggregatedStacco=$CronData['cpa_benchmark']-$AggregatedCostoConversione;
							
							$AggregatedCostoConversione=number_format($AggregatedCostoConversione,2,',','.')."&euro;";
							$conversionColor=$this->set_stacco_color($CronData['cpa_benchmark'],$AggregatedStacco);
							$AggregatedStacco='<td style="background-color:'.$conversionColor.'">'.number_format((-1)*($AggregatedStacco),2,',','.').'&euro;</td>';
						}else{
							$AggregatedCostoConversione="n.c.";
							$AggregatedStacco="<td>n.c.</td>";
						}
						
						//totals aggregated of campaign
						$AggCampaignTotalClick=$AggCampaignTotalClick+$AggregatedCampaignDataByMedia['click'];
						$AggCampaignTotalPixels=$AggCampaignTotalPixels+$AggregatedCampaignDataByMedia['pixel'];
						
						//add this to htmlOldTable + calc : rapporto pixel/click, costo, costo conversione , diff
						$CamapagnaHTMLtable.="<tr><td>Fornitore</td><td>".$FornitoreName."</td><td>".number_format($AggregatedCampaignDataByMedia['click'],0,',','.')."</td><td>".number_format($AggregatedCampaignDataByMedia['pixel'],0,',','.')."</td><td>".number_format($AggregatedRapporto,2,',','.')."%</td><td>".number_format($AggregatedCosto,2,',','.')."&euro;</td><td>".$AggregatedCostoConversione."</td>".$AggregatedStacco."</tr>";
			}
			
			
			
			
			$AggregatedCampaignDataByAffiliateQuery='select affiliato, sum(click_count) as click, sum(pixel_count) as pixel from cpc_to_cpa_data where slug_campagna ="'.$slug.'" '.$QueryDateRestrict.' and affiliato is not null and affiliato !=""  group by affiliato';
			$em2 = $this->getDoctrine()->getManager('pixel_man');
			$stmt2 = $em2->getConnection()->prepare($AggregatedCampaignDataByAffiliateQuery);
			$stmt2->execute();
			$AggregatedCampaignDataByAffiliates =  $stmt2->fetchAll();
			foreach($AggregatedCampaignDataByAffiliates as $AggregatedCampaignDataByAffiliate){
				$writetable++;
						if(isset($affiliatearray[$AggregatedCampaignDataByAffiliate["affiliato"]]) AND $affiliatearray[$AggregatedCampaignDataByAffiliate["affiliato"]]!=''){
							$AffiliatoName=$affiliatearray[$AggregatedCampaignDataByAffiliate["affiliato"]].' ('.$AggregatedCampaignDataByAffiliate["affiliato"].')';
						}else{
							$AffiliatoName=$AggregatedCampaignDataByAffiliate["affiliato"];
						}
						//add this to htmlOldTable + calc : rapporto pixel/click, costo, costo conversione , diff
						$AggregatedRapporto=$AggregatedCampaignDataByAffiliate['pixel']/$AggregatedCampaignDataByAffiliate['click']*100;
						$AggregatedCosto=$AggregatedCampaignDataByAffiliate['click']*$CronData['cpc'];
						if($AggregatedCampaignDataByAffiliate['pixel']>0){
							$AggregatedCostoConversione=$AggregatedCosto/$AggregatedCampaignDataByAffiliate['pixel'];
							$AggregatedStacco=$CronData['cpa_benchmark']-$AggregatedCostoConversione;
							
							$AggregatedCostoConversione=number_format($AggregatedCostoConversione,2,',','.')."&euro;";
							$conversionColor=$this->set_stacco_color($CronData['cpa_benchmark'],$AggregatedStacco);
							$AggregatedStacco='<td style="background-color:'.$conversionColor.'">'.number_format((-1)*($AggregatedStacco),2,',','.').'&euro;</td>';
						}else{
							$AggregatedCostoConversione="n.c.";
							$AggregatedStacco="<td>n.c.</td>";
						}
						
						//totals aggregated of campaign
						$AggCampaignTotalClick=$AggCampaignTotalClick+$AggregatedCampaignDataByAffiliate['click'];
						$AggCampaignTotalPixels=$AggCampaignTotalPixels+$AggregatedCampaignDataByAffiliate['pixel'];
						
						//add this to htmlOldTable + calc : rapporto pixel/click, costo, costo conversione , diff
						$CamapagnaHTMLtable.="<tr><td>Affiliato</td><td>".$AffiliatoName."</td><td>".number_format($AggregatedCampaignDataByAffiliate['click'],0,',','.')."</td><td>".number_format($AggregatedCampaignDataByAffiliate['pixel'],0,',','.')."</td><td>".number_format($AggregatedRapporto,2,',','.')."%</td><td>".number_format($AggregatedCosto,2,',','.')."&euro;</td><td>".$AggregatedCostoConversione."</td>".$AggregatedStacco."</tr>";
			}
			
			
			$CampagnaAggregatedHtml='<p><b>Nessun dato trovato per il periodo</b>: <i>"'.$QueryDateFrontend.'"</i></p>';
			if($writetable>0){
				//creiamo i totali aggregati del periodo per questa campagna
				$AggCampaignTotalRapporto=$AggCampaignTotalPixels/$AggCampaignTotalClick*100;
				$AggCampaignTotalCosto=$AggCampaignTotalClick*$CronData['cpc'];
				if($AggCampaignTotalPixels>0){
					$AggCampaignTotalCostoConversione=$AggCampaignTotalCosto/$AggCampaignTotalPixels;
					$AggCampaignTotalStacco=$CronData['cpa_benchmark']-$AggCampaignTotalCostoConversione;
					
					$AggCampaignTotalCostoConversione=number_format($AggCampaignTotalCostoConversione,2,',','.')."&euro;";
					$conversionColor=$this->set_stacco_color($CronData['cpa_benchmark'],$AggCampaignTotalStacco);
					$AggCampaignTotalStacco='<td style="background-color:'.$conversionColor.'"><b>'.number_format((-1)*($AggCampaignTotalStacco),2,',','.').'&euro;</b></td>';
				}else{
					$AggCampaignTotalCostoConversione="n.c.";
					$AggCampaignTotalStacco="<td>n.c.</td>";
				}
				
			
				$CamapagnaHTMLtable.="<tr><td><b>Totali</b></td><td><b>Tutti</b></td><td><b>".number_format($AggCampaignTotalClick,0,',','.')."</b></td><td><b>".number_format($AggCampaignTotalPixels,0,',','.')."</b></td><td><b>".number_format($AggCampaignTotalRapporto,2,',','.')."%</b></td><td><b>".number_format($AggCampaignTotalCosto,2,',','.')."&euro;</b></td><td><b>".$AggCampaignTotalCostoConversione."</b></td>".$AggCampaignTotalStacco."</tr>";
				
				$CamapagnaHTMLtable.='</table>';
				
				$CampagnaAggregatedHtml=$BeforeTable2;
				$CampagnaAggregatedHtml.=$CamapagnaHTMLtable;
			}
			$CampagnaAggregatedHtml.=$Alerttables;
				
			$ReportHTML=$CampagnaAggregatedHtml;
			
			//restituiamo anche totali count(*)
			//restituiamo anche fredde totali per calcolo percentuale fredde
			$ajax						=$request->get('ajax');
			if($ajax AND $ajax>0){
				$response = new Response();
				//$response->setContent(json_encode($ReportHTML));
				$response->setContent(json_encode(array(
										'report'	=> $ReportHTML,
											)
										));
				$response->headers->set('Content-Type', 'application/json');
				return $response;
			}else{
				return $ReportHTML;
			}
			
			/*$response = new Response();
			$response->setContent(json_encode($ReportHTML));
			$response->headers->set('Content-Type', 'application/json');*/
			
		}else{
			return '<p>Si &egrave; verificato un errore. Prego riprovare</p>';
		}
	}
		
	
	
	
	
	
	public function reportCpctocpaAction(Request $request){
		$slug						=$request->get('cpctocpa_slug');
		//$ReportHTML=$this->get_miscelata_report($request);
		$ReportHTML=$this->getCpctocpaReportAction($request);
		
		$sql = 'SELECT * FROM cpc_to_cpa_crons WHERE slug_campagna="'.$slug.'"';
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_crons =  $stmt->fetchAll();
		$Cron=NULL;
		foreach($_crons as $_cron){
			$Idcron=$_cron['id'];
			$Nomecron=$_cron['slug_campagna'];
			$Cpccron=$_cron['cpc'];
			$Cpacron=$_cron['cpa_benchmark'];
			$Attivocron=$_cron['attivo'];
			$Cron=array(
				'id'		 => $Idcron,
				'nome'	 	=> $Nomecron,
				'cpc'	 	=> $Cpccron,
				'cpa'	 	=> $Cpacron,
				'attivo'	=> $Attivocron,
				);
		}
	
		
		return $this->render('report_cpctocpa.html.twig', array(
			'cron'		=> $Cron,
			'report'		=>$ReportHTML,
		), null); 
	}
		
		
		
		
		
		
		
		
	
	
	//funzione che per ogni campagna (attiva?) recupera nomecampagna, db, table, campagna id
	
	
	
	
	
	
	
	// FINE RENDER FUNCTIONS -------------------------------->

	
	public function deleteCpctocpaAction(Request $request){
		$id 	= $request->get('id');
		$target = '0'; 
		$result = false;
		if(!empty($id)){
			// step 1 - eliminazione ordine 
				
			$em = $this->getDoctrine()->getManager('pixel_man');
			$sql_base 	= "DELETE FROM cpc_to_cpa_crons WHERE id=?";
			$stmt 		= $em->getConnection()->prepare($sql_base);
			if($stmt->execute(array($id))){
				$result = true;
				
			}
		}
		$response = new Response();
		$response->setContent(json_encode(array('eliminato' => $result)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
}