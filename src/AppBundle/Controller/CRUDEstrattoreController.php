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
use AppBundle\Entity\Extraction as Extraction;
use AppBundle\Entity\Extraction_history as Extraction_history;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Cliente as Cliente;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CRUDEstrattoreController extends Controller
{
    
	public $multiblocco=false;
    public $filtri = array();
	public $listaFiltri = array();
	
	// azione inziale apertura pagina estrattore
    public function estrattoreAction(Request $request){
		$sql = "SELECT count(*) AS tot,";
		$sql .= "SUM(CASE WHEN privacy_terzi = 0 THEN 1 ELSE 0 END) AS senzaPrivacy ";	
		$sql .= "from lead_uni";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$conteggi =  $stmt->fetchAll();
		//$pixels = print_r($results, true);
            return $this->_render('listestrattore.html.twig', array(
                //'action' => 'estrattore',
				'conteggi' 		=> $conteggi,
            ), null); 
    }
	
	public function _render($twigpage, $pars=array(),$third=null){
		$this->estrattoreFiltri(); // popolo i filtri
		$pars['filtri'] 		= $this->filtri; 		// implemento di default i filtri
		$pars['multiblocco'] 	= $this->multiblocco; // implemento di default il multiblocco
		$pars['csrf_token'] 	= $this->getCsrfToken('sonata.batch'); 		// implemento di default i filtri
		
		return $this->render($twigpage, $pars, $third); 
	}
	
	// questa funzione popola la lista dei filtri in frontend passando l'identificativo del filtro creato con genera filtri
	public function listaFiltri(){
		$this->listaFiltri = array(	'nome',
									'cognome',
									'cliente',

									);
	}
	
	public function estrattoreFiltri(){
		// generaFiltri($frontname = '', $tag = 'input', $type = 'text', $pars = array(),$options = array(),$callback = array()){
		$this->filtri['nome'] = $this->generaFiltri('Nome','input','text',
										array( 
											'name'	=> 'filter[nome]',
											'id'	=> 'nome',
											'class'	=> 'input-class',
											)
										);
		$this->filtri['cognome'] = $this->generaFiltri('Cognome','input','text',
										array( 
											'name'	=> 'filter[cognome]',
											'id'	=> 'cognome',
											'class'	=> 'input-class',
											)
										);
		$this->filtri['id'] = $this->generaFiltri('Id','input','text',
										array( 
											'name'	=> 'filter[id]',
											'id'	=> 'id',
											'class'	=> 'input-class',
											)
										);
		$this->filtri['email'] = $this->generaFiltri('Email','input','text',
										array( 
											'name'	=> 'filter[email]',
											'id'	=> 'email',
											'class'	=> 'input-class',
											)
										);
		$this->filtri['clienti'] = $this->generaFiltri('Clienti','select',null,
										// parametri 
										array( 
											'name'		=> 'filter[cliente][]',
											'id'		=> 'filtroCliente',
											'class'		=> '',
											'multiple'	=> 'multiple',
											),
											null,
											'getClientiFilter'
										);
		$this->filtri['editore'] = $this->generaFiltri('Editore','input','text',
										// parametri 
										array( 
											'name'		=> 'filter[editore]',
											'id'		=> 'editore',
											'class'		=> '',
											)
										);
		$this->filtri['forma_giuridica'] = $this->generaFiltri('Forma Giuridica','select',null,
										// parametri 
										array( 
											'name'		=> 'filter[forma_giuridica]',
											'id'		=> 'forma_giuridica',
											'class'		=> '',
											),
											null,
											'getFormaGiuridicaFilter'
										);
		$this->filtri['campagna'] = $this->generaFiltri('Campagna','selectCampagna',null,
										// parametri 
										array( 
											'name'		=> 'filter[settore]',
											'id'		=> 'settore',
											'class'		=> 'selettoriCampagna',
											'onchange'	=> 'getTipoCampagnaField(this)',
											'multiple'	=> 'multiple',
											),
											null,
											'getCampagnaSettoreFilter'
										);
									
	}
	
	
	
	public function generaFiltri($frontname = '', $tag = 'input', $type = 'text', $pars = array(),$options = array(), $callback = array()){
		$pars['width'] = '50%';
		$pars['class'] = $pars['class'] . ' form-control';
		$filtro = array(
						'frontname'	=> $frontname,
						'tag'		=> $tag,
						'type' 		=> $type,
						'pars'		=> $pars,
						'options' 	=> $options,
						'callback'  => $callback,
		);
		return $filtro;
	}
	
	// chiamata ajax, genera il codice html di un filtro da appendere al form e lo restituisce alla pagina
	public function getfilterAction(Request $request)
	{
		// recupero i filtri
		$this->estrattoreFiltri();
		$closure ='';
		$parametri = '';
		$result = '';
		$html = '';
		$_tag = '';
		$dropdown = false;
		$filterId = $request->get('filterid'); // indice dell'array
		$filtro = $this->filtri[$filterId];
		
		$rimuoviFiltro = '<label class="control-label">
					<a href="#" onclick="rimuoviFiltro(\''.$filterId.'\')" class="sonata-toggle-filter sonata-ba-action" >
						<i class="fa fa-minus-circle fa-check-square-o" aria-hidden="true"></i>
					</a>
				</label>';
	
		// prelevo l'id
		$objid 		= !empty($filtro['pars']['id']) ? $filtro['pars']['id'] : ''; // tag id dell'oggetto
		$objname 	= !empty($filtro['pars']['name']) ? $filtro['pars']['name'] : '';
		
	
		// parametri pars
		if(is_array($filtro['pars'])){
			foreach($filtro['pars'] as $parName => $parValue){
				$parametri .= ' ' . $parName .'="'. $parValue.'" ';
			}
		}
		
		// gestione di funzioni di callback
		if(!empty($filtro['callback'])){
			$result = $this->$filtro['callback']();
		}

		$html .='<div class="form-group row" id="filtro-'.$filterId.'">';
		$html .='<label for="'.$objname.'" class="col-sm-2 control-label">'.$filtro['frontname'].'</label>';
		
		// pulsanti laterali per settore campagna
		if($filtro['tag']=='selectCampagna'){
			$filtro['tag']='select';
			$_tag = 'selectCampagna';
		}
		
		$html .='<div class="col-sm-7">';
		
		// tag html 
		$html .='<';
		$html .= $filtro['tag'];
		
		$html .= $parametri;
		// gestione del tag 
		switch($filtro['tag']){
			case 'input':
				$html .= 'type="' . $filtro['type'] . '"';
				$closure = ' />';
			break; 
			case 'select':
				$closure = '</select>';
				$dropdown = false;
				$html .= '>';
			break; 
			case 'textarea':
				$closure = '</textarea>';
			break; 
			case 'button':
				$closure = '</button>';
			break;
			default: // input di default
				$html .= ' type="' . $filtro['type'] . '" ';
				$closure = ' />';
			break; 
		}
		
		$html .= $result;
		
		$html .= $closure;
		$html .= "</div>";
		$html .= '<div class="col-sm-1">';
		$html .= $rimuoviFiltro;
		$html .= '</div>';
		$html .= "</div>";
		
		if($_tag=='selectCampagna'){
			$html ='<div class="form-group row" id="filtro-'.$filterId.'">';
			$html .='<label for="'.$objname.'" class="col-sm-2 control-label">'.$filtro['frontname'].'</label>';
			$html .= '<div id="innerbox-filtroCampagna" class="innerbox-filtroCampagna col-sm-9" >';
			$html .= $this->generateFilterCampagnaField($objid,$filterId,$parametri,$result);
			$html .= '<div class="col-sm-1">';
			$html .= $rimuoviFiltro;
			$html .= '</div>';
			$html .= '</div><!-- chiusura filtro-'.$filterId.' -->';
			$html .= '</div><!-- chiusura inner-filtriCampagna -->'; // chiusura inner filtroCampagna
			
		}
	
		$response = new Response();
		$response->setContent(json_encode(array(
			'html'  => $html,
			'objid' => $objid,
		)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function generateFilterCampagnaField($objid,$filterId,$parametri,$resultOptions){
		$html = '';
		$html .= '<div id="filtroCampagna-'. $objid.'" class="row filtriCampagna">
						<div class="col-sm-2">
							<label>'.$filterId.'</label>
							<div class="box-select-all">
								<div class="box-select-all">
									<div class="btn-select-all" id="checktutti_'	. $objid.	'" onclick="setAllTags(\''. $objid .'\',true)">Seleziona tutti</div>
									<div class="btn-clear-all" id="pulisci_'		. $objid.	'" onclick="setAllTags(\''. $objid .'\',false)">Pulisci</div>
								</div>
							</div><!-- chiusura box-select-all -->
						</div><!-- chiusura col-sm-2 -->
				'; 
		$html .= '<div class="col-sm-10">';	
		$html .= '<select '.$parametri.'>';
		$html .= $resultOptions;
		$html .= '</select >';
		$html .= '</div><!-- chiusura col-sm-7 -->';
		$html .= '</div>';
		$html .= "</div><!-- chiusura filtriCampagna xxxxx -->"; // chiusura inner filtroCampagna

		return $html;
	}
	
	public function getClientiFilter(){
		$html = '';
		$sql = "SELECT * FROM cliente c ORDER BY c.name";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$options =  $stmt->fetchAll();
		foreach($options as $option){
			$html.= '<option value="'.$option['id'].'">'. $option['account'] . ' - ' . $option['name'] . '</option>';
		}
		return $html;
	}
	// get filtri campagna
	public function getCampagnaSettoreFilter(){
		// primo filtro campagna è settore
		$html = '';
		$sql = "SELECT DISTINCT c.settore 
				FROM campagna c 
				WHERE c.settore is not null 
				AND c.settore not like ''
				ORDER BY c.settore ASC";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$options =  $stmt->fetchAll();
		foreach($options as $option){
			$html.= '<option value="'.$option['settore'].'">'. $option['settore'] . '</option>';
		}
		return $html;
	}
	
	public function getFormaGiuridicaFilter(){
		$html = '';
		$options =  array(
							'Persona Fisica'	=>	0,
							'Persona Giuridica'	=>	1,
							);
		foreach($options as $label => $value){
			$html.= '<option value="'.$value.'">'. $label . '</option>';
		}
		return $html;
	}
		
	
	public function filtraleadAction(Request $request){
		$filtri = $request->get('filter');
		//var_dump($filtri);exit;
		// type e gia_estratte devono essere sempre presenti
		$tipo_vendita = $filtri['type'];
		$gia_estratte = $filtri['gia_estratte'];
		
		$sql_lead_bloccate = "	SELECT ex.lead_id FROM extraction AS ex 
								WHERE ex.data_sblocco > CURRENT_TIMESTAMP()";
		$sql_privacy_terzi = " lu.privacy_terzi = 1";
		
		
		$inner_clausole = array(); // array di INNER JOIN (comando da inserire nella stringa)
		$sql_clausole = array(); // array di clausole AND
		$or_clausole = array(); // array di clausole OR
		$is_where = false; // verifica se è stato inserito un WHERE nella query
		//print_r($tipo_vendita);exit;
		
		/** determino le azioni da compiere in base ai parametri passati */
		
		
		if($tipo_vendita=='nol'){ // nel caso è stato selezionato noleggio 
			
			$sql_clausole[] = "  
							# NOLEGGIO 
							CURRENT_TIMESTAMP() > DATE_ADD(lu.data, INTERVAL 3 MONTH) ";
			
		}else
		if($tipo_vendita=='ven'){ // nel caso è stato selezionato vendita
			$sql_clausole[] = "  
							 # VENDITA 
							 CURRENT_TIMESTAMP() <= DATE_ADD(lu.data, INTERVAL 3 MONTH) ";
		}
		
		
		if($gia_estratte=='1'){ // nel caso è stato selezionato ESTRATTE 
			// SELEZIONATO Ricerca tra lead -> ESTRATTE ATTUALMENTE (QUINDI SOLO LE LEAD BLOCCATE OGGI)
			$sql_clausole[] = " 
								# ESTRATTE ATTUALMENTE
								lu.id IN (". $sql_lead_bloccate .") ";
			
		}else
		if($gia_estratte=='0'){ // nel caso è stato selezionato ESTRAIBILI
			$this->multiblocco=true; // mostro più blocchi
			// SELEZIONO TUTTE LE LEAD DA LEAD_UNI CHE NON SONO GIA' PRESENTI IN EXTRACTION o, se lo sono, la DATA DI SBLOCCO <= AD OGGI (SBLOCCATE)
			$sql_clausole[] = " 
								# ESTRAIBILI
								lu.id NOT IN (". $sql_lead_bloccate .") ";
			$sql_clausole[] = $sql_privacy_terzi; // se estraibili seleziono tutte quelle che hanno accettato la privacy
		}
		
		// FILTRI AGGIUNTIVI -------------------- ######################
		
		// CLIENTE
		$cliente = isset($filtri['cliente']) ? $filtri['cliente'] : '' ;
		if(!empty($cliente)){
			
			//$inner_clausole[] = 'INNER JOIN cliente c ON ';
			$inner_clausole[] 	= 'INNER JOIN extraction e ON e.lead_id = lu.id';
			
			foreach($cliente as $clienteId){	
				$or_clausole[] 	= 'e.cliente_id = ' . $clienteId;
			}
		}
		// NOME
		$nome = isset($filtri['nome']) ? $filtri['nome'] : '' ;
		if(!empty($nome)){
			$nome = addslashes($nome);
			$sql_clausole[] = "lu.nome LIKE '".$nome."'";
		}
		// COGNOME
		$cognome = isset($filtri['cognome']) ? $filtri['cognome'] : '' ;
		if(!empty($cognome)){
			$cognome = addslashes($cognome);
			$sql_clausole[] = "lu.cognome LIKE '".$cognome."'";
		}
		// EMAIL
		$email = isset($filtri['email']) ? $filtri['email'] : '' ;
		if(!empty($email)){
			$email = addslashes($email);
			$sql_clausole[] = "lu.email LIKE '".$email."'";
		}
		// ID
		$id = isset($filtri['id']) ? $filtri['id'] : '' ;
		if(!empty($id)){
			$id = addslashes($id);
			$sql_clausole[] = "lu.id = ".$id."";
		}
		// EDITORE (MEDIA)
		$editore = isset($filtri['editore']) ? $filtri['editore'] : '' ;
		if(!empty($editore)){
			$editore = addslashes($editore);
			$sql_clausole[] = "lu.editore LIKE '".$editore."'";
		}
		// FORMA GIURIDICA (0 -> Consumer, 1 -> Business )
		$forma_giuridica = isset($filtri['forma_giuridica']) ? $filtri['forma_giuridica'] : '' ;
		if(!empty($forma_giuridica)){
			$forma_giuridica = addslashes($forma_giuridica);
			$sql_clausole[] = "lu.forma_giuridica = ".$forma_giuridica."";
		}
		
		
		// ##### --------------         FINE FILTRI AGGIUNTIVI -------------------- ######################
		// SQL PER CONTEGGIO DATI IN BASE AI FILTRI.
		$sql = "SELECT count(*) AS tot, ";
		$sql .= "SUM(CASE WHEN lu.privacy_terzi = 0 THEN 1 ELSE 0 END) AS senzaPrivacy ";	
		$sql .= "FROM lead_uni AS lu ";
		if(count($inner_clausole)>0){
			$sql .= implode(' ', $inner_clausole);
		}
		
		if(count($sql_clausole)>0){
			$is_where = true;
			$sql .= " WHERE ";
			$sql .= implode(' AND ', $sql_clausole);
		}
		
		if(count($or_clausole)>0){
			if(!$is_where){
				$sql .= " WHERE ";
				$is_where = true;
			}else{
				$sql .= " AND ";
			}
			$sql .= " ( ";
			$sql .= implode(' OR ', $or_clausole);
			$sql .= " )";
		}
	
		
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$conteggi =  $stmt->fetchAll();

		return $this->_render('listestrattore.html.twig', array(
                //'action' => 'estrattore',
                'conteggi' => $conteggi,
            ), null); 
    }
	
        

    
    public function addpixelAction(Request $request)
    {
		// inizializzo le variabili della prima pagina
        return $this->render('newpixel.html.twig', [
			'inserito' => false,
        ]);
    }
    
    public function deletepixelAction(Request $request)
	{
		$pixelid = $request->get('pixelid');
		$em = $this->getDoctrine()->getManager('pixel_man');
		$pixel = $em->getRepository('AppBundle:Pixels', 'pixel_man')->find($pixelid);

		if ($pixel) {
			$em->remove($pixel);
			$em->flush();
			$response = new Response();
			$response->setContent(json_encode(array(
				'eliminato' 	=> true,
			)));
		}
		
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
        
    public function insertpixelAction(Request $request)
    {
		$pixel = new Pixels();

		/* genero l'id della campagna */
		$url = $request->get('dominio');
		$pixel->setUrl($url)->generateIdCampagna();
		$id_campagna = $pixel->getIdCampagna();

		/*salvo il cpl nell'oggetto Pixel */
		$media = $request->get('cplnumber');
		$pixel->setCplNumber($media);

		/* ripulisco il codice del pixel */
		$pixelcode = $request->get('pixel');
		$pixel->setPixel($pixelcode);
		$codicePixel = $pixel->getPixel();
		
		/* setto la descizione */
		$descrizione = $request->get('descrizione');
		$pixel->setDescrizione($descrizione);
		
		/* recupero il falsemedia */
		$falsemedia = $pixel->parseFalseMedia();
		
		/* recupero il payout */
		$payout_code = $request->get('payout_code');
		if(empty($payout_code)){$payout_code=null;}
		$pixel->setPayoutCode($payout_code);
		
		/* attivo il pixel */
		$pixel->setAttivo('1');
		
		/* data creazione pixel*/
		$datenow = new \DateTime("now");
		$pixel->setDataCreazione($datenow);
		
		// se il pixel esiste, aggiorno
		$_pixel = $this->checkExists($id_campagna,$media,$payout_code);
		if($_pixel){
			$_pixel->setCplNumber($media);
			$_pixel->setPixel($pixelcode);
			$_pixel->setDescrizione($descrizione);
			$_pixel->setPayoutCode($payout_code);
			$_pixel->setIdCampagna($id_campagna);
			$pixel = $_pixel;
		}
		
		$em = $this->getDoctrine()->getManager('pixel_man');
		$em->persist($pixel);
		$em->flush();
	
		return $this->render('newpixel.html.twig', [
            'dominio' => $request->get('dominio'),
            'id_campagna' => $id_campagna,
            'pixelcode' => $codicePixel,
            'falsemedia' => $falsemedia,
            'media' => $pixel->getCplNumber(),
            'payoutcode' => $pixel->getPayoutCode(),
            'descrizione' => $pixel->getDescrizione(),
			'inserito' => true,
			//realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
	}
	
	
	private function checkExists($idcampagna,$media,$payout_code){
		$pixels = $this->getDoctrine()->getRepository('AppBundle:Pixels', 'pixel_man');
		$_pixel = $pixels->findOneBy(array('idCampagna'=> $idcampagna,'cplNumber' => $media,'payout_code' => $payout_code,));
		if($_pixel){ 
			return $_pixel; 	
		}else{
			return false;
		}
	}
	
	

	public function checkpixelAction(Request $request)
	{
		$pixel = new Pixels();
		/* genero l'id della campagna */
		$url = $request->get('dominio');
		$pixel->setUrl($url)->generateIdCampagna();
		$id_campagna = $pixel->getIdCampagna();
		/*salvo il cpl nell'oggetto Pixel */
		$media = $request->get('cplnumber');
	
		$payout_code = $request->get('payout_code');
		if(empty($payout_code)){$payout_code=null;}
		$_pixel = $this->checkExists($id_campagna,$media,$payout_code);
		
		// trovato già un pixel 
		$presente = false;
		if($_pixel){ $presente = true; 	}
		$response = new Response();
		$response->setContent(json_encode(array(
			'presente' 	=> $presente,
		)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	 
	public function editpixelAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager('pixel_man');
		$pixelid = $request->get('pixelid');
		$pixel = $this->getDoctrine()
				->getRepository('AppBundle:Pixels', 'pixel_man')
				->find($pixelid);

		if (!$pixel) {
			throw $this->createNotFoundException(
				'Nessun Pixel trovato con id '.$pixelid
			);
		}
		$aggiornato=false;
		$action = $request->get('azione');
		if(isset($action) && $action=='update'){
		
			$idcampagna = $request->get('idcampagna');
			$pixel->setIdCampagna($idcampagna);
			
			$media = $request->get('cplnumber');
			$pixel->setCplNumber($media);
			
			/* ripulisco il codice del pixel */
			$pixelcode = $request->get('pixel');
			$pixel->setPixel($pixelcode);
			
			/* setto la descizione */
			$descrizione = $request->get('descrizione');
			$pixel->setDescrizione($descrizione);
			
			/* recupero il falsemedia */
			$codebase = $request->get('codebase');
			$pixel->setCodebase($codebase);
			
			/* recupero il payout */
			$payout_code = $request->get('payout_code');
			if(empty($payout_code)){$payout_code=null;}
			$pixel->setPayoutCode($payout_code);
		
			/* attivo il pixel */
			$attivo = $request->get('attivo');
			if(isset($attivo)){$attivo=1;}else{$attivo = 0;}
			$pixel->setAttivo($attivo);
			$em->flush();
			$aggiornato=true;
		}
		

		return $this->render('editpixel.html.twig', [
            'pixel' => $pixel,
			'aggiornato' => $aggiornato
		]);
	}        
 
	// FUNZIONI PER RECUPERARE I DATI DAL FILTRO CAMPAGNA
	
	public function getCampagnaFieldAction(Request $request){
		// ricavo il campo da generare 
		$campo 			= $request->get('id'); // id del campo che richiama la funzione
        $qb				= $this->getDoctrine()->getManager()->createQueryBuilder();
        $resultOptions 	= '';
		$objid 			= '';
		$sql 			= '';
		$return 		= array();
		$front 			= $campo;
		$index 			= $campo;
		$objid 			= $campo;
		$campoCompleto	= '';
        switch($campo){
			// caso selezione tipocampagna
			// singolo valore
			case 'tipo_campagna': // restituisco tipo campagna
				$settori = $request->get('settore');
				if(empty($settori)){ break; }
				$label 		= 'Tipo Campagna';
				$onchangeAction = 'getBrandField(this)';
				//var_dump($settori);exit;
				$sql="SELECT DISTINCT c.tipo_campagna as tipo_campagna
						FROM campagna c 
						WHERE (";
				for($i=0;$i<count($settori);$i++){
					$sql .=" c.settore like '".$settori[$i]."' ";
					if(count($settori)>1 && $i!=(count($settori)-1)){
						$sql .= ' OR ';
					}
				}
				$sql 	.=") ORDER BY c.tipo_campagna ASC";

			break;
			// caso selezione brand
			case 'brand': // restituisco il campo brand
				$settori 		= $request->get('settore');
				$tipo_campagna 	= $request->get('tipo_campagna');
				$onchangeAction	= 'getTargetCampagnaField(this)';
				if(empty($tipo_campagna)){ break; }
				$label 			= 'Brand';
				
				$sql="SELECT DISTINCT b.name AS brand, b.id as bid FROM campagna c
						INNER JOIN brand b ON c.brand_id = b.id
						WHERE (";
				for($i=0;$i<count($settori);$i++){
					$sql .=" c.settore like '".$settori[$i]."' ";
					if(count($settori)>1 && $i!=(count($settori)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ') AND (';
				for($i=0;$i<count($tipo_campagna);$i++){
					$sql .=" c.tipo_campagna like '".$tipo_campagna[$i]."' ";
					if(count($tipo_campagna)>1 && $i!=(count($tipo_campagna)-1)){
						$sql .= ' OR ';
					}
				}	
				$index = 'bid'; // necessario per prelevare l'id del brand dalla query
				$sql .= ") ORDER BY b.name ASC";
				
			break;
			// ATTENZIONE! DEVO TENER CONTO DI TUTTI E DICO TUTTI I VALORI PASSATI DALLE SELEZIONI!!
			// caso selezione targetcampagna
			case 'target_campagna':
				$settori 		= $request->get('settore');
				$tipo_campagna 	= $request->get('tipo_campagna');
				$brand		 	= $request->get('brand');
				if(empty($brand)){ break; }
				$onchangeAction	= 'getNomeOffertaField(this)';
				
				$label 			= 'Target campagna';
				$sql="SELECT DISTINCT c.target_campagna FROM campagna c
						WHERE (";
				for($i=0;$i<count($settori);$i++){
					$sql .=" c.settore like '".$settori[$i]."' ";
					if(count($settori)>1 && $i!=(count($settori)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ') AND (';
				for($i=0;$i<count($tipo_campagna);$i++){
					$sql .=" c.tipo_campagna like '".$tipo_campagna[$i]."' ";
					if(count($tipo_campagna)>1 && $i!=(count($tipo_campagna)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ') AND (';
				for($i=0;$i<count($brand);$i++){
					$sql .=" c.brand_id ='".$brand[$i]."' ";
					if(count($brand)>1 && $i!=(count($brand)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ") ORDER BY c.target_campagna ASC";
			break;
			// prelevo il campo nome offerta
			case 'nome_offerta':
				$settori 		= $request->get('settore');
				$tipo_campagna 	= $request->get('tipo_campagna');
				$brand		 	= $request->get('brand');
				$target_campagna= $request->get('target_campagna');
				if(empty($target_campagna)){ break; }
				$onchangeAction	= '';
				$label 			= 'Nome Offerta';
				$sql="SELECT DISTINCT c.nome_offerta FROM campagna c
						WHERE (";
				for($i=0;$i<count($settori);$i++){
					$sql .=" c.settore like '".$settori[$i]."' ";
					if(count($settori)>1 && $i!=(count($settori)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ') AND (';
				for($i=0;$i<count($tipo_campagna);$i++){
					$sql .=" c.tipo_campagna like '".$tipo_campagna[$i]."' ";
					if(count($tipo_campagna)>1 && $i!=(count($tipo_campagna)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ') AND (';
				for($i=0;$i<count($brand);$i++){
					$sql .=" c.brand_id = '".$brand[$i]."' ";
					if(count($brand)>1 && $i!=(count($brand)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ' ) AND ( ';
				for($i=0;$i<count($target_campagna);$i++){
					$sql .=" c.target_campagna like '".$target_campagna[$i]."' ";
					if(count($target_campagna)>1 && $i!=(count($target_campagna)-1)){
						$sql .= ' OR ';
					}
				}	
				$sql .= ") ORDER BY c.target_campagna ASC";
			break;
			default:
			break;
		} 
		
		if(!empty($sql)){
			$parametri 	= 'name="filter['.$objid.']" id="'.$objid.'" class="selettoriCampagna form-control" onchange="'.$onchangeAction.'" multiple="multiple" width="50%"';
			
			$em 	= $this->getDoctrine()->getManager();
			$stmt 	= $em->getConnection()->prepare($sql);
			$stmt->execute();
			$res 	=  $stmt->fetchAll();
			
			foreach($res as $key => $value){
				//var_dump($value); exit; 
				$resultOptions .='<option value="'.$value[$index].'">'.$value[$front].'</option>';
			}
			
			$campoCompleto = $this->generateFilterCampagnaField($objid,$label,$parametri,$resultOptions);
		}
		$return = array('campo' => $campoCompleto, 'id' => $objid);
        $response = new Response();
        $response->setContent(json_encode($return));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    }   
       
}