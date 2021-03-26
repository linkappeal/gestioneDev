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

class TracciamentoPixelController extends Controller
{
    
	private function getConnectionCpl($conn_domain){
		
		// dati connessione offtarget
		$offtarget_host = "localhost";	
		$offtarget_db 	= "leadoutl_dbb";	
		$offtarget_user = "leadoutl_usbd";	
		$offtarget_pass = "A%2)8s!JTkBp";

		$dbavars = array('offtarget'	=> array(	'host' 	=> $offtarget_host,
													'db' 	=> $offtarget_db,
													'user' 	=> $offtarget_user,
													'pass' 	=> $offtarget_pass,
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
	public function getTraceAction(Request $request){
		$id_campagna 	= $request->get('id_campagna');
		$mese 			= $request->get('mese');
		$anno 			= $request->get('anno');
		$unique 		= $request->get('unique');
		$media 			= $request->get('editore');
		$_target 		= $request->get('target');
		$array_values	= array();
		
		$html = '';
		$totali = array('totali' => 0, 'tracciate' => 0);
		
		$array_values['id_campagna'] = str_replace('*','%',$id_campagna);
		
		$target = 'pixel_trace';
		$table_header = array(	'Data'			,
								'IP'			,
								'Device'		,
								'Campagna'		,
								'Prodotto'		,
								'ID Banner'		,
								'ID Step'		,
								'Tipo trace'	,
								'Payout'		,
								'ID Agenzia'	,
								'Editore'		,
								'Lead Code'		,
								'Tracciata'		,
								'Parametri'		,
							);
		$header_search = array('Device', 'Tipo trace');
		if($_target=='click'){ 
			$target = 'click_trace'; 
			$key = array_search('Tracciata',$table_header);
			unset($table_header[$key]);
		}
		
		$sql_cmp = "SELECT * FROM " . $target . "
					WHERE id_campagna LIKE :id_campagna ";
					if(!empty($media)){
						$sql_cmp .= " AND media like :media ";	
						$array_values['media'] =  '%'.$media.'%';
						
					}
					if(!empty($mese) && !empty($anno)){
						$sql_cmp .= " AND YEAR(dt) = :anno AND MONTH(dt) = :mese";			
						$array_values['anno'] = $anno;
						$array_values['mese'] = $mese;
					}
					if(!empty($unique) && $unique == '1'){
						$sql_cmp .= " GROUP BY ip ";	
					}
		$sql_cmp .= " ORDER BY dt ASC";

		try{
			$em 	= $this->getDoctrine()->getManager('pixel_man');
			$stmt 	= $em->getConnection()->prepare($sql_cmp);
			$stmt->execute($array_values);
		
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				$totali['totali'] 		= $stmt->rowCount();
				
				$html .= '<table  id="listatoTrace" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">';
					$html .= '<thead>';
						$html .= '<tr>';
						foreach($table_header as $col_name){
							$search_class= '';
							if(in_array($col_name,$header_search)){
								$search_class= 'class="search"';
							}
							$html .= '<td '.$search_class.'>'. $col_name .'</td>';
						}
						$html .= '</tr>';
					$html .= '</thead>';
				$html .= '<tbody>';
				
				while ($row = $stmt->fetch()) {
					
					
					$data 			= $row['dt'];
					$ip 			= $row['ip'];
					$device 		= $row['device'];
					$campagna 		= $row['id_campagna'];
					$prodotto 		= $row['id_prodotto'];
					$id_banner 		= $row['id_banner'];
					$step 			= $row['id_step'];
					$indiretta 		= $row['indiretta'];
					$payoutcode 	= $row['payout_code'];
					$id_agenzia 	= $row['id_agenzia'];
					$media 			= $row['media'];
					$code 			= $row['code'];

					$traced	= '';
					
					if($target=='pixel_trace'){
						$traced			= $row['traced'];
					}
					$parametri		= $row['parametri'];

					$html_par = '';
					if(!empty($parametri)){
						$parametri = unserialize($parametri);
						
						foreach($parametri as $key => $par){
							$html_par .= $key .': ' . $par;
						}
					}
					
					if($indiretta=='1'){
						$indiretta = '<span class="indiretta-label">Indiretta</span>';
					}else{
						$indiretta = '<span class="diretta-label">Diretta</span>';
					}
					// modifica per differenziazione tabella clicks e pixel
					if($traced!=''){
						if($traced=='1'){
							$traced = '<span class="si-label">SI</span>';
							$totali['tracciate']++;
						}else{
							$traced = '<span class="no-label">NO</span>';
						}
					}
					// dati tabella 
					$html .= '<tr>';
						$html .= '<td>'. $data 		 	.'</td>';
						$html .= '<td>'. $ip 		 	.'</td>';
						$html .= '<td>'. $device 		.'</td>';
						$html .= '<td>'. $campagna 		.'</td>';
						$html .= '<td>'. $prodotto 		.'</td>';
						$html .= '<td>'. $id_banner 	.'</td>';
						$html .= '<td>'. $step 		 	.'</td>';
						$html .= '<td>'. $indiretta 	.'</td>';
						$html .= '<td>'. $payoutcode  	.'</td>';
						$html .= '<td>'. $id_agenzia  	.'</td>';
						$html .= '<td>'. $media 		.'</td>';
						$html .= '<td>'. $code 			.'</td>';
						
						if($traced!=''){
							$html .= '<td>'. $traced		.'</td>';
						}
						$html .= '<td>'. $html_par		.'</td>';
					$html .= '</tr>';
				}
				$html .= '</tbody>';
				$html .= '</table>';
			} // fine if trovati risultati
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		
		$response = new Response();
		$response->setContent(json_encode(array('tabella' => $html,'totali' => $totali)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getLandingsSlugs(){
		try{
			$landingsSlugs = array();
			$em  = $this->getDoctrine()->getManager();
			$sql = "SELECT l.titoloLanding as titolo, l.slugLanding as slug, url 
					FROM landing l ORDER BY l.slugLanding ASC";
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($landingsSlugs = $stmt->fetchAll()) {
					return $landingsSlugs;
				}
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $this;
	}
	
	public function getIdCampagnaDaPixel(){
		try{
			$idCampagne = array();
			$em  = $this->getDoctrine()->getManager('pixel_man');
			$sql = "SELECT p.id_campagna 
					FROM pixel_trace p 
					WHERE id_campagna IS NOT NULL
					AND p.id_campagna !='' 
					GROUP BY p.id_campagna 
					ORDER BY p.id_campagna ASC";
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($idCampagne = $stmt->fetchAll()) {
					return $idCampagne;
				}
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $this;
	}
	
	public function getIdCampagnaDaClick(){
		try{
			$idCampagne = array();
			$em  = $this->getDoctrine()->getManager('pixel_man');
			$sql = "SELECT c.id_campagna 
					FROM click_trace c 
					WHERE id_campagna IS NOT NULL
					AND c.id_campagna !='' 
					GROUP BY c.id_campagna 
					ORDER BY c.id_campagna ASC";
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($idCampagne = $stmt->fetchAll()) {
					return $idCampagne;
				}
			}
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $this;
	}

	// RENDER FUNCTIONS	
	 public function pixelAction($message = null){
		$datepicker 	= $this->getDatePicker();
		$landingsSlugs 	= $this->getLandingsSlugs();
		$idCampagne 	= $this->getIdCampagnaDaPixel();
		$idCampagneClick= $this->getIdCampagnaDaClick();
		
		return $this->render('tracciamento_pixel.html.twig', array(
           'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
           'landingsSlugs'  => $landingsSlugs,
           'idCampagne'  	=> $idCampagne,
           'idCampagneClick'=> $idCampagneClick,
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
	
	// AJAX
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