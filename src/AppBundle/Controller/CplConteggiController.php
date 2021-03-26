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

use \PDO; 

class CplConteggiController extends Controller
{
    
	private function getConnectionCpl($conn_domain){
		
		// dati connessione offtarget
		$offtarget_host = "localhost";	
		$offtarget_db 	= "leadoutl_dbb";	
		$offtarget_user = "leadoutl_usbd";	
		$offtarget_pass = "A%2)8s!JTkBp";

		// dati connessione offerte-promozioni.com
		$offertep_host 	= "46.16.95.34";	
		$offertep_db 	= "offertep_cpl";	
		$offertep_user 	= "offertep_cpl";	
		$offertep_pass 	= "UW[J8ScXWUJC";

		// dati connessione promotelefonia.it
		$promotel_host 	= "46.16.95.34";
		$promotel_db 	= "promotel_cpl";
		$promotel_user 	= "promotel_cpl";
		$promotel_pass 	= "1u0_sQ}hBIBw";


		// dati connessione promoh3g
		$promoh3g_host 	= "46.16.95.34";	
		$promoh3g_db 	= "promoh3g_cpl";	
		$promoh3g_user 	= "promoh3g_cpl";	
		$promoh3g_pass 	= "RbE#=c-_=E#a";

		// dati connessione promoh3g
		$promozio_db_host 	= "46.16.95.34";	
		$promozio_db_db 	= "promozio_db";	
		$promozio_db_user 	= "promozio_db";	
		$promozio_db_pass 	= "6=nhz-A_NgtZ";

		$dbavars = array('offtarget'	=> array(	'host' 	=> $offtarget_host,
													'db' 	=> $offtarget_db,
													'user' 	=> $offtarget_user,
													'pass' 	=> $offtarget_pass,
										),
						'offertep_cpl' 	=> array(	'host' 	=> $offertep_host,
													'db' 	=> $offertep_db,
													'user' 	=> $offertep_user,
													'pass' 	=> $offertep_pass,
						),
						'promotel_cpl' 	=> array(	'host' 	=> $promotel_host,
													'db' 	=> $promotel_db,
													'user' 	=> $promotel_user,
													'pass' 	=> $promotel_pass,
										),
						'promoh3g_cpl' 	=> array(	'host' 	=> $promoh3g_host,
													'db' 	=> $promoh3g_db,
													'user' 	=> $promoh3g_user,
													'pass' 	=> $promoh3g_pass,
										),
						'promozio_db' 	=> array(	'host' 	=> $promozio_db_host,
													'db' 	=> $promozio_db_db,
													'user' 	=> $promozio_db_user,
													'pass' 	=> $promozio_db_pass,
										),
						);
		
			if(!array_key_exists($conn_domain,$dbavars)){		
				$conn = false;
				echo "Non esiste key \\ " . $conn_domain ."  !!!";
			}else{
				$db 	= $dbavars[$conn_domain]['db'];
				$user 	= $dbavars[$conn_domain]['user'];
				$pass 	= $dbavars[$conn_domain]['pass'];
				$host 	= $dbavars[$conn_domain]['host'];
				try {
					$conn 	= new \PDO("mysql:host=".$host.";dbname=".$db."", $user,  $pass);
					$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$conn->exec("set names utf8");
				} catch (PDOException $e) {
					echo "Error!: " . $e->getMessage() . "<br/>";
				}
			}
			return $conn;
	}
	
	// AJAX
	public function getCampagneByClienteIdAction(Request $request){
		$cli_id = $request->get('cli_id');
		$soloCmpAttive = $request->get('soloattive');
		$html_conteggia_btn = '';
		$html_select = '<select name="campagna-select" class="campagna_select" id="campagna-select">';
		$html_select .= '<option value="">Seleziona una campagna...</option>';
		$sql_cmp = "SELECT id from a_landing_cliente 
					WHERE cliente_id = :cliente_id ";
					if(!empty($soloCmpAttive)){
						$sql_cmp .= " AND clienteAttivo = 1 ";			
					}
		$sql_cmp .= "GROUP BY mailoperationCliente, mailCliente 
					ORDER BY offtarget ASC, data_start ASC";

		$em 	= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql_cmp);
		$array_values['cliente_id'] =  	$cli_id;
		$stmt->execute($array_values);
	
		if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
			while ($row = $stmt->fetch()) {
				$id_campagna 	= $row['id'];
				$campagna = $this->getCampagna($id_campagna);
				$html_select .= '<option value="'.$campagna->id.'">'.$campagna->nome_offerta.' - ('. $campagna->url_landing . ')</option>';
			}
		
