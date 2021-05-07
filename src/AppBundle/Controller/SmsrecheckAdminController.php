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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use AppBundle\CustomFunc\SendSmsDirect;
use AppBundle\CustomFunc\WriteHistoryDpoBlacklist;
use Symfony\Component\Security\Core\Security;
//use AppBundle\CustomFunc\Pager as Pager;

class SmsrecheckAdminController extends Controller
{
	
	
	
	// RENDER FUNCIONS 
	//render listato template
	 public function recheckAction(Request $message = null){
		$sql = "SELECT * FROM sms_replies where recheckgestionale=0 and assigned=0 and campaign_id is not NULL and campaign_id!='' and client_id is not NULL and client_id!='' and errorcheck=0";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_smsreplies =  $stmt->fetchAll();
		$smsreplies=array();
		foreach($_smsreplies as $_smsreplie){
			$IdRep=$_smsreplie['id'];
			$CellRep=$_smsreplie['cellulare'];
			$RispostaRep=$_smsreplie['risposta'];
			$DataRep=$_smsreplie['data'];
			$DataOra=$_smsreplie['ora'];
			$CampagnaRep=$_smsreplie['campaign_id'];
			//get campagna
			$em 		= $this->getDoctrine()->getManager();
			$campagnaObj = $em->getRepository('AppBundle:Campagna')->findOneBy(['id' 	=> $CampagnaRep]);
			$CampagnaNameRep=$campagnaObj->getNomeOfferta();
			$em->persist($campagnaObj);
			$em->flush();
			
			$Rep=array(
				'id'		 => $IdRep,
				'cellulare'	 => $CellRep,
				'risposta'	 => $RispostaRep,
				'data'	 	 => $DataRep.' '.$DataOra,
				'campagna'	 => $CampagnaNameRep,
				);
			$smsreplies[]=$Rep;
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
		
		return $this->render('listato_smsrecheck.html.twig', array(
			'messaggio'		=> $message,
			'replies'		=> $smsreplies,
        ), null); 
    }
	 
	 
	 // render pagina crea miscelata
	public function setrecheckAction(Request $request){
		$ToBlack		= $request->get('black');
		$Ass			= $request->get('assegna');
		$All			= $request->get('all');
		$report='';
		//set blacklisted
		if($ToBlack AND $ToBlack!=""){
			$sqlBlack = "UPDATE sms_replies set tobeblacklisted=1 where id in (".$ToBlack.")";
			$em = $this->getDoctrine()->getManager();
			$stmt = $em->getConnection()->prepare($sqlBlack );
			$stmt->execute();
		}
		//set assigned
		if($Ass AND $Ass!=""){
			$sqlAss = "UPDATE sms_replies set tobeassigned=1 where id in (".$Ass.")";
			$em2 = $this->getDoctrine()->getManager();
			$stmt2 = $em2->getConnection()->prepare($sqlAss);
			$stmt2->execute();
		}
		//set checked
		if($All AND $All!=""){
			//$report=DoRecheckAction($All);
			$sqlAll = "UPDATE sms_replies set recheckgestionale=1 where id in (".$All.")";
			$em3 = $this->getDoctrine()->getManager();
			$stmt3 = $em3->getConnection()->prepare($sqlAll);
			$stmt3->execute();
			$mess="Settaggio recheck effettuato";
		}else{
			$mess="Settaggio recheck Non effettuato";
		}
		//lunch sms_recheck
		$this->DoRecheckAction($All);
		$response = new Response();
		$response->setContent(json_encode(array(
										'result'	=> $mess,
											)
										));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	
	
	public function DoRecheckAction($All){
		// LOGS
		
		$output = " -- SMS recheck --  ";
		/*//echo " -- SMS recheck --  ";
		$log->buffer($output);*/
		$time = $_SERVER['REQUEST_TIME'];
		$time = date('Y-m-d H:i:s', $time);
		$timesms = $_SERVER['REQUEST_TIME'];
		$datasms = date('Y-m-d', $timesms);
		$orasms =  date('H:i:s', $timesms);

		$conndbuti =  mysqli_connect("87.98.135.4", "utilscpl_db", "FJg-23nHRNfzTmc7", "utilslinkappeal_cpl");
		$conndb_leadout =  mysqli_connect("46.16.95.34","leadoutl_usbd","A%2)8s!JTkBp", "leadoutl_dbb");


		// TO BE ASSIGNED
		//get all to be assigned
		
		$sql_getToBeAssigned = "SELECT * FROM sms_replies WHERE tobeassigned = 1 and assigned=0 and campaign_id is not NULL and campaign_id!='' and client_id is not NULL and client_id!='' and errorcheck=0 and id in (".$All.")";
		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($sql_getToBeAssigned);
		$stmt->execute();
		$result_getToBeAssigned =  $stmt->fetchAll();
		

		$output .=  "QUERY di recupero to be assigned " .$sql_getToBeAssigned. PHP_EOL;
		//echo  "QUERY di recupero to be assigned " .$sql_getToBeAssigned."<br>";
		if (count($result_getToBeAssigned) > 0) {
			$output .=  "trovati contatti da riassegnare " . PHP_EOL;
			//echo "trovati contatti da riassegnare <br>";
			foreach ($result_getToBeAssigned as $rowToBeAss ) {
				$IdRisposta=$rowToBeAss['id'];
				$mittente=$mittenteNumero=$rowToBeAss['cellulare'];
				$output .=  "contatto numero: " . $mittenteNumero. PHP_EOL;
				//echo "contatto numero: " . $mittenteNumero."<br>";
				$cliente_id = $rowToBeAss['client_id'];
				$nomecampagna = $rowToBeAss['campagna'];
				//recupero la tabella da cui abbiamo estratto la lead, se vuota imposto lead_uni
			    $extraction_id=$rowToBeAss['extraction_id'];
				$campaign_id = $rowToBeAss['campaign_id'];

				//recupero tabella esterna da estraction
				$sql_getTabOrigin = "SELECT Tabella_origine, source_id FROM extraction_sms WHERE cellulare='".$mittente."' AND id =".$extraction_id;
				$em2 = $this->getDoctrine()->getManager();
				$stmt2 = $em2->getConnection()->prepare($sql_getTabOrigin);
				$stmt2->execute();
				$result_getTabOrigin =$stmt2->fetchAll();
				$Tabella_origine='';
				$Lead_Source_id='';
				if (count($result_getTabOrigin) > 0) {
					foreach ($result_getTabOrigin as $result_getTabOriginLoop) {
						$Tabella_origine = $result_getTabOriginLoop ['Tabella_origine'];
						$Lead_Source_id =$result_getTabOriginLoop ['source_id'];
						$output .=  "La tabella di origine �: " . $Tabella_origine.PHP_EOL;
						//echo "La tabella di origine �: " . $Tabella_origine."<br>";
					}
				}
				
				
				//get the campaign info
				$output .=  "CON L'ID DEL CLIENTE POSSO ANDARE A VERIFICARE SE ESISTONO CAMPAGNE ASSOCIATE AL CLIENTE CON offtargetCond = SMS " . PHP_EOL;
				//echo "CON L'ID DEL CLIENTE POSSO ANDARE A VERIFICARE SE ESISTONO CAMPAGNE ASSOCIATE AL CLIENTE CON offtargetCond = SMS <br>" ;
				
				$sql_getCampaign = "SELECT * FROM a_landing_cliente WHERE cliente_id = " . $cliente_id . " AND clienteAttivo = 1 AND offtargetCond = 'SMS' AND campagna_id = ".$campaign_id." order by data_start desc limit 0,1" ;
				//echo "query di landing_cliente". $sql_getCampaign." <br>" ;
				$em3 = $this->getDoctrine()->getManager();
				$stmt3 = $em3->getConnection()->prepare($sql_getCampaign);
				$stmt3->execute();
				$resultCampaign =$stmt3->fetchAll();
				
			
				////echo $resultCampaign->num_rows;
				if (count($resultCampaign) > 0) {
					foreach ($resultCampaign as $rowCampaign) {
						$offtgt_table = $rowCampaign['mailCliente'];
						$landing_id = $rowCampaign['landing_id'];
						$output .=  "TROVATA UNA CAMPAGNA CORRISPONDENTE " . $campaign_id ." CON LANDING ID " . $landing_id . " SULLA TABELLA " . $offtgt_table . PHP_EOL;
						//echo "TROVATA UNA CAMPAGNA CORRISPONDENTE " . $campaign_id ." CON LANDING ID " . $landing_id . " SULLA TABELLA " . $offtgt_table . "<br>";
						
						//recupero dati del contatto dal cellulare e tabella di origine
						$output .=  "ecupero dati del contatto dal cellulare e tabella di origine" . PHP_EOL;
						//echo "ecupero dati del contatto dal cellulare e tabella di origine <br>".$Tabella_origine;
						//differenziazione amplifon
						if(!isset($Tabella_origine) OR $Tabella_origine==""){
							$Tabella_origine="lead_uni"; 
							$output .=  "non siamo riusciti a recuperare da extraction la tabella di origine.. abbiamo settato lead_uni ricercheremo tramite cellulare" .PHP_EOL;
							$sql_getNames = "SELECT * FROM ".$Tabella_origine." WHERE cellulare = '" . $mittente . "' order by data desc limit 0,1" ; 
						}else{
							$sql_getNames = "SELECT * FROM ".$Tabella_origine." WHERE id = '" . $Lead_Source_id . "'" ;
						}
						
						/*if($cliente_id==139 OR $cliente_id==155){
							if($Tabella_origine=="lead_uni"){
								$sql_getNames = "SELECT *, anno_nascita as eta FROM ".$Tabella_origine." WHERE cellulare = '" . $mittente . "' order by data desc limit 0,1" ;
							}else{
								$sql_getNames = "SELECT * FROM ".$Tabella_origine." WHERE cellulare = '" . $mittente . "' order by data desc limit 0,1" ;
							}
						}*/
							
						$output .="query:". $sql_getNames. PHP_EOL;
						//echo "query:". $sql_getNames."<br>";
						////echo "ritrovo la lead.. query:".$sql_getNames."<br>";
						$em4 = $this->getDoctrine()->getManager();
						$stmt4 = $em4->getConnection()->prepare($sql_getNames);
						$stmt4->execute();
						$result =$stmt4->fetchAll();								
						
						if (count($result) > 0){
							foreach($result as $row ){
								$output .="dati:". PHP_EOL;
								//echo "dati: <br>";
							   
								$nome = $row['nome'];
								//echo "nome".$nome;
								$output .="nome".$nome;
								$cognome = $row['cognome'];
								//echo "; cognome".$cognome."<br>";
								$output .="; cognome".$cognome. PHP_EOL;
								//differenziazione amplifon
								/*if($cliente_id==139 OR $cliente_id==155){
									if($Tabella_origine=="lead_uni"){
										$etaAss = 2019-$row['eta'];
										if($etaAss>64){
											$eta='over 65';
										}else{
											$eta='60-65';
										}
									}else{
										$eta = $row['eta'];
									}
									if($eta=='60-65'){
										$eta='under 65';
									}
								}*/
								if($Tabella_origine =="lead_uni"){
									$source_db = $row['source_db'];
									$source_tbl = $row['source_tbl'];
									$source_id = $row['source_id'];   
								}else{
									$source_db = "esterno";
									$source_tbl = $Tabella_origine;
									$source_id =  $row['id']; 
								}
								$lead_id = $row['id'];                                     
								////echo "lead numero: ".$lead_id."<br>";
								$output .=  "TROVATO UN UTENTE CORRISPONDENTE AL NUMERO DI TELEFONO DA CUI ABBIAMO RICEVUTO L'SMS. NOME" . $nome ." COGNOME " . $cognome . " LEAD ID " . $lead_id . "SOURCE DB " . $source_db . " SOURCE TABLE " . $source_tbl . " CON ID ORIGINARIO " . $source_id. PHP_EOL;
								//echo "TROVATO UN UTENTE CORRISPONDENTE AL NUMERO DI TELEFONO DA CUI ABBIAMO RICEVUTO L'SMS. NOME" . $nome ." COGNOME " . $cognome . " LEAD ID " . $lead_id . "SOURCE DB " . $source_db . " SOURCE TABLE " . $source_tbl . " CON ID ORIGINARIO " . $source_id . "<br>";
							}
						}else{
							//echo "Il numero non risulta a db procedo al prossimo<br>";
							$output .="Il numero non risulta a db procedo al prossimo". PHP_EOL;
							 
							$sql_errorNoNumber = "UPDATE sms_replies SET errorcheck=1  WHERE id=".$IdRisposta ;
							$output .= "query update sms replies (error)". $sql_errorNoNumber. PHP_EOL;
							 //echo "query update sms replies (error)".$sql_errorNoNumber;
							$em7 = $this->getDoctrine()->getManager();
							$stmt7 = $em7->getConnection()->prepare($sql_errorNoNumber);
							$stmt7->execute();
							continue;
						}   
						$output .=  "PROCEDO CON L'INSERIMENTO NEL LEADOUT DEL CLIENTE" . PHP_EOL;
						//echo "PROCEDO CON L'INSERIMENTO NEL LEADOUT DEL CLIENTE <br>";
						
								/*if($cliente_id==139 OR $cliente_id==155){
									$sql_insert_leadout = "INSERT INTO " . $offtgt_table . " (SURNAME,NAME,PHONE1,DATA,CLIENTE) 
											VALUES ('" . $cognome . "','" . $nome . "','" . $mittente . "','" . $time . "','" . $eta . "')";
								}else{
									$sql_insert_leadout = "INSERT INTO " . $offtgt_table . " (SURNAME,NAME,PHONE1,DATA) 
											VALUES ('" . $cognome . "','" . $nome . "','" . $mittente . "','" . $time . "')";
								}*/
									
								$sql_insert_leadout = "INSERT INTO " . $offtgt_table . " (SURNAME,NAME,PHONE1,DATA) 
											VALUES ('" . $cognome . "','" . $nome . "','" . $mittente . "','" . $time . "')";
										
								$output .=  "QUERY SALVATAGGIO IN LEADOUT:" . $sql_insert_leadout . PHP_EOL;
								//echo "Query di salvataggio in leadout!!: ". $sql_insert_leadout ."<br><br>";
								
								$result_insert_leadout = mysqli_query($conndb_leadout, $sql_insert_leadout);
								//$result_insert_leadout ="ok";
								if(!$result_insert_leadout){ //errore
									$output .=  "ERRORE COPIA LEAD IN LEADOUT MAIL:" . $offtgt_table . PHP_EOL;
									$output .=  mysqli_error($conndb_leadout) . PHP_EOL;
									//scrivo errore
									$sql_errorTbA = "UPDATE sms_replies SET errorcheck=1  WHERE id=".$IdRisposta  ;      
									$output .= "query update sms replies (error)". $sql_errorTbA. PHP_EOL;
									
									//echo "query update sms replies (error)". $sql_errorTbA;
									$em5 = $this->getDoctrine()->getManager();
									$stmt5 = $em5->getConnection()->prepare($sql_errorTbA);
									$stmt5->execute();
									 $result_sql_errorTbA =$stmt5->fetchAll();
									//$assigned = 0;						
								}else{ // la copia � andata a buon fine
									//$assigned = 1;
									$output .=  "L'INSERIMENTO � ANDATO A BUON FINE NELLA TABELLA" . $offtgt_table . PHP_EOL;
									//echo "L'INSERIMENTO � ANDATO A BUON FINE NELLA TABELLA" . $offtgt_table ."<br>";
									//scrivo assegnata
									$sql_successTbA = "UPDATE sms_replies SET assigned=1    WHERE id=".$IdRisposta  ;      
									$output .=  "query update sms replies ".$sql_successTbA. PHP_EOL;
									//echo "query update sms replies ".$sql_successTbA;
									$em5 = $this->getDoctrine()->getManager();
									$stmt5 = $em5->getConnection()->prepare($sql_successTbA);
									$stmt5->execute();
									
									//dal momento che la copia nel leadout del cliente � andata a buon fine posso procedere e inserire 	su contatore e su extraction
									if($Tabella_origine =="lead_uni"){
										$output .=  "PROVO AD INSERIRE SU CONTATORE DI UTILITY" . PHP_EOL;	
										//echo "PROVO AD INSERIRE SU CONTATORE DI UTILITY<br>";
										$sql_insert_conta = "INSERT INTO contatore (source_db,source_tbl,source_id,cliente_id,campagna_id,landing_id,offtarget,data_lead) VALUES 
													(
													'".$source_db."',
													'".$source_tbl."',
													'".$source_id."',
													'".$cliente_id."',
													'".$campaign_id."',
													'".$landing_id."',
													'0',
													NOW()
													);";
										$output .=  "QUERY DI INSERIMENTO SU CONTATORE" . $sql_insert_conta . PHP_EOL;
										//echo "query di inserimento in contatore: ". $sql_insert_conta."<br>";
										$risultato_conta = mysqli_query($conndbuti, $sql_insert_conta);
									   if(!$risultato_conta){
											$output .=  "MYSQL ERRORE INSERT LEAD IN CONTATORE TABLE: ". mysqli_error($conndbuti).PHP_EOL;
											//echo "MYSQL ERRORE INSERT LEAD IN CONTATORE TABLE: ". mysqli_error($conndbuti)."<br>";
										} else {
											$output .=  "INSERIMENTO NEL CONTATORE RIUSCITO" . PHP_EOL;
											//echo  "INSERIMENTO NEL CONTATORE RIUSCITO<br>";
										}
									}else{
										$output .=  "SKIPPO L'INSERIMENTO IN CONTATORE PERCH� LA LEAD NON APPARTIENE A LEAD_UNI" . PHP_EOL;
										//echo "SKIPPO L'INSERIMENTO IN CONTATORE PERCH� LA LEAD NON APPARTIENE A LEAD_UNI <br>";
									}
									
									$output .=  "PROVO AD INSERIRE SU EXTRACTION PER BLOCCARE L'ANAGRAFICA PER UN TOT DI TEMPO DA ALTRI USI" .PHP_EOL;
									//echo "PROVO AD INSERIRE SU EXTRACTION PER BLOCCARE L'ANAGRAFICA PER UN TOT DI TEMPO DA ALTRI USI <br>";
									
									
									//inserisco su extraction come noleggio a 3 mesi
									if($campaign_id){
										if($Tabella_origine =="lead_uni"){
											$output .=  "SALVO LA LEAD interna CON ID: " . $lead_id ." COME NOLEGGIATA." . PHP_EOL;
											$sql_insert_extraction = "INSERT INTO extraction (nome_db,nome_tabella,lead_id,cliente_id,tipo_vendita,data_inserimento,data_sblocco) VALUES 
										('". $source_db ."','".$source_tbl."','".$source_id."','".$cliente_id."','Noleggio',NOW(),NOW() + INTERVAL 3 MONTH);";			
											 $output .="query di inserimento in extraction lead interne : ".$sql_insert_extraction.PHP_EOL;
											//echo "query di inserimento in extraction lead interne : ".$sql_insert_extraction."<br><br>";;
											$risultato_extraction = mysqli_query($conndbuti, $sql_insert_extraction);
										}else{
											////echo "salvo su extraction esterne";
											$output .=  "SALVO LA LEAD esterna CON ID: " . $lead_id ." COME NOLEGGIATA NEL CONTATORE DELLE LEAD ESTERNE." . PHP_EOL;
											$sql_insert_extraction = "INSERT INTO extraction_lead_esterne (lead_id,cliente_id,tipo_vendita,data_inserimento,data_sblocco) VALUES 
										('".$source_id."','".$cliente_id."','Noleggio',NOW(),NOW() + INTERVAL 3 MONTH);";			
											 $output .="query di inserimento in extraction lead esterne : ".$sql_insert_extraction.PHP_EOL;
											//echo "query di inserimento in extraction lead esterne : ".$sql_insert_extraction."<br><br>";
											$em6 = $this->getDoctrine()->getManager();
											$stmt6 = $em6->getConnection()->prepare($sql_insert_extraction);
											$stmt6->execute();
											
										}
										
										/*if(!$risultato_extraction){
											$output .=  "MYSQL ERRORE INSERT LEAD IN EXTRACTION TABLE: ". PHP_EOL . mysqli_error($conndbuti) . PHP_EOL;
										} else {*/
											$output .=  "INSERIMENTO SU EXTRACTION RIUSCITO" . PHP_EOL;
											//echo  "INSERIMENTO SU EXTRACTION RIUSCITO<br>";
										/*}*/
									
									}  						
					
								}
							
						  
					} 
				} else { 
					$output .=  "NON E' STATA TROVATA UNA CAMPAGNA CORRISPONDENTE AL CLIENTE E ALLE CONDIZIONI RICHIESTE SALTO A PROSSIMO NUMERO" . PHP_EOL;
					//echo "NON E' STATA TROVATA UNA CAMPAGNA CORRISPONDENTE AL CLIENTE E ALLE CONDIZIONI RICHIESTE SALTO A PROSSIMO NUMERO<br>";
				}
			   
				
			}
		}else{
			// //echo NESSUN'ANAGRAFICA <br>";
			$output .=  "----!! Non abbiamo trovato nessun TO BE ASSIGNED".PHP_EOL;
			//echo "---!! Non abbiamo trovato nessun TO BE ASSIGNED <br>";
		}


		$output .=  "ANALIZZIAMO LE BLACKLISTATE".PHP_EOL;
		// TO BE BLACKLISTED
			//RECUPERA DATO COMPLETO LISTA DI ORIGINE
			$sql_getToBeBlacklisted = "SELECT * FROM sms_replies WHERE tobeblacklisted = 1 and blacklisted=0 and campaign_id is not NULL and campaign_id!='' and errorcheck=0  and id in (".$All.")";
			//echo "Query search for tobeblacklisted".  $sql_getToBeBlacklisted."<br><br>";
			$em6 = $this->getDoctrine()->getManager();
			$stmt6 = $em6->getConnection()->prepare($sql_getToBeBlacklisted);
			$stmt6->execute();
			$result_getToBeBlacklisted =$stmt6->fetchAll();

			$output .=  "QUERY di recupero to be blacklisted " .$sql_getToBeBlacklisted . PHP_EOL;
			if (count($result_getToBeBlacklisted) > 0) {
				foreach ($result_getToBeBlacklisted as $rowToBeBl) {
					$IdRisposta= $rowToBeBl['id'];
					//INSERISCI NUMERO IN BLACKLIST NUMERI se leaduni se no clienti
					$mittente=$cellulare=$rowToBeBl['cellulare'];
					$cliente_id = $rowToBeBl['client_id'];
					$campaign_id = $rowToBeBl['campaign_id'];
					$extraction_id=$rowToBeBl['extraction_id'];
				
				//recupero tabella esterna da estraction
				$sql_getTabOrigin = "SELECT Tabella_origine, source_id FROM extraction_sms WHERE id=".$extraction_id;
				$em7 = $this->getDoctrine()->getManager();
				$stmt7 = $em7->getConnection()->prepare($sql_getTabOrigin);
				$stmt7->execute();
				$result_getTabOrigin =$stmt7->fetchAll();
				$Tabella_origine='';
				if (count($result_getTabOrigin) > 0) {
					foreach ($result_getTabOrigin as $result_getTabOriginLoop) {
						$Tabella_origine = $result_getTabOriginLoop ['Tabella_origine'];
						$Lead_Source_id = $result_getTabOriginLoop ['source_id'];
						$output .=  "La tabella di origine �: " . $Tabella_origine.PHP_EOL;
						//echo "La tabella di origine �: " . $Tabella_origine."<br>";
					}
				}
				if(!isset($Tabella_origine) OR $Tabella_origine==""){$Tabella_origine="lead_uni"; $output .=  "non siamo riusciti a recuperare da extraction la tabella di orginne.. abbiamo settato lead_uni " .PHP_EOL;}
				
				//$Tabella_origine = $rowToBeAss[Tabella_origine];
////////////////////////////////////////////////////////////////////////
///
///
                $DateNow=date('Y-m-d H:i:s');
                switch (trim($Tabella_origine)) {
                    // *******************************************************************
                    case "lead_uni":
                        // inserimento in blacklist_numeri
                        $sql_insert_in_blacklist = "INSERT IGNORE INTO blacklist_numeri (cellulare,cliente,tipo_estrazione,data_creazione) VALUES 
                                                    ('".  $cellulare."', ".$cliente_id.", 'SMS AUTO', NOW());";
                        $em8 = $this->getDoctrine()->getManager();
                        $stmt8 = $em8->getConnection()->prepare($sql_insert_in_blacklist);
                        $stmt8->execute();
// invio sms lead_uni
/////////////////////////////////////// invio sms_send
                            if($this->container->getParameter('sms_send_lead_uni')){
                                $sms_send = new SendSmsDirect($cellulare);
                                $sms_send->Send();
                            }
// invio sms
                        // inserimento in blacklist_extraction
                        // recupero info per id lead_uni
                        //lead_id***, source_db***, source_tbl, source_id
                        $sql_recupero_info_lead_uni_id ="SELECT source_db,source_tbl, source_id from lead_uni where id = ".$Lead_Source_id;
                        $em28 = $this->getDoctrine()->getManager();
                        $stmt28 = $em28->getConnection()->prepare($sql_recupero_info_lead_uni_id);
                        $stmt28->execute();
                            $result_getLeadUniInfo = $stmt28->fetchAll();
                            if (count($result_getLeadUniInfo) > 0) {
                                $Query="INSERT IGNORE INTO blacklist_extraction (lead_id, source_db, source_tbl, source_id, data_creazione)
                                        VALUES (".$Lead_Source_id.",'".$result_getLeadUniInfo[0]['source_db']."','".$result_getLeadUniInfo[0]['source_tbl']."','".$result_getLeadUniInfo[0]['source_id']."','".$DateNow."')";
                                $em38 = $this->getDoctrine()->getManager();
                                $stmt38 = $em38->getConnection()->prepare($Query);
                                $stmt38->execute();
                      }
 // fine case lead_uni
                            break;
                            // *******************************************************************
                    case "lead_uni_esterne":
                        // inserimento in blacklist_clienti		numeri
                        $sql_insert_in_blacklist ="INSERT IGNORE INTO blacklist_numeri (cellulare,cliente,tipo_estrazione,data_creazione) VALUES 
	                                            ('".$cellulare."', ".$cliente_id.", 'SMS AUTO', NOW());";
                        $em48 = $this->getDoctrine()->getManager();
                        $stmt48 = $em48->getConnection()->prepare($sql_insert_in_blacklist);
                        $stmt48->execute();
/////////////////////////////////////// invio sms_send

                                if($this->container->getParameter('sms_send_lead_uni_esterne')){
                                   $sms_send = new SendSmsDirect($cellulare);
                                   $sms_send->Send();
                                }
// invio sms
                        // inserimento in blacklist_esterne
                        $sql_insert_into_blacklist_esterne ="INSERT IGNORE INTO blacklist_esterne (lead_id, data_inserimento) VALUES (".$Lead_Source_id.",'".$DateNow."')";
                        $em58 = $this->getDoctrine()->getManager();
                        $stmt58 = $em58->getConnection()->prepare($sql_insert_into_blacklist_esterne);
                        $stmt58->execute();
                      break;
                     // *******************************************************************
                    // fine case lead_uni_esterne
                    default:
                }

// dpo_history
                    $DateNow=date('Y-m-d h:i:s');
                    $Par="type= ".$Tabella_origine."; id= ".$Lead_Source_id."; cell= ".$cellulare." ; FROM= SMSM_RECHECK";
                    $action = 'blacklist';
                    $user=$this->getUser()->getUsername();
                    if(!isset($user) OR $user==''){
                        $user='non ritrovato';
                    }
                    $Query="INSERT INTO blacklist_history_dpo (user, action, option, data_action) VALUES ('".$user."', '".$action."','".$Par."', '".$DateNow."')";
                    $em68 = $this->getDoctrine()->getManager();
                    $stmt68 = $em68->getConnection()->prepare($Query);
                    $stmt68->execute();

////////////////////////////////////////////////////////////////////////
///
///
					/*DA STUDIARE IF QUERY OK*/
					//$risultato_blacklist =$stmt8->fetchAll();
					$sql_successTbA = "UPDATE sms_replies SET blacklisted=1 WHERE id=".$IdRisposta ;      
					//echo "query update sms_replies: ". $sql_successTbA."<br><br>";
					$em99 = $this->getDoctrine()->getManager();
					$stmt99 = $em99->getConnection()->prepare($sql_successTbA);
					$stmt99->execute();

				}
			}else{
				// //echo NESSUN'ANAGRAFICA <br>";
				$output .=  "----!! Non abbiamo trovato nessun TO BE Blacklisted".PHP_EOL;
				//echo "---!! Non abbiamo trovato nessun TO BE blacklisted <br>";
			}
		
	}

	
}