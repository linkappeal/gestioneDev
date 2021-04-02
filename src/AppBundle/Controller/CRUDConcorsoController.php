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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CRUDConcorsoController extends Controller
{
    
    
	 public function conteggiAction($message = null){
		$customers = $this->getAllCustomers();
		$concorsi = $this->getAllConcorsi();
		return $this->render('list_concorso_conteggi.html.twig', array(
           'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
           'customers' 		=> $customers,
           'concorsi' 		=> $concorsi,
           'message' 		=> $message,
        ), null); 
    }
   
	public function getConteggioConcorsoAction(Request $request){
		setlocale(LC_TIME, 'ita', 'it_IT');
		$codice_cnc = $request->get('codice_cnc');
		$html_menu = '';
		$html_cont = '';
		
		$totaliCliente = $this->getConcorsoTotal($codice_cnc);
		
		$annoMesi = array();
		$annoMesi = $this->getConcorsoMesiAnni($codice_cnc);
		
		$hmtl_cont_left = '';
		foreach($annoMesi as $anno => $mesi){
			$totale_anno = 0;
			$espanso ='';
		//	if($anno == date('Y')){ $espanso = 'aria-expanded="true"';}
			//menu
			$totale_anno = $this->getConcorsoAnnoTotale($codice_cnc,$anno);

				$html_menu .= '<div class="panel-heading">';
				$html_menu .= '<h4 class="panel-title">';
				$html_menu .= '<a data-toggle="collapse" '. $espanso.' href="#'.$anno.'"><strong>'.$anno.'</strong><span class="totali_anno">'.$totale_anno.'<i class="fa fa-users" aria-hidden="true"></i></span></a>';
				$html_menu .= '</h4>';
				$html_menu .= '</div>';
				$html_menu .='<div id="'.$anno.'" class="panel-collapse collapse in" '.$espanso.'>';
				$html_menu .='<ul class="list-group">';

						 
				// itero i giorni del mese selezionato
				foreach($mesi as $mese){
					$giorni_in_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
					$nomeMese = ucfirst(strftime('%B', strtotime($anno.'-'.$mese.'-01')));
					$giorni_nel_mese = range(1,$giorni_in_mese);
					
					$totaleMese 		= $this->getConcorsoMeseTotale($codice_cnc,$mese,$anno); // totale del mese analizzato
					
					$html_menu .='<li class=" submenu-link list-group-item" onclick="loadRightContent(\''.$anno .'_'.$nomeMese.'\',\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\')">
					<i class="fa fa-calendar" aria-hidden="true"></i> '.$nomeMese.'<span class="totale_mese">'.$totaleMese.'<i class="fa fa-users" aria-hidden="true"></i></span></li>';
					
					//$media_del_mese 	= $this->getConcorsoMediaFromMese($codice_cnc, $mese, $anno); // tutti i media del mese in array media=>totale 
					
					if($mese == date('m')){ 
						$activetab = 'active';
						$click_giorni = '';
						foreach($giorni_nel_mese as $g){
							$click_giorni .= '<span class="giornini_clicks" onclick="toggleGiorno(\'day_'.$g.'_'.$mese.'_'.$anno.'\')">'.$g.'</span>';
						}
						$click_giorni .= '<span class="giornini_clicks_tutti" onclick="toggleTuttiGiorni(\''.$mese.'\',\''.$anno.'\')">Tutti</span>';
						

						$hmtl_cont_left .= '<div id="'.$anno .'_'.$nomeMese.'" class="right-panel-content fade in '.$activetab.'" data-fill="1">';
						$hmtl_cont_left .= '<div class="col-md-12">';
						$hmtl_cont_left .= '<div class="title-subtab col-md-12">
											<div class="header-nomeMese">
												<i class="fa fa-calendar" aria-hidden="true"></i>'.$nomeMese.' ' . $anno . '
											</div>
											<div class="header-giornini"><span class="giornini giorni_'.$mese.'_'.$anno.'">'.$click_giorni.'</span></div>
											<div class="header-totali">
												<span class="totals">
													<i class="fa fa-users" aria-hidden="true"></i> Totali: <strong>'. $totaleMese . '</strong>
												</span>
											</div>
									  </div>';
						
						// blocco conteggio aggregato mese
						$hmtl_cont_left .= '<div class="conteggi-mese col-md-12">';
						$hmtl_cont_left .= '<div class="col-md-12 header-box-mese"><h4><i class="fa fa-globe" aria-hidden="true"></i> Aggregato del mese</h4></div>';

						
						$mese_media = $this->getConcorsoDayMediaByGiorno($codice_cnc,$mese,$anno);
						
						$hmtl_cont_left .='<div class="col-md-2">
										<div class="col-md-12 btns-show-left-tabs text-center tab_click_'.$mese.'_'.$anno.'_m tbl_active">
											<span onclick="showContMeseTab(\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\',\'m\')">
											<i class="fa fa-external-link" aria-hidden="true"></i> Media</span>
										</div>
										<div class="col-md-12 btns-show-left-tabs text-center tab_click_'.$mese.'_'.$anno.'_c tbl_deactive">
											<span onclick="showContMeseTab(\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\',\'c\')">
											<i class="fa fa-user-circle" aria-hidden="true"></i> Clienti</span>
										</div>
									</div>';
						
						$grandtotale_m_media = 0;
						$hmtl_cont_left .='<div class="col-md-10"><!-- blocco mese destro -->';
						$hmtl_cont_left .='<div class="row"><div class="col-md-12 tab_'.$mese.'_'.$anno.'_m tab-show-left-tabs" data-fill="1">';	
						foreach($mese_media as $m_media => $totale_m_media){
							$__media 	= $m_media;
							if(empty(trim($__media))){
								$__media = 'non presente';
							}
							$hmtl_cont_left .= '<div class="col-md-12 submedia-box">
													<div class="col-md-9">' . $__media . '</div>
													<div class="col-md-3 totali_media">
														<strong> ' . $totale_m_media . '</strong>
													</div>
												</div>';
							$grandtotale_m_media += $totale_m_media;
						}
						$hmtl_cont_left .='</div><!-- /tab-show-left-tabs -->';
						$hmtl_cont_left .= '<div class="col-md-12 tab_'.$mese.'_'.$anno.'_c tab-show-left-tabs"></div>';
						$hmtl_cont_left .= '</div>';
						$hmtl_cont_left .= '	<div class="footer-mese-box col-md-12 last-row-totale-gen">
											<div class="col-md-9"><strong>Totali generate</strong></div>
											<div class="col-md-3 totali_media">
												<strong> ' . $grandtotale_m_media . '</strong>
											</div>
										</div>';
						$hmtl_cont_left .='</div><!-- /blocco mese destro -->';
						$hmtl_cont_left .='</div><!-- /conteggi-mese -->';
								  
						
							$html_cont_left_days = '';
							for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
								// header box giorno
								$html_cont_left_days .='<div id="day_'.$giorno.'_'.$mese.'_'.$anno.'" class="col-md-3 col-xs-6 col-sm-6 day-box day-box_'.$mese.'_'.$anno.'">
												<h4>
													<i class="fa fa-calendar" aria-hidden="true"></i>Giorno: <strong>'.$giorno.'</strong>
												</h4>';
								// pulsanti header switch clienti e media
								$html_cont_left_days .='<div class="">
												<div class="col-md-6 btns-show-tabs text-center tab_click_'.$giorno.'_'.$mese.'_'.$anno.'_m tb_active">
													<span onclick="showContDayTab(\''.$codice_cnc.'\',\''.$giorno.'\',\''.$mese.'\',\''.$anno.'\',\'m\')">
													<i class="fa fa-external-link" aria-hidden="true"></i> Media</span>
												</div>
												<div class="col-md-6 btns-show-tabs text-center tab_click_'.$giorno.'_'.$mese.'_'.$anno.'_c tb_deactive">
													<span onclick="showContDayTab(\''.$codice_cnc.'\',\''.$giorno.'\',\''.$mese.'\',\''.$anno.'\',\'c\')">
													<i class="fa fa-user-circle" aria-hidden="true"></i> Clienti</span>
												</div>
											</div>';
								
								$giorni_media = $this->getConcorsoDayMediaByGiorno($codice_cnc, $mese,$anno,$giorno); // array di tutti i giorni e i media giorno => array('mdia1' => totale)
								$totale_giorno = 0;
								$html_days_cont = '';
								
								foreach($giorni_media as $media => $totale_media){
									$class_row 	= '';
									$_media 	= $media;
									$onclick = 'onclick="getMediaInfo(\''.$_media.'\',\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\')"';
									if(empty(trim($_media))){
										$_media = 'non presente';
									}
									$html_days_cont .= '<div class="col-md-12 submedia-box ' . $class_row . '" '.$onclick.'">
															<div class="col-md-9">' . $_media . '</div>
															<div class="col-md-3 totali_media">
																<strong> ' . $totale_media . '</strong>
															</div>
														</div>';
									$totale_giorno += $totale_media;

								}
								
								$html_cont_left_days .='<div class="row"><div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_m tab-show-tabs" data-fill="1">';	
								$html_cont_left_days .= $html_days_cont;
								$html_cont_left_days .= '</div>';
								$html_cont_left_days .= '<div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_c tab-show-tabs"></div>';
								$html_cont_left_days .= '</div>';
								$html_cont_left_days .= '<div class="footer-day-box col-md-12 last-row-totale-gen">
												<div class="col-md-9"><strong>Totali generate</strong></div>
												<div class="col-md-3 totali_media">
													<strong>' . $totale_giorno . '</strong>
												</div>';
								$html_cont_left_days .= '</div><!-- /footer-day-box-->';	
								$html_cont_left_days .= '</div><!-- /day-box-->';	

							} // fine for giorni
						
								
						
						$hmtl_cont_left .= $html_cont_left_days;
						$hmtl_cont_left .= '</div><!-- /col-md-4 -->';
						$hmtl_cont_left .= '</div>';
					}else{
						// creazione del div vuoto per il contenuto
						$hmtl_cont_left .= '<div id="'.$anno .'_'.$nomeMese.'" class="right-panel-content fade in" ></div>';
					}
				} // fine foreach mese
				
				$html_menu .='</ul>';
				$html_menu .='</div>';
				$html_menu .='</div>';
				$html_menu .='</div>';

		}
		

		$response = new Response();
		$response->setContent(json_encode(array('totali_cliente' => $totaliCliente, 'menu'=> $html_menu, 'contenuto' => $hmtl_cont_left)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	public function getMeseRightContentAction(Request $request){
		setlocale(LC_TIME, 'ita', 'it_IT');
		$codice_cnc = $request->get('codice_cnc');
		$mese = $request->get('mese');
		$anno = $request->get('anno');
		$html_cont = '';
		
		$hmtl_cont_left = '';
		// itero i giorni del mese selezionato

		$giorni_in_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
		$nomeMese = ucfirst(strftime('%B', strtotime('2017-'.$mese.'-01')));
		$giorni_nel_mese = range(1,$giorni_in_mese);
		
		$totaleMese = $this->getConcorsoMeseTotale($codice_cnc,$mese,$anno); // totale del mese analizzato
		$activetab = 'active';
		$click_giorni = '';
		foreach($giorni_nel_mese as $g){
			$click_giorni .= '<span class="giornini_clicks" onclick="toggleGiorno(\'day_'.$g.'_'.$mese.'_'.$anno.'\')">'.$g.'</span>';
		}
		$click_giorni .= '<span class="giornini_clicks_tutti" onclick="toggleTuttiGiorni(\''.$mese.'\',\''.$anno.'\')">Tutti</span>';
        
		$hmtl_cont_left .= '<div class="col-md-12">';
		$hmtl_cont_left .= '<div class="title-subtab col-md-12">
								<div class="header-nomeMese">
									<i class="fa fa-calendar" aria-hidden="true"></i>'.$nomeMese.' ' . $anno . '
								</div>
								<div class="header-giornini"><span class="giornini giorni_'.$mese.'_'.$anno.'">'.$click_giorni.'</span></div>
								<div class="header-totali">
									<span class="totals">
										<i class="fa fa-users" aria-hidden="true"></i> Totali: <strong>'. $totaleMese . '</strong>
									</span>
								</div>
						  </div>';
					
		// blocco conteggio aggregato mese
		$hmtl_cont_left .= '<div class="conteggi-mese col-md-12">';
		$hmtl_cont_left .= '<div class="col-md-12 header-box-mese"><h4><i class="fa fa-globe" aria-hidden="true"></i> Aggregato del mese</h4></div>';

		
		$mese_media = $this->getConcorsoDayMediaByGiorno($codice_cnc,$mese,$anno);
					
		$hmtl_cont_left .='<div class="col-md-2">
						<div class="col-md-12 btns-show-left-tabs text-center tab_click_'.$mese.'_'.$anno.'_m tbl_active">
							<span onclick="showContMeseTab(\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\',\'m\')">
							<i class="fa fa-external-link" aria-hidden="true"></i> Media</span>
						</div>
						<div class="col-md-12 btns-show-left-tabs text-center tab_click_'.$mese.'_'.$anno.'_c tbl_deactive">
							<span onclick="showContMeseTab(\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\',\'c\')">
							<i class="fa fa-user-circle" aria-hidden="true"></i> Clienti</span>
						</div>
					</div>';
					
		$grandtotale_m_media = 0;
		$hmtl_cont_left .='<div class="col-md-10"><!-- blocco mese destro -->';
		$hmtl_cont_left .='<div class="row"><div class="col-md-12 tab_'.$mese.'_'.$anno.'_m tab-show-left-tabs" data-fill="1">';	
		foreach($mese_media as $m_media => $totale_m_media){
			$__media 	= $m_media;
			if(empty(trim($__media))){
				$__media = 'non presente';
			}
			$hmtl_cont_left .= '<div class="col-md-12 submedia-box">
									<div class="col-md-9">' . $__media . '</div>
									<div class="col-md-3 totali_media">
										<strong>&nbsp;' . $totale_m_media . '</strong>
									</div>
								</div>';
			$grandtotale_m_media += $totale_m_media;
		}
		$hmtl_cont_left .='</div><!-- /tab-show-left-tabs -->';
		$hmtl_cont_left .= '<div class="col-md-12 tab_'.$mese.'_'.$anno.'_c tab-show-left-tabs"></div>';
		$hmtl_cont_left .= '</div>';
		$hmtl_cont_left .= '	<div class="footer-mese-box col-md-12 last-row-totale-gen">
							<div class="col-md-9"><strong>Totali generate</strong></div>
							<div class="col-md-3 totali_media">
								<strong>' . $grandtotale_m_media . '</strong>
							</div>
						</div>';
		$hmtl_cont_left .='</div><!-- /blocco mese destro -->';
		$hmtl_cont_left .='</div><!-- /conteggi-mese -->';
				  
					
		$html_cont_left_days = '';
		for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
			// header box giorno
			$html_cont_left_days .='<div id="day_'.$giorno.'_'.$mese.'_'.$anno.'" class="col-md-3 col-xs-6 col-sm-6 day-box day-box_'.$mese.'_'.$anno.'">
							<h4>
								<i class="fa fa-calendar" aria-hidden="true"></i>Giorno: <strong>'.$giorno.'</strong>
							</h4>';
			// pulsanti header switch clienti e media
			$html_cont_left_days .='<div class="">
							<div class="col-md-6 btns-show-tabs text-center tab_click_'.$giorno.'_'.$mese.'_'.$anno.'_m tb_active">
								<span onclick="showContDayTab(\''.$codice_cnc.'\',\''.$giorno.'\',\''.$mese.'\',\''.$anno.'\',\'m\')">
								<i class="fa fa-external-link" aria-hidden="true"></i> Media</span>
							</div>
							<div class="col-md-6 btns-show-tabs text-center tab_click_'.$giorno.'_'.$mese.'_'.$anno.'_c tb_deactive">
								<span onclick="showContDayTab(\''.$codice_cnc.'\',\''.$giorno.'\',\''.$mese.'\',\''.$anno.'\',\'c\')">
								<i class="fa fa-user-circle" aria-hidden="true"></i> Clienti</span>
							</div>
						</div>';
			
			$giorni_media = $this->getConcorsoDayMediaByGiorno($codice_cnc, $mese,$anno,$giorno); // array di tutti i giorni e i media giorno => array('mdia1' => totale)
			$totale_giorno = 0;
			$html_days_cont = '';
			
			foreach($giorni_media as $media => $totale_media){
				$class_row 	= '';
				$_media 	= $media;
				$onclick = 'onclick="getMediaInfo(\''.$_media.'\',\''.$codice_cnc.'\',\''.$mese.'\',\''.$anno.'\')"';
				if(empty(trim($_media))){
					$_media = 'non presente';
				}
				$html_days_cont .= '<div class="col-md-12 submedia-box ' . $class_row . '" '.$onclick.'">
										<div class="col-md-9">' . $_media . '</div>
										<div class="col-md-3 totali_media">
											<strong> ' . $totale_media . '</strong>
										</div>
									</div>';
				$totale_giorno += $totale_media;

			}
			
			$html_cont_left_days .='<div class="row"><div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_m tab-show-tabs" data-fill="1">';	
			$html_cont_left_days .= $html_days_cont;
			$html_cont_left_days .= '</div>';
			$html_cont_left_days .= '<div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_c tab-show-tabs"></div>';
			$html_cont_left_days .= '</div>';
			$html_cont_left_days .= '<div class="footer-day-box col-md-12 last-row-totale-gen">
							<div class="col-md-9"><strong>Totali generate</strong></div>
							<div class="col-md-3 totali_media">
								<strong>' . $totale_giorno . '</strong>
							</div>';
			$html_cont_left_days .= '</div><!-- /footer-day-box-->';	
			$html_cont_left_days .= '</div><!-- /day-box-->';	

		} // fine for giorni
					
							
					
		$hmtl_cont_left .= $html_cont_left_days;
		$hmtl_cont_left .= '</div><!-- /col-md-4 -->';

		$response = new Response();
		$response->setContent(json_encode(array('contenuto' => $hmtl_cont_left)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	private function getAllConcorsi(){
		try{
			$sql_concorso = "select * from concorso";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_concorso);
			$stmt->execute();
			$concorsi =  $stmt->fetchAll();
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $concorsi; 
	}
	

	
	private function getConcorsoDayMediaByMese($codice_cnc,$numeroMese,$anno){
		try{
			$array_values= array();
			$giorni_media = array();
			setlocale(LC_TIME, 'ita', 'it_IT');
			$data_return = array();
			
			$giorni_in_mese = cal_days_in_month(CAL_GREGORIAN, $numeroMese, $anno);
			
			for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
				// ciclo query
				$sql_medias = "select count(*) as tot_media, media from lead l where";
				if(!empty($codice_cnc)){
					$sql_medias .= "  l.codice_premio = :codice_cnc and";
					$array_values['codice_cnc'] = $codice_cnc;
				}
				$sql_medias .= " month(data) = :numeroMese
									and year(data) = :anno
									and day(data) = :giorno_i
									group by media";
			
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;
			$array_values['giorno_i'] 	=  	$giorno;

			$stmt->execute($array_values);
			
			$grandTotal = 0;
			//$monthName = ucfirst(strftime('%B', strtotime('2017-'.$numeroMese.'-01')));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$media 		= $row['media'];
					$tot_media	= $row['tot_media'];
					$giorni_media[$giorno][$media] = $tot_media; 
				}
		
			} // fine if trovati risultati
		} // fine for giorni
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $giorni_media; 
	}
	
	private function getConcorsoDayMediaByGiorno($codice_cnc,$numeroMese,$anno,$giorno = ''){
		try{
			$array_values= array();
			$giorni_media = array();
			
			// ciclo query
			$sql_medias = "select count(*) as tot_media, media from lead l where";
			if(!empty($codice_cnc)){
				$sql_medias .= "  l.codice_premio = :codice_cnc and ";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_medias .= " month(data) = :numeroMese
								and year(data) = :anno ";
			
			if(!empty($giorno)){
				$sql_medias .= "  and day(data) = :giorno_i ";
				$array_values['giorno_i'] =	$giorno;
			}
			// modifica del 05-01-18: i fornitori tracciano solo se tutte le roivacy partener sono flaggate.
			//$sql_medias .= "  and l.privacy_partner !='' group by media";
            $sql_medias .= " group by media";
			
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= 	$anno;

			$stmt->execute($array_values);
			
			$grandTotalMedia = 0;
			//$monthName = ucfirst(strftime('%B', strtotime('2017-'.$numeroMese.'-01')));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$media 		= $row['media'];
					$tot_media	= $row['tot_media'];
					$giorni_media[$media] = $tot_media; 
					$grandTotalMedia += $tot_media;
				}
		
			} // fine if trovati risultati
			//$giorni_media['Totale'] = $grandTotalMedia;
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $giorni_media; 
	}
	
	private function getConcorsoDayClientiByMese($codice_cnc,$numeroMese,$anno){
		try{
			$array_values= array();
			$giorni_clienti = array();
			setlocale(LC_TIME, 'ita', 'it_IT');
			
			$giorni_in_mese = cal_days_in_month(CAL_GREGORIAN, $numeroMese, $anno);
			
			for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
				// ciclo query
				$sql_medias = "select count(*) as tot, cl.cliente_id as cliente_id from lead l 
								inner join clienti_lead as cl on cl.lead_id = l.id 
								where";
				if(!empty($codice_cnc)){
					$sql_medias .= "  l.codice_premio = :codice_cnc and";
					$array_values['codice_cnc'] = $codice_cnc;
				}
				$sql_medias .= " month(data) = :numeroMese
									and year(data) = :anno
									and day(data) = :giorno_i
									and cl.esterna=0
									group by cl.cliente_id";
			
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;
			$array_values['giorno_i'] 	=  	$giorno;

			$stmt->execute($array_values);
			
			$grandTotal = 0;
			//$monthName = ucfirst(strftime('%B', strtotime('2017-'.$numeroMese.'-01')));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$cliente_id 	= $row['cliente_id'];
					$tot_media	= $row['tot'];
					$giorni_clienti[$giorno][$cliente_id] = $tot_media; 
				}
		
			}else{
				$giorni_clienti[$giorno]['0'] = 0; 
			}// fine if trovati risultati
		} // fine for giorni
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $giorni_clienti; 
	}
	
	public function getClientiConcorsoByGiornoAction(Request $request){
		try{
			$codice_cnc 	= $request->get('codice_cnc');
			$numeroMese 	= $request->get('mese');
			$anno       	= $request->get('anno');
			$giorno     	= $request->get('giorno');
			$array_values	= array();
			$html_days_cont = '';
			
			//query su esterne
			// ciclo query
			$sql_medias = "select count(*) as tot, cl.cliente_id as cliente_id from lead_esterne l 
							inner join clienti_lead as cl on cl.lead_id = l.id 
							where";
			if(!empty($codice_cnc)){
				$sql_medias .= "  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_medias .= " month(data) = :numeroMese
								and year(data) = :anno
								and day(data) = :giorno_i
								and cl.esterna=1
								group by cl.cliente_id";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;
			$array_values['giorno_i'] 	=  	$giorno;

			$stmt->execute($array_values);
			$ClientiArray=array();
			$ClientiLeadEsterneArray=array();
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					if(!in_array($row['cliente_id'],$ClientiArray)){$ClientiArray[]=$row['cliente_id'];}
					$ClientiLeadEsterneArray[$row['cliente_id']]=$row['tot'];
					
				}
			}
			
			
			
			// ciclo query
			$sql_medias = "select count(*) as tot, cl.cliente_id as cliente_id from lead l 
							inner join clienti_lead as cl on cl.lead_id = l.id 
							where";
			if(!empty($codice_cnc)){
				$sql_medias .= "  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_medias .= " month(data) = :numeroMese
								and year(data) = :anno
								and day(data) = :giorno_i
								and cl.esterna=0
								group by cl.cliente_id";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;
			$array_values['giorno_i'] 	=  	$giorno;

			$stmt->execute($array_values);
			$ClientiLeadInterneArray=array();
			
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					if(!in_array($row['cliente_id'],$ClientiArray)){$ClientiArray[]=$row['cliente_id'];}
					
					$ClientiLeadInterneArray[$row['cliente_id']]=$row['tot'];
				}
			}
			
			$totale_clienti_int = 0;
			$totale_clienti_est = 0;
			
				foreach($ClientiArray as $cliente_id) {
					if(isset($ClientiLeadInterneArray[$cliente_id])){
						$totInterne	= $ClientiLeadInterneArray[$cliente_id];
					}else{
						$totInterne	=0;
					}
					if(isset($ClientiLeadEsterneArray[$cliente_id])){
						$totEsterne	= $ClientiLeadEsterneArray[$cliente_id];
					}else{
						$totEsterne	=0;
					}
					
					$cliente_nome = $this->getClienteName($cliente_id);
					
					$html_days_cont .= '<div class="col-md-12 submedia-box">
											<div class="col-md-7">
												' . $cliente_nome . '
											</div>
											<div class="col-md-3 totali_media">' . $totInterne . '/'.$totEsterne.'</div>
											<div class="col-md-2 totali_media"> <strong>'.($totInterne+$totEsterne).'</strong></div>
										</div>';
					$totale_clienti_int += $totInterne;
					$totale_clienti_est += $totEsterne;
				}
				
				if($html_days_cont!=''){
					$html_days_cont = '<div class="col-md-12 submedia-box">
											<div class="col-md-7"><b>Cliente</b>
											</div>
											<div class="col-md-3 totali_media"><b>Int/Est</b></div>
											<div class="col-md-2 totali_media"><b>Tot</b></div>
										</div>'.$html_days_cont;
					
				}
			$html_days_cont .= '<div class="col-md-12 submedia-box last-row-totale">
									<div class="col-md-7">
										<strong>Totale clienti</strong>
									</div>
									<div class="col-md-3 totali_media">
										<strong>' . $totale_clienti_int . '/'.$totale_clienti_est.'</strong>
									</div>
									<div class="col-md-2 totali_media"> <strong>'.($totale_clienti_int+$totale_clienti_est).'</strong></div>
								</div>';
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		$response = new Response();
		$response->setContent(json_encode(array('contenuto' => $html_days_cont)));
		$response->headers->set('Content-Type', 'application/json');
		return $response; 
	}
	
	public function getClientiConcorsoByMeseAction(Request $request){
		try{
			$codice_cnc 	= $request->get('codice_cnc');
			$numeroMese 	= $request->get('mese');
			$anno       	= $request->get('anno');
			$array_values	= array();
			$html_days_cont = '';
			//query leads esterne
			$sql_medias = "select count(*) as tot, cl.cliente_id as cliente_id from lead_esterne l 
							inner join clienti_lead as cl on cl.lead_id = l.id 
							where";
			if(!empty($codice_cnc)){
				$sql_medias .= "  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_medias .= " month(data) = :numeroMese
								and year(data) = :anno
								and cl.esterna=1
								group by cl.cliente_id";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;
			
			$ClientiArray=array();
			$ClientiLeadEsterneArray=array();
			
			$stmt->execute($array_values);
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					if(!in_array($row['cliente_id'],$ClientiArray)){$ClientiArray[]=$row['cliente_id'];}
					$ClientiLeadEsterneArray[$row['cliente_id']]=$row['tot'];
				}
			}
			
			
			// leads interne
			$sql_medias = "select count(*) as tot, cl.cliente_id as cliente_id from lead l 
							inner join clienti_lead as cl on cl.lead_id = l.id 
							where";
			if(!empty($codice_cnc)){
				$sql_medias .= "  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_medias .= " month(data) = :numeroMese
								and year(data) = :anno
								and cl.esterna=0
								group by cl.cliente_id";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= $anno;

			$stmt->execute($array_values);
			$totale_clienti = 0;
			$ClientiLeadInterneArray=array();
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					if(!in_array($row['cliente_id'],$ClientiArray)){$ClientiArray[]=$row['cliente_id'];}
					$ClientiLeadInterneArray[$row['cliente_id']]=$row['tot'];
					
				}
			}
			
			
			
			foreach($ClientiArray as $cliente_id) {
					if(isset($ClientiLeadInterneArray[$cliente_id])){
						$totInterne	= $ClientiLeadInterneArray[$cliente_id];
					}else{
						$totInterne	=0;
					}
					if(isset($ClientiLeadEsterneArray[$cliente_id])){
						$totEsterne	= $ClientiLeadEsterneArray[$cliente_id];
					}else{
						$totEsterne	=0;
					}
					$cliente_nome = $this->getClienteName($cliente_id);
					$html_days_cont .= '<div class="col-md-12 submedia-box">
											<div class="col-md-7">
												' . $cliente_nome . '
											</div>
											<div class="col-md-3 totali_media">' . $totInterne . '/'.$totEsterne.'</div>
											<div class="col-md-2 totali_media"> <strong>'.($totInterne+$totEsterne).'</strong></div>
										</div>';
					$totale_clienti_int += $totInterne;
					$totale_clienti_est += $totEsterne;
				}
				
			if($html_days_cont!=''){
					$html_days_cont = '<div class="col-md-12 submedia-box">
											<div class="col-md-7"><b>Cliente</b>
											</div>
											<div class="col-md-3 totali_media"><b>Int/Est</b></div>
											<div class="col-md-2 totali_media"><b>Tot</b></div>
										</div>'.$html_days_cont;
					
				}
			$html_days_cont .= '<div class="col-md-12 submedia-box last-row-totale">
									<div class="col-md-7">
										<strong>Totale clienti</strong>
									</div>
									<div class="col-md-3 totali_media">
										<strong>' . $totale_clienti_int . '/'.$totale_clienti_est.'</strong>
									</div>
									<div class="col-md-2 totali_media"> <strong>'.($totale_clienti_int+$totale_clienti_est).'</strong></div>
								</div>';
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		$response = new Response();
		$response->setContent(json_encode(array('contenuto' => $html_days_cont)));
		$response->headers->set('Content-Type', 'application/json');
		return $response; 
	}
	
	private function getClienteName($cliente_id){
		try{
			$ragione_sociale = '';
			$sql_find_mesi ="select ragione_sociale from clienti c where id = ?";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute(array($cliente_id));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$ragione_sociale = $row['ragione_sociale'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $ragione_sociale; 
	}
	
	
	public function getMediaInfoAction(Request $request){
		$codice_cnc = $request->get('codice_cnc');
		$mese 		= $request->get('mese');
		$anno 		= $request->get('anno');
		$media 		= $request->get('media');
		$html 		='';
		try{
			setlocale(LC_TIME, 'ita', 'it_IT');
			$mediaMese = $this->getTotalMediaFromAnno($codice_cnc,$media,$anno);
			$totaleAnno = 0;
			
			$html .='<div class="media-box">';
			$html .='<div class="">';
			$html .='<div class="col-md-12">';
		
			foreach($mediaMese as $mese => $totale){
				$strong = '';
				if($mese==date('m')){ $strong = 'strong';}
				$nomeMese = ucfirst(strftime('%B', strtotime('2017-'.$mese.'-01')));
				$totaleAnno += $totale;
				$html .='<div class="col-md-6 riga_mese nome_mese '.$strong.'"><i class="fa fa-calendar"></i> '.$nomeMese.'</div><div class="riga_mese col-md-6 align-right">'.$totale.'</div>';
			}
			$html .='</div>';
			$html .='<div class="col-md-12 totals_media_box">
						<div class="col-md-6">
							<i class="fa fa-share-square" aria-hidden="true"></i>Totale <strong>' .$anno.'</strong>
						</div>
						<div class="col-md-6 align-right">'.$totaleAnno .'</div>
					</div>';
			
			$concorsi = $this->getConcorsiByMediaInDate($codice_cnc,$media,$anno);
			if(count($concorsi)>0 && is_array($concorsi)){
				$html .='<div class="col-md-12 title_concorsi_box"><h4>Dettaglio per mese del media: <strong>'.$media.'</strong></h4></div>';
				$html .='<div class="scroll-dettaglio-box">';
				foreach($concorsi as $mese => $concorso){
					$strong = '';
					if($mese==date('m')){ $strong = 'strong';}
					$nomeMese = ucfirst(strftime('%B', strtotime('2017-'.$mese.'-01')));
					
					
					$html .='<div class="col-md-12 info_media_concorso_row">';
					$html .='<div class="col-md-12 '.$strong.' title_mese"><i class="fa fa-calendar"></i>' . $nomeMese . ' '. $anno.'</div>';
					
					foreach($concorso as $nome_concorso => $totale_per_concorso){
						$html .='<div class="col-md-6  nome_mese riga_mese">
									<i class="fa fa-star"></i> '. $nome_concorso.'
								</div>
								<div class="col-md-6 align-right riga_mese">'.$totale_per_concorso.'
								</div>';
					}
					$html .='</div>';
				}
				$html .='</div><!-- /scroll-dettaglio-box -->';
			}
			
			$html .='</div>';
			$html .='</div>';
			
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		
		$response = new Response();
		$response->setContent(json_encode(array('content' => $html)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	private function getConcorsoAnnoTotale($codice_cnc,$anno){
		try{
			$array_values = array();
			$totaleanno = 0;
			$sql_find_mesi ="select count(*) as totaleanno from lead l where";
			if(!empty($codice_cnc)){
				$sql_find_mesi .= " l.codice_premio = :codice_cnc and ";
				$array_values['codice_cnc']	= 	$codice_cnc;	
			}
			$sql_find_mesi .= " year(data) = '".$anno."'";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
			$grandTotal = 0;
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totaleanno = $row['totaleanno'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totaleanno; 
	}
	

	private function getGeneratoConcorsoGiorno($codice_cnc,$giorno,$mese,$anno){
		try{
			$array_values = array();
			$totalimese = 0;
			$sql_find_mesi ="select count(*) as totaligiorno from lead l where ";
			if(!empty($codice_cnc)){
				$sql_find_mesi .="  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_find_mesi .=" day(data) 		= '" . $giorno . "'
							   and month(data) 		= '" . $mese   . "'
							   and year(data) 	= '" . $anno   . "'";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
			$grandTotal = 0;
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totalimese = $row['totaligiorno'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totalimese; 
	}
	
	private function getConcorsoMeseTotale($codice_cnc,$mese,$anno){
		try{
			$array_values = array();
			$totalimese = 0;
			$sql_find_mesi ="select count(*) as totalimese from lead l where ";
			if(!empty($codice_cnc)){
				$sql_find_mesi .="  l.codice_premio = :codice_cnc and";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_find_mesi .=" month(data) = '".$mese."'
							and year(data) = '".$anno."'";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
			$grandTotal = 0;
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totalimese = $row['totalimese'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totalimese; 
	}
	
	private function getMediaFromMese($cliente_id, $mese, $anno){
		try{
			$data_return = array();
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$sql_media = "select count(*) as tot, media from lead l
							inner join clienti_lead cl on cl.lead_id = l.id
							where cl.cliente_id = ?
							and month(programmata) = '".$mese."'
							and year(programmata) = '".$anno."'
							and cl.esterna=0
							group by media";
			$stmt2 	= $em->getConnection()->prepare($sql_media);
			$stmt2->execute(array($cliente_id));
			if ($stmt2->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row2 = $stmt2->fetch()) {
					$media = empty($row2['media']) ? 'non-presente' : $row2['media'];
					$data_return[$media] = $row2['tot'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $data_return; 
	}
	
	private function getConcorsoMediaFromMese($codice_cnc, $mese, $anno){
		try{
			$array_values = array();
			$data_return = array();
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$sql_media = "select count(*) as tot, media from lead l where ";
			if(!empty($codice_cnc)){
				$sql_media .= " l.codice_premio = :codice_cnc and ";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_media .= " month(data) = '".$mese."'
							and year(data) = '".$anno."'
							group by media";
			$stmt2 	= $em->getConnection()->prepare($sql_media);
			$stmt2->execute($array_values);
			if ($stmt2->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row2 = $stmt2->fetch()) {
					$media = empty($row2['media']) ? 'non-presente' : $row2['media'];
					$data_return[$media] = $row2['tot'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $data_return; 
	}
	
	private function getConcorsiByMediaInDate($codice_cnc,$media,$anno){
		try{
			$concorsi = array();
			$em 	= $this->getDoctrine()->getManager('concorso_man');				
				$sql_media = "select  
							month(data) as mese, 
							codice_premio as concorso, 
							count(*) as totale_concorso
							from lead l
							where 
							year(data) = '".$anno."'
							and media = '".$media."' ";
			if(!empty($codice_cnc)){
				$sql_media .= " and l.codice_premio = '".$codice_cnc."' ";
			}
			$sql_media .= " group by mese, codice_premio
							order by mese asc";
						
			$stmt 	= $em->getConnection()->prepare($sql_media);
			$stmt->execute();
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while($row = $stmt->fetch()){
					$concorsi[$row['mese']][$row['concorso']] = $row['totale_concorso'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $concorsi; 
	}
	
	private function getTotalMediaFromAnno($codice_cnc,$media,$anno){
		try{
			$mesi = array();
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$sql_media = "select count(*) as tot, month(data) as mese
							from lead l
							where 
							year(data) = '".$anno."'
							and media = '".$media."'";
			if(!empty($codice_cnc)){
				$sql_media .= " and l.codice_premio = '".$codice_cnc."'";
			}
			$sql_media .= "	group by mese";
			$stmt 	= $em->getConnection()->prepare($sql_media);
			$stmt->execute();
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$mese = $row['mese'];
					$media = empty($row['media']) ? 'non-presente' : $row['media'];
					$mesi[$mese] = $row['tot'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $mesi; 
	}

	private function getClienteTotal($cliente_id){
		try{
			$totali = 0;
			// recuper tutti i mesi
			$sql_find_mesi ="select count(*) as totale from clienti_lead cl where cl.cliente_id = ?";
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute(array($cliente_id));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totali = $row['totale'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	
	private function getConcorsoTotal($codice_cnc){
		try{
			$totali = 0;
			// recuper tutti i mesi
			$sql_find_mesi ="select count(*) as totale from lead l ";
			if(!empty($codice_cnc)){
				$sql_find_mesi .= "  where l.codice_premio = ?";
			}
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute(array($codice_cnc));
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totali = $row['totale'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	

	
	private function getConcorsoMesiAnni($codice_cnc){
		try{
			$mesiAnni = array();
			$array_values = array();
			// recuper tutti i mesi
			$sql_find_mesi ="select MONTH(data)AS mese, year(data) as anno
							from lead l";
			if(!empty($codice_cnc)){
				$sql_find_mesi .= " where l.codice_premio = :codice_cnc";
				$array_values['codice_cnc'] = $codice_cnc;
			}
			$sql_find_mesi .= " GROUP BY MESE, anno";
			
			$em 	= $this->getDoctrine()->getManager('concorso_man');
			$stmt = $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$mese = $row['mese'];
					$anno = $row['anno'];
					$mesiAnni[$anno][] = $mese;
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $mesiAnni; 
	}
	
	private function getAllCustomers(){
		$sql = "SELECT * FROM clienti WHERE attivo=1";
		$em = $this->getDoctrine()->getManager('concorso_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_customers =  $stmt->fetchAll();
		return $_customers;
	}
}