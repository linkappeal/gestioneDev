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
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Landing as Landing;
use AppBundle\Entity\Cliente as Cliente;
use AppBundle\Entity\A_landing_cliente as LandingCampagna;
use AppBundle\Entity\Payout_gruppi as PayoutGruppi;
use AppBundle\Entity\Payout_ordine_cliente as PayoutOrdineCliente;
use AppBundle\Entity\Payout_ordine_fornitore as PayoutOrdineFornitore;
use AppBundle\Entity\Lead_uni_extra_fields as ExtraFields;
use AppBundle\Entity\Lead_uni_extra_values as ExtraValues;
use AppBundle\Entity\A_lead_extra_values as LeadExtraField;
use AppBundle\Entity\Ordini_storni as OrdiniStorni;
use AppBundle\Entity\Ordini_modificati as OrdiniModificati;
use AppBundle\Controller\landingScreenshot as ScreenShot;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CRUDOrdiniController extends Controller
{
	
	public $nospam='';
	public $esclusioni='';
	public $totali_righe = array(	'Cliente' 		=>'',
									'Campagna' 		=>'',
									'Tipo_payout' 	=>'',
									'lead_totali'	=> 0,
									'perc_trash' 	=>'',
									'lead_trash'	=> 0,
									'perc_effet' 	=>'',
									'lead_nette'	=> 0,
									'budget'		=> 0,
									'delta_budget'	=> 0,
									'payout'		=> '',
									'totale'		=> 0,
									);
	public $tableFooter 	= '';
	public $indice_riga 	= 0;
	public $OrdineOverride 	= false;
	// RENDER FUNCIONS 
	
	// render listato Clienti/Campagne
    public function clientiAction($message = null){
		$ordini 	= $this->getOrdiniCliente();
		
		return $this->render('listato_ordini_cliente.html.twig', array(
			'ordini'  		=> $ordini,
			//'message' 		=> $message,
        ), null); 
    }
	
	// render conteggi Clienti
	public function conteggiClientiAction($message = null){

		$datepicker 		= $this->getDatePicker();
		$clienti 			= $this->getDoctrine()->getRepository('AppBundle:Cliente')->findBy(array(), array('name' => 'ASC'));
		
		return $this->render('conteggi_clienti.html.twig', array(
		   'datepicker' 	=> $datepicker,
		   'clienti' 		=> $clienti,
		), null); 
	}
	// render pagina di aggiunta payout clienti
	public function creaAction($message = null){
		$clienti 			= $this->getDoctrine()->getRepository('AppBundle:Cliente')->findBy(array(), array('name' => 'ASC'));
		
		return $this->render('crea_ordini_cliente.html.twig', array(
			'clienti' 		=> $clienti,
		), null); 
	}
	
	// render pagina di modifica ordine payout clienti
	public function modificaAction(Request $request, $message = null){
		
		$ordine_id 				= $request->get('ordine_id');
		$ordine 				= $this->getOrderById($ordine_id);
		$clienti 				= $this->getDoctrine()->getRepository('AppBundle:Cliente')->find($ordine['cliente_id']);
		$cliente_nome 			= $clienti->getName();
		
		$landing_cliente_info	= $ordine['landing_cliente']; //$this->getLandingClienteByClienteId($ordine['cliente_id'], $ordine['id_landing_cliente']);
		//$landing_cliente		= $landing_cliente_info[0]['campagna']->getNomeOfferta() . ' - Landing: ' . $landing_cliente_info[0]['landing']->getSlugLanding();
		//$colonne 				= $this->getCPLColumns($landing_cliente_info[0]['dbCliente'], $landing_cliente_info[0]['mailCliente']);
		$landing_cliente		= $landing_cliente_info->getCampagna()->getNomeOfferta() . ' - Landing: ' . $landing_cliente_info->getLanding()->getSlugLanding();
		//$colonne 				= $this->getCPLColumns($landing_cliente_info->getDbCliente(), $landing_cliente_info->getMailCliente());
		
		
		$colonne = $this->getCPLColumns($landing_cliente_info->getDbCliente(), $landing_cliente_info->getMailCliente());
		$colonne_html_options = '';
		foreach($colonne as $colonna){
			$colonne_html_options .= '<option data-target="0" value="' . $colonna . '">'.$colonna.'</option>';
		}
		// dati extra 
		$colonne_html_options .= $this->getExtraFieldsOptions();
		// pixel columns
		$colonne_html_options .= $this->getPixelTraceOptions();
		
		
		$dominio 				= $landing_cliente_info->getDbCliente();
		$tabmail 				= $landing_cliente_info->getMailCliente();
		
		
		// gestione nuovi payout con serialize campo
		
		foreach($ordine['gruppi'] as $id_gruppo =>  $gruppo){
			foreach($gruppo['payouts'] as $key => $payout){
				if(!empty($payout->getCampo())){ // il campo anche se payout singolo è sempre 
					//echo $payout->getCampo();
					//die();
					$arrCampoPay = unserialize($payout->getCampo());
					$payout->setCampo($arrCampoPay);
					/*
					if(count($arrCampoPay)>1){
						$_campopay = '';
						$_valorepay = '';
						foreach($arrCampoPay as $campopay){
							$_campopay 	.= $campopay->nomecampo;
							$_valorepay .= $campopay->valorecampo;
						}
						$gruppo['payouts'][$key]['nomecampo'] 	= $_campopay;
						$gruppo['payouts'][$key]['valorecampo'] = $_valorepay;
						
					}else{
						$gruppo['payouts'][$key] = $arrCampoPay[0];
					
					}
					*/
				}
			}
			$ordine['gruppi'][$id_gruppo]['payouts'] = $gruppo['payouts'];
		}
		
		
		
		return $this->render('edit_ordini_cliente.html.twig', array(
			'csrf_token' 			=> $this->getCsrfToken('sonata.batch'),
			'ordine' 				=> $ordine  ,
			'cliente_nome' 			=> $cliente_nome ,
			'landing_cliente' 		=> $landing_cliente  ,
			'landing_cliente_info' 	=> $landing_cliente_info,
			//'landing_cliente_info' 	=> $landing_cliente_info[0]  ,
			'colonne'		 		=> $colonne_html_options  ,
			'dominio'		 		=> $dominio  ,
			'tabmail'		 		=> $tabmail  ,
		), null); 
	}
	
	
	
	// FINE RENDER FUNCTIONS -------------------------------->
	 
	
	public function getOrdiniCliente(){
		$ordini = array();
		$em 	= $this->getDoctrine()->getManager();
		$conn 	= $em->getConnection();
		if($conn){
			$sql_ordini = "select * from ordini_cliente order by data_creazione desc";
			$stmt 	= $em->getConnection()->prepare($sql_ordini);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($ordine = $stmt->fetch()) {
					
					$ordine['landing_cliente'] 	= $this->getLandingCliente($ordine['id_landing_cliente']);
					$ordine['gruppi'] 			= $this->getAllGruppiFromOrderId($ordine['id']);
					$ordine['payouts'] 			= $this->getPayoutFromGruppoId($ordine['id_gruppo']);

					$payouts = array();
					foreach($ordine['payouts'] as $payout){
						$payouts[] = $payout->getPayout();
					}
					$ordine['payout'] = implode(',',$payouts);
					if($ordine['landing_cliente']){
						$ordine['cliente'] 			= $ordine['landing_cliente']->getCliente();	
						$ordine['campagna'] 		= $ordine['landing_cliente']->getCampagna();
						$ordine['landing'] 			= $ordine['landing_cliente']->getLanding(); 
					}
					$ordini[] = $ordine;
				}
			}
		}
		return $ordini;
	}
	
	public function getLandingCliente($id_landing_cliente = ''){
			$LandingCliente = array();
		try{

			if(!empty($id_landing_cliente)){
				$LandingCliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($id_landing_cliente);
			}else{ // se non è settato l'id le prendo tutte
				$LandingCliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->findAll();
			}
			
		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
		}
		return $LandingCliente;
	}
	
	public function getLandingClienteByClienteId($id_cliente = '', $id_landing_cliente = ''){
		try{
			$allLandingInfo = array();
			$LandingCliente = array();
			$em  = $this->getDoctrine()->getManager();

			$sql = "SELECT l.* 
					FROM a_landing_cliente l";
			if(!empty($id_cliente)){
				$sql .=  " where l.cliente_id = ".$id_cliente;
			}
			if(!empty($id_landing_cliente)){
				$sql .=  " AND l.id = ".$id_landing_cliente;
			}
			$sql .= " ORDER BY l.cliente_id ASC";
			$stmt 	= $em->getConnection()->prepare($sql);
			$result = $stmt->execute();
			
			$LandingCliente = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach($LandingCliente as $landing){
				$_landing 				= $landing;
				$_landing['campagna'] 	= $this->getDoctrine()->getRepository('AppBundle:Campagna')->find($landing['campagna_id']);
				$_landing['landing'] 	= $this->getDoctrine()->getRepository('AppBundle:Landing')->find($landing['landing_id']);
				$allLandingInfo[] 		= $_landing;
			}

		}catch(PDOException $e){
			echo "Error: " . $e->getMessage();
			}
		return $allLandingInfo;
	}
	
	
	public function getCPLColumns($dominio= '', $tabmail = ''){
		//$cpl_conn = $this->getConnectionCpl($dominio);
		$conn 	= $this->getDoctrine()->getManager()->getConnection();
		if($conn){		
			// recupero tutte le colonne
			$stmt = $conn->prepare("DESCRIBE lead_uni");
			$stmt->execute();
			$table_fields = $stmt->fetchAll(\PDO::FETCH_COLUMN);
			
		}
		return $table_fields;
	}
	
	private function getCampagnaTotale($campagna, $mese='',$anno='',$campo_valore = array()){
		try{
			$totali = 0;
			$array_values = array();
			$sql_tot = "select count(*) as tot from lead_uni lu";
			$sql_tot .= " WHERE lu.source_db = :source_db AND lu.source_tbl = :source_tbl";
			$sql_tot .= " AND MONTH(lu.data) = :mese AND YEAR(lu.data) = :anno ";
			
			if(!empty($campo_valore)){
				$campo = $campo_valore['campo'];
				$valore = $campo_valore['valore'];
				$valore = str_replace('*','%',$valore); 
				$sql_tot .= " AND ".$campo." like :valore_campo ";
				$array_values['valore_campo'] 	=  	$valore;
			}
			
				$array_values['mese'] 			=  	$mese;
				$array_values['anno'] 			=  	$anno;
				$array_values['source_db'] 		=  	$campagna['dominio'];
				$array_values['source_tbl'] 	=  	$campagna['mail'];
			
			
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql_tot);
			$stmt->execute($array_values);
		
			if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
				while ($row = $stmt->fetch()) {
					$totali = $row['tot'];
				}
			}
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	

	
	
	public function getLandingCampagneFromCliente($cliente_id){
		$landingCliente =  $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->findBy(array('cliente' => $cliente_id));
		return $landingCliente;
	}
	
	public function getAllCampaignInfo($mese, $anno, $cliente_id){
		$valori = array();
		$campagneInfo = array();
		
		
		
		$sql = "select pg.id_ordine from payout_gruppo_ordine pg
					inner join payout_gruppi g
					on pg.id_gruppo = g.id
					right join ordini_cliente o
					on pg.id_ordine =  o.id
					where pg.target = 0
					AND (100*".$anno.")+".$mese."
					between 
					YEAR(g.data_inizio)*100 + MONTH(g.data_inizio)  
					AND 
					YEAR(IFNULL(g.data_fine,NOW()))*100 + MONTH(IFNULL(g.data_fine,NOW())) "; 
		if(!empty($cliente_id) && strtolower($cliente_id)!='all'){
			$sql .= " AND o.cliente_id = ? ";
			$valori[] = $cliente_id;
		}
		$sql .= " group by pg.id_ordine
					order by g.data_inizio asc";
		
		/*
		$sql = "SELECT 
				alc.id as id_landing_cliente,
				alc.cliente_id as cliente_id,
				alc.campagna_id as campagna_id,
				alc.dbCliente as dbCliente, 
				alc.mailCliente as mailCliente, 
				alc.dbCliente as dominio, 
				alc.mailCliente as mail
				FROM a_landing_cliente alc"; 
				if(!empty($cliente_id) && strtolower($cliente_id)!='all'){
					$sql .= " WHERE alc.cliente_id = ?";
					$valori = array($cliente_id);
					$sql .= " AND alc.clienteAttivo = 1 ";
				}else{
					$sql .= " WHERE alc.clienteAttivo = 1 ";
				}
				$sql .= " ORDER BY alc.cliente_id ASC, alc.data_creazione DESC";
		*/	
		$eman   	= $this->getDoctrine()->getManager();
		$stmt_cmp 	= $eman->getConnection()->prepare($sql);
		if($stmt_cmp->execute($valori)){
			$campagneInfo = $stmt_cmp->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $campagneInfo;
	}

	// AJAX FUNCTIONS
	
	public function getLandingCampagneFromClienteAction(Request $request){
		$cliente_id = $request->get('cliente_id');
		$mostra_tutte = $request->get('mostra_tutte');
		$html = '';
		if(!isset($mostra_tutte) || empty($mostra_tutte)){
			$html .= '<option value="all">Tutte le campagne</option>';
		}
		if(!empty($cliente_id) && strtolower($cliente_id!='all')){
			$landingCampagne =  $this->getLandingClienteByClienteId($cliente_id);
			
			if(count($landingCampagne)>0){
				foreach($landingCampagne as $landing){
					$html .='<option value="'.$landing['id'] .'" data-dominio="'.$landing['dbCliente'].'" data-tabmail="'.$landing['mailCliente'].'" data-campagna_id="'. $landing['campagna']->getId() .'">'
											. $landing['campagna']->getNomeOfferta()
											.' - (Landing: '. $landing['landing']->getSlugLanding(). ')</option>';
				}
			}else{
				$html .='<option value="">Non ci sono campagne per questo cliente</option>';
			}
		}

		$response = new Response();
		$response->setContent(json_encode($html));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	public function getAffiliati($id_fornitore){
		$affiliati = array();
		if(!empty($id_fornitore)){
			$em   = $this->getDoctrine()->getManager();
			$sql = "SELECT * FROM affiliati a WHERE a.id IN (SELECT o.id_affiliato FROM ordini_fornitore o where o.id_fornitore = ?) ORDER BY a.nome";
			$stmt = $em->getConnection()->prepare($sql);
			if($stmt->execute(array($id_fornitore))){
				if($stmt->rowCount()>0){
					$affiliati = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				}
			}
		}
		return $affiliati;
	}
	
	
	public function getAffilitatiFornitoreAction(Request $request){
		$id_fornitore = $request->get('id_fornitore');
		
		$affiliati = $this->getAffiliati($id_fornitore);
		
		$html = '';
		if(!empty($affiliati)){
			$html .= '<option value="">Seleziona un affiliato</option>';
			$html .= '<option value="all">Tutti gli affiliati</option>';
			foreach($affiliati as $affiliato){
				$html .='<option value="'.$affiliato['id'] .'" 
												data-refid="'.$affiliato['refid'].'">'
												. $affiliato['nome']
												.' - ('.$affiliato['refid'].')'.
							'</option>';
			}
			
			
		}
		
		$response = new Response();
		$response->setContent(json_encode($html));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	public function getCampagneByFornitoreAction(Request $request){
		$id_fornitore = $request->get('id_fornitore');
		$mostra_tutte = $request->get('mostra_tutte');
		
		$html = '';
		if(!isset($mostra_tutte) || empty($mostra_tutte)){
			$html .= '<option value="all">Tutte le campagne</option>';
		}
		if(!empty($id_fornitore)){
			$landingCampagne = $this->getCampagneByFornitore($id_fornitore);
			if(count($landingCampagne)>0){
				foreach($landingCampagne as $landing){
				
				if($id_fornitore!='all'){
						$id_landing_cliente = $landing['id_landing_cliente'];
					}else{
						$id_landing_cliente = $landing['id'];
					}
					
					$LandingCliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($id_landing_cliente);
					$html .='<option value="'.$LandingCliente->getId() .'" 
												data-dominio="'.$LandingCliente->getDbCliente() .'" 
												data-tabmail="'.$LandingCliente->getMailCliente() .'" 
												data-campagna_id="'. $LandingCliente->getCampagna()->getId() .'">'
												. $LandingCliente->getCampagna()->getNomeOfferta()
												.' - '. $LandingCliente->getCliente()->getName() 
												.' - (Landing: '. $LandingCliente->getLanding()->getSlugLanding(). ')'. 
							'</option>';
				}
			}else{
				$html .='<option value="">Non ci sono campagne per questo cliente</option>';
			}
		}
		$response = new Response();
		$response->setContent(json_encode($html));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getCampagneByFornitoreAndAffiliatoAction(Request $request){
		$id_fornitore = $request->get('id_fornitore');
		$id_affiliato = $request->get('id_affiliato');
		$mostra_tutte = $request->get('mostra_tutte');
		
		$html = '';
		if(!isset($mostra_tutte) || empty($mostra_tutte)){
			$html .= '<option value="all">Tutte le campagne</option>';
		}
		if(!empty($id_fornitore)){
			$landingCampagne = $this->getCampagneByFornitoreAndAffiliato($id_fornitore,$id_affiliato);
			if(count($landingCampagne)>0){
				foreach($landingCampagne as $landing){
				
				if($id_fornitore!='all'){
						$id_landing_cliente = $landing['id_landing_cliente'];
					}else{
						$id_landing_cliente = $landing['id'];
					}
					
					$LandingCliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($id_landing_cliente);
					$html .='<option value="'.$LandingCliente->getId() .'" 
												data-dominio="'.$LandingCliente->getDbCliente() .'" 
												data-tabmail="'.$LandingCliente->getMailCliente() .'" 
												data-campagna_id="'. $LandingCliente->getCampagna()->getId() .'">'
												. $LandingCliente->getCampagna()->getNomeOfferta()
												.' - (Landing: '. $LandingCliente->getLanding()->getSlugLanding(). 
							'</option>';
				}
			}else{
				$html .='<option value="">Non ci sono campagne per questo affiliato</option>';
			}
		}
		$response = new Response();
		$response->setContent(json_encode($html));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	/**
	 * La funzione preleva tutte le campagne associate ad uno specifico fornitore nella tabella 
	 * degli ordini fornitori
	 *
	 */
	public function getCampagneByFornitore($id_fornitore){
		$landings_cliente = array();
		$sql_value = array();
		
		if(!empty($id_fornitore)){
			$em   = $this->getDoctrine()->getManager();
			
			if($id_fornitore!='all'){
				$sql  = "SELECT * FROM ordini_fornitore ";
				$sql  .= " WHERE id_fornitore = ? ";
				$sql_value = array($id_fornitore);
			
			}else{
				$sql  = "SELECT * FROM a_landing_cliente group by campagna_id";

			}
			$sql .= " ORDER BY data_creazione desc";
			$stmt = $em->getConnection()->prepare($sql);
			if($stmt->execute($sql_value)){
				if($stmt->rowCount()>0){
					$landings_cliente = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				}
			}
		}
		return $landings_cliente;
		
	}
	
	/**
	 * La funzione preleva tutte le campagne associate ad uno specifico fornitore 
	 * ed ad un affiliato nella tabella degli ordini fornitori
	 *
	 */
	public function getCampagneByFornitoreAndAffiliato($id_fornitore, $id_affiliato){
		$landings_cliente = array();
		$sql_value = array();
		
		if(!empty($id_fornitore) && !empty($id_affiliato)){
			$em   = $this->getDoctrine()->getManager();
			
			if($id_fornitore!='all'){
				$sql  = "SELECT * FROM ordini_fornitore ";
				$sql  .= " WHERE id_fornitore = ? ";
				$sql  .= " AND id_affiliato = ? ";
				$sql_value = array($id_fornitore, $id_affiliato);
			
			}else{
				$sql  = "SELECT * FROM a_landing_cliente WHERE id_fornitore = ? group by campagna_id";
				$sql_value = array($id_fornitore);

			}
			$sql .= " ORDER BY data_creazione desc";
			$stmt = $em->getConnection()->prepare($sql);
			if($stmt->execute($sql_value)){
				if($stmt->rowCount()>0){
					$landings_cliente = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				}
			}
		}
		return $landings_cliente;
		
	}
	
	
	public function getLandingScreenshotAction(Request $request){
		$img = '';
		$url = $request->get('urlsite');
		$screenshot = new ScreenShot($url);
		$img = $screenshot->scatta()->getImg();
		
		$response = new Response();
		$response->setContent(json_encode(array('status' => true, 'img' => $img)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	/**
	* La funzione elimina l'ordine del cliente seguendo gli step:
	* 1 - Eliminazione ordine dalla tabella ordini_cliente
	* 2 - Recupero degli id _gruppo e id payout dalla tabella payout_gruppo_ordine dove target= 0 //Clienti 
	* 3 - Eliminazione dei gruppi dalla tabella payout gruppi secondo gli id prelevati alla step 2
	* 4 - Eliminazione dei payout dalla tabella payout_ordine_cliente secondo gli id prelevati allo step 2
	* 5 - Eliminazione delle associazioni dalla tabella payout_gruppo_ordine
	* pars Request request 
	* Return http response json
	*/
	public function deleteOrderAction(Request $request){
		$id 	= $request->get('id');
		$target = '0'; 
		$result = false;
		if(!empty($id)){
			// step 1 - eliminazione ordine 
			$em  		= $this->getDoctrine()->getManager();
			$sql_base 	= "DELETE FROM ordini_cliente WHERE id=?";
			$stmt 		= $em->getConnection()->prepare($sql_base);
			if($stmt->execute(array($id))){
				
				//step 2 - recupo gli id_gruppo e id payout
				//$payouts = $this->getPayoutFromGruppoId($id_gruppo)
				$sql_get_ids = "select id, id_gruppo, id_payout from payout_gruppo_ordine
								WHERE target=0 AND id_ordine = ?";
				
				$stmt_get 	= $em->getConnection()->prepare($sql_get_ids);
				$gruppi = $this->getAllGruppiFromOrderId($id);
				if($stmt_get->execute(array($id))){
					while($row = $stmt_get->fetch()){
						$_em  	= $this->getDoctrine()->getManager();

						$payout = $this->getDoctrine()->getRepository('AppBundle:Payout_ordine_cliente')->find($row['id_payout']);
						$gruppo = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')		->find($row['id_gruppo']);
						
						// step 3 e 4 - rimuovo payout e gruppo
						if($payout){
							$_em->remove($payout);
						}
						if($gruppo){
							$_em->remove($gruppo);
						}
						$_em->flush();
					}
				}
				// step 5 rimuovo tutte le righe con id_ordine dalla tabella payout_gruppo_ordine
				$sql_del_go 	= "DELETE FROM payout_gruppo_ordine WHERE id_ordine=? and target=0";
				$stmt_del_go 	= $em->getConnection()->prepare($sql_del_go);
				if($stmt_del_go->execute(array($id))){
					$result = true;
				}
				
			}
		}
		$response = new Response();
		$response->setContent(json_encode(array('eliminato' => $result)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function removeSingleGroupAction(Request $request){
		$id_gruppo = $request->get('id_gruppo');
		$gruppo = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')->find($id_gruppo);
		$em 	= $this->getDoctrine()->getManager();
		$em->remove($gruppo);
		$em->flush();
		
		// elimino i payout associati
		$sql 	= "DELETE FROM payout_ordine_cliente WHERE id IN (SELECT id_payout FROM payout_gruppo_ordine WHERE id_gruppo=?)";
		$stmt 	= $em->getConnection()->prepare($sql);
		$stmt->execute(array($id_gruppo));
		// elimino le associazioni 
		$sql 	= "DELETE FROM payout_gruppo_ordine WHERE id_gruppo=?";
		$stmt 	= $em->getConnection()->prepare($sql);
		$stmt->execute(array($id_gruppo));
				
		
		$response = new Response();
		$response->setContent(json_encode(array('eliminato' => true)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;

	}
	
	public function updateTableAfterStorniAction(Request $request){
		$lead_totali 	= $request->get('lead_totali');
		$tetto_trash 	= $request->get('tetto_trash');
		$budget 		= $request->get('budget');
		$valore_payout 	= $request->get('valore_payout');
		$codes 			= $request->get('codes');
		
		$id_ordine		= $request->get('id_ordine');
		$id_payout		= $request->get('id_payout');
		$target			= $request->get('target');
		
		$mese			= $request->get('mese');
		$anno			= $request->get('anno');
		
		/*
			//nospam ed esclusioni
			$nospam			= $request->get('nospam');
			$esclusioni		= $request->get('esclusioni');
			
			$this->nospam 	= $nospam;
			
			if(!empty($esclusioni)){
				$this->esclusioni = $esclusioni;
			}
		*/
		if(!empty($codes)){
			$codes = json_decode($codes);
			$codes = serialize($codes);
		}
		
		// insert / update delle lead stornate
		
		$em 	= $this->getDoctrine()->getManager();
		$storno = $em->getRepository('AppBundle:Ordini_storni')->findOneBy([
																		'id_ordine' 	=> $id_ordine,
																		'id_payout' 	=> $id_payout,
																		'ordine_mese' 	=> $mese,
																		'ordine_anno' 	=> $anno,
																		'target' 		=> $target,
																		]);
		if(empty($storno)){ // storno non ancora salvato
			$storno = new OrdiniStorni();
		}
		$ora = new \DateTime("now");
		$storno->setIdOrdine($id_ordine)->setIdPayout($id_payout)
						->setTarget($target)
						->setLeadsCode($codes)
						->setOrdineMese($mese)
						->setOrdineAnno($anno)
						->setCreationDate($ora);
		$em->persist($storno);
		$em->flush();
		
		// arrotondo: 
		// se target = 0 (empty) allora è un cliente e arrotondo per difetto
		// se target = 1 !empty allora è un fornitore e arrotondo per eccesso
		$arrotonda = empty($target) ? 'difetto' : 'eccesso';
		$totali = $this->calcolaPays($lead_totali,$tetto_trash,$budget,$valore_payout,$arrotonda);

		$response = new Response();
		$response->setContent(json_encode($totali));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	/*
	* La funzione che restituisce i totali calcolati dopo una modifica manuale dell'ordine
	*
	*/
	public function updateTableAfterLeadAddAction(Request $request){
		$base_lead_totali 		= $request->get('base_lead_totali'); // le lead lorde totali inserite a mano
		$base_lead_trash 		= $request->get('base_lead_trash'); // le lead trash totali inserite a mano
		$tetto_trash 			= $request->get('tetto_trash');
		$budget 				= $request->get('budget');
		$valore_payout 			= $request->get('valore_payout');
		$tipo		 			= $request->get('tipo'); // questo dato può essere vuoto o "Trash", in base al suo valore calcolo i totali
		
		$lead_totali_modificate	= $request->get('lead_totali_modificate'); // le lead base che si stanno inviando (può essere la base delle lorde o del trash, sarà il valore di "$tipo" a definire quale dato modificare
		
		$id_ordine				= $request->get('id_ordine');
		$id_payout				= $request->get('id_payout');
		$target					= $request->get('target');
			
		// arrotondamento calcolo dei pay: 
		// se target = 0 (empty) allora è un cliente e arrotondo per difetto
		// se target = 1 !empty allora è un fornitore e arrotondo per eccesso
		$arrotonda 				= empty($target) ? 'difetto' : 'eccesso';
		
		$mese					= $request->get('mese');
		$anno					= $request->get('anno');
		
	
		$em 	= $this->getDoctrine()->getManager();
		$OrdineModificato = $em->getRepository('AppBundle:Ordini_modificati')->findOneBy([
																		'id_ordine' 	=> $id_ordine,
																		'id_payout' 	=> $id_payout,
																		'ordine_mese' 	=> $mese,
																		'ordine_anno' 	=> $anno,
																		'target' 		=> $target,
																		]);
																	
		$differenza_lorde = 0;
		if(empty($OrdineModificato)){ // OrdineModificato non ancora salvato
			// SE L'ORDINE NON ESISTE, VADO A INIZIALIZZARLO CON I TOTALI BASE DI LORDE E TRASH
			$OrdineModificato = new OrdiniModificati();
			$OrdineModificato->setBaseLorde($base_lead_totali);
			$OrdineModificato->setBaseTrash($base_lead_trash);
			
		}else{
			// SE L'ORDINE MODIFICATO E' GIA' SETTATO, PRELEVO I VALORI BASE
			$base_lead_totali = $OrdineModificato->getBaseLorde();
			$base_lead_trash  = $OrdineModificato->getBaseTrash();
			$differenza_lorde = $OrdineModificato->getDifferenzaLorde();;
			
		}

		// GESTIONE DELLA DIFFERENZA E SALVATAGGIO IN DB
		// se il tipo è trash allora modifico solo i dati relativi al trash
		
		
		if($tipo=='Trash'){ 
			// TRASH
			// LA DIFFERENZA INVIATA TRAMITE POST è PER IL TRASH
			
			$trash_modificato = $lead_totali_modificate;
			$OrdineModificato->setTrashModificato($trash_modificato);
			$differenza = $lead_totali_modificate-$base_lead_trash;
			$OrdineModificato->setDifferenzaTrash($differenza);
			
			
			//$totale_lead_trash = $lead_totali_modificate; 
			
			// Mi ricalcolo le lorde per eseguire i calcoli con il nuovo trash
			$totale_lead_lorde = $base_lead_totali+$differenza_lorde; 
			
			$totali = $this->calcolaPaysSuModifica($totale_lead_lorde,$tetto_trash,$budget,$valore_payout,$trash_modificato, $arrotonda);
		}else{	
			// LORDE
			// LA DIFFERENZA INVIATA TRAMITE POST è PER LE LORDE
			$differenza = $lead_totali_modificate-$base_lead_totali;
			$OrdineModificato->setDifferenzaLorde($differenza);
			// ricalcolo i totali in base al valore delle lead lorde inserito a mano
			$totale_lead_lorde = $lead_totali_modificate; 
			$totali = $this->calcolaPaysSuModifica($totale_lead_lorde,$tetto_trash,$budget,$valore_payout,'', $arrotonda);
			
			// aggiorno la differenza delle nuove lead trash con il nuovo trash calcolato
			//$differenza_trash = $totali->lead_trash-$base_lead_trash;
			//$OrdineModificato->setDifferenzaTrash($differenza_trash);
			
		}
	
		
		$ora = new \DateTime("now");
		$OrdineModificato->setIdOrdine($id_ordine)->setIdPayout($id_payout)
						->setTarget($target)
						->setOrdineMese($mese)
						->setOrdineAnno($anno)
						->setCreationDate($ora);
		$em->persist($OrdineModificato);
		$em->flush();
		
		$response = new Response();
		$response->setContent(json_encode($totali));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	// funzione principale conteggio CLIENTE
	public function conteggiaClienteAction(Request $request){

		$resultsRows 			= array();
		$campagna 				= array();
		$id_landing_cliente		= $request->get('id_landing_cliente');
		$campagna['id']			= $request->get('campagna_id');
		$campagna['dominio']	= $request->get('campagna_dominio');
		$campagna['mail'] 		= $request->get('campagna_mail');
		$cliente_id 			= $request->get('cliente_id');
		$mese 					= $request->get('mese');
		$anno 					= $request->get('anno');
		
		/*
		$nospam					= $request->get('nospam');
		$esclusioni				= $request->get('esclusioni');
		$this->esclusioni 		= $esclusioni;
		$this->nospam 			= $nospam;
		*/
			
		$this->indice_riga		= 0;
		// render header tabella
		$html = '';
		$html .='<table id="listatoConteggi" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
						<thead>
							<td>Cliente</td>
							<td>Campagna</td>
							<td>Tipo Payout</td>
							<td>Tot Lead</td>
							<td>% Tetto Trash</td>
							<td>Lead Trash</td>
							<td>% Eff. Lead Trash</td>
							<td>Lead Nette</td>
							<td>Budget</td>
							<td>Delta Budget</td>
							<td>Payout(€)</td>
							<td>Totale(€)</td>
							<td>Download</td>
							<td>Storni</td>
						</thead>
					<tbody>';
		
			// tutte le campagne
		if(strtolower($id_landing_cliente)=='all'){
			
			$orders_ids = $this->getAllCampaignInfo($mese, $anno, $cliente_id);
			
			if(is_array($orders_ids)){
				foreach($orders_ids as $order_ids){
					$order_id = $order_ids['id_ordine']	;
					$ordine	  = $this->getOrderById($order_id);
					
					//$ordine 	= $this->getOrderByIdLandingCliente($campaignInfo['id_landing_cliente']); /// $this->getOrdineInfo($campaignInfo['id_landing_cliente']);
					if($ordine){ // se esiste l'ordine per la campagna iterata
						$html .= $this->generateTableRow($ordine, $mese, $anno);
					}
				}
			}
		}else{
			// è stata selezionata solo una campagna
			$ordine 	= $this->getOrderByIdLandingCliente($id_landing_cliente); //$this->getOrdineInfo($id_landing_cliente);
			if($ordine){
				$html 		   .= $this->generateTableRow($ordine, $mese, $anno);
			}
		}
		$html .='</tbody></table>';
		
		
		$response = new Response();
		$response->setContent(json_encode(array(
												'html' 	 => $html, 
												'totali' => $this->totali_righe, 
												'footer' => $this->tableFooter,
											   )
											)
								);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	
	
	
	
	
	public function getStornoTableAction(Request $request){	
			
			
			$source_db		= $request->get('dominio');
			$source_tbl 	= $request->get('mail'); 
			$mese			= $request->get('mese'); 
			$anno			= $request->get('anno'); 
			$data_min		= $request->get('data_min'); 
			$data_max		= $request->get('data_max'); 
			$indice_riga	= $request->get('indice');
			$media			= $request->get('media');
			$falsemedia		= $request->get('falsemedia');
			$id_campagna	= $request->get('id_campagna');
			$id_ordine		= $request->get('id_ordine');
			$id_payout		= $request->get('id_payout');
			
			$tabella		= 'lead_uni';
			$conn 			= $this->getDoctrine()->getManager()->getConnection();
			
			$target 		= '0'; 
				
			$payout 		= $this->getDoctrine()->getRepository('AppBundle:Payout_ordine_cliente')->find($id_payout);
			$payout_campo 	= $payout->getCampo();
			
			$query 			= $this->generateQueryForExport($tabella, $source_db, $source_tbl, $media, $falsemedia, $mese, $anno, $data_min, $data_max, $payout_campo);
			
		
			$table_head 		= '';
			$table_row 			= '';
			$table_footer 		= '';
			$stornate			= array();
			
			if($conn){
				// recupero tutte le colonne
				$stmt = $conn->prepare($query);
				$stmt->execute();
				if($stmt->rowCount()>0){
					
					$em 	= $this->getDoctrine()->getManager();
					$storno = $em->getRepository('AppBundle:Ordini_storni')->findOneBy([
																					'id_ordine'		=> $id_ordine,
																					'id_payout'		=> $id_payout,
																					'ordine_mese' 	=> $mese,
																					'ordine_anno' 	=> $anno,
																					'target' 		=> $target,
																					]);
					if(!empty($storno)){ // storno non ancora salvato
						$leads_code = $storno->getLeadsCode();
						if(!empty($leads_code)){
							$stornate = unserialize($leads_code);
						}
					}

					$table_head = $this->getTableStorniHead($indice_riga, $target, $mese, $anno, $id_ordine, $id_payout);
					
					$indice_tr = 0;
					while($row = $stmt->fetch()){
						
						$checked = 'checked="checked"';
						if(in_array($row['code'],$stornate)){
							$checked = '';
						}
						
						$table_row .= '<tr data-indice="'.$indice_tr.'">';
						$table_row .= '<td> <input type="checkbox" name="leadcode[]" id="chkbox-'.$indice_tr.'" class="chkbox" '. $checked . ' value="' . $row['code'] . '" /> </td>';
						$table_row .= '<td>'.$row['nome']			.'</td>';
						$table_row .= '<td>'.$row['cognome']		.'</td>';
						$table_row .= '<td>'.$row['cellulare']		.'</td>';
						$table_row .= '<td>'.$row['email']			.'</td>';
						$table_row .= '<td>'.$row['indirizzo_ip']	.'</td>';
						$table_row .= '<td>'.$row['code']			.'</td>';
						$table_row .= '<td>'.$row['data']			.'</td>';
						$table_row .= '</tr>';
						
						$indice_tr++;
					}

					$table_footer .= '</tbody>';
					$table_footer .= '</table>';
				}
			}
			
			$storni_list_block_btn = '<button class="btn btn-primary" onclick="stornaBulk('.$indice_riga.')">Storna</button>';
			$storni_list_block 	= '<div class="row">
									<div class="col-md-10"><h4>Inserisci i codici da stornare</h4></div>
									<div class="col-md-1 close-table-storni pull-right" onclick="closeTableStorni(\''. $indice_riga .'\')"><i class="fa fa-times"></i></div>
									<div class="col-md-7"><textarea style="width:100%" id="list_bulk_'.$indice_riga.'"></textarea></div>
									<div class="col-md-2">'.$storni_list_block_btn.'</div>
									</div>';
			
			$table_html = $storni_list_block . $table_head . $table_row . $table_footer;
			
			
			// l'indice viene passato alla funzione tramite get da "this" chiamata ajax
			$footer_box = '<button class="btn btn-primary" onclick="updateTableAfterStorni('.$indice_riga.')">Ricalcola</button>';
			
			
			$response = new Response();
			$response->setContent(json_encode(array('table' => $table_html,'footer_box' => $footer_box )));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
	}
	
	public function getStornoTableIndirettaAction(Request $request){	
			
			
			$id_campagna	= $request->get('id_campagna');
			$id_ordine		= $request->get('id_ordine');
			$id_payout		= $request->get('id_payout');
			$mese			= $request->get('mese'); 
			$anno			= $request->get('anno'); 
			$data_min		= $request->get('data_min'); 
			$data_max		= $request->get('data_max'); 
			$indice_riga	= $request->get('indice');
			$media			= $request->get('media');
			$falsemedia		= $request->get('falsemedia');
			$affiliatoRefid = $request->get('refid');
			
			$payout 		= $this->getDoctrine()->getRepository('AppBundle:Payout_ordine_cliente')->find($id_payout);
			$payout_campo	= $payout->getCampo();
			$indiretta		= '1';
			$tabella		= 'pixel_trace';
			$conn 			= $this->getDoctrine()->getManager('pixel_man')->getConnection();
			
			$target 		= '0'; 
			
			$query 			= $this->generateQueryForExportIndiretta($tabella, $id_campagna, $media, $falsemedia, $affiliatoRefid, $mese, $anno, $data_min, $data_max, $payout_campo);
			
			$table_head 	= '';
			$table_row 		= '';
			$table_footer 	= '';
			$stornate		= array();
			
			if($conn){
				// recupero tutte le colonne
				$stmt = $conn->prepare($query);
				$stmt->execute();
				if($stmt->rowCount()>0){
					
					$em 	= $this->getDoctrine()->getManager();
					$storno = $em->getRepository('AppBundle:Ordini_storni')->findOneBy([
																					'id_ordine'		=> $id_ordine,
																					'id_payout'		=> $id_payout,
																					'ordine_mese' 	=> $mese,
																					'ordine_anno' 	=> $anno,
																					'target' 		=> $target,
																					]);
					if(!empty($storno)){ // storno non ancora salvato
						$leads_code = $storno->getLeadsCode();
						if(!empty($leads_code)){
							$stornate = unserialize($leads_code);
						}
					}

					$table_head = $this->getTableStorniHead($indice_riga, $target, $mese, $anno, $id_ordine, $id_payout, $indiretta);
					
					$indice_tr = 0;
					while($row = $stmt->fetch()){
						
						$checked = 'checked="checked"';
						if(in_array($row['code'],$stornate)){
							$checked = '';
						}
						
						$table_row .= '<tr data-indice="'.$indice_tr.'">';
						$table_row .= '<td> <input type="checkbox" name="leadcode[]" id="chkbox-'.$indice_tr.'" class="chkbox" '. $checked . ' value="' . $row['code'] . '" /> </td>';
						$table_row .= '<td>'.$row['id_agenzia']		.'</td>';
						$table_row .= '<td>'.$row['media']			.'</td>';
						$table_row .= '<td>'.$row['ip']				.'</td>';
						$table_row .= '<td>'.$row['code']			.'</td>';
						$table_row .= '<td>'.$row['dt']				.'</td>';
						$table_row .= '</tr>';
						
						$indice_tr++;
					}

					$table_footer .= '</tbody>';
					$table_footer .= '</table>';
				}
			}
			
			$storni_list_block_btn = '<button class="btn btn-primary" onclick="stornaBulk('.$indice_riga.')">Storna</button>';
			$storni_list_block 	= '<div class="row">
									<div class="col-md-10"><h4>Inserisci i codici da stornare</h4></div>
									<div class="col-md-1 close-table-storni pull-right" onclick="closeTableStorni(\''. $indice_riga .'\')"><i class="fa fa-times"></i></div>
									<div class="col-md-7"><textarea style="width:100%" id="list_bulk_'.$indice_riga.'"></textarea></div>
									<div class="col-md-2">'.$storni_list_block_btn.'</div>
									</div>';
			
			$table_html = $storni_list_block . $table_head . $table_row . $table_footer;
			
			// l'indice viene passato alla funzione tramite get da "this" chiamata ajax
			$footer_box = '<button class="btn btn-primary" onclick="updateTableAfterStorni('.$indice_riga.')">Ricalcola</button>';
			
			$response = new Response();
			$response->setContent(json_encode(array('table' => $table_html,'footer_box' => $footer_box )));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
	}
	
	
	public function getTableStorniHead($indice_riga, $target, $mese, $anno, $id_ordine, $id_payout, $indiretta = ''){
		$table_head = '<table id="table_storni_box-' . $indice_riga . '" data-target="'.$target.'" data-mese="'.$mese.'" data-anno="'.$anno.'" data-ordine="'.$id_ordine.'" data-payout="'.$id_payout.'" class="table table-striped table-bordered table-hover tabella_storni"  width="100%">';
		
		if(empty($indiretta)){
			$table_head .= '<thead>
							<tr>
								<th>Sel			</th>
								<th>Nome		</th>
								<th>Cognome		</th>
								<th>Cellulare	</th>
								<th>Email		</th>
								<th>IP			</th>
								<th>Codice		</th>
								<th>Data		</th>
							</tr>
						</thead>
						<tbody>';
		}else{
			$table_head .= '<thead>
						<tr>
							<th>Sel			</th>
							<th>Affiliato	</th>
							<th>Media		</th>
							<th>IP			</th>
							<th>Codice		</th>
							<th>Data		</th>
						</tr>
					</thead>
					<tbody>';
		}
		return $table_head;
	}
	
	public function getStornoFornitoriTableAction(Request $request){	
			
			$tabella			= 'lead_uni';
				
			$id_landing_cliente = $request->get('id_landing_cliente'); 
			$mese				= $request->get('mese'); 
			$anno				= $request->get('anno'); 
			$data_min			= $request->get('data_min'); 
			$data_max			= $request->get('data_max'); 
			$payout_campo 		= $request->get('campo');
			$payout_valore		= $request->get('valore_campo');
			$indice_riga		= $request->get('indice');
			$media				= $request->get('media');
			$falsemedia			= $request->get('falsemedia');
			$affiliatoRefid		= $request->get('refid');
			$id_ordine			= $request->get('id_ordine');
			$id_payout			= $request->get('id_payout');
			$target 			= '1';
			
			// prelevo dalla sessione i codici ad filtrare nella query
			$session = new Session();
			$_codes = $session->get('sess_codes');
			$codes 	= !empty($_codes) ? $_codes : '';
			
			// prelevo la landing_cliente 
			$LandingCliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($id_landing_cliente);
			
			$id_campagna 	= $LandingCliente->getLanding()->getSlugLanding();
			$indiretta 		= $LandingCliente->getIndiretta();
			$campagna_id 	= $LandingCliente->getCampagna()->getId();
						
			$table_head 	= '';
			$table_row 		= '';
			$table_footer 	= '';
			$stornate 		= array(); 
					
			// HEADER TABELLA
			$table_head = $this->getTableStorniHead($indice_riga, $target, $mese, $anno, $id_ordine, $id_payout, $indiretta);
			// FOOTER TABELLA
			$table_footer .= '</tbody>';
			$table_footer .= '</table>';
			
			$em 	= $this->getDoctrine()->getManager();
			// GESTIONE DEGLI STORNI
			$storno = $em->getRepository('AppBundle:Ordini_storni')
						->findOneBy([
								'id_ordine' 	=> $id_ordine,
								'id_payout' 	=> $id_payout,
								'target' 		=> $target,
								'ordine_mese' 	=> $mese,
								'ordine_anno' 	=> $anno,
								]);
							
			if(!empty($storno)){ // storno trovato
				$leads_code = $storno->getLeadsCode();
				if(!empty($leads_code)){
					$stornate = unserialize($leads_code);
				}
			}
						
			// inizializzo la query di selezione delle lead per prelevare i codes dalla tabella pixel_trace
			$sql_pt = $this->generateQueryForExportIndiretta('pixel_trace', $id_campagna, $media, $falsemedia, $affiliatoRefid, $mese, $anno, $data_min, $data_max, $payout_campo, '');
			$conn_pt = $this->getDoctrine()->getManager('pixel_man')->getConnection();
			
			
			// PRELEVO I CODES DALLA TABELLA PIXEL_TRACE
			$codici = array();
			if($conn_pt){
				$stmt_pt = $conn_pt->prepare($sql_pt);
				$stmt_pt->execute();
				if($stmt_pt->rowCount()>0){
					$indice_tr = 0;
					while($pt_row = $stmt_pt->fetch(\PDO::FETCH_ASSOC)){
						// GENERO LE RIGHE DELLA TABELLA PER EVENTUALE CAMPAGNA IN DIRETTA
						
						// UNCHECK DELLE LEAD STORNATE
						$checked = 'checked="checked"';
						if(in_array($pt_row['code'],$stornate)){
							$checked = '';
						}
									
						$table_row .= '<tr data-indice="'.$indice_tr.'">';
						$table_row .= '<td> <input type="checkbox" name="leadcode[]" id="chkbox-'.$indice_tr.'" class="chkbox" '. $checked . ' value="' . $pt_row['code'] . '" /> </td>';
						$table_row .= '<td>'.$pt_row['id_agenzia']			.'</td>';
						$table_row .= '<td>'.$pt_row['media']		.'</td>';
						$table_row .= '<td>'.$pt_row['ip']		.'</td>';
						$table_row .= '<td>'.$pt_row['code']			.'</td>';
						$table_row .= '<td>'.$pt_row['dt']	.'</td>';
						$table_row .= '</tr>';
									
						// CREO L'ARRAY DI CODICI
						$codici[] = $pt_row['code'];
						$indice_tr++;
					}
				}
			}

			/*	
			 * SE LA CAMPAGNA  NON è IN DIRETTA 
			 * LE LEAD POSSONO ESSERE PRELEVATE DA LEAD UNI
			 * PASSANDO PRIMA PER PIXEL_TRACE E PRELEVANDO I CODES
			*/
			if(empty($indiretta)){ 
				
				// RESETTO LE RIGHE DELLA TABELLA
				$table_row = ''; 
				
				if(!empty($codici)){ // se ci sono lead (codes)
					// ESSENDO UNA CAMPAGNA DIRETTA POSSO PRELEVARE I DATI DELLE ANAGRAFICHE DA LEAD_UNI PASSANDO I CODE TROVATI
					$query = "SELECT * FROM lead_uni l where l.code in ('".implode("','",$codici)."') AND l.campagna_id = ".$campagna_id. " GROUP BY l.code ORDER BY l.data ASC";
					
					//print_r($codici);
					//
					//print_r($query);
					//die();
						$conn 	= $em->getConnection();
						if($conn){
							// recupero tutte le colonne
							$stmt = $conn->prepare($query);
							$stmt->execute();
							if($stmt->rowCount()>0){
								$indice_tr = 0;
								while($row = $stmt->fetch()){
									
									// UNCHECK DELLE LEAD STORNATE
									$checked = 'checked="checked"';
									if(in_array($row['code'],$stornate)){
										$checked = '';
									}
									
									$table_row .= '<tr data-indice="'.$indice_tr.'">';
									$table_row .= '<td> <input type="checkbox" name="leadcode[]" id="chkbox-'.$indice_tr.'" class="chkbox" '. $checked . ' value="' . $row['code'] . '" /> </td>';
									$table_row .= '<td>'.$row['nome']			.'</td>';
									$table_row .= '<td>'.$row['cognome']		.'</td>';
									$table_row .= '<td>'.$row['cellulare']		.'</td>';
									$table_row .= '<td>'.$row['email']			.'</td>';
									$table_row .= '<td>'.$row['indirizzo_ip']	.'</td>';
									$table_row .= '<td>'.$row['code']			.'</td>';
									$table_row .= '<td>'.$row['data']			.'</td>';
									$table_row .= '</tr>';
									
									$indice_tr++;
								}
								
								
							}
						}
				}

			}

			$storni_list_block_btn = '<button class="btn btn-primary" onclick="stornaBulk('.$indice_riga.')">Storna</button>';
			$storni_list_block 	= '<div class="row">
									<div class="col-md-10"><h4>Inserisci i codici da stornare</h4></div>
									<div class="col-md-1 close-table-storni pull-right" onclick="closeTableStorni(\''. $indice_riga .'\')"><i class="fa fa-times"></i></div>
									<div class="col-md-7"><textarea style="width:100%" id="list_bulk_'.$indice_riga.'"></textarea></div>
									<div class="col-md-2">'.$storni_list_block_btn.'</div>
									</div>';
			
			// RENDER DELLA TABELLA
			$table_html = $storni_list_block . $table_head . $table_row . $table_footer;
			
			// l'indice viene passato alla funzione tramite get da "this" chiamata ajax
			$footer_box = '<button class="btn btn-primary" onclick="updateTableAfterStorni('.$indice_riga.')">Ricalcola</button>';
			
			$response = new Response();
			$response->setContent(json_encode(array('table' => $table_html,'footer_box' => $footer_box )));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
	}
	
	public function generateTableRow($ordineInfo,$mese,$anno){
		$html = '';
		$totali_leads 	 = 0;

		$landing_cliente = $ordineInfo['landing_cliente'];
		$nomeCliente 	 = $landing_cliente->getCliente()->getName();
		$nomeOfferta	 = $this->convertEntitiesToChar($landing_cliente->getCampagna()->getNomeOfferta());
		$db_cliente 	 = $landing_cliente->getDbCliente(); 
		$tab_cliente 	 = $landing_cliente->getMailCliente();
		$indiretta 		 = $landing_cliente->getIndiretta();
		$id_campagna	 = $landing_cliente->getLanding()->getSlugLanding();
		$target 		 = 0; // target 0 cliente
			
		foreach($ordineInfo['gruppi'] as $id_gruppo => $gruppo){
				
			// dati temporali
			/* data di inizio ricerca del totale delle lead:
				la data di inizio per il primo gruppo è sempre antecedente all'ingresso delle lead 
				non possono essere generate lead prima di questa data in quanto la data indentifica l'ingresso dell'ordine cpl.
				la query di recupro totale deve essre generata prendendo il mese di riferimento
				select * from lead_uni where month(data) = 1 and year(data) = 2018 and date(data) <= $data_fine (la data finale se non esiste sarà il giorno odierno)
				con la funzione
				//$this->getCampagnaTotaleParziale($campagna,$mese,$anno,$_data_fine,$campo_valore);
			*/
				
			$_data_inizio	= $gruppo['info']->getDataInizio(); // formato data ricevuto: Y-m-d h:i:s
			$data_fine 		= $gruppo['info']->getDataFine();   // formato data ricevuto:  Y-m-d h:i:s
			$_data_fine 	= empty($data_fine) ? date('Y-m-d') : $data_fine;				
			
			$tetto_trash	= $gruppo['info']->getTettoTrash();
			$budget			= $gruppo['info']->getBudget();
			
			
			// verifica se il gruppo attualmente iterato ha data iniziale e data finale compresa tra la data passata:
			/**
			 * Logica: prelevo l'anno e il mese dalle date del gruppo, moltiplico l'anno per 100 e sommo il mese (es: 2018-01-25 => (2018*100)+01 = 201801).
			 * lo stesso faccio per l'anno e il mese da ricercare (es. anno 2018, mese Marzo => (2018*100)+03 = 201803)
			 * Una volta ottenuti i 3 numeri (datainiziale, datafinale, datadaricercare), verifico che la datadaricercare sia >= alla data di inizio e <= alla data di fine
			 * in questo modo posso controllare se il mese e l'anno sono all'interno del range senza tenere conto del giorno
			**/
			
			
			
			// somma delle date
			$data_inizio_anno 	=date("Y",strtotime($_data_inizio));
			$data_inizio_mese 	=date("m",strtotime($_data_inizio));
			$data_fine_anno 	=date("Y",strtotime($_data_fine));
			$data_fine_mese 	=date("m",strtotime($_data_fine));
			
			$sumDataInizio 		= ($data_inizio_anno*100 )	+ $data_inizio_mese;
			$sumDataFine 		= ($data_fine_anno*100 )	+ $data_fine_mese;
			
			// data da ricercare
			$sumDataRicerca = ($anno*100) + $mese;
			
			
			//echo "data inizio: " 	. $sumDataInizio . "<br>";
			//echo "data fine: " 		. $sumDataFine. "<br>";
			//echo "data ricerca: " 		. $sumDataRicerca. "<br>";
			//echo "-----------------------------------<br>";
			// data inizio: 201803        <br>
			// data fine: 201804          <br>
			// data ricerca: 201803       <br>
			// -----------------------------------

			if($sumDataRicerca >= $sumDataInizio && $sumDataRicerca <= $sumDataFine){
				
				if(is_array($ordineInfo['payouts']) && !empty($ordineInfo['payouts']) ){ // gestione payout
					
					// conteggio i payout per singola lead
					foreach($gruppo['payouts'] as $payout){
						$lead_totali		= 0;
						$lead_trash			= NULL;
						$id_payout 			= $payout->getId();
						$descrizione_payout	= $payout->getDescrizione();
						$valore_payout		= $payout->getPayout();
						$payout_campo		= $payout->getCampo();
						
						$storno_class 		= '';
						
						if(isset($payout_campo) && !empty($payout_campo)){ 
							
							
							$campi_payout = unserialize($payout_campo);
						
							/*
							$campo_valore = array(
													'campo' 		=> $payout_campo,
													'valore' 		=> $payout_campo_valore,
													'tipo_campo' 	=> $payout_tipo_campo, // tipo campo 0,1,2
												 );
							*/
							$lead_totali = $this->getCampagnaTotaleParziale($landing_cliente, $mese, $anno, $_data_inizio, $_data_fine, $campi_payout);
						}else{                                             
							//echo "Singolo";                              
							// payout singolo                              
							$lead_totali = $this->getCampagnaTotaleParziale($landing_cliente, $mese, $anno, $_data_inizio, $_data_fine, '');
						}
						
						// mi salvo le lead totali senza operazioni eseguite (storni o modifiche a mano)
						$lead_originali_base = $lead_totali; 
						
						// GESTIONE DEGLI STORNI SE PRECEDENTEMENTE IMPOSTATI
						$tot_storni = 0;
						$em 	= $this->getDoctrine()->getManager();
						$storno = $em->getRepository('AppBundle:Ordini_storni')->findOneBy([
																	'id_ordine' 	=> $ordineInfo['id'],
																	'id_payout' 	=> $id_payout,
																	'ordine_mese' 	=> $mese,
																	'ordine_anno' 	=> $anno,
																	'target' 		=> $target, // 0 cliente
																	]);
						if(!empty($storno)){
							$leads_code = $storno->getLeadsCode();
							if(!empty($leads_code)){
								$storni_arr = unserialize($leads_code);
								$tot_storni = count($storni_arr);
								// riscrivo i totali se ci sono storni precedentemente settati
								$lead_totali = $lead_totali-$tot_storni;
								$storno_class = "giastornata";
							}
						}
						// FINE GESTIONE STORNI --------------------------------------------------------------------------------------
							
						
						// INIZIO GESTIONE ORDINE MODIFICATO A MANO -------------------------------------------------------------------
						$ordineModificato = $em->getRepository('AppBundle:Ordini_modificati')->findOneBy([
																	'id_ordine' 	=> $ordineInfo['id'],
																	'id_payout' 	=> $id_payout,
																	'ordine_mese' 	=> $mese,
																	'ordine_anno' 	=> $anno,
																	'target' 		=> $target, // 0 CLIENTE
																	]);
						
						// CREO L'ICONA NEL CASO CI SIA STATA UNA VARIAZIONE DI LORDE O DI TRASH 
						$icon_original_lorde = '';
						$icon_original_trash = '';
						
						// SE E' IMPOSTATO UN ORDINE MODIFICATO MANUALMENTE, PRENDO LE DIFFERENZE PER LE LEAD LORDE E PER LE LEAD TRASH
						if(!empty($ordineModificato)){
							$differenza_lorde  	= $ordineModificato->getDifferenzaLorde();
							$differenza_trash  	= $ordineModificato->getDifferenzaTrash();
							$lead_trash 		= $ordineModificato->getTrashModificato(); // prelevo eventuale trash modificato, se non è mai stato modificato questo valore è null
							$base_trash			= $ordineModificato->getBaseTrash();
							$base_lorde			= $ordineModificato->getBaseLorde();
						
							// aggiungo la differenza delle lead lorde ai totali
							$lead_totali = $lead_totali+$differenza_lorde;
						
							// IL LORDO E' STATO MODIFICATO
							if($base_lorde!=$lead_totali){
								$icon_original_lorde = '<div class="tooltip-addedLead tooltipl text-center"><i class="fa fa-link" aria-hidden="true"></i><span class="tooltipltext">'.$base_lorde.'</span></div>';
							}
							
							// GESTIONE LEAD TRASH SE MODIFICATE A MANO
							if(is_numeric($base_trash)){ // IL TRASH E' STATO MODIFICATO
								if($base_trash!==$lead_trash){ // il trash modificato deve essere diverso da quello originale, in caso positivo mostro l'iconcina con il trash originale
									$icon_original_trash = '<div class="tooltip-addedLead tooltipl text-center"><i class="fa fa-link" aria-hidden="true"></i><span class="tooltipltext">'.$base_trash.'</span></div>';
								}
								// differenza_trash può essere un numero positivo o negativo
								//$base_trash  = $base_trash + $differenza_trash; 
							}
						} // fine se ordine è stato modificato a mano
						// FINE GESTIONE ORDINE MODIFICATO A MANO --------------------------------------------------------------------------
							
						// inizializzazione dei valori
						$perc_lead_trash_rouded	= 0;
						$lead_nette				= 0;
						$delta_budget			= 0;
						$totale					= 0;
						
						$link_download 	= '<div class="tooltipl text-center"><i class="fa fa-times-circle" aria-hidden="true"></i><span class="tooltipltext">Non ci sono lead da scaricare</span></div>';
						$link_storni 	= '<div class="tooltipl text-center"><i class="fa fa-times-circle" aria-hidden="true"></i><span class="tooltipltext">Non ci sono lead da stornare</span></div>';
												
						if(!empty($lead_totali)){
							
							// arrotondo: è un cliente e arrotondo per difetto
							$arrotonda = 'difetto';
							// calcolo 
							$calcolato = $this->calcolaPaysSuModifica($lead_totali,$tetto_trash,$budget,$valore_payout,$lead_trash, $arrotonda);
								
							// QUI CALCOLO LE LEAD TRASH EFFETTIVE RELATIVE AL TOTALE DELLE LEAD CALCOLATO
							$lead_trash 				= !empty($calcolato->lead_trash) ? $calcolato->lead_trash : '0';
						
							$perc_eff_lead_trash 		= $calcolato->perc_eff_lead_trash;
							$perc_lead_trash_rouded 	= $calcolato->perc_lead_trash_rouded;
							$lead_nette 				= $calcolato->lead_nette;
							$delta_budget 				= $calcolato->delta_budget;
							$totale 					= $calcolato->totale;
						
							
							if(isset($payout_campo) && !empty($payout_campo)){
								$campi_pay = unserialize($payout_campo);
								
							}
							
							
							$payout_campo_par 			= isset($payout_campo) 			? $payout_campo			: '';
							$payout_campo_valore_par 	= isset($payout_campo_valore) 	? $payout_campo_valore	: '';
							//$nospam_par 				= !empty($this->nospam) 		? '1'					: '';
							
							
							
							$url_download = $this->admin->generateUrl(	'exportConteggio', array(
																		'dominio'		=> $db_cliente,
																		'mail'			=> $tab_cliente,
																		'mese'			=> $mese, 
																		'data_min'		=> $_data_inizio, 
																		'data_max'		=> $_data_fine, 
																		'anno' 			=> $anno,
																		'campo'			=> $payout_campo_par,
																		'valore_campo'	=> $payout_campo_valore_par,
															)
														);
							$link_download 	= '<a href="'.$url_download.'">
												<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica
												</a>';
							
							if(!empty($indiretta)){
								$link_storni 	= '<a href="javascript:void(0)" 
			onclick="getStornoTableIndiretta(\''.$ordineInfo['id'].'\',\''.$id_campagna.'\',\''.$id_payout.'\',\''.$mese.'\',\''.$anno.'\',\''.$_data_inizio.'\',\''.$_data_fine.'\',\''. $this->indice_riga .'\')">
												<i class="fa fa-outdent '. $storno_class .'" aria-hidden="true"></i></a>';
							}else{
								$link_storni 	= '<a href="javascript:void(0)" 
			onclick="getStornoTable(\''.$ordineInfo['id'].'\',\''. $id_payout . '\',\''. $db_cliente .'\',\''.$tab_cliente.'\',\''.$mese.'\',\''.$anno.'\',\''.$_data_inizio.'\',\''.$_data_fine.'\',\''. $this->indice_riga .'\')">
												<i class="fa fa-outdent '. $storno_class .'" aria-hidden="true"></i></a>';
							}
							
						}
						$lead_trash 		= !empty($lead_trash) ? $lead_trash : '0';
						$btn_add_lead_lorde = '<div class="addLead-box addL-box" id="addLead-box-'.$this->indice_riga.'">
												<i class="fa fa-plus-circle addLead-btn" aria-hidden="true" onclick="addLead(\''.$this->indice_riga.'\')"></i>
											  </div>';
						$btn_add_lead_trash = '<div class="addLeadTrash-box addL-box" id="addLeadTrash-box-'.$this->indice_riga.'">
												<i class="fa fa-plus-circle addLeadTrash-btn" aria-hidden="true" onclick="addLead(\''.$this->indice_riga.'\',\'Trash\')"></i>
											  </div>';
						// render righe tabella
						//if($id_gruppo == $ordineInfo['id_gruppo']){ // se è il payout attuale
							// render righe tabella
							$html .='<tr id="riga-'.$this->indice_riga.'" 
									data-ordineid="'.$ordineInfo['id'].'" 
									data-payoutid="'.$id_payout.'" 
									data-mese="'.$mese.'" 
									data-anno="'.$anno.'" 
									data-totali-lead-base="'.$lead_originali_base.'">'. // qui salvo il totale delle lead calcolate senza modifiche da storni o aggiunta lead manuali: utile per sottrarre gli storni
										
									'<td data-val="'.	$nomeCliente									.'" id="nome_cliente-'		.$this->indice_riga . '">'. $nomeCliente 						. '</td>' .
									'<td data-val="'.	$nomeOfferta									.'" id="nome_offerta-'		.$this->indice_riga . '">'. $nomeOfferta 						. '</td>' .
									'<td data-val="'.	$descrizione_payout								.'" id="descrizione_payout-'.$this->indice_riga . '">'. $descrizione_payout 				. '</td>' .
									'<td data-val="'.	$lead_totali									.'" id="lead_totali-' 		.$this->indice_riga . '">'. $icon_original_lorde . $lead_totali . $btn_add_lead_lorde .'</td>' .
									'<td data-val="'.	number_format($tetto_trash,2,',','')			.'" id="tetto_trash-'		.$this->indice_riga . '">'. number_format($tetto_trash,2,',','') . '</td>' .
									'<td data-val="'.	$lead_trash										.'" id="lead_trash-'		.$this->indice_riga . '">'. $icon_original_trash . $lead_trash . $btn_add_lead_trash . '</td>' .
									'<td data-val="'.	number_format($perc_lead_trash_rouded,2,',','')	.'" id="perc_lead_trash-'	.$this->indice_riga . '">'. number_format($perc_lead_trash_rouded,2,',','') . '</td>' .
									'<td data-val="'.	$lead_nette										.'" id="lead_nette-'		.$this->indice_riga . '">'. $lead_nette 						. '</td>' .
									'<td data-val="'.	$budget											.'" id="budget-'			.$this->indice_riga . '">'. $budget 							. '</td>' .
									'<td data-val="'.	$delta_budget									.'" id="delta_budget-'		.$this->indice_riga . '">'. $delta_budget						. '</td>' .
									'<td data-val="'.	number_format($valore_payout,2,',','')			.'" id="valore_payout-'		.$this->indice_riga . '">'. number_format($valore_payout,2,',','') 	.  '</td>' .
									'<td data-val="'.	number_format($totale,2,',','') 				.'" id="totale-'			.$this->indice_riga . '">'. number_format($totale,2,',','') 			. '</td>' .
										
									// action buttons riga
									'<td>'. $link_download 	. '</td>' .
									'<td>'. $link_storni 	. '</td>' .
								'</tr>'; //row
							// salvo i totali
						//}
						
						$this->totali_righe['lead_totali'] 	+= $lead_totali;
						//echo $this->totali_righe['lead_totali'];
						$this->totali_righe['lead_trash'] 	+= $lead_trash;
						$this->totali_righe['lead_nette'] 	+= $lead_nette;
						$this->totali_righe['budget'] 		+= $budget;
						$this->totali_righe['delta_budget'] += $delta_budget;
					//	$this->totali_righe['payout'] 		+= $valore_payout;
						$this->totali_righe['totale'] 		+= $totale;
						$this->totali_righe['dw'] 			= '';
						$this->totali_righe['st'] 			= '';
						
						// incremento l'indice della riga
						$this->indice_riga++;
					}
				// footer
				$footer = '<tfoot class="fix_foot_totals"><tr class="footer_totals strong">';
				
				foreach($this->totali_righe as $key => $col_tot){
					if($key=='totale'){ $col_tot = number_format($col_tot,2,',',''); }
					$footer .= '<th id="grand_totale-'.$key.'" data-val="'.$col_tot.'"><strong>'. $col_tot	. '</strong></th>';
				}
				$footer .= '</tr></tfoot>';
				$this->tableFooter = $footer;
			}
			
			
			} // fine if controllo che la data ricercata sia tra le 2 date del gruppo (sumdataricerca,sumdatainizio,sumdatafine)
		}
	
		return $html;
	}
	
	/**
	* funzione di calcolo payouts
	* @params integer $lead_totali, decimal $tetto_trash, integer $budget, decimal $valore_payout
	* return object $results
	**/
	public function calcolaPays($lead_totali,$tetto_trash,$budget,$valore_payout, $arrotonda = 'eccesso'){
		$results = new \StdClass();
		
		$round = PHP_ROUND_HALF_EVEN;
		if($arrotonda!='eccesso'){
			$round = PHP_ROUND_HALF_ODD;
		}
		
		$results->lead_trash				= round(((($lead_totali*$tetto_trash)/100)),0,$round);
		$results->perc_eff_lead_trash		= (($results->lead_trash/$lead_totali) * 100);
		$results->perc_lead_trash_rouded	= round($results->perc_eff_lead_trash,0,$round);
		$results->lead_nette				= $lead_totali-$results->lead_trash;
		$results->delta_budget 				= $results->lead_nette-$budget;
		$results->totale 					= ($valore_payout*$results->lead_nette);
		
		return $results;
	}
	
	/**
	* funzione di calcolo payouts modificata sui valori passati: valida in caso di modifica manuale dell'ordine
	* @params integer $lead_totali, decimal $tetto_trash, integer $budget, decimal $valore_payout
	* return object $results
	**/
	public function calcolaPaysSuModifica($lead_totali,$tetto_trash,$budget,$valore_payout,$lead_trash = '',$arrotonda = 'eccesso'){
		$results = new \StdClass();
		
		$round = PHP_ROUND_HALF_EVEN;
		if($arrotonda!='eccesso'){
			$round = PHP_ROUND_HALF_ODD;
		}
		
		if($lead_totali>=0){
			
			if(is_numeric($lead_trash)){
				$results->lead_trash			= $lead_trash;


			}else{
				$results->lead_trash			= round(((($lead_totali*$tetto_trash)/100)),0,$round);

			}
			$results->perc_eff_lead_trash		= (($results->lead_trash/$lead_totali) * 100);
			$results->perc_lead_trash_rouded	= round($results->perc_eff_lead_trash,0,$round);
			$results->lead_nette				= $lead_totali-$results->lead_trash;
			$results->delta_budget 				= $results->lead_nette-$budget;
			$results->totale 					= ($valore_payout*$results->lead_nette);
		}else{ 
			// se le lead totali sono 0, allora non posso dividere e rilascio tutti 0
			$results->lead_trash				= 0;
			$results->perc_eff_lead_trash		= 0;
			$results->perc_lead_trash_rouded	= 0;
			$results->lead_nette				= 0;
			$results->delta_budget 				= 0;
			$results->totale 					= 0;
		}
		

		return $results;
	}
	
	/**
	 * Funzione di calcolo totale, parziale per ogni gruppo in base al tempo di funzione dei payouts
	 * il parziale si basa su 3 tipologie di richerca in caso di payout multiplo:
	 * solo in caso di payout multiplo, bisogna distinguere i 3 tipi di campo:
	 * 0 -> campo relativo alla tabella, il nome del campo è lo stesso della colonna, può essere risolto con un unica query sul db lead_uni 
	 * 1 -> campo extra: il valore del campo deve essere prima prelevato. recuperiamo l'id del valore attraverso la funzione 
	 * $value_id = getExtraFieldValueIdByValueNameAndFieldId($campo_valore['valore'], 				 campo_valore['campo'])
	 * una volta trovato l'id del valore abbiamo bisogno degli id delle lead appartenenti a quella campagna (a_landing_cliente) per contare quante lead si ritrovano l'id del valore attraverso la tabella a_lead_extra_values con la funzione getTotLeadOnExtraValue($lead_ids,$value_id) al funzione restituisce il totale delle lead che hanno quel valore.
	 * @pars array campagna target ('dominio', 'mail'), integer anno, mese, string date_min, date_max, array campo_valore ('campo','valore','tipo_campo')
	 **/
		
	private function getCampagnaTotaleParziale($landing_cliente, $mese='',$anno='', $data_min, $data_max, $campi_payout = array()){
		
		
		try{
			$totali = 0;
			$array_values = array();
			$da_selezionare  = " count(*) as tot ";
			
			$indiretta						= $landing_cliente->getIndiretta();
			$id_campagna					= $landing_cliente->getLanding()->getSlugLanding();
			
			$array_values['mese'] 			= $mese;
			$array_values['anno'] 			= $anno;
			$array_values['data_min'] 		= $data_min;
			$array_values['data_max'] 		= $data_max;
			$array_values['source_db'] 		= $landing_cliente->getDbCliente();
			$array_values['source_tbl'] 	= $landing_cliente->getMailCliente();
			
			
			
			
			$sql_pay = '';
			if(!empty($campi_payout)){ // se è un payout multiplo
				// gestione del payout multiplo
				
				
				$sql_pay = '';
				
				$indice_valore_campo = 0;
				
				/** NOTE: il seguente foreach viene iniziato e terminato prima dello switch. La cosa è errata in quanto il valore tipocampo deve essere iterato insieme allo swicth.
				 * con questa predisposizione, non sarà possibile effettuare conteggi sui totali su campi di tabelle diverse (es. colonna su lead_uni ed extra value)
				 * la modifica è in whislist e verrà successivamente implementata.
				 *
				*/
				foreach($campi_payout as $campo_payout){
					
					$indice_valore_campo++;
					
					$key_valore_campo = "valore_campo" . $indice_valore_campo;
					
					$tipo_campo = $campo_payout->tipocampo;
					
					$campo 		= trim($campo_payout->nomecampo);
					$valore 	= $campo_payout->valorecampo;
					$valore 	= str_replace('*','%',$valore); 
					
					$sql_pay .= " AND ".$campo." like :".$key_valore_campo." ";
					$array_values[$key_valore_campo] 	=  	trim($valore);
					
				} // fine foreach campo_payout	

					switch($tipo_campo){ 
						case '0':  // campi da colonne lead_uni
							$totali = $this->getTotalByQueryParams($da_selezionare, $array_values, $sql_pay);
							$totali = $totali['0']['tot'];
						break;
						case '1':  // campi extra seleziono solo gli id dalla query
							$da_selezionare = " lu.id ";
							$lead_ids 		= $this->getTotalByQueryParams($da_selezionare, $array_values);
							$value_id 		= $this->getExtraFieldValueIdByValueNameAndField($valore, $campo);
							$totali 		= $this->getTotLeadOnExtraValue($lead_ids,$value_id); // ritorna il numero di lead con quel valore
							
						break;
						case '2':  // campi prelevati dalla tabella pixel_trace
							
							// GESTIONE DELLA CAMPAGNA IN DIRETTA
							/*
							 * Se la campagna è in diretta, i campi da tenere in cosiderazione in caso di payout multiplo sono solo quelli presenti nella tabella pixel_trace
							 * la gestione della campagna è quindi inserita in questo blocco dove il tipo di campo del payout è 2 cioè prelevato da pixel_Trace
							 *
							*/
							if(!$indiretta){
								$da_selezionare = " lu.code ";
								$lead_codes 	= $this->getTotalByQueryParams($da_selezionare, $array_values);
								$totali			= $this->getTotalFromPixelTraceByCodes($lead_codes,$campo,$valore);
							}else{
								$totali			= $this->getTotalFromPixelTraceByIdCampagna($id_campagna,$array_values,$campo,$valore);
							}
							$totali			= $totali['0']['tot'];
						break;
					}
				
			
			}else{ // se non è un payout multiplo
				// payout singolo
				if(!$indiretta){
					$totali = $this->getTotalByQueryParams($da_selezionare, $array_values);
				}else{
					$totali = $this->getTotalFromPixelTraceByIdCampagna($id_campagna,$array_values);
				}
					$totali = $totali['0']['tot'];
				
			}

		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	
	
	
	public function getTotalByQueryParams($da_selezionare  = " count(*) as tot ", $array_values, $addAnd = '', $codes = array()){
		$results = false;
			
		$sql_tot  = " WHERE lu.source_db = :source_db AND lu.source_tbl = :source_tbl";
		$sql_tot .= " AND MONTH(lu.data) = :mese AND YEAR(lu.data) = :anno ";
		$sql_tot .= " AND DATE(lu.data) >= DATE(:data_min) ";
		$sql_tot .= " AND DATE(lu.data) <= DATE(:data_max) ";

		if(!empty($addAnd)){
			$sql_tot .= " " . $addAnd . " ";
		}
		
		
		if(!empty($codes)){
			$_codes = implode(',',$codes);
			$sql_tot .=" AND lu.code in (".$_codes.")";
		}
				
		
		$sql_top = "select " . $da_selezionare . " from lead_uni lu";
		$sql_completa = $sql_top . $sql_tot;
		
		//print_r($sql_completa);
		//print_r($array_values);
		
		$em 	= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql_completa);
		$stmt->execute($array_values);
	
		if ($stmt->rowCount()>0) { // lead presente, aggiungo 2 gg
			$results = $stmt->fetchAll();
		}
		
		return $results;
		
	}
	
	public function getTotalFromPixelTraceByCodes($codes,$campo,$valore_campo){
		$results = 0;
		if(!empty($codes)){
			$valori_query = array();
			$str_codes = "'". implode("','", $codes) . "'";
			$sql_Trace = "SELECT COUNT(*) as tot FROM pixel_trace pt
							WHERE pt.code IN (" . $str_codes . ") ";
						
			if(!empty($campo)){
				$sql_Trace .= " AND " . $campo . " like ? ";
				$valori_query = array($valore_campo);
			}
			
			$em 	= $this->getDoctrine()->getManager('pixel_man');
			$stmt 	= $em->getConnection()->prepare($sql_Trace);
			$stmt->execute($valori_query);
			if ($stmt->rowCount()>0) { 
				$results = $stmt->fetchAll();
			}
		}
		return $results;
	}	
	
	public function getTotalFromPixelTraceByIdCampagna($id_campagna, $array_values, $campo = '', $valore_campo = ''){
		$results = 0;

		$valori_query = array();
		$sql_Trace = "SELECT COUNT(*) as tot FROM pixel_trace 
						WHERE id_campagna = '".$id_campagna."' ";
					
		if(!empty($campo)){
			$sql_Trace .= " AND " . $campo . " like :valore_campo ";
			$valori_query['valore_campo'] = $valore_campo;
		}
		
		if(!empty($array_values)){
				
			if(!empty($array_values['mese'])){
				$sql_Trace .= " AND MONTH(dt) = :mese";
				$valori_query['mese'] = $array_values['mese'];
			}
			
			if(!empty($array_values['anno'])){
				$sql_Trace .= " AND YEAR(dt) = :anno";
				$valori_query['anno'] = $array_values['anno'];
			}    
			
			if(!empty($array_values['data_min'])){
				$sql_Trace .= " AND DATE(dt) >= DATE(:data_min)";
				$valori_query['data_min'] = $array_values['data_min'];
			} 
			
			if(!empty($array_values['data_max'])){
				$sql_Trace .= " AND DATE(dt) <= DATE(:data_max)";
				$valori_query['data_max'] = $array_values['data_max'];
			} 
						
		}
		
	
		$em 	= $this->getDoctrine()->getManager('pixel_man');
		$stmt 	= $em->getConnection()->prepare($sql_Trace);
		$stmt->execute($valori_query);
		if ($stmt->rowCount()>0) { 
			$results = $stmt->fetchAll();
		}
		return $results;
	}
	
	/**
	 * Recupero l'id di un extra field attraverso il suo name 
	 * @param string nome campo extra
	 * return integer field_id
	**/
	public function getExtraFieldIdByName($extra_field_name){
		$field 		= $this->getDoctrine()->getRepository('AppBundle:Lead_uni_extra_fields')->findOneBy(array('name' => $extra_field_name));
		$field_id 	= $field->getId();
		return $field_id;
	}
	
	/**
	 * Recupero l'id valore di un extra field attraverso il suo valore (name) ed il suo field_id
	 * @param string valore (name) del campo extra, string  nome del campo extra
	 * return integer field_id
	**/
	public function getExtraFieldValueIdByValueNameAndField($extra_field_value,$extra_field_name){
		$value_id = false;
		$field_id 	= $this->getExtraFieldIdByName($extra_field_name);
		$value 		= $this->getDoctrine()->getRepository('AppBundle:Lead_uni_extra_values')->findOneBy(array('name' => $extra_field_value, 'field' => $field_id));
		if($value){
			$value_id 	= $value->getId();
		}
		return $value_id;
	}
	
	/**
	 * Recupero il totale delle lead che hanno l'id del valore campo extra passato
	 * @param array ids leads, integer campo extra value id
	 * return integer totale
	**/	
	public function  getTotLeadOnExtraValue($lead_ids,$value_id){
		$result = array('totale' => 0);
		if(count($lead_ids) && $value_id){
			$sql = 'SELECT COUNT(*) AS totale FROM a_lead_extra_values axv 
					WHERE axv.lead_id IN (' . implode(',',$lead_ids) . ')
					AND axv.value_id = ?';
			$em  = $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute(array($value_id));
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		}
		return $result['totale'];
	}
	
	public function getOrdineInfo($id_landing_cliente){
		$ordine 	= false;
		$_ordine 	= $this->getOrderByIdLandingCliente($id_landing_cliente);
		if($_ordine){
			$ordine 	= $this->getOrderById($_ordine['id']);
		}
		return $ordine;
	}
	 
	
	
	
	public function getOrdineFornitoreInfo($id_landing_cliente,$id_fornitore, $id_affiliato = ''){
		
		$_ordine 	= $this->getOrderByIdLandingFornitore($id_landing_cliente,$id_fornitore,$id_affiliato);
		$ordine		= false;
		if($_ordine){
			$ordine = $this->getOrderById($_ordine['id'],1);
		}
		return $ordine;
	}
	
	public function createPayoutGroup($budget, $tetto_trash, $data_inizio,$data_fine){
		$ora 			= date('Y-m-d H:i:s');
		$data_inizio 	= \DateTime::createFromFormat('d/m/Y', $data_inizio)->format('Y-m-d H:i:s');
		
		$data_fine 		= !empty($data_fine) ? \DateTime::createFromFormat('d/m/Y', $data_fine)->format('Y-m-d H:i:s') : null;
		$gruppo = new PayoutGruppi;
		$gruppo->setBudget($budget)->setTettoTrash($tetto_trash)->setDataInizio($data_inizio)->setDataFine($data_fine)->setDataCreazione($ora);

		$em = $this->getDoctrine()->getManager();
		$em->persist($gruppo);
		$em->flush();
		return $gruppo->getId();
	}
	
	public function updateDataFinePayoutGroup($id_gruppo,$data_fine, $force_save = false){
		//$data_fine = \DateTime::createFromFormat('d/m/Y', $data_fine)->format('Y-m-d H:i:s') : '';
		$gruppo = $this->getDoctrine()
				->getRepository('AppBundle:Payout_gruppi')
				->find($id_gruppo);
		
		if($gruppo){
			$data_fine_settata = $gruppo->getDataFine();
			$em = $this->getDoctrine()->getManager();
			$gruppo->setDataFine($data_fine);
			
			// se devo forzare il salvataggio, salvo
			if($force_save){
				$em->persist($gruppo);
				$em->flush();
			}else{ // se non devo forzare, controllo se è vuoto prima di salvare: nel caso ci sia un qualche valore, non sovrascrivo
				if(empty($data_fine_settata)){
					$em->persist($gruppo);
					$em->flush();
				}
			}
		}
	}
	
	public function updatePayoutGroup($id_gruppo,$budget, $tetto_trash, $data_inizio, $data_fine){
		$data_fine = !empty($data_fine) ? \DateTime::createFromFormat('d/m/Y', $data_fine)->format('Y-m-d H:i:s') : null;
		/*$data_fine = !empty($data_fine) ? \DateTime::createFromFormat('d/m/Y', $data_fine)->format('Y-m-d H:i:s') : null;
		$data_fine 	= !empty($data_fine) ? date('Y-m-d H:i:s', strtotime("-1 day", strtotime($data_fine))) : null; */
		
		$data_inizio = !empty($data_inizio) ? \DateTime::createFromFormat('d/m/Y', $data_inizio)->format('Y-m-d H:i:s') : '';


		$gruppo = $this->getDoctrine()
				->getRepository('AppBundle:Payout_gruppi')
				->find($id_gruppo);
			
		$gruppo->setBudget($budget)->setTettoTrash($tetto_trash)->setDataInizio($data_inizio)->setDataFine($data_fine);
		$em = $this->getDoctrine()->getManager();
		$em->persist($gruppo);
		$em->flush();
	}
	
	public function associaPayoutGruppoOrdine($id_payout,$id_gruppo,$id_ordine,$target = 0){
		/**
		 * Gestione dell'override dell'ordine: se è settata la variabile $this->OrdineOverride (settata dalla funzione chekorder), vuol dire che sto eseguendo un override dell'ordine 
		 * La variabile $this->OrdineOverride contiene l'array con id_gruppo da 
		 *
		**/
		$em  	= $this->getDoctrine()->getManager();
		// QUERY BASE DI INSERIMENTO, IL VALORE DI $SQL CAMBIA NEL CASO CI SIA UN OVERRIDE E NEL CASO LA RIGA ESISTA
		$sql = "INSERT INTO payout_gruppo_ordine (id_payout, id_gruppo, id_ordine, target, data_creazione) 
					VALUE(?,?,?,?,NOW())";
		$ordineData = array($id_payout, $id_gruppo, $id_ordine, $target);
		
		
		
		
		// EFFETTUO L'OVERRIDE SOLO SE LE DATE SONO UGIALI, ALTRIMENTI INSERISCO
		
		/*
		if(!empty($this->OrdineOverride)){
			
			
			$id_vecchio_gruppo = $this->OrdineOverride['id_gruppo'];
			
			// verifico l'esistenza del gruppo:
			$sql_check = "SELECT * FROM payout_gruppo_ordine WHERE target = ? AND id_ordine = ? AND id_gruppo = ?";
			$ordineDataCheck = array($target, $id_ordine, $id_vecchio_gruppo);
			
			$stmt_check 	= $em->getConnection()->prepare($sql_check);
			$result_check 	= $stmt_check->execute($ordineDataCheck);
			
			if($result_check){
				if ($stmt_check->rowCount()>0) {
					// se la riga esiste eseguo l'update
					$sql = "UPDATE payout_gruppo_ordine SET  id_payout = ?, id_gruppo= ?
							WHERE target = ? AND id_ordine = ? AND id_gruppo = ? "; // id gruppo è l'id del vecchio gruppo da aggiornare
					$ordineData = array($id_payout, $id_gruppo, $target, $id_ordine, $id_vecchio_gruppo);
				}
			}
			// effettuato l'override svuoto la variabile
			$this->OrdineOverride = false;
		}*/

		$stmt 	= $em->getConnection()->prepare($sql);
		$stmt->execute($ordineData);
		//return $em->getConnection()->lastInsertId();
	}
	
	public function insertOrdineCliente($cliente_id, $id_landing_cliente,$id_gruppo,$ordine_descrizione){
		// salvataggio dell'ordine
		$sql = "INSERT INTO ordini_cliente (cliente_id, id_landing_cliente,id_gruppo,descrizione,data_creazione) 
				VALUE(?, ?,?,?,NOW())";
		$ordineData = array($cliente_id, $id_landing_cliente,$id_gruppo,$ordine_descrizione);

		$em  	= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql);
		$stmt->execute($ordineData);
		$id_ordine = $em->getConnection()->lastInsertId();
		return $em->getConnection()->lastInsertId();
	}
	
	public function insertOrdineFornitore($id_landing_cliente,$id_fornitore,$id_agenzia,$id_gruppo,$ordine_descrizione){
		// salvataggio dell'ordine
		$em  	= $this->getDoctrine()->getManager();
		
		if(empty($id_agenzia)){ $id_agenzia = null;	}	
	
			/**
			 * Se esiste l'ordine, vado a salvare i dati nella variabile globale OrdineOverride ed eseguo l'update della riga in DB.
			 * La variabile OrdineOverride mi permetterà di rimuovere il gruppo associato all'ordine e di sovrascriverlo con il nuovo gruppo
			**/
		/*
		if($order){
			echo "l'ordine esiste";
			$this->OrdineOverride = $order;
			$order_id = $order['id'];
			$sql = "UPDATE ordini_fornitore SET id_gruppo = ?, descrizione = ? WHERE id = ?";
			$ordineData = array($id_gruppo,$ordine_descrizione,$order_id);
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute($ordineData);
		}else{
			*/
			$sql 	= "INSERT INTO ordini_fornitore (id_landing_cliente,id_fornitore,id_affiliato,id_gruppo,descrizione,data_creazione) 
				VALUE(?,?,?,?,?,NOW())";
			$ordineData = array($id_landing_cliente,$id_fornitore,$id_agenzia,$id_gruppo,$ordine_descrizione);
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute($ordineData);
			$order_id = $em->getConnection()->lastInsertId();
		/* } */

		//print_r($order_id);
		//die('<---');
		return $order_id;
	}
	
	/**
	 * La funzione aggiorna l'id del gruppo per un dato ordine 
	**/
	public function updateOrderGruppo($id_ordine,$new_id_gruppo,$target){
		
		$table = "ordini_cliente";
		if($target=='1'){
			$table = "ordini_fornitore";
		}
		
		$sql 	= "UPDATE " . $table . " SET id_gruppo = ? 
					WHERE id = ? ";
				
		$ordineData = array($new_id_gruppo,$id_ordine);
				
		if(!empty($id_ordine)){
			$em  	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql);
			$result = $stmt->execute($ordineData);
		}
	}
	
	public function checkOrderFornitore($id_landing_cliente,$id_fornitore,$id_agenzia){
		$order 	= false;
		$sql 	= "SELECT * FROM ordini_fornitore 
					WHERE id_landing_cliente = ?
					AND id_fornitore = ? ";
				
		$ordineData = array($id_landing_cliente,$id_fornitore);
				
		if(!empty($id_agenzia)){
			$sql .= " AND id_affiliato = ? ";
			$ordineData[] = $id_agenzia;
		}else{
			$sql .= " AND id_affiliato is null ";
		}
		
		//print_r($sql);
		//print_r($ordineData);
		//echo "-------------------------------------";
		
		$em  	= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql);
		$result = $stmt->execute($ordineData);
		if($result){
			if ($stmt->rowCount()>0) {
				$order = $stmt->fetch();
				$gruppo = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')->find($order['id_gruppo']);
				$order['gruppo'] = $gruppo;
			}
			
		}
		return $order;
	}
	
	
	
	public function salvaPayAction(Request $request){
		$id_gruppo			 	= $request->get('id_gruppo');
		$budget					= $request->get('budget');
		$payouts				= $request->get('payout');
		$tetto_trash			= $request->get('tetto_trash');
		$data_inizio			= $request->get('data_inizio');
		$data_fine				= $request->get('data_fine');
		$target 				= $request->get('target');
		
		if($target=='0'){
			$tabella_target = 'Payout_ordine_cliente';
		}else{
			$tabella_target = 'Payout_ordine_fornitore';
			
		}
		
		
		if(isset($payouts)){
			$payouts = utf8_encode($payouts);
			$payouts = json_decode($payouts, true);
			
			if(is_array($payouts)){
				
				$em	= $this->getDoctrine()->getManager();
				
				foreach($payouts as $__payout){
					// PER OGNI PAYOUT CREO LA SUA ENTITà E AGGIORNO
					$id_payout = $__payout['id'];
					$pay = $__payout['payout'];
					
					$payout = $em->getRepository('AppBundle:' . $tabella_target)->find($id_payout);
					if($payout){
						$payout->setPayout($pay); // salvo il pay 
						$campi_attuali =  $payout->getCampo();	
							
						// gestione del campo payout
						if(isset($__payout['campi'])){
							foreach($__payout['campi'] as $indice_campo => $campo){

								if(!empty($campi_attuali)){
									$campi_attuali = unserialize($campi_attuali);
									// modifico l'array campo 
									//salvo il nuovo valorecampo
									$campi_attuali[$indice_campo]->valorecampo = $campo['valorecampo'];
									//serializzo di nuovo l'array 
									$new_campi = serialize($campi_attuali);
									// salvo il nuovo campo 
									$payout->setCampo($new_campi);
								}
							}
						} // fine controllo __payout['campi']
						
						//// salvo il payout
						$em->persist($payout);
						$em->flush();
					} // fine controllo esistenza payout
				} // /foreach payouts
			}
		}
		$this->updatePayoutGroup($id_gruppo,$budget, $tetto_trash, $data_inizio, $data_fine);
		
		$response = new Response();
		$response->setContent(json_encode(array('result'	=> true,)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	public function salvaOrdineAction(Request $request){
		$cliente_id			 	= $request->get('cliente_id');
		$id_landing_cliente 	= $request->get('clienteCampagna');
		$budget					= $request->get('budget');
		$tetto_trash			= $request->get('trash');
		$tipo_payout			= $request->get('tipo_payout');
		$ordine_descrizione		= $request->get('ordine_descrizione');
		$data_inizio			= $request->get('data_inizio');
		$data_fine				= $request->get('data_fine');
		
		
		
		$valore_campo_multiplo 	= array();
		$target_campo_multipli 	= array();
		$payout_multiplo 		= array();
		$campo_payout 			= array();
		$payout_descrizione		= array();
		
		$payoutArray = array();
		$target = 0; // cliente
		
		$payouts = json_decode($request->get('payouts'));
		/*
		Array ( 
			[0] => stdClass Object ( 
					[campi] => Array 
							( 
							[0] => stdClass Object ( 
													[nomecampo] => sesso 
													[valorecampo] => M 
													[tipocampo] => 0 
													) 
							[1] => stdClass Object ( 
													[nomecampo] => nome 
													[valorecampo] => Luca 
													[tipocampo] => 0 
												   ) 
							) 
					[payout] => 12 
					[descrizione] => desc1 
			) 
			[1] => stdClass Object ( 
					[campi] => Array ( 
							[0] => stdClass Object ( 
													[nomecampo] => provincia 
													[valorecampo] => NA 
													[tipocampo] => 0 
												   ) 
							[1] => stdClass Object ( 
													[nomecampo] => cliente 
													[valorecampo] => si 
													[tipocampo] => 0 
													)
							) 
					[payout] => 13 
					[descrizione] => desc2 ) )
		*/
		
		// creo il gruppo
		$id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine);
		$id_ordine = $this->insertOrdineCliente($cliente_id, $id_landing_cliente,$id_gruppo,$ordine_descrizione);

		if($id_ordine){
			$this->salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target); // $payouts deve essere un array
		}

		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> true,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

public function editOrdineAction(Request $request){
		$id_ordine			 	= $request->get('id_ordine');
		$id_landing_cliente 	= $request->get('clienteCampagna');
		$budget					= $request->get('budget');
		$tetto_trash			= $request->get('trash');
		$tipo_payout			= $request->get('tipo_payout');
		$ordine_descrizione		= $request->get('ordine_descrizione');
		$data_inizio			= $request->get('data_inizio');
		$data_fine				= $request->get('data_fine');
		$nuovo_payout			= $request->get('nuovo_payout');
		$result					= true;
		$message 				= '';			
		$valori 				= array('descrizione' 	=> $ordine_descrizione,);
		
		$valore_campo_multiplo 	= array();
		$payout_multiplo 		= array();
		$campo_payout 			= array();
		$payout_descrizione		= array();
		$payoutArray 			= array();
		$target = 0; // cliente
		
		// VERIFICA SE è STATA EFFETTUATA UNA RICHIESTA DI NUOVO PAYOUT
		if($nuovo_payout){
			
			//  CONTROLLO DATE INTERSECANTI
			
			//prelevo tutti i gruppi associati all'ordine:
			$gruppi = $this->getAllGruppiFromOrderId($id_ordine);
			foreach($gruppi as $gruppo){
				$data_inizio_pay 	= strtotime($gruppo['info']->getDataInizio());
				$data_fine_pay   	= !empty($gruppo['info']->getDataFine()) ? strtotime($gruppo['info']->getDataFine()) : strtotime('-1 day');
				
				$_data_inizio 		= \DateTime::createFromFormat('d/m/Y', $data_inizio)->format('Y-m-d H:i:s');
				$_data_inizio_check = strtotime($data_inizio);
				
				$interseca 			= $this->checkDateBeetween($_data_inizio_check,$data_inizio_pay,$data_fine_pay);
				
				if($interseca){
					$result		= false;
					$message 	= "La data inserita interseca con una data payout";
					break; // esco dal ciclo
				}
			}
			
			if($result){

				$em  = $this->getDoctrine()->getManager();

				// creo il gruppo
				$id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine);
				$valori['id_gruppo'] = $id_gruppo;
				
				$ordine = $this->getOrderById($id_ordine);
				
				// aggiorno la data dell'ultimo gruppo associato all'ordine con la data di inizio del nuovo gruppo
				$_data_inizio_obj = \DateTime::createFromFormat('Y-m-d H:i:s', $_data_inizio);
				$_data_fine_gruppo = $_data_inizio_obj->modify('-1 day')->format('Y-m-d H:i:s'); 
				$this->updateDataFinePayoutGroup($ordine['id_gruppo'],$_data_fine_gruppo);  
				
				if($id_ordine){

					$payouts = json_decode($request->get('payouts'));
					$this->salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target); // $payouts deve essere un array
				}
			} // if result interseca
		} //IF PAYOUT NUOVO
		// FINE SALVATAGGIO PAYOUT
		
		if($result){
			// aggiorno l'ordine con i valori generati
			$this->updateOrdine($id_ordine,$valori,$target);
		}
				
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $result,
										'message'	=> $message,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	/**
 	 * @param int id_ordine
	 * @param array valori
	 * return bool
	*/
	public function updateOrdine($id_ordine,$valori,$target='0'){
		$ordine_data = array();
		$_valori = array();
		$table = 'ordini_cliente';
		if($target=='1'){
			$table = 'ordini_fornitore';
		}
		$sql = "UPDATE " . $table . 
			   " SET ";
		foreach($valori as $key => $value){
			$_valori[] 		= $key .'=?';
			$ordine_data[] 	= $value;
		}
		$sql .=	implode(',',$_valori);			
		$sql .=	" WHERE id = ?";
		$ordine_data[] 	= $id_ordine;
		$em 	= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql);
		return $stmt->execute($ordine_data);
	}
	
	
	
	
	// DA ELIMINARE ANCHE DA ADMIN CONTROLLER
	public function checkOrdineAction(Request $request){
		$trovato = false;
		$id_landing_cliente = $request->get('clienteCampagna');
		if($this->getOrderByIdLandingCliente($id_landing_cliente)){
			$trovato = true;
		}
		$response = new Response();
		$response->setContent(json_encode($trovato));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	
	}
	
	
	
	
	
	public function getOrderByIdLandingCliente($id_landing_cliente){
		$ordine = false;
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM ordini_cliente WHERE id_landing_cliente = ?";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_landing_cliente))){
			if ($stmt->rowCount()>0) {
				$ordine = $stmt->fetch(\PDO::FETCH_ASSOC);

				$id_ordine = $ordine['id'];
				$ordine['gruppi'] 			= $this->getAllGruppiFromOrderId($id_ordine);
				$ordine['payouts'] 			= $ordine['gruppi'][$ordine['id_gruppo']]['payouts'];
				$ordine['data_inizio'] 		= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataInizio();
				$ordine['data_fine'] 		= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataFine();
				$landingCliente 			= $this->getLandingCliente($ordine['id_landing_cliente']);
				$ordine['landing_cliente'] 	= $landingCliente;
				$ordine['cliente_id'] 		= $landingCliente->getCliente()->getId(); //['cliente_id'];
				}
		}
		return $ordine;
	}
	
	/**
	* @param ind id_ordine, ind target cliente/fornitore
	* return array ordine con gruppi e payouts
	*/
	
	public function getOrderById($id_ordine, $target = '0'){
		$ordine = array();
		$ordine['payouts'] = array();
		$ordine['cliente_id'] = '';
		$table = 'ordini_cliente';
		if($target=='1'){
			$table = 'ordini_fornitore';
		}
		
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM " . $table . " WHERE id = ? ";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_ordine))){
			$ordine = $stmt->fetch(\PDO::FETCH_ASSOC);
			//$ordine['payouts'] = $this->getPayoutByIdOrdine($order_id);
			$ordine['gruppi'] 		= $this->getAllGruppiFromOrderId($id_ordine, $target);
			$ordine['payouts'] 		= $ordine['gruppi'][$ordine['id_gruppo']]['payouts'];

			$ordine['data_inizio'] 	= '';
			$ordine['data_fine'] 	= '';

			if(isset($ordine['gruppi'][$ordine['id_gruppo']]['info'])){
				$ordine['data_inizio'] 	= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataInizio();
				$ordine['data_fine'] 	= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataFine();
			}
			$landingCliente 			= $this->getLandingCliente($ordine['id_landing_cliente']);
			$ordine['landing_cliente'] 	= $landingCliente;
			//$ordine['cliente_id'] 		= $landingCliente->getCliente()->getId(); //['cliente_id'];
		}
		return $ordine;
	}
	
	
	/*
	* @param int id_gruppo, $target (0 cliente, 1 fornitore)
	* return array di id_payout 
	*/
	public function getPayoutFromGruppoId($id_gruppo, $target = '0'){
		$payouts = array();
		$entityTable = 'Payout_ordine_cliente';
		if($target == '1'){
			$entityTable = 'Payout_ordine_fornitore';
		}
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM payout_gruppo_ordine WHERE target=? AND id_gruppo = ?";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($target,$id_gruppo))){
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
				$payouts[] = $this->getDoctrine()->getRepository('AppBundle:' . $entityTable)->find($row['id_payout']);
			}
		}
		return $payouts;
	}
	
	/*
	* @param int id_ordine
	* return array di id_gruppo
	*/	
	public function getAllGruppiFromOrderId($id_ordine, $target = '0'){
		$gruppi = array();
		$appRepository = 'Payout_ordine_cliente';
		
		if($target=='1'){
			$appRepository = 'Payout_ordine_fornitore';
		}
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT id_gruppo,id_payout FROM payout_gruppo_ordine WHERE id_ordine = ? AND target= ? ORDER BY id_gruppo DESC";
		
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_ordine,$target))){
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
				
				// associo i payouts all'array con chiave = id gruppo 
				$gruppi[$row['id_gruppo']]['payouts'][] = $this->getDoctrine()->getRepository('AppBundle:'.$appRepository)->find($row['id_payout']);
				if(!isset($gruppi[$row['id_gruppo']]['info'])){
					// inserisco nella chiave "info" del gruppo le informazioni su budget e date.
					$gruppi[$row['id_gruppo']]['info'] = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')->find($row['id_gruppo']);
				}
			}
		}
		return $gruppi;
	}
	
	/*
	* @param int id_ordine
	* return array payouts
	*/	
	public function getPayoutByIdOrdine($id_ordine){
		$ordine = false;
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM payout_ordine_cliente WHERE id_ordine = ?";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_ordine))){
			$ordine = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $ordine;
	}
	
	public function getCampaignColumnsAction(Request $request){
		$dominio = $request->get('dominio');
		$tabmail = $request->get('tabmail');
		$colonne = $this->getCPLColumns($dominio, $tabmail);
		$html_options = '';
		foreach($colonne as $colonna){
			$html_options .= '<option data-target="0" value="' . $colonna . '">'.$colonna.'</option>';
		}
		// dati extra 
		$html_options .= $this->getExtraFieldsOptions();
		// pixel columns
		$html_options .= $this->getPixelTraceOptions();
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'html'	=> $html_options,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;		
	}
	
	//AJAX FUNCTIONS --------------------------->
	
	// CONNESSIONE A DATABASE SU CPL
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
	
	################## FUNZIONI DI ESPORTAZIONE CSV 
	
	
	public function exportConteggioAction(Request $request){	
			
			$tabella		= 'lead_uni';
			
			$source_db		= $request->get('dominio');
			$source_tbl 	= $request->get('mail'); 
			$mese			= $request->get('mese'); 
			$anno			= $request->get('anno'); 
			$data_min		= $request->get('data_min'); 
			$data_max		= $request->get('data_max'); 
			$payout_campo 	= $request->get('campo');
			$payout_valore	= $request->get('valore_campo');
			
			$media			= $request->get('media')		;
			$falsemedia		= $request->get('falsemedia')	;
			$id_campagna	= $request->get('id_campagna');
	
			/*
				$nospam			= $request->get('nospam');
				$this->nospam 	= $nospam;
			*/
			$nome_offerta 	= 'Conteggi';
			
			$append_fn = '';
			if(isset($mese) && !empty($mese)){ 		$append_fn .= $mese . '-';  }
			if(isset($anno) && !empty($anno)){ 		$append_fn .= $anno . '-';  }
			if(isset($media) && !empty($media)){	$append_fn .= $media . '-'; }
			
			$filename = preg_replace('/[^A-Za-z0-9]/', "", $nome_offerta) . '_' . $append_fn . date('d-m-Y');
			
			$query = $this->generateQueryForExport($tabella, $source_db, $source_tbl, $media, $falsemedia, $mese, $anno, $data_min, $data_max, $payout_campo);
			
			return $this->export($filename,$query);
			exit();
	}
	
		public function exportConteggioFornitoriAction(Request $request){	
			
			$tabella		= 'lead_uni';
			
			$source_db		= $request->get('dominio');
			$source_tbl 	= $request->get('mail'); 
			$mese			= $request->get('mese'); 
			$anno			= $request->get('anno'); 
			$data_min		= $request->get('data_min'); 
			$data_max		= $request->get('data_max'); 
			$payout_campo 	= $request->get('campo');
			$payout_valore	= $request->get('valore_campo');
			
			$media			= $request->get('media')		;
			$falsemedia		= $request->get('falsemedia')	;
			$id_campagna	= $request->get('id_campagna');
	
			/*
				$nospam			= $request->get('nospam');
				$this->nospam 	= $nospam;
			*/
				
			$nome_offerta 	= 'Conteggi';
			
			$session = new Session();
		//	$session->start();
			$_codes = $session->get('sess_codes');
			$codes 	= !empty($_codes) ? $_codes : '';
			
			$append_fn = '';
			if(isset($mese) && !empty($mese)){ 		$append_fn .= $mese . '-';  }
			if(isset($anno) && !empty($anno)){ 		$append_fn .= $anno . '-';  }
			if(isset($media) && !empty($media)){	$append_fn .= $media . '-'; }
			if(isset($payout_campo) && !empty($payout_campo)){	$append_fn .= $media . '-'; }
			
			$filename = preg_replace('/[^A-Za-z0-9]/', "", $nome_offerta) . '_' . $append_fn . date('d-m-Y');
			
			$query = $this->generateQueryForExport($tabella, $source_db, $source_tbl, $media, $falsemedia, $mese, $anno, $data_min, $data_max, $payout_campo, $codes);
		
			return $this->export($filename,$query);
			exit();
	}
	
		
	
	private function generateQueryForExport($tabella, $source_db, $source_tbl, $media, $falsemedia, $mese, $anno, $data_min, $data_max, $payout_campo= '', $codes = ''){
			$where_cls = array();
			$first_where 	= "source_tbl = '" . $source_tbl . "' AND source_db = '" . $source_db . "'";
			// immetto la selezione dalla tabella nella query
			$where_cls[] 	= $first_where;
				
			
			$query = "SELECT * FROM " . $tabella;
			
			// GESTIONE ESTRAZIONE IN BASE ALLA DATA
	
			if(isset($mese) && !empty($mese)){
				$where_cls[] = "MONTH(data) = '" . $mese . "'";
			}
			if(isset($anno)&& !empty($anno)){
				$where_cls[] = "YEAR(data) = '" . $anno . "'";
			}
			if(isset($data_min)&& !empty($data_min)){
				$where_cls[] = "DATE(data) >= DATE('" . $data_min . "')";
			}
			if(isset($data_max)&& !empty($data_max)){
				$where_cls[] = "DATE(data) <= DATE('" . $data_max . "')";
			}
			if(isset($payout_campo) && !empty($payout_campo)){
				$payout_campo = unserialize($payout_campo);
				if(is_array($payout_campo)){
					foreach($payout_campo as $campo){
						if(!empty($campo->nomecampo)){
							$nomecampo 		= $campo->nomecampo;
							$valorecampo	= trim($campo->valorecampo);
							$where_cls[] 	= $nomecampo . " like '" . $valorecampo . "'";
						}
					}
				}
			}
			
			
			// GESTIONE ESTRAZIONE IN BASE AL MEDIA
			if(!empty($media)){
				$str_where = "(editore = '" . $media . "'";
				if(!empty($media)){
					$str_where .= " OR editore = '".$falsemedia."'";
				
				}
				$str_where .= ")";
				$where_cls[] = $str_where;
			}

			if(isset($codes) && !empty($codes) && is_array($codes)){
				$_CODES = "('" . implode("','",$codes) . "')";
				$where_cls[] = "code IN " . $_CODES;
			}
			
			// GENERAZIONE QUERY FINALE
			if(!empty($where_cls) && count($where_cls)>1){
				$query .= " WHERE ";
				$query .= implode(' AND ',$where_cls);
			}elseif(!empty($where_cls) && count($where_cls)==1){
				$query .= " WHERE " . $where_cls[0];
			}
			//// RAGGRUPPO PER CODE
			//$query .= ' GROUP BY code ';
			/* se raggruppo per code le lead mostrate nello storno possono essere di meno rispetto a quelle 
				* conteggiate in quanto attualmente nei sistemi landing il calcolo del code è inserito nel 
				* momento in cui si atterra.
				* Se l'utente lascia la lead e clicca sul tasto indietro del borwser il form mantiene i dati 
				* inseriti a mano e se rimanda la lead, entrerà una nuova lead con lo stesso codice della precedente.
			*/
			
			return $query;
	}	
	
	private function generateQueryForExportIndiretta($tabella, $id_campagna, $media, $falsemedia, $affiliatoRefid, $mese, $anno, $data_min, $data_max, $payout_campo= '', $codes = ''){
			$where_cls = array();
			$first_where 	= "id_campagna = '" . $id_campagna . "' ";
			// immetto la selezione dalla tabella nella query
			$where_cls[] 	= $first_where;
				
			
			$query = "SELECT * FROM " . $tabella;
			
			if(isset($affiliatoRefid) && !empty($affiliatoRefid)){
				$where_cls[] = "id_agenzia = '" . $affiliatoRefid . "'";
			}
			// GESTIONE ESTRAZIONE IN BASE ALLA DATA
			
			
			if(isset($mese) && !empty($mese)){
				$where_cls[] = "MONTH(dt) = '" . $mese . "'";
			}
			if(isset($anno)&& !empty($anno)){
				$where_cls[] = "YEAR(dt) = '" . $anno . "'";
			}
			if(isset($data_min)&& !empty($data_min)){
				$where_cls[] = "DATE(dt) >= DATE('" . $data_min . "')";
			}
			if(isset($data_max)&& !empty($data_max)){
				$where_cls[] = "DATE(dt) <= DATE('" . $data_max . "')";
			}
			if(isset($payout_campo) && !empty($payout_campo)){
				$payout_campo = unserialize($payout_campo);
				if(is_array($payout_campo)){
					foreach($payout_campo as $campo){
						if(!empty($campo->nomecampo)){
							$nomecampo 		= $campo->nomecampo;
							$valorecampo	= trim($campo->valorecampo);
							$where_cls[] 	= $nomecampo . " like '" . $valorecampo . "'";
						}
					}
				}
			}
			
			
			// GESTIONE ESTRAZIONE IN BASE AL MEDIA
			if(!empty($media)){
				$str_where = "(media = '" . $media . "'";
				if(!empty($media)){
					$str_where .= " OR media = '".$falsemedia."'";
				
				}
				$str_where .= ")";
				$where_cls[] = $str_where;
			}
			// --------------------------------------
			
			if(isset($codes) && !empty($codes) && is_array($codes)){
				$_CODES = "('" . implode("','",$codes) . "')";
				$where_cls[] = "code IN " . $_CODES;
			}
			
			// GENERAZIONE QUERY FINALE
			if(!empty($where_cls) && count($where_cls)>1){
				$query .= " WHERE ";
				$query .= implode(' AND ',$where_cls);
			}elseif(!empty($where_cls) && count($where_cls)==1){
				$query .= " WHERE " . $where_cls[0];
			}
			
			return $query;
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
	
	/*###################### FINE CLIENTI ORDINI ################################******************************/
	
	
	
	
	
	
	//*** ######################      FORNITORI ---------------------------------------------------------------------- ***/
	
	// RENDER FUNCIONS
	  public function fornitoriAction($message = null){
		//$ordini 	= $this->getOrdiniFornitori();
		$datepicker 		= $this->getDatePicker();
		return $this->render('listato_ordini_fornitore.html.twig', array(
			'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
			'datepicker'  		=> $datepicker,
			//'message' 		=> $message,
        ), null); 
    }
	// render conteggi Clienti
	public function conteggiFornitoriAction($message = null){
		$landingClienti		= $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->findBy(array(), array('data_start' => 'ASC'));
		$datepicker 		= $this->getDatePicker();
		$fornitori 			= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->findBy(array(), array('nome' => 'ASC'));
		
		return $this->render('conteggi_fornitori.html.twig', array(
		   'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
		   'landingClienti' => $landingClienti,
		   'datepicker' 	=> $datepicker,
		   'fornitori' 		=> $fornitori,
		), null); 
	}
	// render pagina di aggiunta payout fornitori
	public function creaFornitoreAction($message = null){
		$fornitori 			= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->findBy(array(), array('nome' => 'ASC'));
		$affiliati 			= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->findBy(array(), array('nome' => 'ASC'));
		$landingCliente =  $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->findBy(array('clienteAttivo' =>'1'));
		
		return $this->render('crea_ordini_fornitore.html.twig', array(
			'csrf_token' 		=> $this->getCsrfToken('sonata.batch'),
			'fornitori' 		=> $fornitori,
			'affiliati' 		=> $affiliati,
			'landingCliente' 	=> $landingCliente,
		), null); 
	}
	
	// render pagina di modifica ordine fornitore
	public function modificaFornitoreAction(Request $request, $message = null){
		$ordine_id 				= $request->get('ordine_id');
		$ordine 				= $this->getOrderFornitoreById($ordine_id);
		$fornitore 				= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->find($ordine['id_fornitore']);
		$fornitore_nome 		= $fornitore->getNome();
		$affiliato_nome	 		= '';
		if(!empty($ordine['id_affiliato'])){
			$affiliato = $this->getDoctrine()->getRepository('AppBundle:Affiliati')->find($ordine['id_affiliato']);
			if($affiliato){
				$affiliato_nome = $affiliato->getNome();
			}
		}
		$landing_cliente_info	= $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($ordine['id_landing_cliente']);
		$landing_cliente		= $landing_cliente_info->getCampagna()->getNomeOfferta() . '- ' . $landing_cliente_info->getCliente()->getName() . ' - Landing: ' . $landing_cliente_info->getLanding()->getSlugLanding();
		$colonne 				= $this->getPixelTraceColumns(); //($landing_cliente_info->getDbCliente(), $landing_cliente_info->getMailCliente());
		return $this->render('edit_ordini_fornitore.html.twig', array(
			'csrf_token' 			=> $this->getCsrfToken('sonata.batch'),
			'ordine' 				=> $ordine  ,
			'fornitore_nome' 		=> $fornitore_nome ,
			'affiliato_nome' 		=> $affiliato_nome ,
			'landing_cliente' 		=> $landing_cliente  ,
			'landing_cliente_info' 	=> $landing_cliente_info,
			'colonne'		 		=> $colonne  ,
		), null); 
	}
	
	// FINE RENDER FUNCTIONS -------------------------------->
	
	// FUNZIONI DATI 
	public function getOrdiniFornitori(){
		$ordini = array();
		$em 	= $this->getDoctrine()->getManager();
		$conn 	= $em->getConnection();
		$target = '1';
		
		if($conn){
			$sql_ordini = "select * from ordini_fornitore order by data_creazione desc";
			$stmt 	= $em->getConnection()->prepare($sql_ordini);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($row = $stmt->fetch()) {
					$ordine=$row;
					$ordine['landing_cliente'] 	= $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($row['id_landing_cliente']);
					$ordine['gruppi'] 			= $this->getAllGruppiFromOrderId($ordine['id'],'1');
					$ordine['payouts'] 			= $this->getPayoutFromGruppoId($ordine['id_gruppo'], $target);

					$payouts = array();
					foreach($ordine['payouts'] as $payout){
						$payouts[] = $payout->getPayout();
					}
					$ordine['payout'] = implode(',',$payouts);
					$fornitore_id 				= $ordine['id_fornitore'];
					$ordine['fornitore'] 		= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->find($fornitore_id);
					if(!empty($ordine['id_affiliato'])){
						$ordine['affiliato'] 	= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->find($ordine['id_affiliato']);
					}
					$ordini[] = $ordine;
				}
			}
		}
		return $ordini;
	}
	 
	public function getOrderByIdLandingFornitore($id_landing_cliente,$id_fornitore,$id_affiliato = ''){
		$ordine = false;
		$valori_sql = array($id_landing_cliente,$id_fornitore);
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM ordini_fornitore WHERE id_landing_cliente = ? AND id_fornitore = ?";
		
		if(isset($id_affiliato) && !empty($id_affiliato)){
			$sql  .= " AND id_affiliato = ? ";
			$valori_sql[] = $id_affiliato;
		}
		
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute($valori_sql)){
			$ordine = $stmt->fetch(\PDO::FETCH_ASSOC);
		}
		return $ordine;
	}
		
		
	// FUNZIONI DI SALVATAGGIO 
	
	public function getFornitoreAffiliati($id_fornitore){
		$valori_sql = array($id_landing_cliente,$id_fornitore);
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM affiliati WHERE id_landing_cliente = ? AND id_fornitore = ?";
		
		if(isset($id_affiliato) && !empty($id_affiliato)){
			$sql  .= " AND id_affiliato = ? ";
			$valori_sql[] = $id_affiliato;
		}
		
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute($valori_sql)){
			$ordine = $stmt->fetch(\PDO::FETCH_ASSOC);
		}
		return $ordine;
	}
	
	
	
	/**
	 * Se l'ordine esiste vado ad effettuare un controllo sulle date dei payout:
	 * --> ORDINE - PRELEVO TUTTI I GRUPPI PAYOUT DI QUELL'ORDINE
				  - CONTROLLO LA DATA DELL'ULTIMO GRUPPO INSERITA
				   - CASI 
				   -- DATA PARTENZA MAGGIORE = SE LA DATA è MAGGIORE, AGGIORNO IL GRUPPO IMPOSTANDO IL GIORNO -1 COME DATA DI TERMINE
				   -- DATA PARTENZA UGUALE = SE LA DATA è UGUALE AGGIORNO SOLO PAY , BUDGET E TETTO TRASH DEL GRUPPO ATTUALE
				   -- DATA PARTENZA INFERIORE = NON FACCIO NULLA??
	 *				
	 *				
	**/
		
	public function salvaOrdineFornitoreAction(Request $request){
		$id_landing_cliente 	= $request->get('fornitoreCampagna');
		$fornitori			 	= $request->get('id_fornitore');
		$id_agenzia		 		= $request->get('id_agenzia');
		$budget					= $request->get('budget');
		$tetto_trash			= $request->get('trash');
		$tipo_payout			= $request->get('tipo_payout');
		$ordine_descrizione		= $request->get('ordine_descrizione');
		$data_inizio			= $request->get('data_inizio');
		$data_fine				= $request->get('data_fine');
		
		$valore_campo_multiplo 	= array();
		$target_campo_multipli 	= array();
		$payout_multiplo 		= array();
		$campo_payout 			= array();
		$payout_descrizione		= array();
		
		$payoutArray 			= array();
		$target 				= 1; 		// fornitore
		
		// creazione dell'array payouts
		$payouts 				= json_decode($request->get('payouts'));
		
		// gestione fornitori multipli
		if(count($fornitori)>1){ // se i fornitori inviati alla funzione sono + di 1
			foreach($fornitori as $id_fornitore){
				$affiliati	= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->findBy(array('id_fornitore' => $id_fornitore));
				// imposto l'array degli affiliati con un unico elemento per permettere almeno un salvataggio
				if(empty($affiliati)){ 
					$affiliati = array(0 => null);
				}
				// se sono stati trovati affiliati li itero, altrimenti setto l'array a 1 elemento
				foreach($affiliati as $affiliato){ 
					// se l'affiliato che sto iterando non è null (cioè il fornitore non ha affiliati recupero l'id
					if(!empty($affiliato)){ 
						$id_agenzia = $affiliato->getId();
					}else{
						$id_agenzia = null;
					}
					$order 	= $this->checkOrderFornitore($id_landing_cliente,$id_fornitore,$id_agenzia);
					/** 
					 * se l'ordine è esistente, verifico se le date del pay sono uguali. In caso positivo aggiorno l'ordineData
					 * altrimenti aggiungo un nuovo gruppo payout per quest'ordine
					**/ 
					if($order){ // ordine esistente
						$this->gestioneOrdineEsistente($order,$payouts,$budget,$tetto_trash,$data_inizio,$data_fine,$target);
					}else{ // se l'ordine non esiste
						// creo il gruppo
						$id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine);
						$id_ordine = $this->insertOrdineFornitore($id_landing_cliente,$id_fornitore,$id_agenzia,$id_gruppo,$ordine_descrizione);
						$this->salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target); // $payouts deve essere un array
					} // fine if controllo esistenza ordine
				} // fine loop affiliati
			} // fine loop fornitori
		}else{ // SE I FORNITORI SELEZIONATI SONO SOLO 1
			$id_fornitore = $fornitori[0];
			$order 	= $this->checkOrderFornitore($id_landing_cliente,$id_fornitore,$id_agenzia);
			
			if($order){ // se l'ordine è esistente confronto le date del suo gruppo con quelle inserite
				$this->gestioneOrdineEsistente($order,$payouts,$budget,$tetto_trash,$data_inizio,$data_fine,$target);
			}else{ // se l'ordine non esiste
				$id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine); // creo il gruppo
				$id_ordine = $this->insertOrdineFornitore($id_landing_cliente,$id_fornitore,$id_agenzia,$id_gruppo,$ordine_descrizione);
				$this->salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target); // $payouts deve essere un array
			} // fine if controllo esistenza ordine
		}
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> true,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	/**
	 * La funzione gestisce il caso in cui l'ordine (fornitore) esista. Controllo se i dati inseriti fanno riferimento alla stessa data o meno.
	 * In caso la data sia la stessa, l'ordine viene aggiornato, in caso contrario viene associato un nuovo gruppo con nuovi payouts e 
	 * inserita una data di termine al precedente gruppo.
	 * Attualmente la funzione è attiva solo per i fornitori per creazione in bulk
	**/
	public function gestioneOrdineEsistente($order,$payouts,$budget,$tetto_trash,$data_inizio,$data_fine,$target){
	
		$em  			= $this->getDoctrine()->getManager();
		$id_ordine 		= $order['id'];
		// prelevo la data di partenza del payout 
		$data_inizio_ordine = $order['gruppo']->getDataInizio();
		$createDate 		= new \DateTime($data_inizio_ordine);
		$data_inizio_ordine = $createDate->format('Y-m-d');
		
		$data_inizio_creata	= \DateTime::createFromFormat('d/m/Y', $data_inizio)->format('Y-m-d');
		
		$id_gruppo = $order['gruppo']->getId();
		
		/**
		 * Se le date di partenza sono uguali eseugo l'UPDATE dell'ordine: 
		 * ricavo l'id del gruppo, aggiorno budget e tetto trash del gruppo 
		 * per il gruppo ricavo gli id (se previsti più di uno) dei payouts, elimino tutti i payout associati e i loro riferimenti 
		 * nella tabella payout_gruppo_ordine, inserisco nuovi payout e associo all'id_gruppo.
		 **/

		if(strtotime($data_inizio_ordine) == strtotime($data_inizio_creata)){
			// aggiorno il gruppo
			$gruppo = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')->find($id_gruppo);
			$gruppo->setBudget($budget)->setTettoTrash($tetto_trash);
			$em->persist($gruppo);
			$em->flush();
			// rimuovo i vecchi payout e l'associazione nel gruppo 
			$__payouts = $this->getPayoutFromGruppoId($id_gruppo, $target); // prelevo i payout
			$this->deleteAllPayout($__payouts, $id_gruppo, $target); // la funzione rimuove sia i payout che l'associazione al gruppo
			// una volta eliminati tutti i payout e le associazioni, ricreo i nuovi pay e riassocio
			$this->salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target); // la funzione crea nuovi payout e associa al gruppo passato
			// FINE UPDATE DELL'ORDINE
				
			// nel caso le date di partenza del pay e quelle inviate non sono uguali, ma l'ordine esiste, provvedo a creare un nuovo gruppo e pay 
		}elseif(strtotime($data_inizio_creata)>strtotime($data_inizio_ordine)){ 
			/**
			 * In questo caso devo semplicemente crare un nuovo payout e un nuovo gruppo, modificando il vecchio inserendo come data di fine quella di inizio inviata - 1 day
			 * come se fosse una classica modifica dell'ordine 
			**/
			
			// aggiorno la data dell'ultimo gruppo associato all'ordine con la data di inizio del nuovo gruppo
			$_data_fine_gruppo = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($data_inizio_creata)));
			$this->updateDataFinePayoutGroup($id_gruppo,$_data_fine_gruppo); 
			
			$new_id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine);
			$this->salvaNuovoPayout($payouts,$new_id_gruppo,$id_ordine,$target); // $payouts deve essere un array
			$this->updateOrderGruppo($id_ordine,$new_id_gruppo,$target); // aggiorno l'ordine con il nuovo gruppo creato.
		}
	}
	
	
	/**
	 * La funzione riceve in ingresso un array di entità payout 
	 * e per ogni entità rimuove la sua corrispondenza nel db
	**/
	public function deleteAllPayout($payouts, $id_gruppo, $target){
		if(isset($payouts)){
			
			if($target == '1'){
				$entityTable = 'Payout_ordine_fornitore';
			}else{
				$entityTable = 'Payout_ordine_cliente';
			}
			// ids_payouts deve essere un array	
			
			$em  = $this->getDoctrine()->getManager();
			
			// per ogni id payout rimuovo il suo riferimento nel db 
			foreach($payouts as $payout){
				// prelevo il payout 
				$em->remove($payout);
				$em->flush();
			}
			$this->deletePayoutGruppoOrdine($id_gruppo);
		}
	}
	
	/**
	 * La funzione elimina dalla tabella payout_gruppo_ordine la riga inerente al gruppo passato. 
 	 * Essendo gli id gruppi univoci, non si crea la problematica di passare anche un parametro target:
	**/
	public function deletePayoutGruppoOrdine($id_gruppo){
		$sql 	= "DELETE FROM payout_gruppo_ordine WHERE id_gruppo=?";
		$em  			= $this->getDoctrine()->getManager();
		$stmt 	= $em->getConnection()->prepare($sql);
		$stmt->execute(array($id_gruppo));                            
									
	}
	
	/**
	 * La funzione crea dei nuovi payout passati tramite array e associa questi all'id_gruppo 
	 * e all'id_ordine passati nella funzione
	**/
	public function salvaNuovoPayout($payouts,$id_gruppo,$id_ordine,$target){
		if(isset($payouts) && is_array($payouts)){
			$em  = $this->getDoctrine()->getManager();
			
			foreach($payouts as $payoutRow){
				$campo_serializzato = null;
				if(!empty($payoutRow->campi[0]->nomecampo)){
					$campo_serializzato = serialize($payoutRow->campi);
				}
				
				$data_creazione = new \DateTime("now");
				
				if($target == '1'){
					$payoutEntity = new PayoutOrdineFornitore();
				}else{
					$payoutEntity = new PayoutOrdineCliente();
				}
				$payoutEntity->setCampo($campo_serializzato)
							 ->setPayout		($payoutRow->payout)
							 ->setDescrizione	($payoutRow->descrizione)
							 ->setDataCreazione	($data_creazione);
				
				$em->persist($payoutEntity);
				$em->flush();
				$id_payout =  $payoutEntity->getId();
				$this->associaPayoutGruppoOrdine($id_payout,$id_gruppo,$id_ordine,$target);
			}
		}
	}
	
	public function getPayoutMultiploValue($_valori, $_target, $_payout, $_campi, $_descrizioni){
		$payoutArray = array();

		parse_str($_valori, 		$valore_campo_multiplo	);
		parse_str($_payout, 		$payout_multiplo		);
		parse_str($_campi , 		$campo_payout 			);
		parse_str($_descrizioni , 	$payout_descrizione		);

		$target_campo_multipli = json_decode($_target);
		
		if(!empty($campo_payout)){
			for($i=0;$i<count($campo_payout['campo_multiplo']);$i++){
				if(trim($payout_multiplo['payout_multiplo'][$i])!='' && trim($valore_campo_multiplo['valore_campo_multiplo'][$i])!=''){
					$payoutArray[$i]['campo'] 		= $campo_payout['campo_multiplo'][$i];
					$payoutArray[$i]['valore']		= $valore_campo_multiplo['valore_campo_multiplo'][$i];
					$payoutArray[$i]['payout']		= $payout_multiplo['payout_multiplo'][$i];
					$payoutArray[$i]['descrizione']	= $payout_descrizione['payout_descrizione'][$i];
					$payoutArray[$i]['tipo_campo']	= $target_campo_multipli[$i]->target;
				}
			}
		}
		return $payoutArray;
	}

	public function getPayoutSingoloValue($payout, $payout_descrizione){
		$payoutArray = array();

		$payoutArray[0]['campo']  		= null;
		$payoutArray[0]['valore'] 		= null;
		$payoutArray[0]['tipo_campo'] 	= null;
		$payoutArray[0]['payout'] 		= $payout;
		$payoutArray[0]['descrizione'] 	= $payout_descrizione;

		return $payoutArray;
	}
	
	public function getPayoutByIdOrdineFornitore($id_ordine){
		$ordine = false;
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM payout_ordine_fornitore WHERE id_ordine = ?";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_ordine))){
			$ordine = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $ordine;
	}
	
	public function deleteOrderFornitoreAction(Request $request){
		$id = $request->get('id');
		$target = 1; // target 1 fornitore
		$result = false;
		
		if(!empty($id)){
			// step 1 - eliminazione ordine 
			$em  		= $this->getDoctrine()->getManager();
			$sql_base 	= "DELETE FROM ordini_fornitore WHERE id=?";
			$stmt 		= $em->getConnection()->prepare($sql_base);
			if($stmt->execute(array($id))){
				
				//step 2 - recupo gli id_gruppo e id payout
				//$payouts = $this->getPayoutFromGruppoId($id_gruppo)
				$sql_get_ids = "select id, id_gruppo, id_payout from payout_gruppo_ordine
								WHERE target=1 AND id_ordine = ?";
				
				$stmt_get 	= $em->getConnection()->prepare($sql_get_ids);
				if($stmt_get->execute(array($id))){
					while($row = $stmt_get->fetch()){
						$_em  	= $this->getDoctrine()->getManager();

						$payout = $this->getDoctrine()->getRepository('AppBundle:Payout_ordine_fornitore')->find($row['id_payout']);
						$gruppo = $this->getDoctrine()->getRepository('AppBundle:Payout_gruppi')->find($row['id_gruppo']);
						
						// step 3 e 4 - rimuovo payout e gruppo
						if($payout){
							$_em->remove($payout);
						}
						if($gruppo){
							$_em->remove($gruppo);
						}
						$_em->flush();
					}
				}
				// step 5 rimuovo tutte le righe con id_ordine dalla tabella payout_gruppo_ordine
				$sql_del_go 	= "DELETE FROM payout_gruppo_ordine WHERE id_ordine=? and target=1";
				$stmt_del_go 	= $em->getConnection()->prepare($sql_del_go);
				if($stmt_del_go->execute(array($id))){
					$result = true;
				}
				
			}
		}
		$response = new Response();
		$response->setContent(json_encode(array('eliminato' => $result)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	// funzione principale conteggio CLIENTE
	public function conteggiaFornitoreAction(Request $request){

		$resultsRows 			= array();
		$id_landing_cliente		= $request->get('id_landing_cliente');
		$id_fornitore 			= $request->get('id_fornitore');
		$id_affiliato 			= $request->get('id_affiliato');
		$mese 					= $request->get('mese');
		$anno 					= $request->get('anno');
		$data_max 				= $request->get('data_max');
		$data_min 				= $request->get('data_min');
		$fornitori = array();

		$this->indice_riga		= 0;
		// render header tabella
		$html = '';
		$html .='<table id="listatoConteggi" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
						<thead>
							<td>Fornitore</td>
							<td>Campagna</td>
							<td>Tipo Payout</td>
							<td>Tot Lead</td>
							<td>% Tetto Trash</td>
							<td>Lead Trash</td>
							<td>% Eff. Lead Trash</td>
							<td>Lead Nette</td>
							<td>Budget</td>
							<td>Delta Budget</td>
							<td>Payout(€)</td>
							<td>Totale(€)</td>
							<td>Download</td>
							<td>Storni</td>
						</thead>
					<tbody>';
		
		if(strtolower($id_landing_cliente)=='all'){
			// prelevo tutti gli id degli ordini con payout che ricadono nel mese e nell'anno inviato alla funzione 
			$orders_ids = $this->getAllFornitoreCampaignInfo($mese, $anno, $id_fornitore, $id_affiliato);
			
			if(is_array($orders_ids)){
				foreach($orders_ids as $order_ids){
					$order_id = $order_ids['id_ordine']	;
					
					$ordine			= $this->getOrderById($order_id,1);
						
					$fornitore 		= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->find($ordine['id_fornitore']);
					$affiliato 		= '';
					if(!empty($ordine['id_affiliato'])){
						$affiliato 		= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->find($ordine['id_affiliato']);
					}
					//$landing_cliente= $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($campaignInfo['id_landing_cliente']); 
					
					$html .= $this->generateFornitoreTableRow($ordine, $fornitore, $affiliato, $mese, $anno);
				}
			}
		}else{ // se è stata selzionata una unica campagna 
			
			if($id_fornitore=='all'){
				$fornitori		= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->findAll();
			}else{ 
				// se il fornitore è unico, $fornitori deve essere un array ad unico valore
				$fornitori[] 	= $this->getDoctrine()->getRepository('AppBundle:Fornitori')->find($id_fornitore);

				
				//controllo se è stato richiesto un affiliato
				if(!empty($id_affiliato)){
					// se è stato selezionato un affiliato verifico se è singolo o tutti 
					if($id_affiliato=='all'){
						$affiliati		= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->findBy(array(),array('nome' => 'ASC'));
					}else{
						$affiliati[] 	= $this->getDoctrine()->getRepository('AppBundle:Affiliati')->find($id_affiliato);
					}
				}
			}
			
			foreach($fornitori as $fornitore){
				
				if(!empty($id_affiliato)){
					
				
					
					foreach($affiliati as $affiliato){
						$ordine = $this->getOrdineFornitoreInfo($id_landing_cliente,$fornitore->getId());
						
						
					//	echo $affiliato->getId();
						
						if($ordine){
							$html .= $this->generateFornitoreTableRow($ordine, $fornitore, $affiliato, $mese, $anno);
						}
					}
				
				}else{
					// non sono stati selezionati affiliati
					$ordine = $this->getOrdineFornitoreInfo($id_landing_cliente,$fornitore->getId());

					if($ordine){
						$html .= $this->generateFornitoreTableRow($ordine, $fornitore, '', $mese, $anno);
					}
				}
			}
		}
		$html .='</tbody></table>';
		
		
		$response = new Response();
		$response->setContent(json_encode(array('html' => $html, 
												'totali' => $this->totali_righe, 
												'footer' => $this->tableFooter,
												)
											)
								);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
		
	public function getAllFornitoreCampaignInfo($mese,$anno,$id_fornitore = '', $id_affiliato = ''){
		$valori = array();
		$campagneInfo = array();
		
		/*
		$sql = "SELECT id_landing_cliente,
						id_fornitore,
						id_affiliato
				FROM ordini_fornitore "; 
			if(!empty($id_fornitore) && strtolower($id_fornitore)!='all'){
				$sql .= " WHERE id_fornitore = ?";
				$valori[] = $id_fornitore;
				if(!empty($id_affiliato) && strtolower($id_affiliato)!='all'){
					$sql .= " AND id_affiliato = ?";
					$valori[] = $id_affiliato;
				}
			}
			$sql .= " ORDER BY id_fornitore ASC, data_creazione DESC";

		*/
			
		$sql = "select pg.id_ordine from payout_gruppo_ordine pg
					inner join payout_gruppi g
					on pg.id_gruppo = g.id
					right join ordini_fornitore o
					on pg.id_ordine =  o.id
					where pg.target = 1
					AND (100*".$anno.")+".$mese."
					between 
					YEAR(g.data_inizio)*100 + MONTH(g.data_inizio)  
					AND 
					YEAR(IFNULL(g.data_fine,NOW()))*100 + MONTH(IFNULL(g.data_fine,NOW())) "; 
		if(!empty($id_fornitore) && strtolower($id_fornitore)!='all'){
			$sql .= " AND o.id_fornitore = ? ";
			$valori[] = $id_fornitore;
			if(!empty($id_affiliato) && strtolower($id_affiliato)!='all'){
				$sql .= " AND o.id_affiliato = ?";
				$valori[] = $id_affiliato;
			}
		}
		$sql .= " group by pg.id_ordine
					order by g.data_inizio asc";
		
		$eman   	= $this->getDoctrine()->getManager();
		$stmt_cmp 	= $eman->getConnection()->prepare($sql);
		if($stmt_cmp->execute($valori)){
			$_campagneInfo = $stmt_cmp->fetchAll();
		}
		return $_campagneInfo;
	}
	
	
	public function convertEntitiesToChar($str){
		$stringa = str_replace('&amp;','e',$str);
		$stringa = str_replace('&','e',$stringa);
		return $stringa;
	}
    /** !!! controllare se ordine info contiente le seguenti informazioni:
		$ordineInfo['gruppi'] -> singolo gruppo deve esser un entità doctrine
		$ordineInfo['payouts']
		$gruppo['info']
		
	*/
	
	public function generateFornitoreTableRow($ordineInfo, $fornitore, $affiliato = '', $mese, $anno){
		$html 			 = '';
		$media 			 = $fornitore->getMedia();
		$falsemedia 	 = $fornitore->getFalsemedia();
		$nomeFornitore 	 = $fornitore->getNome();
		$target          = '1';
		

		// AFFILIATO 
		$affiliatoRefid	 = '';
		if(!empty($affiliato)){
			$affiliatoRefid	 = $affiliato->getRefid();
			$nomeFornitore   = $affiliato->getNome();
		}
		
		$landing_cliente = $ordineInfo['landing_cliente'];
		$nomeOfferta	 = $this->convertEntitiesToChar($landing_cliente->getCampagna()->getNomeOfferta());
		$db_cliente		 = $landing_cliente->getDbCliente();
		$tab_cliente	 = $landing_cliente->getMailCliente();
		$slugLanding	 = $landing_cliente->getLanding()->getSlugLanding();
		
		
		// itero tutti i gruppi appartenenti al payout del fornitore
		foreach($ordineInfo['gruppi'] as $id_gruppo => $gruppo){
			$_data_inizio	= $gruppo['info']->getDataInizio();
			$data_fine 		= $gruppo['info']->getDataFine();
			$_data_fine 	= empty($data_fine) ? date('Y-m-d', time()) : $data_fine;				
			
			$tetto_trash	= $gruppo['info']->getTettoTrash();
			$budget			= $gruppo['info']->getBudget();
			
			// verifica se il gruppo attualmente iterato ha data iniziale e data finale compresa tra la data passata:
			
			
			/**
			 * Logica: prelevo l'anno e il mese dalle date del gruppo, moltiplico l'anno per 100 e sommo il mese (es: 2018-01-25 => (2018*100)+01 = 201801).
			 * lo stesso faccio per l'anno e il mese da ricercare (es. anno 2018, mese Marzo => (2018*100)+03 = 201803)
			 * Una volta ottenuti i 3 numeri (datainiziale, datafinale, datadaricercare), verifico che la datadaricercare sia >= alla data di inizio e <= alla data di fine
			 * in questo modo posso controllare se il mese e l'anno sono all'interno del range senza tenere conto del giorno
			**/
			
			
			
			// somma delle date
			$data_inizio_anno 	=date("Y",strtotime($_data_inizio));
			$data_inizio_mese 	=date("m",strtotime($_data_inizio));
			$data_fine_anno 	=date("Y",strtotime($_data_fine));
			$data_fine_mese 	=date("m",strtotime($_data_fine));
			
			$sumDataInizio 		= ($data_inizio_anno*100 )	+ $data_inizio_mese;
			$sumDataFine 		= ($data_fine_anno*100 )	+ $data_fine_mese;
			
			// data da ricercare
			$sumDataRicerca = ($anno*100) + $mese;
			
			
			//echo "data inizio: " 	. $sumDataInizio . "<br>";
			//echo "data fine: " 		. $sumDataFine. "<br>";
			//echo "data ricerca: " 		. $sumDataRicerca. "<br>";
			//echo "-----------------------------------<br>";
			// data inizio: 201803        <br>
			// data fine: 201804          <br>
			// data ricerca: 201803       <br>
			// -----------------------------------

			if($sumDataRicerca >= $sumDataInizio && $sumDataRicerca <= $sumDataFine){	
				
				if(is_array($ordineInfo['payouts']) && !empty($ordineInfo['payouts']) ){ // gestione payout
					
					foreach($gruppo['payouts'] as $payout){
						$lead_totali		= 0;
						$lead_trash			= null; // inizializzo le lead trash come null
						$id_payout 			= $payout->getId();
						$descrizione_payout	= $payout->getDescrizione();
						$valore_payout		= $payout->getPayout();
						$payout_campo		= $payout->getCampo();
						$payout_tipo_campo	= $payout->getTipoCampo();
						$payout_campo_valore= $payout->getCampoValore();
						$storno_class 		= "";
						if(isset($payout_campo) && !empty($payout_campo)){ 
							$campi_payout = unserialize($payout_campo);
							$lead_totali = $this->getCampagnaFornitoreTotaleParziale($landing_cliente, $media, $falsemedia, $affiliatoRefid, $mese, $anno, $_data_inizio, $_data_fine, $campi_payout);
						}else{ 
							$lead_totali = $this->getCampagnaFornitoreTotaleParziale($landing_cliente, $media, $falsemedia, $affiliatoRefid, $mese, $anno, $_data_inizio, $_data_fine, '');
						}
						
						// mi salvo le lead totali senza operazioni eseguite (storni o modifiche a mano)
						$lead_originali_base = $lead_totali; 
						
						// GESTIONE DEGLI STORNI SE PRECEDENTEMENTE IMPOSTATI
						$tot_storni = 0;
						$em 	= $this->getDoctrine()->getManager();
						
						$storno = $em->getRepository('AppBundle:Ordini_storni')->findOneBy([
																	'id_ordine' 	=> $ordineInfo['id'],
																	'id_payout' 	=> $id_payout,
																	'ordine_mese' 	=> $mese,
																	'ordine_anno' 	=> $anno,
																	'target' 		=> $target, // fornitore
																	]);
						if(!empty($storno)){
							$leads_code = $storno->getLeadsCode();
							if(!empty($leads_code)){
								$storni_arr  = unserialize($leads_code);
								$tot_storni  = count($storni_arr);
								// riscrivo i totali se ci sono storni precedentemente settati
								$lead_totali = $lead_totali-$tot_storni;
								$storno_class = "giastornata";
							}
						}
						
						// INIZIO GESTIONE ORDINE MODIFICATO A MANO -------------------------------------------------------------------
						$ordineModificato = $em->getRepository('AppBundle:Ordini_modificati')->findOneBy([
																	'id_ordine' 	=> $ordineInfo['id'],
																	'id_payout' 	=> $id_payout,
																	'ordine_mese' 	=> $mese,
																	'ordine_anno' 	=> $anno,
																	'target' 		=> $target, // fornitore
																	]);
						
						// CREO L'ICONA NEL CASO CI SIA STATA UNA VARIAZIONE DI LORDE O DI TRASH 
						$icon_original_lorde = '';
						$icon_original_trash = '';
						
						// SE E' IMPOSTATO UN ORDINE MODIFICATO MANUALMENTE, PRENDO LE DIFFERENZE PER LE LEAD LORDE E PER LE LEAD TRASH
						if(!empty($ordineModificato)){
							$differenza_lorde  	= $ordineModificato->getDifferenzaLorde();
							$differenza_trash  	= $ordineModificato->getDifferenzaTrash();
							$lead_trash 		= $ordineModificato->getTrashModificato(); // prelevo eventuale trash modificato, se non è mai stato modificato questo valore è null
							$base_trash			= $ordineModificato->getBaseTrash();
							$base_lorde			= $ordineModificato->getBaseLorde();
						
							// aggiungo la differenza delle lead lorde ai totali
							$lead_totali = $lead_totali+$differenza_lorde;
						
							// IL LORDO E' STATO MODIFICATO
							if($base_lorde!=$lead_totali){
								$icon_original_lorde = '<div class="tooltip-addedLead tooltipl text-center"><i class="fa fa-link" aria-hidden="true"></i><span class="tooltipltext">'.$base_lorde.'</span></div>';
							}
							
							// GESTIONE LEAD TRASH SE MODIFICATE A MANO
							if(is_numeric($base_trash)){ // IL TRASH E' STATO MODIFICATO
								if($base_trash!==$lead_trash){ // il trash modificato deve essere diverso da quello originale, in caso positivo mostro l'iconcina con il trash originale
									$icon_original_trash = '<div class="tooltip-addedLead tooltipl text-center"><i class="fa fa-link" aria-hidden="true"></i><span class="tooltipltext">'.$base_trash.'</span></div>';
									
								}
								// differenza_trash può essere un numero positivo o negativo
								//$base_trash  = $base_trash + $differenza_trash; 
							}
						} // fine se ordine è stato modificato a mano
						// FINE GESTIONE ORDINE MODIFICATO A MANO --------------------------------------------------------------------------
							
						// inizializzazione dei valori
						$perc_lead_trash_rouded	= 0;
						$lead_nette				= 0;
						$delta_budget			= 0;
						$totale					= 0;
					
					$link_download = '<div class="tooltipl text-center"><i class="fa fa-times-circle" aria-hidden="true"></i><span class="tooltipltext">Non ci sono lead da scaricare</span></div>';
					$link_storni   = '<div class="tooltipl text-center"><i class="fa fa-times-circle" aria-hidden="true"></i><span class="tooltipltext">Non ci sono lead da scaricare</span></div>';
					if(!empty($lead_totali)){
							
						// arrotondo: è un fornitore e arrotondo per eccesso
						$arrotonda = 'eccesso';
						// calcolo 
						
						$calcolato = $this->calcolaPaysSuModifica($lead_totali,$tetto_trash,$budget,$valore_payout,$lead_trash,$arrotonda);
						
						// QUI CALCOLO LE LEAD TRASH EFFETTIVE RELATIVE AL TOTALE DELLE LEAD CALCOLATO
						$lead_trash 				= !empty($calcolato->lead_trash) ? $calcolato->lead_trash : '0'; 
						
						$perc_eff_lead_trash 		= $calcolato->perc_eff_lead_trash;
						$perc_lead_trash_rouded 	= $calcolato->perc_lead_trash_rouded;
						$lead_nette 				= $calcolato->lead_nette;
						$delta_budget 				= $calcolato->delta_budget;
						$totale 					= $calcolato->totale;

						$payout_campo_par 			= isset($payout_campo) 			? $payout_campo			: '';
						$payout_campo_valore_par 	= isset($payout_campo_valore) 	? $payout_campo_valore	: '';
						$nospam_par 				= !empty($this->nospam) 		? '1'					: '';
						
						$url_download = $this->admin->generateUrl(	'exportConteggioFornitori', array(
																	'dominio'		=> $db_cliente,
																	'mail'			=> $tab_cliente,
																	'media'			=> $media,
																	'falsemedia'	=> $falsemedia,
																	'id_campagna'	=> $slugLanding,
																	'mese'			=> $mese, 
																	'data_min'		=> $_data_inizio, 
																	'data_max'		=> $_data_fine, 
																	'anno' 			=> $anno,
																	'campo'			=> $payout_campo_par,
																	'valore_campo'	=> $payout_campo_valore_par,
																	'nospam'		=> $nospam_par,
														)
													);
						$link_download 	= '<a href="'.$url_download.'">
											<i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i> Scarica
											</a>';
										
						$link_storni='<a href="javascript:void(0)" onclick="getStornoFornitoriTable(\''.$ordineInfo['id'].'\',\''.$id_payout.'\',\''. $landing_cliente->getId() .'\',\''.$media.'\',\''.$falsemedia.'\',\''.$affiliatoRefid.'\',\''.$mese.'\',\''.$anno.'\',\''.$_data_inizio.'\',\''.$_data_fine.'\',\''.$payout_campo_par.'\',\''.$payout_campo_valore_par.'\',\'' . $nospam_par . '\',\''. $this->indice_riga .'\')">
											<i class="fa fa-outdent '. $storno_class .'" aria-hidden="true"></i></a>';
										
						//
					}
					$lead_trash = !empty($lead_trash) ? $lead_trash : '0';
						
					$btn_add_lead_lorde = '<div class="addLead-box addL-box" id="addLead-box-'.$this->indice_riga.'">
											<i class="fa fa-plus-circle addLead-btn" aria-hidden="true" onclick="addLead(\''.$this->indice_riga.'\')"></i>
										   </div>';
					$btn_add_lead_trash = '<div class="addLeadTrash-box addL-box" id="addLeadTrash-box-'.$this->indice_riga.'">
											<i class="fa fa-plus-circle addLeadTrash-btn" aria-hidden="true" onclick="addLead(\''.$this->indice_riga.'\',\'Trash\')"></i>
										   </div>';
					// render righe tabella
					$html .='<tr id="riga-'.$this->indice_riga.'" 
										data-ordineid="'.$ordineInfo['id'].'" 
										data-payoutid="'.$id_payout.'" 
										data-mese="'.$mese.'" 
										data-anno="'.$anno.'" 
										data-totali-lead-base="'.$lead_originali_base.'">'. // qui salvo il totale delle lead calcolate senza modifiche da storni o aggiunta lead manuali: utile per sottrarre gli storni

							'<td data-val="'.	$nomeFornitore									.'" id="nome_cliente-'		.$this->indice_riga . '">'	. $nomeFornitore 											. '</td>' .
							'<td data-val="'.	$nomeOfferta									.'" id="nome_offerta-'		.$this->indice_riga . '">' 	. $nomeOfferta 												. '</td>' .
							'<td data-val="'.	$descrizione_payout								.'" id="descrizione_payout-'.$this->indice_riga . '">' 	. $descrizione_payout 										. '</td>' .
							'<td data-val="'.	$lead_totali									.'" id="lead_totali-' 		.$this->indice_riga . '">' 	. $icon_original_lorde . $lead_totali . $btn_add_lead_lorde . '</td>' .
							'<td data-val="'.	number_format($tetto_trash,2,',','')			.'" id="tetto_trash-'		.$this->indice_riga . '">'  . number_format($tetto_trash,2,',','') 						. '</td>' .
							'<td data-val="'.	$lead_trash										.'" id="lead_trash-'		.$this->indice_riga . '">' 	. $icon_original_trash . $lead_trash  . $btn_add_lead_trash . '</td>' .
							'<td data-val="'.	number_format($perc_lead_trash_rouded,2,',','')	.'" id="perc_lead_trash-'	.$this->indice_riga . '">' 	. number_format($perc_lead_trash_rouded,2,',','') 			. '</td>' .
							'<td data-val="'.	$lead_nette										.'" id="lead_nette-'		.$this->indice_riga . '">' 	. $lead_nette 												. '</td>' .
							'<td data-val="'.	$budget											.'" id="budget-'			.$this->indice_riga . '">' 	. $budget 													. '</td>' .
							'<td data-val="'.	$delta_budget									.'" id="delta_budget-'		.$this->indice_riga . '">' 	. $delta_budget												. '</td>' .
							'<td data-val="'.	number_format($valore_payout,2,',','') 			.'" id="valore_payout-'		.$this->indice_riga . '">'  . number_format($valore_payout,2,',','') 					. '</td>' .
							'<td data-val="'.	number_format($totale,2,',','') 				.'" id="totale-'			.$this->indice_riga . '">' 	. number_format($totale,2,',','') 							. '</td>' .
								
								// action buttons riga
								'<td>'. $link_download 	. '</td>' .
								'<td>'. $link_storni 	. '</td>' .
							'</tr>'; //row
						
					$this->totali_righe['lead_totali'] 	+= $lead_totali;
					//echo $this->totali_righe['lead_totali'];
					$this->totali_righe['lead_trash'] 	+= $lead_trash;
					$this->totali_righe['lead_nette'] 	+= $lead_nette;
					$this->totali_righe['budget'] 		+= $budget;
					$this->totali_righe['delta_budget'] += $delta_budget;
				//	$this->totali_righe['payout'] 		+= $valore_payout;
					$this->totali_righe['totale'] 		+= $totale;
					$this->totali_righe['dw'] 			= '';
					$this->totali_righe['st'] 			= '';
					
					// incremento l'indice della riga
					$this->indice_riga++;
					}
					// footer
					$footer = '<tfoot class="fix_foot_totals"><tr class="footer_totals strong">';
					foreach($this->totali_righe as $key => $col_tot){
						if($key=='totale'){
							$col_tot = number_format($col_tot,2,',','');
						}
						$footer .= '<th id="grand_totale-'.$key.'" data-val="'.$col_tot.'"><strong>'. $col_tot	. '</strong></th>';
					}
					$footer .= '</tr></tfoot>';
					$this->tableFooter = $footer;
				} // if
			} // fine if controllo se le date del gruppo sono all'interno della data ricercata 
		} // foreach
		return $html;
	}
	
	/**
	* 	!!!!!!!! controllare che il campo campagna contenga l'id della campagna 'id_campagna' => $campagna['slugLanding'],
	*  1 prelevo i codici dalla tabella pixel trace
	*  2 verifico se il tipo campo è = 2 in modo da prelevare il valore direttamente dalla query al punto 1
	*  altrimenti gestisco i tipo campo con le seguenti logiche
	*  - una volta recuperati i code vado a controllare i tipo campo tra 0 e 1
	*  se  0 effettuo una query:
	* 
	* select count(*) as tot from lead_uni l where $nome_campo like :valore_campo and l.code in (:codici da step 1)
	* 
	* se 1 devo cercare tra i campi speciali. 
	* prelevo tutti gli id da lead_uni dove i code in (:codici da step 1 )
	*	l'array di id lo passo alla funzione per i campi extra avendo prelevato prima il valore del campo da cerca nel seguente modo:
	*	
	*	$value_id 		= $this->getExtraFieldValueIdByValueNameAndField($valore, $campo); // id del valore  del campo extra
	*	$totali 		= $this->getTotLeadOnExtraValue($lead_ids,$value_id); // ritorna il numero di lead con quel valore
	*	
	* Return integer totali
	**/

	private function getCampagnaFornitoreTotaleParziale($landing_cliente, $media, $falsemedia, $affiliatoRefid, $mese='',$anno='', $data_min, $data_max, $campo_valore = array()){
		try{                 
			$session = new Session();
			//$session->start();
			
			$session->set('sess_codes','');
			$totali = 0;
			$array_values = array();

			$id_campagna = $landing_cliente->getLanding()->getSlugLanding();
			
			$da_selezionare  = " count(*) as tot ";
			$array_values['mese'] 			=  	$mese;
			$array_values['anno'] 			=  	$anno;
			$array_values['data_min'] 		=  	$data_min;
			$array_values['data_max'] 		=  	$data_max;
			$array_values['data_max'] 		=  	$data_max;
			$array_values['data_max'] 		=  	$data_max;
			
			$pixel_values = array();
			$leads_codes  = array();
			
			
			
			$sql_codes = "SELECT code FROM pixel_trace where 
							id_campagna = :id_campagna
							AND (media = :media OR media = :falsemedia)";
			
			
			$pixel_values = array(  'id_campagna' => $id_campagna,
									'media' 	  => $media, 
									'falsemedia'  => $falsemedia,
								);
			if(!empty($affiliatoRefid)){
				$sql_codes .= " AND  id_agenzia = :refid ";
				$pixel_values['refid'] = $affiliatoRefid;
			}
			
			if(!empty($mese) && !empty($anno)){
				$sql_codes .= " AND MONTH(dt) = :mese AND YEAR(dt) = :anno";
				$pixel_values['mese'] = $mese;
				$pixel_values['anno'] = $anno;
			}
			if(!empty($data_max) && !empty($data_min)){
				$sql_codes .= " AND DATE(dt) >= DATE(:data_min)
								AND DATE(dt) <= DATE(:data_max) ";
				$pixel_values['data_min'] 	=  	$data_min;
				$pixel_values['data_max'] 	=  	$data_max;
							
			}
			
			if(!empty($campo_valore)){
				$tipo_campo	= $campo_valore['tipo_campo'];
				if($tipo_campo=='2'){
					$campo 		= $campo_valore['campo'];
					$valore 	= $campo_valore['valore'];
					$valore 	= str_replace('*','%',$valore); 
					$sql_codes .= " AND ".$campo." like :valore_campo ";
					$pixel_values['valore_campo'] 	=  	$valore;
				}
			}
			
			$em_pixel 	= $this->getDoctrine()->getManager('pixel_man');
			$stmt_pixel = $em_pixel->getConnection()->prepare($sql_codes);
			$stmt_pixel->execute($pixel_values);
			
			if ($stmt_pixel->rowCount()>0) { // trovate lead
				$totali = $stmt_pixel->rowCount();
				$codes 	= $stmt_pixel->fetchAll(\PDO::FETCH_COLUMN);

				$session->set('sess_codes', $codes);
				$sql_pay = '';
				if(!empty($campo_valore)){ // se è un payout multiplo e non ha valore che ricerca nella tabella pixel
					if($campo_valore['tipo_campo']!='2'){
						// gestione del payout multiplo
						$campo 			 = $campo_valore['campo'];
						$valore 		 = $campo_valore['valore'];
						$valore 		 = str_replace('*','%',$valore); 
						$tipo_campo 	 = $campo_valore['tipo_campo'];
						switch($tipo_campo){
							case '0':  // campi da colonne lead_uni
								
								$sql_pay = " AND ".$campo." like :valore_campo ";
								$array_values['valore_campo'] 	=  	$valore;
								$totali = $this->getTotalByQueryParams($da_selezionare, $array_values, $sql_pay,$codes);
								$totali = $totali['0']['tot'];
							break;
							case '1':  // campi extra seleziono solo gli id dalla query
								$da_selezionare = " lu.id ";
								$lead_ids 		= $this->getTotalByQueryParams($da_selezionare, $array_values,'',$codes);
								$value_id 		= $this->getExtraFieldValueIdByValueNameAndField($valore, $campo);
								$totali 		= $this->getTotLeadOnExtraValue($lead_ids,$value_id); // ritorna il numero di lead con quel valore
								
							break;
						}
					}
				}
			}
			
		}catch(Exception $e){
			echo "Error: " . $e->getMessage();
		}
		return $totali; 
	}
	
	public function getFornitoreColumnsAction(Request $request){
		$colonne = $this->getPixelTraceColumns();
		$html_options = '';
		foreach($colonne as $colonna){
			$html_options .= '<option data-target="2" value="' . $colonna . '">'.$colonna.'</option>';
		}
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'html'	=> $html_options,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;		
	}
	
	public function getPixelTraceColumns(){
		$conn 	= $this->getDoctrine()->getManager('pixel_man')->getConnection();
		if($conn){		
			// recupero tutte le colonne
			$stmt = $conn->prepare("DESCRIBE pixel_trace");
			$stmt->execute();
			$table_fields = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		}
		return $table_fields;
	}
	
	public function checkDateBeetween($data, $data_inizio, $data_fine){
		if (($data >= $data_inizio) && ($data < $data_fine)){
			return true;
		}else{
			return false;
		}
	}
	
	
	public function getExtraFieldsOptions(){
		$html = '';
		$extraFields = $this->getDoctrine()->getRepository('AppBundle:Lead_uni_extra_fields')->findAll();
		
		foreach($extraFields as $field){
			$html .= '<option data-target="1" value="'.$field->getName().'">Extra: ' . $field->getName() . '</option>';
		}
		return $html;	
	}
	
	public function getPixelTraceOptions(){
		$conn 	= $this->getDoctrine()->getManager('pixel_man')->getConnection();
		$html_options = '';
		if($conn){		
			// recupero tutte le colonne
			$stmt = $conn->prepare("DESCRIBE pixel_trace");
			$stmt->execute();
			
			$colonne = $stmt->fetchAll(\PDO::FETCH_COLUMN);
			foreach($colonne as $colonna){
				$html_options .= '<option data-target="2" value="' . $colonna . '">Trace: '.$colonna.'</option>';
			}
			return $html_options;
		}
		
	}
	
   /**
	* @param ind id_ordine
	* return array ordine con gruppi e payouts
	*/
	
	public function getOrderFornitoreById($id_ordine){
		$ordine = array(); 
		$ordine['payouts'] = array();
		$ordine['cliente_id'] = '';
		
		$em   = $this->getDoctrine()->getManager();
		$sql  = "SELECT * FROM ordini_fornitore WHERE id = ?";
		$stmt = $em->getConnection()->prepare($sql);
		if($stmt->execute(array($id_ordine))){
			$ordine = $stmt->fetch(\PDO::FETCH_ASSOC);
			$ordine['gruppi'] 			= $this->getAllGruppiFromOrderId($id_ordine,'1');
			$ordine['payouts'] 			= $ordine['gruppi'][$ordine['id_gruppo']]['payouts'];
			$ordine['data_inizio'] 		= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataInizio();
			$ordine['data_fine'] 		= $ordine['gruppi'][$ordine['id_gruppo']]['info']->getDataFine();
			$landingCliente 			= $this->getLandingCliente($ordine['id_landing_cliente']);
			$ordine['landing_cliente'] 	= $landingCliente;
			$ordine['cliente_id'] 		= $landingCliente->getCliente()->getId(); //['cliente_id'];
		}
		return $ordine;
	}

	
	
	
	
	public function editFornitoreOrdineAction(Request $request){
		$id_ordine			 	= $request->get('id_ordine');
		$id_landing_cliente 	= $request->get('clienteCampagna');
		$budget					= $request->get('budget');
		$tetto_trash			= $request->get('trash');
		$tipo_payout			= $request->get('tipo_payout');
		$ordine_descrizione		= $request->get('ordine_descrizione');
		$data_inizio			= $request->get('data_inizio');
		$data_fine				= $request->get('data_fine');
		$nuovo_payout			= $request->get('nuovo_payout');
		$result					= true;
		$message 				= '';			
		$valori 				= array('descrizione' 	=> $ordine_descrizione,);
		
		$valore_campo_multiplo 	= array();
		$payout_multiplo 		= array();
		$campo_payout 			= array();
		$payout_descrizione		= array();
		$payoutArray 			= array();
		$target = 1; // fornitore
		
		// VERIFICA SE è STATA EFFETTUATA UNA RICHIESTA DI NUOVO PAYOUT
		if($nuovo_payout){
			
			//  CONTROLLO DATE INTERSECANTI
			
			//prelevo tutti i gruppi associati all'ordine:
			$gruppi = $this->getAllGruppiFromOrderId($id_ordine,'1');
			foreach($gruppi as $gruppo){
				$data_inizio_pay 	= strtotime($gruppo['info']->getDataInizio());
				$data_fine_pay   	= !empty($gruppo['info']->getDataFine()) ? strtotime($gruppo['info']->getDataFine()) : strtotime('-1 day');
				
				$_data_inizio 		= \DateTime::createFromFormat('d/m/Y', $data_inizio)->format('Y-m-d H:i:s');
				$_data_inizio_check = strtotime($data_inizio);
				
				$interseca 			= $this->checkDateBeetween($_data_inizio_check,$data_inizio_pay,$data_fine_pay);
				
				if($interseca){
					$result		= false;
					$message 	= "La data inserita interseca con una data payout";
					break; // esco dal ciclo
				}
			}
			
			if($result){
				if($tipo_payout=='multiplo'){ // payout multiplo
					
					$_valori 		= $request->get('valori_campi_multipli');
					$_target 		= $request->get('target_campi_multipli');
					$_payout 		= $request->get('payout_multipli');			
					$_campi 		= $request->get('campi_multipli');
					$_descrizioni 	= $request->get('payout_descrizioni');
					
					parse_str($_valori, 		$valore_campo_multiplo	);
					parse_str($_payout, 		$payout_multiplo		);
					parse_str($_campi , 		$campo_payout 			);
					parse_str($_descrizioni , 	$payout_descrizione		);

					$target_campo_multipli = json_decode($_target);
					

					if(!empty($campo_payout)){
						for($i=0;$i<count($campo_payout['campo_multiplo']);$i++){
							if(trim($payout_multiplo['payout_multiplo'][$i])!='' && trim($valore_campo_multiplo['valore_campo_multiplo'][$i])!=''){
								$payoutArray[$i]['campo'] 		= $campo_payout['campo_multiplo'][$i];
								$payoutArray[$i]['valore']		= $valore_campo_multiplo['valore_campo_multiplo'][$i];
								$payoutArray[$i]['payout']		= $payout_multiplo['payout_multiplo'][$i];
								$payoutArray[$i]['descrizione']	= $payout_descrizione['payout_descrizione'][$i];
								$payoutArray[$i]['tipo_campo']		= $target_campo_multipli[$i]->target;
							}
						}
					}
				}else{ // payout singolo
					
					$payout							= $request->get('payout_singolo');
					$payout_descrizione				= $request->get('payout_descrizione');
					$payoutArray[0]['campo']  		= null;
					$payoutArray[0]['valore'] 		= null;
					$payoutArray[0]['tipo_campo'] 	= null;
					$payoutArray[0]['payout'] 		= $payout;
					$payoutArray[0]['descrizione'] 	= $payout_descrizione;
				}
				
				$em  = $this->getDoctrine()->getManager();

				// creo il gruppo
				$id_gruppo = $this->createPayoutGroup($budget, $tetto_trash, $data_inizio, $data_fine);
				$valori['id_gruppo'] = $id_gruppo;
				
				$ordine = $this->getOrderById($id_ordine,'1'); // 1 fornitore

				// aggiorno la data dell'ultimo gruppo associato all'ordine con la data di inizio del nuovo gruppo
				$_data_inizio_obj = \DateTime::createFromFormat('Y-m-d H:i:s', $_data_inizio);
				$_data_fine_gruppo = $_data_inizio_obj->modify('-1 day')->format('Y-m-d H:i:s');
				
				$this->updateDataFinePayoutGroup($ordine['id_gruppo'],$_data_fine_gruppo);  
				
				if($id_ordine){
					foreach($payoutArray as $payoutRow){
						$data_creazione = new \DateTime("now");
						$payoutOrdineFornitore = new PayoutOrdineFornitore();
						$payoutOrdineFornitore->setCampo			($payoutRow['campo'])
												->setTipoCampo		($payoutRow['tipo_campo'])
												->setCampoValore	($payoutRow['valore'])
												->setPayout			($payoutRow['payout'])
												->setDescrizione	($payoutRow['descrizione'])
												->setDataCreazione	($data_creazione);
						
						$em = $this->getDoctrine()->getManager();
						$em->persist($payoutOrdineFornitore);
						$em->flush();
						$id_payout =  $payoutOrdineFornitore->getId();
						$this->associaPayoutGruppoOrdine($id_payout,$id_gruppo,$id_ordine,$target);
					}
				}
			} // if result interseca
		} //IF PAYOUT NUOVO
		// FINE SALVATAGGIO PAYOUT
		
		if($result){
			// aggiorno l'ordine con i valori generati
			$this->updateOrdine($id_ordine,$valori,$target);
		}
				
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $result,
										'message'	=> $message,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getListFornitoriOrdiniAction(Request $request){
		$mese = $request->get('mese');
		$anno = $request->get('anno');
		
		
		$table_head = '<table id="listatoCC" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>ID</th><th>Sel</th><th class="search_drop">Fornitore</th><th class="search_drop">Campagna</th><th>Landing</th><th class="search_drop">Stato</th><th>Descrizione</th><th>Payout</th><th>Budget</th><th>Trash</th><th>Data Creazione</th><th>Azioni</th>
								</tr>
							</thead>
						<tbody>';
		
			
		$ordini_ids = $this->getAllFornitoreCampaignInfo($mese, $anno);
		$html ='';
		foreach($ordini_ids as $ordine => $ordine_ids){
			$ordine_id = $ordine_ids['id_ordine'];
			$ordine = $this->getOrderById($ordine_id,1);
			
			$modificaLink = $url_download = $this->admin->generateUrl(	'modificaFornitore', array('ordine_id'=> $ordine_id));
			
			// assegnazione dei payout
			$payouts = array();
			foreach($ordine['payouts'] as $payout){
				$payouts[] = $payout->getPayout();
			}
			$ordine['payout'] = implode(',',$payouts);
					
			$landing_cliente = $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->find($ordine['id_landing_cliente']);
			$fornitore = $this->getDoctrine()->getRepository('AppBundle:Fornitori')->find($ordine['id_fornitore']);
			
			$affiliato = '';
			if (!empty($ordine['id_affiliato'])){
				$affiliato = $this->getDoctrine()->getRepository('AppBundle:Affiliati')->find($ordine['id_affiliato']);
			}
			
			$html .='<tr id="cc_row_'. $ordine['id'] . '">
					<td><div class="red-'. $ordine['id'] . '-id">'. $ordine['id'] . '</div></td>
					<td style="text-align: center;"><input type="checkbox" value="'. $ordine['id'] . '" class="chkbox" /></td>
					<td>';
						if (!empty($affiliato)){
							$html .= $affiliato->getNome();
						}else{ 
							$html .= $fornitore->getNome();
						}
			$html .='</td>
					<td>' . $landing_cliente->getCampagna()->getNomeOfferta() . '</td>
					<td><div class="red-'. $ordine['id'] . '-id_landing get-screenshot" data-url_landing="'. $landing_cliente->getLanding()->getUrl() . '">
						'. $landing_cliente->getLanding()->getSlugLanding() . '
						</div>
					</td>
					<td>';
			if ($landing_cliente->getClienteAttivo()==1){
				$html .='<span class="attivo_label">Attivo</span>';
			}else{
				$html .='<span class="disattivo_label">Disattivo</span>';
			}
			$html .='</td>
					<td>' . $ordine['descrizione'] . '</td>
					<td>';
			if(count($ordine['gruppi']) > 1){
				$html .='<a href="javascript:void(0)" onclick="toggleGruppiPayout(\''.$ordine['id'].'\')">'.$ordine['payout'].'€ <i class="fa fa-info-circle" aria-hidden="true"></i></a>';
			}else{
				$html .= $ordine['payout'] . '€';
			}
			$html .= '</td>
					<td class="no-padding">
						'.  $ordine['gruppi'][$ordine['id_gruppo']]['info']->getBudget()  .'
					</td>
					<td class="no-padding">
						'. $ordine['gruppi'][$ordine['id_gruppo']]['info']->getTettoTrash() .'
					</td>
					<td class="no-padding">
						'. $ordine['data_creazione'] .'
					</td>
					<td>
						<a title="Modifica" href="'.$modificaLink.'" class="btn btn-sm btn-default"><i class="fa fa-pencil" aria-hidden="true"></i></a>
						<a title="Elimina" href="javascript:void(0);" class="btn btn-sm btn-default" onclick="confermaEliminazione(\''. $ordine['id'] . '\')"><i class="fa fa-window-close" aria-hidden="true"></i></a>
					</td>';
			
			if(count($ordine['gruppi']) > 1){
			$html .='	
				<div style="display:none;" class="gruppo-'. $ordine['id'] .' gruppo_ordini">
						<div class="row hidden-tablehead" onclick="toggleGruppiPayout(\''.$ordine['id'].'\')">
						<div class="col-md-1 pull-right"><i class="fa fa-times"></i></div>
						</div>
						<div class="row hidden-tablehead info-pup">
							<div class="col-md-6"><i class="fa fa-user"></i> '. $fornitore->getNome() .'</div>
							<div class="col-md-6"><i class="fa fa-window-maximize"></i> '. $landing_cliente->getCampagna()->getNomeOfferta() .'</div>
						</div>
						<div class="row hidden-tablehead">
							<div class="col-md-4">Payout(s)		</div>
							<div class="col-md-4">Data Inizio	</div>
							<div class="col-md-4">Data Termine	</div>
						</div>';
				foreach($ordine['gruppi'] as $gruppo) {
					$html .='<div class="row">
							<div class="col-md-4">';
					foreach($gruppo['payouts'] as $subpayouts){
						$html .= $subpayouts->getPayout() . '€,';
					}
					$html .='</div>
							<div class="col-md-4">' .$gruppo['info']->getDataInizio() . '</div>
							<div class="col-md-4">';
							if(!empty($gruppo['info']->getDataFine())){
								$html .= $gruppo['info']->getDataFine();
							}else{
								$html .='Non definito';
							}
					$html .='</div>
						</div>';
				}
				$html .='</div>';
			}
			$html .='</tr>';
		} //endfor
		
		$table_html = $table_head . $html . '</tbody></table>';
		$response = new Response();
		$response->setContent(json_encode(array('table' => $table_html )));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	/**
	 * FUNZIONE DI SERIALIZZAZIONE DATABASE CLIENTI E FORNITORI: RENDE I CAMPI CON PAYOUT MULTIPLI UNICO CAMPO SERIALIZZATO
	 * 
	**/
	public function serializzaAction($message = null){
		
		
		ob_start();
		$em	= $this->getDoctrine()->getRepository('AppBundle:Payout_ordine_fornitore');
		
		$qb = $em->createQueryBuilder('p'); // $em is your entity manager
		$result = $qb->select('p')
			->where('p.campo IS NOT NULL')
			->andWhere("p.campo not like '%{%'")
			->getQuery()->getResult();
		
			foreach($result as $payout){
				$valoreFinale = array();
				$nomecampo = $payout->getCampo();
				$valorecampo = $payout->getCampoValore();
				$tipocampo = $payout->getTipoCampo();
				
				$classe_pay = new \stdClass();
				$classe_pay ->nomecampo 	= $nomecampo;
				$classe_pay ->valorecampo 	= $valorecampo;
				$classe_pay ->tipocampo 	= $tipocampo;
				
				
				$valoreFinale[] = $classe_pay;
				$serialized = serialize($valoreFinale);
				
				$nem 	= $this->getDoctrine()->getManager();
				//
				$pay = $nem->getRepository('AppBundle:Payout_ordine_fornitore')->find($payout->getId());
				
				$pay->setCampo($serialized);
						//->setTipoCampo(null)
						//->setCampoValore(null);

				$nem->persist($pay);
				$nem->flush();
				
			}
		
		$cont = ob_get_contents();
		ob_end_clean();
		return new Response(
            '<html><body>'.$cont.'</body></html>'
        );
	}
	
	/**
	 * FUNZIONE CHE AGGIUNGE AL DATABASE ORDINI CLIENTI L'ID DEL CLIENTE RELATIVO ALLA LANDING_CLIENTE
	 * 
	**/
	public function updateClientiAction($message = null){
		
		
		
		ob_start();
		$em 	= $this->getDoctrine()->getManager();
		$conn 	= $em->getConnection();
		$em1 	= $this->getDoctrine()->getManager();
		$conn1 	= $em1->getConnection();
		if($conn){
			$sql_ordini = "select * from ordini_cliente";
			$stmt 	= $em->getConnection()->prepare($sql_ordini);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				$incr = 0;
				while ($ordine = $stmt->fetch()) {
					$incr++;
					$ordine_id = $ordine['id'];
					$ordine['landing_cliente'] 	= $this->getLandingCliente($ordine['id_landing_cliente']);
					$cliente_id 			= $ordine['landing_cliente']->getCliente()->getId();	
					
					
					echo $incr . "<br>";
					echo "Ordine id " . $ordine_id . PHP_EOL . "<br>";
					echo "landing_cliente " . $ordine_id . PHP_EOL . "<br>";
					echo "cliente " . $cliente_id;
					echo "<br>";
					echo "------------------------------------<br><br>";
					
					if($conn1){
						$sql_ordini = "UPDATE ordini_cliente set cliente_id = ? where id = ?";
						$stmt1 	= $em1->getConnection()->prepare($sql_ordini);
						$data = array($cliente_id,$ordine_id);
						$stmt1->execute($data);
						
					}
										
				}
			}
		}
		$cont = ob_get_contents();
		ob_end_clean();
		return new Response(
            '<html><body>'.$cont.'</body></html>'
        );
	}
}