			$html_conteggia_btn = '<button id="conteggia-btn" onclick ="conteggia()" class="btn btn-primary"><i class="fa fa-calculator" aria-hidden="true"></i> Conteggia</button>';
		} // fine if trovati risultati
		
		$html_select .= '</select>';
		
		$response = new Response();
		$response->setContent(json_encode(array('select' => $html_select,'button' => $html_conteggia_btn)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getCampagna($id){
		try{
			$em 	= $this->getDoctrine()->getManager();
			$sql 	= "SELECT * from a_landing_cliente WHERE id = :id";
			$stmt 	= $em->getConnection()->prepare($sql);
			$array_values['id'] =  	$id;
			$stmt->execute($array_values);
			if ($stmt->rowCount()>0) {
				$campagna = new \stdClass();
				while ($row = $stmt->fetch()) {
					$campagna->id   			= $row['id'];
					$campagna->campagna_id   	= $row['campagna_id'];
					$campagna->landing_id   	= $row['landing_id'];
					$campagna->offtarget	   	= $row['offtarget'];
					$db 						= $row['dbCliente'];
					if($campagna->offtarget){ 	$db = 'offtarget';	}
					$campagna->dominio			= $db;
					$campagna->mail_operation	= $row['mailoperationCliente'];
					$campagna->mail				= $row['mailCliente'];
					$campagna->campi			= $row['campiExtLeadout'];
					$campagna->data_start		= $row['data_start'];
					$campagna->data_end			= $row['data_end'];
					$campagna->nome_offerta		= $this->getOfferta($campagna->campagna_id);
					$landing					= $this->getLandingInfo($campagna->landing_id);
					$campagna->url_landing		= $landing['url'];
				}
				return $campagna;
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
	}

	public function getOfferta($id){
		try{
			$em  = $this->getDoctrine()->getManager();
			$sql = "SELECT * from campagna WHERE id= :id";
			$stmt 	= $em->getConnection()->prepare($sql);
			$array_values['id'] =  	$id;
			$stmt->execute($array_values);
			if ($stmt->rowCount()>0) {
				while ($row = $stmt->fetch()) {
					return $row['nome_offerta'];
				}
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $this;
	}
	
	public function getLandingInfo($landing_id){
		try{
			$em  = $this->getDoctrine()->getManager();
			$sql = "SELECT * from landing WHERE id= :id";
			$stmt 	= $em->getConnection()->prepare($sql);
			$array_values['id'] =  	$landing_id;
			$stmt->execute($array_values);
			if ($stmt->rowCount()>0) {
				while ($landing = $stmt->fetch()) {
					return $landing;
				}
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $this;
	}

	// RENDER FUNCTIONS	
	 public function conteggiAction($message = null){
		$customers = $this->getAllCustomers();
		$datepicker = $this->getDatePicker();
		
		return $this->render('cpl_conteggi.html.twig', array(
           'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
           'customers' 		=> $customers,
           'message' 		=> $message,
		   'datepicker'		=> $datepicker,
        ), null); 
    }
	 
	 private function getDatePicker(){ // crea le select per datepicker
		setlocale(LC_TIME, 'ita', 'it_IT');
		//mesi
		$datep['mesi'] = '<select id="mese" name="mese">';
		for( $i = 1; $i <= 12; $i++ ) {
			$selected = '';
			if(date('m')==$i){
				
				$selected = 'selected="selected"';
			}
			$datep['mesi'] .= '<option ' . $selected . ' value="'.$i.'">' . ucfirst(strftime('%B',mktime(0,0,0,$i,1))) . '</option>';
		}
		$datep['mesi'] .= '</select>';
		 
		//anni
		$datep['anni'] = '<select id="anno" name="anno">';
		
		for( $i = 2010; $i <= date('Y'); $i++ ) {
			$selected = '';
			if(date('Y')==$i){
				$selected = 'selected="selected"';
			}
			$datep['anni'] .= '<option ' . $selected . ' value="'.$i.'">' .  $i . '</option>';
		}
		$datep['anni'] .= '</select>';
		return $datep;
	 }
	 
	
	
	 
	 
	public function getConteggiCampagnaAction(Request $request){
		setlocale(LC_TIME, 'ita', 'it_IT');
		$id_campagna = $request->get('campagna_id');
		$html_menu = '';
		$html_cont = '';
		
		$campagna = false;
		$meseRichiesto = '';
		$annoRichiesto = '';
		
		if($id_campagna!='0'){
			$campagna = $this->getCampagna($id_campagna);
		}else{
			$meseRichiesto = $request->get('mese');
			$annoRichiesto = $request->get('anno');
		}
		
		$totali_campagna = $this->getCampagnaTotale($campagna,$meseRichiesto,$annoRichiesto);
		
		$annoMesi = array();
		$annoMesi = $this->getConteggioByCampagna($campagna,$meseRichiesto,$annoRichiesto);
		
		$hmtl_cont_left = '';
		foreach($annoMesi as $anno => $mesi){
			$totale_anno = 0;
			$espanso ='';
		//	if($anno == date('Y')){ $espanso = 'aria-expanded="true"';}
			//menu
			$totale_anno = $this->getCampagnaAnnoTotale($campagna,$anno);

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
					$nomeMese = ucfirst(strftime('%B', strtotime('2017-'.$mese.'-01')));
					$giorni_nel_mese = range(1,$giorni_in_mese);
					
					$totaleMese 		= $this->getCampagnaMeseTotale($campagna,$mese,$anno); // totale del mese analizzato
					
					$html_menu .='<li class=" submenu-link list-group-item" onclick="loadRightContent(\''.$anno .'_'.$nomeMese.'\',\''.$id_campagna.'\',\''.$mese.'\',\''.$anno.'\')">
					<i class="fa fa-calendar" aria-hidden="true"></i> '.$nomeMese.'<span class="totale_mese">'.$totaleMese.'<i class="fa fa-users" aria-hidden="true"></i></span></li>';
					
					if($mese == date('m')){ 
						$activetab = 'active';
						$click_giorni = '';
						foreach($giorni_nel_mese as $g){
							//$click_giorni .= '<span class="giornini_clicks" onclick="toggleGiorno(\'day_'.$g.'_'.$mese.'_'.$anno.'\')">'.$g.'</span>';
							$click_giorni .= '<span class="giornini_clicks" onclick="toggleGiorno('. $g .','. $mese .','. $anno .','. $id_campagna .')">'.$g.'</span>';
						}
						//$click_giorni .= '<span class="giornini_clicks_tutti" onclick="toggleTuttiGiorni(\''.$mese.'\',\''.$anno.'\')">Tutti</span>';
						$click_giorni .= '<span class="giornini_clicks_tutti" onclick="toggleTuttiGiorni('.$mese.','.$anno.','.$id_campagna.')">Tutti</span>';
						

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
						$hmtl_cont_left .= '<div class="col-md-12 header-box-mese" onclick="toggleDettaglioAggregato()">
												<div class="col-md-10">
													<h4><i class="fa fa-globe" aria-hidden="true"></i> Aggregato del mese</h4>
												</div>
												<div class="col-md-2">
												<h4><i class="fa fa-caret-square-o-up slideupicon" aria-hidden="true"></i></h4>
												</div>
											</div>';

						
						$mese_media = $this->getCampagnaDayMediaByGiorno($campagna,$mese,$anno);

						$grandtotale_m_media = 0;
						$hmtl_cont_left .='<div class="col-md-12"><!-- blocco mese destro -->';
						$hmtl_cont_left .='<div class="row"><div class="row tab_'.$mese.'_'.$anno.'_m tab-show-left-tabs" data-fill="1">';	
						foreach($mese_media as $m_media => $totale_m_media){
							$__media 	= $m_media;
							if(empty(trim($__media))){
								$__media = 'non presente';
							}
							$link_extraction = $this->admin->generateUrl('exportTable',array(
														'campagna_id'	=> $id_campagna,
														'mese'			=> $mese, 
														'anno' 			=> $anno,
														'media'			=> $m_media,
													)
												);
											
							$hmtl_cont_left .= '<div class="col-md-12 submedia-box">
													<div class="col-md-7">' . $__media . '</div>
													<div class="col-md-3 totali_media">
														<strong>' . $totale_m_media . '</strong>
													</div>
													<div class="col-md-2">
														<a href="'.$link_extraction.'">
															<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica
														</a>
													</div>
												</div>';
							$grandtotale_m_media += $totale_m_media;
						}
						
						$link_extraction = $this->admin->generateUrl('exportTable',array(
														'campagna_id'	=> $id_campagna,
														'mese'			=> $mese, 
														'anno' 			=> $anno,
													)
												);
											
						$hmtl_cont_left .='</div><!-- /tab-show-left-tabs -->';
						$hmtl_cont_left .= '</div>';
						$hmtl_cont_left .= '	<div class="footer-mese-box row last-row-totale-gen">
											<div class="col-md-7"><strong>Totali generate</strong></div>
											<div class="col-md-3 totali_media">
												<strong>' . $grandtotale_m_media . '</strong>
											</div>
											<div class="col-md-2">
												<a href="'.$link_extraction.'">
													<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica '.$nomeMese.' '.$anno.'
												</a>
											</div>
										</div>';
						$hmtl_cont_left .='</div><!-- /blocco mese destro -->';
						$hmtl_cont_left .='</div><!-- /conteggi-mese -->';
						$hmtl_cont_left .='<div class="col-md-12 giornini-content">';
						$hmtl_cont_left .='</div><!-- /giornini-content -->';
								  
						
							$html_cont_left_days = '';
							/*
							for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
								// header box giorno
								$html_cont_left_days .='<div id="day_'.$giorno.'_'.$mese.'_'.$anno.'" class="col-md-3 col-xs-6 col-sm-6 day-box day-box_'.$mese.'_'.$anno.'">
												<h4>
													<i class="fa fa-calendar" aria-hidden="true"></i>Giorno: <strong>'.$giorno.'</strong>
												</h4>';
								$giorni_media = $this->getCampagnaDayMediaByGiorno($campagna, $mese,$anno,$giorno); // array di tutti i giorni e i media giorno => array('mdia1' => totale)
								$totale_giorno = 0;
								$html_days_cont = '';
								
								foreach($giorni_media as $media => $totale_media){
									$class_row 	= '';
									$_media 	= $media;
									$onclick = 'onclick="getMediaInfo(\''.$_media.'\',\''.$campagna->id.'\',\''.$mese.'\',\''.$anno.'\')"';
									if(empty(trim($_media))){
										$_media = 'non presente';
									}
									$html_days_cont .= '<div class="col-md-12 submedia-box ' . $class_row . '" '.$onclick.'">
															<div class="col-md-9">' . $_media . '</div>
															<div class="col-md-3 totali_media">
																<strong>' . $totale_media . '</strong>
															</div>
														</div>';
									$totale_giorno += $totale_media;

								}
								
								$html_cont_left_days .='<div class="row"><div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_m tab-show-tabs" data-fill="1">';	
								$html_cont_left_days .= $html_days_cont;
								$html_cont_left_days .= '</div>';
								$html_cont_left_days .= '</div>';
								$html_cont_left_days .= '<div class="footer-day-box col-md-12 last-row-totale-gen">
												<div class="col-md-9"><strong>Totali generate</strong></div>
												<div class="col-md-3 totali_media">
													<strong>' . $totale_giorno . '</strong>
												</div>';
								$html_cont_left_days .= '</div><!-- /footer-day-box-->';	
								$html_cont_left_days .= '</div><!-- /day-box-->';	

							} // fine for giorni
						
								*/
								
						
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
		
		$hmtl_scarica_button = '';
		if($totali_campagna>0 && $id_campagna!=0){
			$link_scarica_tutto = $this->admin->generateUrl('exportTable',array('campagna_id'	=> $id_campagna));
			$hmtl_scarica_button = '<a class="btn btn-primary" href="'.$link_scarica_tutto.'"><i class="fa fa-download" aria-hidden="true"></i> Scarica tutto</a>';
		}

		$response = new Response();
		$response->setContent(json_encode(array(
											'totali_campagna' 	=> $totali_campagna,
											'menu'				=> $html_menu, 
											'contenuto' 		=> $hmtl_cont_left,
											'scarica_button' 	=> $hmtl_scarica_button,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	// AJAX
	public function getGiorniniTotalAction(Request $request){
		setlocale(LC_TIME, 'ita', 'it_IT');
		$campagna_id = $request->get('campagna_id');
		$campagna = false;
		if($campagna_id!='0'){
			$campagna = $this->getCampagna($campagna_id);
		}
		$giorno = $request->get('giorno');
		$mese 	= $request->get('mese');
		$anno 	= $request->get('anno');
		
		$html_day_box = '';
		// header box giorno
		$html_day_box .='<div id="day_'.$giorno.'_'.$mese.'_'.$anno.'" class="col-md-3 col-xs-6 col-sm-6 day-box day-box_'.$mese.'_'.$anno.'">
						<h4>
							<i class="fa fa-calendar" aria-hidden="true"></i>Giorno: <strong>'.$giorno.'</strong>
						</h4>';
		$giorni_media = $this->getCampagnaDayMediaByGiorno($campagna, $mese,$anno,$giorno); // array di tutti i giorni e i media giorno => array('mdia1' => totale)
		$totale_giorno = 0;
		$html_days_cont = '';
		
		foreach($giorni_media as $media => $totale_media){
			$class_row 	= '';
			$_media 	= $media;
			//$onclick = 'onclick="getMediaInfo(\''.$_media.'\',\''.$campagna->id.'\',\''.$mese.'\',\''.$anno.'\')"';
			$onclick = '';
			if(empty(trim($_media))){
				$_media = 'non presente';
			}
			$html_days_cont .= '<div class="col-md-12 submedia-box ' . $class_row . '" '.$onclick.'">
									<div class="col-md-9">' . $_media . '</div>
									<div class="col-md-3 totali_media">
										<strong>' . $totale_media . '</strong>
									</div>
								</div>';
			$totale_giorno += $totale_media;

		}
		
		$html_day_box .='<div class="row"><div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_m tab-show-tabs" data-fill="1">';	
		$html_day_box .= $html_days_cont;
		$html_day_box .= '</div>';
		$html_day_box .= '</div>';
		$html_day_box .= '<div class="footer-day-box col-md-12 last-row-totale-gen">
						<div class="col-md-9"><strong>Totali generate</strong></div>
						<div class="col-md-3 totali_media">
							<strong>' . $totale_giorno . '</strong>
						</div>';
		$html_day_box .= '</div><!-- /footer-day-box-->';	
		$html_day_box .= '</div><!-- /day-box-->';	

		$response = new Response();
		$response->setContent(json_encode(array(
											'giorno_box' 		=> $html_day_box,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	// AJAX
	public function getMeseRightContentAction(Request $request){
		setlocale(LC_TIME, 'ita', 'it_IT');
		$campagna_id = $request->get('campagna_id');
		$mese = $request->get('mese');
		$anno = $request->get('anno');
		$html_cont = '';
		
		$hmtl_cont_left = '';
		// itero i giorni del mese selezionato

		$giorni_in_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
		$nomeMese = ucfirst(strftime('%B', strtotime('2017-'.$mese.'-01')));
		$giorni_nel_mese = range(1,$giorni_in_mese);
		
		$campagna = $this->getCampagna($campagna_id);
		$totaleMese = $this->getCampagnaMeseTotale($campagna,$mese,$anno); // totale del mese analizzato
		$activetab = 'active';
		$click_giorni = '';
		foreach($giorni_nel_mese as $g){
			$click_giorni .= '<span class="giornini_clicks" onclick="toggleGiorno('. $g .','. $mese .','. $anno .','. $campagna_id .')">'.$g.'</span>';
		}
		$click_giorni .= '<span class="giornini_clicks_tutti" onclick="toggleTuttiGiorni('.$mese.','.$anno.','.$campagna_id.')">Tutti</span>';
        
		$hmtl_cont_left .= '<div class="col-md-12">';
		$hmtl_cont_left .= '<div class="title-subtab col-md-12">
								<div class="header-nomeMese">
									<i class="fa fa-calendar" aria-hidden="true"></i>'.$nomeMese.' ' . $anno . '
								</div>
								<div class="header-giornini"><span class="giornini giorni_'.$mese.'_'.$anno.'">'.$click_giorni.'</span></div>
								<div class="header-totali">
									<span class="totals">
										<i class="fa fa-users" aria-hidden="true"></i><strong>'. $totaleMese . '</strong>
									</span>
								</div>
						  </div>';
					
		// blocco conteggio aggregato mese
		$hmtl_cont_left .= '<div class="conteggi-mese col-md-12">';
		$hmtl_cont_left .= '<div class="col-md-12 header-box-mese" onclick="toggleDettaglioAggregato()">
												<div class="col-md-10">
													<h4><i class="fa fa-globe" aria-hidden="true"></i> Aggregato del mese</h4>
												</div>
												<div class="col-md-2">
												<h4><i class="fa fa-caret-square-o-up slideupicon" aria-hidden="true"></i></h4>
												</div>
											</div>';

		
		$mese_media = $this->getCampagnaDayMediaByGiorno($campagna,$mese,$anno);
			
		$grandtotale_m_media = 0;
		$hmtl_cont_left .='<div class="col-md-12"><!-- blocco mese destro -->';
		$hmtl_cont_left .='<div class="row"><div class="row tab_'.$mese.'_'.$anno.'_m tab-show-left-tabs" data-fill="1">';	
		foreach($mese_media as $m_media => $totale_m_media){
			$__media 	= $m_media;
			if(empty(trim($__media))){
				$__media = 'non presente';
			}
			$link_extraction = $this->admin->generateUrl('exportTable',array(
														'campagna_id'	=> $campagna_id,
														'mese'			=> $mese, 
														'anno' 			=> $anno,
														'media'			=> $m_media,
													)
												);
			$hmtl_cont_left .= '<div class="col-md-12 submedia-box">
										<div class="col-md-7">' . $__media . '</div>
										<div class="col-md-3 totali_media">
											<strong>' . $totale_m_media . '</strong>
										</div>
										<div class="col-md-2">
											<a href="'.$link_extraction.'">
												<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica
											</a>
										</div>
								</div>';
			$grandtotale_m_media += $totale_m_media;
		}
		
		$link_extraction = $this->admin->generateUrl('exportTable',array(
														'campagna_id'	=> $campagna_id,
														'mese'			=> $mese, 
														'anno' 			=> $anno,
													)
												);
		$hmtl_cont_left .='</div><!-- /tab-show-left-tabs -->';
		//$hmtl_cont_left .= '<div class="col-md-12 tab_'.$mese.'_'.$anno.'_c tab-show-left-tabs"></div>';
		$hmtl_cont_left .= '</div>';
		$hmtl_cont_left .= '	<div class="footer-mese-box row last-row-totale-gen">
							<div class="col-md-7"><strong>Totali generate</strong></div>
							<div class="col-md-3 totali_media">
								<strong>' . $grandtotale_m_media . '</strong>
							</div>
							<div class="col-md-2">
								<a href="'.$link_extraction.'">
									<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica '.$nomeMese.' '.$anno.'
								</a>
							</div>
						</div>';
		$hmtl_cont_left .='</div><!-- /blocco mese destro -->';
		$hmtl_cont_left .='</div><!-- /conteggi-mese -->';
		$hmtl_cont_left .='<div class="giornini-content">';
		$hmtl_cont_left .='</div><!-- /giornini-content -->';
				  
					
		$html_cont_left_days = '';
		
		/*
		for($giorno=1;$giorno<=$giorni_in_mese;$giorno++){
			// header box giorno
			$html_cont_left_days .='<div id="day_'.$giorno.'_'.$mese.'_'.$anno.'" class="col-md-3 col-xs-6 col-sm-6 day-box day-box_'.$mese.'_'.$anno.'">
							<h4>
								<i class="fa fa-calendar" aria-hidden="true"></i>Giorno: <strong>'.$giorno.'</strong>
							</h4>';
			$giorni_media = $this->getCampagnaDayMediaByGiorno($campagna, $mese,$anno,$giorno); // array di tutti i giorni e i media giorno => array('mdia1' => totale)
			$totale_giorno = 0;
			$html_days_cont = '';
			
			foreach($giorni_media as $media => $totale_media){
				$class_row 	= '';
				$_media 	= $media;
				$onclick = 'onclick="getMediaInfo(\''.$_media.'\',\''.$campagna->id.'\',\''.$mese.'\',\''.$anno.'\')"';
				if(empty(trim($_media))){
					$_media = 'non presente';
				}
				$html_days_cont .= '<div class="col-md-12 submedia-box ' . $class_row . '" '.$onclick.'">
										<div class="col-md-9">' . $_media . '</div>
										<div class="col-md-3 totali_media">
											<strong>' . $totale_media . '</strong>
										</div>
									</div>';
				$totale_giorno += $totale_media;

			}
			
			$html_cont_left_days .='<div class="row"><div class="col-md-12 tab_'.$giorno.'_'.$mese.'_'.$anno.'_m tab-show-tabs" data-fill="1">';	
			$html_cont_left_days .= $html_days_cont;
			$html_cont_left_days .= '</div>';
			$html_cont_left_days .= '</div>';
			$html_cont_left_days .= '<div class="footer-day-box col-md-12 last-row-totale-gen">
							<div class="col-md-9"><strong>Totali generate</strong></div>
							<div class="col-md-3 totali_media">
								<strong>' . $totale_giorno . '</strong>
							</div>';
			$html_cont_left_days .= '</div><!-- /footer-day-box-->';	
			$html_cont_left_days .= '</div><!-- /day-box-->';	

		} // fine for giorni
		*/			
							
					
		$hmtl_cont_left .= $html_cont_left_days;
		$hmtl_cont_left .= '</div><!-- /col-md-4 -->';

		$response = new Response();
		$response->setContent(json_encode(array('contenuto' => $hmtl_cont_left)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	
	private function getCampagnaTotale($campagna,$mese='',$anno=''){
		try{
			$totali = 0;
			$array_values = array();
			$sql_tot = "select count(*) as tot from lead_uni";
			if($campagna){
				$sql_tot .= " WHERE source_db = :source_db AND source_tbl = :source_tbl";
				$array_values['source_db'] 		=  	$campagna->dominio;
				$array_values['source_tbl'] 	=  	$campagna->mail;
			}else{
				if(!empty($mese) && !empty($anno)){
					$sql_tot .= " WHERE month(data) = :mese AND year(data) = :anno";
					$array_values['mese'] 	=  	$mese;
					$array_values['anno'] 	=  	$anno;
				}
			}
			
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_tot);
			$stmt->execute($array_values);
		
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totali = $row['tot'];
				}
			}
			
			/*
			$sql_tot = "select count(*) as tot from " . $campagna->mail;
			$conn 	= $this->getConnectionCpl($campagna->dominio);
			if($conn){
				$stmt 	= $conn->prepare($sql_tot);
				$stmt->execute();
				if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
					while ($row = $stmt->fetch()) {
						$totali = $row['tot'];
					}
				} // fine if trovati risultati
			}
			*/
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	
	private function getCampagnaDayMediaByGiorno($campagna,$numeroMese,$anno,$giorno = ''){
		try{
			$array_values= array();
			$giorni_media = array();
			
			// ciclo query
			//$sql_medias = "select count(*) as tot_media, media from " . $campagna->mail . " m where";
			$sql_medias = "select count(*) as tot_media, editore from lead_uni where";
			if($campagna){
				$sql_medias .= " source_tbl = :source_tbl AND source_db = :source_db AND ";
				$array_values['source_db'] 	=  	$campagna->dominio;
				$array_values['source_tbl'] =  	$campagna->mail;
			}
			$sql_medias .= " month(data) = :numeroMese and year(data) = :anno ";
			
			if(!empty($giorno)){
				$sql_medias .= "  and day(data) = :giorno_i ";
				$array_values['giorno_i'] =	$giorno;
			}
			$sql_medias .= " group by editore";
			
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_medias);
			$array_values['numeroMese'] =  	$numeroMese;
			$array_values['anno'] 		= 	$anno;
			
			$stmt->execute($array_values);
			
			$grandTotalMedia = 0;
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$media 			= $row['editore'];
					$tot_media		= $row['tot_media'];
					$giorni_media[$media] = $tot_media; 
					$grandTotalMedia += $tot_media;
				}
			}

			
			
			/*
			$conn 	= $this->getConnectionCpl($campagna->dominio);
			if($conn){
				$stmt 	= $conn->prepare($sql_medias);
				$array_values['numeroMese'] =  	$numeroMese;
				$array_values['anno'] 		= 	$anno;

				$stmt->execute($array_values);
				
				$grandTotalMedia = 0;
				if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
					while ($row = $stmt->fetch()) {
						$media 		= $row['media'];
						$tot_media	= $row['tot_media'];
						$giorni_media[$media] = $tot_media; 
						$grandTotalMedia += $tot_media;
					}
			
				} // fine if trovati risultati
			}
			*/
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $giorni_media; 
	}
	
	private function getCampagnaAnnoTotale($campagna,$anno){
		try{
			$array_values = array();
			$totaleanno = 0;
			//$sql_find_mesi ="select count(*) as totaleanno from ".$campagna->mail." m where";
			//$sql_find_mesi .= " year(data) = '".$anno."'";
			
			$sql_find_mesi ="select count(*) as totaleanno from lead_uni where";
			if($campagna){
				$sql_find_mesi .= " source_db = :source_db AND source_tbl = :source_tbl and ";
				$array_values['source_db'] =  	$campagna->dominio;
				$array_values['source_tbl'] =  	$campagna->mail;
			}
			$sql_find_mesi .= " year(data) = '".$anno."'";
			
			//$conn 	= $this->getConnectionCpl($campagna->dominio);
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
			
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totaleanno = $row['totaleanno'];
				}
			} 
			
			/*
			if($conn){
				$stmt 	= $conn->prepare($sql_find_mesi);
				$stmt->execute($array_values);
				$grandTotal = 0;
				if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
					while ($row = $stmt->fetch()) {
						$totaleanno = $row['totaleanno'];
					}
				}
			}
			*/
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totaleanno; 
	}
	
	private function getCampagnaMeseTotale($campagna,$mese,$anno){
		try{
			$array_values = array();
			$totalimese = 0;
			//$sql_find_mesi ="select count(*) as totalimese from " . $campagna->mail . " m where ";
			$sql_find_mesi = "select count(*) as totalimese from lead_uni l where ";
			if($campagna){
				$sql_find_mesi .=" source_db = :source_db AND source_tbl = :source_tbl AND ";
				$array_values['source_db']  = $campagna->dominio;
				$array_values['source_tbl'] = $campagna->mail;
			}
			$sql_find_mesi .=" month(data) = '".$mese."' AND year(data) = '".$anno."'";
			
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);
		
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totalimese = $row['totalimese'];
				}
			}
			
			/*
			$conn 	= $this->getConnectionCpl($campagna->dominio);
			if($conn){
				$stmt 	= $conn->prepare($sql_find_mesi);
				$stmt->execute($array_values);
				$grandTotal = 0;
				if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
					while ($row = $stmt->fetch()) {
						$totalimese = $row['totalimese'];
					}
				}
			}
			*/
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totalimese; 
	}
	
	private function getConteggioByCampagna($campagna,$mese='',$anno=''){
		try{
			$mesiAnni = array();
			$array_values = array();
			// recuper tutti i mesi
			//$sql_find_mesi ="select MONTH(data) AS mese, year(data) as anno from ".$campagna->mail." m";
			$sql_find_mesi ="select MONTH(data) AS mese, year(data) as anno from lead_uni l ";
			if($campagna){
				$sql_find_mesi .=" WHERE source_db= :source_db AND source_tbl = :source_tbl";
				$array_values['source_db'] 	=  	$campagna->dominio;
				$array_values['source_tbl'] =  	$campagna->mail;
			}else{
				if(!empty($mese) && !empty($anno)){
					$sql_find_mesi .= " WHERE month(data) = :mese AND year(data) = :anno";
					$array_values['mese'] 	=  	$mese;
					$array_values['anno'] 	=  	$anno;
				}
			}
			
			$sql_find_mesi .= " GROUP BY MESE";
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_find_mesi);
			$stmt->execute($array_values);

			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$mese = $row['mese'];
					$anno = $row['anno'];
					$mesiAnni[$anno][] = $mese;
				}
			}
			
			/*
			$conn 	= $this->getConnectionCpl($campagna->dominio);
			if($conn){
				$stmt 	= $conn->prepare($sql_find_mesi);
				$stmt->execute($array_values);
				if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
					while ($row = $stmt->fetch()) {
						$mese = $row['mese'];
						$anno = $row['anno'];
						$mesiAnni[$anno][] = $mese;
					}
				}
			}
			*/
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $mesiAnni; 
	}
	
	private function getAllCustomers(){
		$sql = "SELECT * FROM cliente ORDER BY name ASC";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_customers =  $stmt->fetchAll();
		return $_customers;
	}
	
	public function exportTableAction(Request $request){
		
			$campagna_id	= $request->get('campagna_id');
			$append_fn 		= '';
			$where_cls = array();
			$nome_offerta 	= 'Clienti-Campagne';
			if($campagna_id!=0){
				$campagna 		= $this->getCampagna($campagna_id);
				$source_db 		= $campagna->dominio;
				$source_tbl		= $campagna->mail;
				$dominio 		= $campagna->dominio;
				$nome_offerta 	= $campagna->nome_offerta;
				$first_where 	= "source_tbl = '" . $source_tbl . "' AND source_db = '" . $source_db . "'";
				// immetto la selezione dalla tabella nella query
				$where_cls[] 	= $first_where;
				
			}
			
				$tabella		= 'lead_uni'; //$campagna->mail;
				$media			= $request->get('media');
				$anno			= $request->get('anno');
				$mese			= $request->get('mese');
			
			$query = "SELECT * FROM " . $tabella;
			

			//gestione del tipo di richiesta ------------
			// GESTIONE ESTRAZIONE IN BASE ALLA DATA
	
			if(isset($mese)){
				$where_cls[] = "MONTH(data) = '" . $mese . "'";
				$append_fn .= $mese . '-';
			}
			if(isset($anno)){
				$where_cls[] = "YEAR(data) = '" . $anno . "'";
				$append_fn .= $anno . '-';
			}
			
			// GESTIONE ESTRAZIONE IN BASE AL MEDIA
			if(!empty($media)){
				$where_cls[] = "editore = '" . $media . "'";
				$append_fn .= $media.'-';
			}
			// --------------------------------------
			
			// GENERAZIONE QUERY FINALE
			if(!empty($where_cls) && count($where_cls)>1){
				$query .= " WHERE ";
				$query .= implode(' AND ',$where_cls);
			}elseif(!empty($where_cls) && count($where_cls)==1){
				$query .= " WHERE " . $where_cls[0];
			}
			$filename = preg_replace('/[^A-Za-z0-9]/', "", $nome_offerta) . '_' . $append_fn . date('d-m-Y');
			$this->export($filename,$query);
	}
	
	private function export($filename,$query){
		
		
		$rootDir = $this->container->getParameter('kernel.root_dir');
		$fileExporterPath = $rootDir.'/../vendor/phpexportdata/php-export-data.class.php';
		$controllo_colonne = array();
		try{
			// connessione al db cpl
			$conn 	= $this->getDoctrine()->getManager()->getConnection();
			if($conn){
				
				
				// recupero tutte le colonne
				$stmt = $conn->prepare("DESCRIBE lead_uni");
				$stmt->execute();
				$table_fields = $stmt->fetchAll(\PDO::FETCH_COLUMN);
				
				// query 
				$stmt2 	= $conn->prepare($query);
				$stmt2->execute();
				$righe = array();
				if ($stmt2->rowCount()>0) { // lead presente
					while($dati_lead  = $stmt2->fetch()){
						$_riga = array();
						$lead_id = $dati_lead['id'];
						
						//query prelevo valori
						$query_pv 	= "select * from a_lead_extra_values where lead_id = " . $lead_id;
						$stmt_pv 	= $conn->prepare($query_pv);
						$stmt_pv->execute();
						if ($stmt_pv->rowCount()>0) { // lead presente, aggiungo 2 gg
							while($row_pv  = $stmt_pv->fetch()){
								$value_id = $row_pv['value_id'];
								//query prelevo valori
								$query_val = "select * from lead_uni_extra_values where id = " . $value_id;
								$stmt_val 	= $conn->prepare($query_val);
								$stmt_val->execute();
								if ($stmt_val->rowCount()>0) { // lead presente, aggiungo 2 gg
									while($row_val  = $stmt_val->fetch()){
										$field_id = $row_val['field_id'];
										$__valore = $row_val['name'];
										
										// aggiungo il dato alla riga
										$dati_lead[] = $__valore;
										
										//query prelevo colonne
										$query_nome_ev 	= "select * from lead_uni_extra_fields where id = " . $field_id;
										$stmt_nome_ev 	= $conn->prepare($query_nome_ev);
										$stmt_nome_ev->execute();
										if($stmt_nome_ev->rowCount()>0){ // lead presente, aggiungo 2 gg
											while($row_ev  = $stmt_nome_ev->fetch()){
												$nome_extra_col = $row_ev['name'];
												//query prelevo valori
												if(!array_key_exists($nome_extra_col,$controllo_colonne)){
													$controllo_colonne[$nome_extra_col] = '';
													$table_fields[] = $nome_extra_col;
												}
											} //while row_ev
										} // if $stmt_nome_ev
									} // while $row_val  = $stmt_val
								} // if $stmt_val
							} //while stmt_pv
						} // if stmt_pv
						
						//aggiungo le righe
						$righe[] = $dati_lead;
						
					} // while stmt2
					// inizializzo l'exporter
					
					include_once($fileExporterPath);
					$exporter = new \ExportDataCSV('browser', $filename . '.csv');
					$exporter->initialize(); // starts streaming data to web browser
					$row  = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
					// header 
					$exporter->addRow($table_fields);
					foreach($righe as $key => $valore ){
						$exporter->addRow($valore);
					}
					$exporter->finalize();
					exit(); 
				} // fine if trovati risultati in lead_uni
			} // if conn
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return false;
	}
}