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

use AppBundle\Entity\Miscelata as Miscelata;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class MiscelateAdminController extends Controller
{
	
	
	
	// RENDER FUNCIONS 
	//render listato template
	 public function miscelateAction(Request $message = null){
		
		$em 		= $this->getDoctrine()->getManager();
		//$mis2 = $em->getRepository('AppBundle:Miscelata')->findOneBy(['id' 	=> 2,]);
		//$mis = $em->getRepository('AppBundle:Miscelata')->findAll(array('attiva' => 'DESC'));
		$miscelate 	= $em->getRepository('AppBundle:Miscelata')->findBy(array(), array('attiva' => 'DESC'));
		$messageType   	=$message->get('Messaggio');
		$IdMisc		=$message->get('IdMisc');
		if($messageType=='MisCrea'){
			$MessaggioHtml='<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Miscelata creata (id '.$IdMisc.')</h3>';
		}elseif($messageType=='MisMod'){
			$MessaggioHtml=$MessaggioHtml='<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Miscelata modificata (id '.$IdMisc.')</h3>';
		}
		if(isset($MessaggioHtml)){
			$message=array($MessaggioHtml,$messageType,$IdMisc);
		}else{
			$message=FALSE;
		}
		
		return $this->render('listato_miscelate.html.twig', array(
			'miscelate'		=> $miscelate,
			'messaggio'		=> $message,
        ), null); 
    }
	 
	 
	 // render pagina crea miscelata
	public function creaAction($message = null){
		$alandings			= $this->getDoctrine()->getRepository('AppBundle:A_landing_cliente')->findBy(array(), array('id' => 'DESC'));
		$alandingsArr=array();
		foreach($alandings as $alanding){
			$landingId=$alanding->getLanding()->getId();
			if($landingId >0){
				$alandingsArr[]=array($alanding->getLanding()->getSlugLanding(),$alanding->getId());
			}
			
		}
		$campagne			= $this->getDoctrine()->getRepository('AppBundle:Campagna')->findBy(array(), array('data_start' => 'DESC'));
		$em 	= $this->getDoctrine()->getManager();
		$miscelate = $em->getRepository('AppBundle:Miscelata')->findAll();
		$miscelata=FALSE;
		return $this->render('crea_miscelata.html.twig', array(
			'campagne'		=> $campagne,
			'alandings' 		=> $alandings,
			'miscelata'		=>$miscelata,
			'miscelate'		=>$miscelate,
		), null); 
	}
	
	//SAVE FUNCTIONS
		public function salvaMiscelataAction(Request $request){
		$nome		 	= $request->get('nome');
		$hot_sources 			= json_decode(stripslashes($request->get('hot_sources')));
		$cold_source			= json_decode(stripslashes($request->get('cold_source')));
		$mixed_table			= json_decode(stripslashes($request->get('mixed_table')));
		$percentuale_fredde		= $request->get('percentuale_fredde');
		$limite					= $request->get('limite');
		$attiva					= $request->get('attiva');
		$cliente_id				= $request->get('cliente_id');
		$campagna_id			= $request->get('campagna_id');
		$landing_id				= $request->get('landing_id');
		
		$em  = $this->getDoctrine()->getManager();
		$NewMiscelata = new Miscelata();
		$NewMiscelata  	->setNome($nome)
						->sethot_sources($hot_sources)
						->setcold_source($cold_source)
						->setmixed_table	($mixed_table)
						->setpercentuale_fredde ($percentuale_fredde)
						->setLimite	($limite)
						->setAttiva	($attiva);
		if($cliente_id>0 AND $campagna_id>0  AND $landing_id>0){
			$NewMiscelata ->setcliente_id($cliente_id)->setcampagna_id($campagna_id)->setlanding_id($landing_id);
		}
		$em->persist($NewMiscelata);
		$em->flush();	
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $NewMiscelata->getId(),
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	// render pagina di modifica ordine payout clienti
	public function modificaAction(Request $request, $message = null){
		$em 	= $this->getDoctrine()->getManager();
		//get miscelata by id 
		$miscelata_id 			= $request->get('miscelata_id');
		$miscelata = $em->getRepository('AppBundle:Miscelata')->findOneBy(['id' 	=> $miscelata_id]);
		
		//get all miscelateAction
		$miscelate = $em->getRepository('AppBundle:Miscelata')->findAll();
		//get landings
		$alandings	= $em->getRepository('AppBundle:A_landing_cliente')->findBy(array(), array('id' => 'DESC'));
		$alandingsArr=array();
		foreach($alandings as $alanding){
			$landingId=$alanding->getLanding()->getId();
			if($landingId >0){
				$alandingsArr[]=array($alanding->getLanding()->getSlugLanding(),$alanding->getId());
			}
			
		}
		//get campagne
		$campagne  = $em->getRepository('AppBundle:Campagna')->findBy(array(), array('data_start' => 'DESC'));
		
		//pass miscelata to listato template
		
		
		
		return $this->render('crea_miscelata.html.twig', array(
			'campagne'		=> $campagne,
			'alandings' 		=> $alandings,
			'miscelata'		=>$miscelata,
			'miscelate'		=>$miscelate,
		), null); 
	}
	
	public function editMiscelataAction(Request $request){
		$id						= $request->get('id');
		$nome		 			= $request->get('nome');
		$hot_sources 			= json_decode(stripslashes($request->get('hot_sources')));
		$cold_source			= json_decode(stripslashes($request->get('cold_source')));
		$mixed_table			= json_decode(stripslashes($request->get('mixed_table')));
		$percentuale_fredde		= $request->get('percentuale_fredde');
		$limite					= $request->get('limite');
		$attiva					= $request->get('attiva');
		$cliente_id				= $request->get('cliente_id');
		$campagna_id			= $request->get('campagna_id');
		$landing_id				= $request->get('landing_id');
		
		$em  = $this->getDoctrine()->getManager();
		$miscelata = $em->getRepository('AppBundle:Miscelata')->findOneBy(['id' 	=> $id]);
		$miscelata  	->setNome($nome)
						->sethot_sources($hot_sources)
						->setcold_source($cold_source)
						->setmixed_table	($mixed_table)
						->setpercentuale_fredde ($percentuale_fredde)
						->setLimite	($limite)
						->setAttiva	($attiva);
		if($cliente_id>0 AND $campagna_id>0  AND $landing_id>0){
			$miscelata ->setcliente_id($cliente_id)->setcampagna_id($campagna_id)->setlanding_id($landing_id);
		}
		$em->persist($miscelata);
		$em->flush();	
		
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $miscelata->getId(),
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}	
		
	
	public function getMiscelataReportAction(Request $request){
		$id						=$request->get('miscelata_id');
		if($id AND $id>0){
			$granulosita			=$request->get('granulosita');
			if(!$granulosita){ $granulosita="mese";}
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
					$QueryDateRestrict='data_miscelazione >="'.$customstart.'" AND data_miscelazione <="'.$customend.'"';
					$QueryDateFrontend='dal <b>'.str_replace("00:00:00", "", $customstart).'</b> al <b>'.str_replace("23:59:59", "", $customend).'</b>';
				}
				
			}
			//se non abbiamo già stabilito una restrizione sulla query a livello di date procediamo analizzando i preset
			if($QueryDateRestrict==' ' AND $presetdata!='completo'){
				if($presetdata=='sette'){
					//ultimi sette giorni
					$QueryDateRestrict='date_add(data_miscelazione, interval 7 day) >= NOW()';
					$QueryDateFrontend='Ultimi 7 giorni';
				}elseif($presetdata=='trenta'){
					//ultimi 30 giorni
					$QueryDateRestrict='date_add(data_miscelazione, interval 30 day) >= NOW()';
					$QueryDateFrontend='Ultimi 30 giorni';
				}elseif($presetdata=='mese'){
					$QueryDateRestrict='MONTH(data_miscelazione)=MONTH(NOW()) AND YEAR(data_miscelazione)=YEAR(NOW())';
					$QueryDateFrontend='Questo mese';
				}elseif($presetdata=='mesepre'){
					$QueryDateRestrict='MONTH(data_miscelazione)=MONTH(NOW())-1 AND YEAR(data_miscelazione)=YEAR(NOW())';
					$QueryDateFrontend='Mese precedente';
				}
			}
			if($QueryDateRestrict!=' '){
				$QueryDateRestrict=' AND '.$QueryDateRestrict;
			}
			//settaggio variabili statiche
			$ArrHotSources=array();//tutte le etichette calde
			$Calde=0;
			$ArrColdSources=array();//tutte le etchette fredde
			$Fredde=0;
			$ArrTotali=array('calda'=>array(),'fredda'=>array(), 'tot'=>0);//array(hs=>array(source1=>33,source2=>2), cs=>array(source1=>33,source2=>2)
			$Totali=0;//numero di tutte le anagrafiche
			$ArrGranulo=array();// tutte le numeriche divise o per mese o per giorno:  key (datarange mese/giorno) =>array(hs=>array(source1=>33,source2=>2), cs=>array(source1=>33,source2=>2), tot=>0)
			$ArrGranuloTecnico=array();
			$ReportHTML='';
			
			//capiamo in base alla granularita la query da eseguire
			if($granulosita=="mese"){
				$ReportQuery='SELECT DATE_FORMAT(data_miscelazione,"%Y-%m") as datatecnica, DATE_FORMAT(data_miscelazione,"%b %Y") as data, COUNT(*) as quantita, lead_type, source_et from miscelate_light_report  WHERE id_miscelata='.$id.' '.$QueryDateRestrict.' GROUP BY DATE_FORMAT(data_miscelazione,"%y-%m"), source_et, lead_type ORDER BY data_miscelazione DESC';
			}else{
				$ReportQuery='SELECT DATE_FORMAT(data_miscelazione,"%Y-%m-%d") as datatecnica, DATE_FORMAT(data_miscelazione,"%d/%c/%Y") as data, COUNT(*) as quantita, lead_type, source_et from miscelate_light_report  WHERE id_miscelata='.$id.' '.$QueryDateRestrict.' GROUP BY DATE(data_miscelazione), source_et, lead_type ORDER BY data_miscelazione DESC';
				
			}
			$em 	= $this->getDoctrine()->getManager();
			$conn 	= $em->getConnection();
			if($conn){
				$stmt 	= $em->getConnection()->prepare($ReportQuery);
				$stmt->execute();
				if ($stmt->rowCount()>0) {
					while ($gruppoR = $stmt->fetch()) {
						//per ogni riga ritrovata
						$Rdata=$gruppoR['data'];
						$RdataTecnica=$gruppoR['datatecnica'];
						$Rquantita=$gruppoR['quantita'];
						$Rtype=$gruppoR['lead_type'];
						$Retichetta=$gruppoR['source_et'];
						//controllare se il periodo esiste come key in $ArrGranulo se no aggiungerlo con valore array('hs'=>array(),'cs'=>array())
						if(!array_key_exists($Rdata, $ArrGranulo)){
							$ArrGranulo[$Rdata]=array('calda'=>array(),'fredda'=>array(), 'tot'=>0);
							$ArrGranuloTecnico[$Rdata]=$RdataTecnica;
						}
						//se calda
						if($Rtype=='calda'){
							//controlla se esiste nell array $ArrHotSources l'etichetta altrimenti aggiungerla
							if(!in_array($Retichetta,$ArrHotSources)){$ArrHotSources[]=$Retichetta;}
							//controlla se esiste nell array $ArrTotali['hs'] altrimenti aggiungerla e setta il valore a 0
							if(!array_key_exists($Retichetta, $ArrTotali['calda'])){
								$ArrTotali['calda'][$Retichetta]=0;
							}
							//aumentare del count il valore della key (etichetta) nell array $ArrTotali['hs']
							$ArrTotali['calda'][$Retichetta]=$ArrTotali['calda'][$Retichetta]+$Rquantita;
							//controlla se nel periodo $ArrGranulo[periodo][hs] esiste l'etichetta altrimenti aggiungerla e setta il valore a count: 'etichetta'=>count
							if(!array_key_exists($Retichetta, $ArrGranulo[$Rdata]['calda'])){
								$ArrGranulo[$Rdata]['calda'][$Retichetta]=0;
							}
							//aggiungi al conteggio dell'etichetta in $ArrGranulo[periodo][hs]
							$ArrGranulo[$Rdata]['calda'][$Retichetta]=$ArrGranulo[$Rdata]['calda'][$Retichetta]+$Rquantita;
							//aumentare il valore $ArrGranulo[periodo][tot] di count
							$ArrGranulo[$Rdata]['tot']=$ArrGranulo[$Rdata]['tot']+$Rquantita;
							//aumenta di count il valore di $Calde
							$Calde=$Calde+$Rquantita;
							$Totali=$Totali+$Rquantita;
						}elseif($Rtype=='fredda'){
							//stessa cosa di calda con fredda
							//controlla se esiste nell array $ArrColdSources l'etichetta altrimenti aggiungerla
							if(!in_array($Retichetta,$ArrColdSources)){$ArrColdSources[]=$Retichetta;}
							//controlla se esiste nell array $ArrTotali['fredda'] altrimenti aggiungerla e setta il valore a 0
							if(!array_key_exists($Retichetta, $ArrTotali['fredda'])){
								$ArrTotali['fredda'][$Retichetta]=0;
							}
							//aumentare del count il valore della key (etichetta) nell array $ArrTotali['fredda']
							$ArrTotali['fredda'][$Retichetta]=$ArrTotali['fredda'][$Retichetta]+$Rquantita;
							//controlla se nel periodo $ArrGranulo[periodo][fredda] esiste l'etichetta altrimenti aggiungerla e setta il valore a count: 'etichetta'=>count
							if(!array_key_exists($Retichetta, $ArrGranulo[$Rdata]['fredda'])){
								$ArrGranulo[$Rdata]['fredda'][$Retichetta]=0;
							}
							//aggiungi al conteggio dell'etichetta in $ArrGranulo[periodo][hs]
							$ArrGranulo[$Rdata]['fredda'][$Retichetta]=$ArrGranulo[$Rdata]['fredda'][$Retichetta]+$Rquantita;
							//aumentare il valore $ArrGranulo[periodo][tot] di count
							$ArrGranulo[$Rdata]['tot']=$ArrGranulo[$Rdata]['tot']+$Rquantita;
							//aumenta di count il valore di $Calde
							$Fredde=$Fredde+$Rquantita;
							$Totali=$Totali+$Rquantita;
						}
						
						
					}
					//calcoliamo la percentuale di fredde e calde per questo periodo
					$PercCalde=round($Calde*100/$Totali,2);
					$PercFredde=round(100-$PercCalde,2);
					//$ReportHTML=$ReportQuery.' : '.count($ArrGranulo);
					//abbiamo creato la base dati su cui creare la var html
					
					//andiamo a creare l'html
					//creiamo l'html statico (<table>)
					$ReportHTMLOpen='<table id="ReportTable" class="table table-bordered" data-period="'.$presetdata.'" data-da="'.$customstart.'" data-a="'.$customend.'"><tbody>';
					$ReportHTMLClose='</tbody></table>';
					//creiamo la prima riga di head
						//colonna 0: vuota, prima colonna: vuota, seconda colonna: calde colspan=count($ArrHotSources)+ percentuale di calde su totale, terza colonna: fredde colspan=count($ArrColdSources)+ percentuale di fedde su totale
					$ReportHTML1Row='
						<tr class="Rsuperheadtable">
							<th class="Rgranulo"></th>
							<th class="Rtotali"></th>
							<th class="RfCalde" colspan="'.count($ArrHotSources).'">Fonti Calde <small class="percvalue">('.$PercCalde.'%)</small></th>
							<th class="RfFredda" colspan="'.count($ArrColdSources).'">Fonti Fredde <small class="percvalue">('.$PercFredde.'%)</small></th>
						</tr>
						';
					
					
					//creiamo la seconda riga di heading 
						//colonna 0: vuota, prima colonna: totali; foreach $ArrHotSources crea colonna, foreach $ArrColdSources crea colonna
					$ReportHTML2Row='
						<tr class="Rheadtable">
							<th class="Rgranulo"></th>
							<th class="Rtotali">Totali</th>';
							foreach($ArrHotSources as $ArrHotSource){
								$ReportHTML2Row.='<th class="RfCalda" >'.$ArrHotSource.'</th>';
							}
							foreach($ArrColdSources as $ArrColdSource){
								$ReportHTML2Row.='<th class="RfFredda" >'.$ArrColdSource.'</th>';
							}
						$ReportHTML2Row.='</tr>';
					
					
					
					
					//creiamo dati totali
						//colonna 0 ='totali' 
						//prima colonna= $Totali
						//foreach $ArrHotSources if is present as key in $ArrTotali[hs] echo valore altrimenti echo 0
						//foreach $ArrColdSources if is present as key in $ArrTotali[cs] echo valore altrimenti echo 0
					$ReportHTMLTotaliRow='
						<tr class="Rbodytable RbodytableTotals">
							<td class="Rgranulo">Totali  periodo</td>
							<td class="Rtotali">'.$Totali.'</td>';
							foreach($ArrHotSources as $ArrHotSource){
								
								if(array_key_exists($ArrHotSource, $ArrTotali['calda'])){
									$ReportHTMLTotaliRow.='<td class="RfCalda get-cpl" data-etichetta="'.$ArrHotSource.'" data-tot="'.$ArrTotali['calda'][$ArrHotSource].'">';
									$ReportHTMLTotaliRow.=$ArrTotali['calda'][$ArrHotSource];
									$ReportHTMLTotaliRow.='  <small class="percvalue">('.round(($ArrTotali['calda'][$ArrHotSource]*100/$Totali),2).'%)</small>';
								}else{
									$ReportHTMLTotaliRow.='<td class="RfCalda" data-etichetta="'.$ArrHotSource.'" data-tot="0">';
									$ReportHTMLTotaliRow.=0;
								}
								$ReportHTMLTotaliRow.='</td>';
							}
							foreach($ArrColdSources as $ArrColdSource){
								if(array_key_exists($ArrColdSource, $ArrTotali['fredda'])){
									$ReportHTMLTotaliRow.='<td class="RfFredda get-cpl" data-etichetta="'.$ArrColdSource.'" data-tot="'.$ArrTotali['fredda'][$ArrColdSource].'">';
									$ReportHTMLTotaliRow.=$ArrTotali['fredda'][$ArrColdSource];
									$ReportHTMLTotaliRow.='  <small class="percvalue">('.round(($ArrTotali['fredda'][$ArrColdSource]*100/$Totali),2).'%)</small>';
								}else{
									$ReportHTMLTotaliRow.='<td class="RfFredda" data-etichetta="'.$ArrColdSource.'" data-tot="0">';
									$ReportHTMLTotaliRow.=0;
								}
								$ReportHTMLTotaliRow.='</td>';
							}
					$ReportHTMLTotaliRow.='</tr>';
					
					$ReportHTMLGranuli='';
					//creiamo righe granulosità
					//foreach $ArrGranulo
					foreach($ArrGranulo as $granulo=>$GranuloData){
						$ReportHTMLGranuli.='<tr  class="Rbodytable">';
						//riga 0 Key della granulosità
						$ReportHTMLGranuli.='<td class="Rgranulo">'.$granulo.'</td>';
						//prima riga: totali della granulosità [tot]
						$ReportHTMLGranuli.='<td class="Rtotali">'.$GranuloData['tot'].'</td>';
						foreach($ArrHotSources as $ArrHotSource){
							if(array_key_exists($ArrHotSource,$GranuloData['calda'])){
								$ReportHTMLGranuli.='<td class="RfCalda get-cpl" data-granulo="'.$ArrGranuloTecnico[$granulo].'" data-etichetta="'.$ArrHotSource.'" data-tot="'.$GranuloData['calda'][$ArrHotSource].'">';
								$ReportHTMLGranuli.=$GranuloData['calda'][$ArrHotSource];
								$ReportHTMLGranuli.='  <small class="percvalue">('.round(($GranuloData['calda'][$ArrHotSource]*100/$GranuloData['tot']),2).'%)</small>';
							}else{
								$ReportHTMLGranuli.='<td class="RfCalda" data-granulo="'.$ArrGranuloTecnico[$granulo].'" data-etichetta="'.$ArrHotSource.'" data-tot="0">';
								$ReportHTMLGranuli.=0;
							}
							$ReportHTMLGranuli.='</td>';
						}
						foreach($ArrColdSources as $ArrColdSource){
							
							if(array_key_exists($ArrColdSource, $GranuloData['fredda'])){
								$ReportHTMLGranuli.='<td class="RfFredda get-cpl" data-granulo="'.$ArrGranuloTecnico[$granulo].'" data-etichetta="'.$ArrColdSource.'" data-tot="'.$GranuloData['fredda'][$ArrColdSource].'">';
								$ReportHTMLGranuli.=$GranuloData['fredda'][$ArrColdSource];
								$ReportHTMLGranuli.='  <small class="percvalue">('.round(($GranuloData['fredda'][$ArrColdSource]*100/$GranuloData['tot']),2).'%)</small>';
							}else{
								$ReportHTMLGranuli.='<td class="RfFredda" data-granulo="'.$ArrGranuloTecnico[$granulo].'" data-etichetta="'.$ArrColdSource.'" data-tot="0">';
								$ReportHTMLGranuli.=0;
							}
							$ReportHTMLGranuli.='</td>';
						}
						$ReportHTMLGranuli.='</tr>';
					}
					$ReportHTML='<p style="margin-bottom:30px;"><b>Dati per il periodo <i>"'.$QueryDateFrontend.'"</i></b></p>'.$ReportHTMLOpen.$ReportHTML1Row.$ReportHTML2Row.$ReportHTMLTotaliRow.$ReportHTMLGranuli.$ReportHTMLClose;	
							
							//prima riga: totali della granulosità [tot]
							//foreach $ArrHotSources if is present as key in this[hs] echo valore altrimenti echo 0
							//foreach $ArrHotSources if is present as key in this[cs] echo valore altrimenti echo 0
					
				}else{
					$ReportHTML='<p><b>Nessun dato ritrovato per il periodo <i>"'.$QueryDateFrontend.'"</i></b></p>';
				}
			}else{
				$HtmlAlert.='<p>Errore sulla connessione.riprova</p>';
			}
			$ReportHTML=$ReportHTML.$HtmlAlert;
			
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
		
	
	
	
	
	
	public function reportMiscelataAction(Request $request){
		$id						=$request->get('miscelata_id');
		//$ReportHTML=$this->get_miscelata_report($request);
		$ReportHTML=$this->getMiscelataReportAction($request);
		$em  = $this->getDoctrine()->getManager();
		$miscelata = $em->getRepository('AppBundle:Miscelata')->findOneBy(['id' 	=> $id]);
		//get totali reali
		$em2  = $this->getDoctrine()->getManager();
		$conn2 	= $em->getConnection();
		$TotalQuery='SELECT COUNT(*) as totale FROM miscelate_light_report where id_miscelata='.$id;
		if($conn2){
			$stmt 	= $em2->getConnection()->prepare($TotalQuery);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($ToataleQresult = $stmt->fetch()) {
					$totaleLead=$ToataleQresult['totale'];
				}
			}
		}
		//get rapporto reali
		$em3  = $this->getDoctrine()->getManager();
		$conn3 	= $em->getConnection();
		$PercentualeQuery='SELECT COUNT(*) as totale FROM miscelate_light_report WHERE id_miscelata='.$id.' AND  lead_type="calda"';
		if($conn3){
			$stmt 	= $em3->getConnection()->prepare($PercentualeQuery);
			$stmt->execute();
			if ($stmt->rowCount()>0) {
				while ($PercQresult = $stmt->fetch()) {
					$CaldeTotali=$PercQresult['totale'];
				}
			}
		}
		if($totaleLead AND $totaleLead>0){
				$PercCalde=round($CaldeTotali*100/$totaleLead,2);
				$PercFredde=round(100-$PercCalde,2);
		}else{
				$PercCalde=$PercFredde=0;
		}
	
		
		return $this->render('report_miscelata.html.twig', array(
			'miscelata'		=> $miscelata,
			'report'		=>$ReportHTML,
			'totale'		=>$totaleLead,
			'percentuale'	=>$PercFredde,
		), null); 
	}
		
		
		
		
		public function  getCplGranuloAction(Request $request){
			$id						=$request->get('miscelata_id');
			$data					=$request->get('granu');
			$etichetta				=$request->get('et');
			$tipo					=$request->get('ty');
			$tot					=$request->get('tot');
			$presetdata				=$request->get('preset');
			$customstart				=$request->get('da');
			$customend				=$request->get('a');
			$em  = $this->getDoctrine()->getManager();
			$conn 	= $em->getConnection();
			$HtmlAlert='';
			if($data){
				//Query riferita a un granulo
				$queryCpl='SELECT COUNT(*) AS totale, cpl from miscelate_light_report where id_miscelata='.$id.' and source_et="'.$etichetta.'" and lead_type="'.$tipo.'" and data_miscelazione LIKE "'.$data.'%" GROUP BY cpl';
				$HTML='<p>Periodo: <b>'.$data.'</b><br>Tipo di lead: <b>'.$tipo.'</b><br>Etichetta: <b>'.$etichetta.'</b><br>Lead totali: <b>'.$tot.'</b></p>';
			}else{
				//query riferita al totale del periodo selezionato
				$QueryDateRestrict=' ';
				$QueryDateFrontend="completo";
				if($presetdata=='personale'){
					if(!$customstart OR !$customend){
						$presetdata="completo";
						$HtmlAlert.='<p>La data richiesta non era corretta. risultati mostrati su tutte le date</p>';
						}else{
						$QueryDateRestrict='data_miscelazione >="'.$customstart.'" AND data_miscelazione <="'.$customend.'"';
						$QueryDateFrontend='dal <b>'.str_replace("00:00:00", "", $customstart).'</b> al <b>'.str_replace("23:59:59", "", $customend).'</b>';
					}
					
				}
				//se non abbiamo già stabilito una restrizione sulla query a livello di date procediamo analizzando i preset
				if($QueryDateRestrict==' ' AND $presetdata!='completo'){
					if($presetdata=='sette'){
						//ultimi sette giorni
						$QueryDateRestrict='date_add(data_miscelazione, interval 7 day) >= NOW()';
						$QueryDateFrontend='Ultimi 7 giorni';
					}elseif($presetdata=='trenta'){
						//ultimi 30 giorni
						$QueryDateRestrict='date_add(data_miscelazione, interval 30 day) >= NOW()';
						$QueryDateFrontend='Ultimi 30 giorni';
					}elseif($presetdata=='mese'){
						$QueryDateRestrict='MONTH(data_miscelazione)=MONTH(NOW()) AND YEAR(data_miscelazione)=YEAR(NOW())';
						$QueryDateFrontend='Questo mese';
					}elseif($presetdata=='mesepre'){
						$QueryDateRestrict='MONTH(data_miscelazione)=MONTH(NOW())-1 AND YEAR(data_miscelazione)=YEAR(NOW())';
						$QueryDateFrontend='Mese precedente';
					}
				}
				if($QueryDateRestrict!=' '){
					$QueryDateRestrict=' AND '.$QueryDateRestrict;
				}
				$queryCpl='SELECT COUNT(*) AS totale, cpl from miscelate_light_report where id_miscelata='.$id.' and source_et="'.$etichetta.'" and lead_type="'.$tipo.'" '.$QueryDateRestrict.' GROUP BY cpl';
				
				$HTML='<p>Periodo: '.$QueryDateFrontend.'<br>Tipo di lead: <b>'.$tipo.'</b><br>Etichetta: <b>'.$etichetta.'</b><br>Lead totali: <b>'.$tot.'</b></p>';
				
			}
			if($conn){
				$stmt 	= $em->getConnection()->prepare($queryCpl);
				$stmt->execute();
				if ($stmt->rowCount()>0) {
					$HtmlTable='<table class="table table-bordered" style="margin-top:22px;"><tbody>';
					while ($cplData = $stmt->fetch()) {
						//$CaldeTotali=$PercQresult['totale'];
						$HtmlTable.='<tr><td style="font-weight: bold;;text-align:left;">'.$cplData['cpl'].'</td><td style="text-align:center;">'.$cplData['totale'].'</td><td style="text-align:center;">'.round($cplData['totale']*100/$tot , 2).'%</td></tr>';
					}
					$HtmlTable.='</tbody></table>';
					$HTML.=$HtmlTable;
				}else{
					$HTML.='<p>Nessun dato trovato</p>';
				}
			}else{
				$HTML.='<p>Errore di connessione</p>';
			}
			
			$response = new Response();
			//$response->setContent(json_encode($ReportHTML));
			$response->setContent(json_encode(array(
										'cpl'	=> $HTML.$HtmlAlert,
											)
										));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}
		
		
		
	
	
	//funzione che per ogni campagna (attiva?) recupera nomecampagna, db, table, campagna id
	
	
	
	
	
	
	
	// FINE RENDER FUNCTIONS -------------------------------->

	
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
	public function deleteMiscelataAction(Request $request){
		$id 	= $request->get('id');
		$target = '0'; 
		$result = false;
		if(!empty($id)){
			// step 1 - eliminazione ordine 
			$em  		= $this->getDoctrine()->getManager();
			$sql_base 	= "DELETE FROM miscelate_light WHERE id=?";
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