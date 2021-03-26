<?php 

/**
 * LA SEGUENTE FUNZIONE LEGGE LE LEAD ESTRATTE DA UTILSLINKAPPEAL_CPL E LE INSERISCE (SE NON PRESENTI) O AGGIORNA (SE GIA' PRESENTI) IN LA_GESTIONE.EXTRACTION 
 * RICAVANDO L'ID DELLA LEAD DALLA TABELLA LA_GESTIONE.LEAD_UNI. LA FUNZIONE DEVE PREVEDERE CASO DI LEAD NON ESISTENTE IN LEAD_UNI E SALTARE QUELLA LEAD (VERRA' POI AGGIUNTA NEL PROSSIMO GIRO) 
 * 
**/

	
function updateExtractionTbl($mysqli_utils, $mysqli){
         
    global $logfile;
    global $db_config;
    global $mailErrors;
    
	$db_gestione = "la_gestione"; //"symfony";
    $counters_lead = array('tot_ext_upd_leads' => 0, 'tot_ext_add_leads' => 0);
     
    $arrLeaduniConv = array();
    $destExtrData = array();
          
    $mysqli_utils->set_charset("utf8");
   
   
   
   
   /* 
	//Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id
    $strSql = "select id, source_db, source_tbl, source_id from ".$db_gestione.".lead_uni";
	
	
	$str = "Prendo tutte le lead dalla tabella gestione - lead_uni con la QUERY:" . PHP_EOL . $strSql .PHP_EOL;
	fwrite($logfile, $str . PHP_EOL);
	echo $str;
	
	 if (!$result = $mysqli->query($strSql)) {     
     
        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        
        echo "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error; 
        $mailErrors[] = "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        exit;
    }else{
		while($ret = $result->fetch_assoc()){
            $arrLeaduniConv[$ret['source_db']][$ret['source_tbl']][$ret['source_id']] = $ret['id'];
        }  
	}
	//print_r($arrLeaduniConv);
	//echo count($arrLeaduniConv);
	
	//PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN EXTRACTION SU GESTIONE.EXTRACTION
	
	$strSql = "select id, source_db, source_tbl, source_id from ".$db_gestione.".extraction ORDER BY data_estrazione ASC limit 0,1";
	$str = "PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN EXTRACTION SU GESTIONE.EXTRACTION:" . PHP_EOL . $strSql .PHP_EOL;
	fwrite($logfile, $str . PHP_EOL);
	echo $str;
	
	if (!$result = $mysqli->query($strSql)) {     
     
        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        
        echo "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error; 
        $mailErrors[] = "Errore select estrazione ultima data da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        exit;
    }else{
		while($ret = $result->fetch_assoc()){
            $arrLeaduniConv[$ret['source_db']][$ret['source_tbl']][$ret['source_id']] = $ret['id'];
        }  
	}
*/

	//Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id
	$sqlExtUti = "SELECT * FROM utilslinkappeal_cpl.extraction where cliente_id=64 order by data_inserimento ASC";
	
    if (!$result = $mysqli_utils->query($sqlExtUti)) {     
     
        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sqlExtUti . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_utils->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli_utils->error . "\n");
        
        echo "Errore select iniziale da utility.extraction: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error; 
        $mailErrors[] = "Errore select iniziale da utility.extraction: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error;
        exit;
    }else{
        while($ret = $result->fetch_assoc()){ //	CICLO INIZIALE DI PRELIEVO LEAD
			$save_history = false; // variabile inizializzata ad ogni lead, mi darà l'informazione se salvare la storia di estrazione o meno.
			$src_db 			= $ret['nome_db'];
			$src_tab			= $ret['nome_tabella'];
			$src_leadId 		= $ret['lead_id'];
			$src_clienteid 		= $ret['cliente_id']; // lo riporto durante la insert o l'update
			$src_tipoVendita 	= $ret['tipo_vendita'];
			$src_dataVendita 	= $ret['data_inserimento'];
			$src_dataSblocco 	= $ret['data_sblocco'];
			$src_dataSblocco = !empty($src_dataSblocco) ? $src_dataSblocco : "NULL"; // imposta la data di sblocco a null se vuota
			
			// per ogni record prelevo il suo id in lead_uni
			
			$sqlGetIdFromLeaduni = "SELECT id FROM ".$db_gestione.".lead_uni 
									WHERE source_db='". $src_db 	."' 
									AND	source_tbl='". 	$src_tab 	."' 
									AND source_id='". 	$src_leadId ."'";
			
			if (!$resultGetIdFromLeaduni = $mysqli->query($sqlGetIdFromLeaduni)) {     
				fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
				fwrite($logfile, date("YmdHis")." Query: " . $sqlGetIdFromLeaduni . "\n");
				fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
				fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
				
				echo "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error; 
				$mailErrors[] = "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error;
				exit;
			}else{ // la query di ricerca id lead da leaduni è andata a buon fine
				if($resultGetIdFromLeaduni->num_rows>0){ // se è stato trovato un id associato
					 while($retLeadUni = $resultGetIdFromLeaduni->fetch_assoc()){ // ciclo su leaduni
						$LeadUniID = $retLeadUni['id'];
						// trovato id da lead_uni, verifico se inserire o aggiornare la tabella ".$db_gestione.".extraction
						$sqlSearchLeadInExtraction = "SELECT * FROM ".$db_gestione.".extraction WHERE extraction.lead_id=".$LeadUniID;
						if (!$resultSearchInExtraction = $mysqli->query($sqlSearchLeadInExtraction)) {     
							fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
							fwrite($logfile, date("YmdHis")." Query: " . $sqlSearchLeadInExtraction . "\n");
							fwrite($logfile, date("YmdHis")." Errno: " . $mysqli->errno . "\n");
							fwrite($logfile, date("YmdHis")." Error: " . $mysqli->error . "\n");
							
							echo "Errore esecuzione query: " . $sqlSearchLeadInExtraction . PHP_EOL . $mysqli->error; 
							$mailErrors[] = "Errore esecuzione query: " . $sqlSearchLeadInExtraction . PHP_EOL . $mysqli->error;
							exit;
						}else{ // se non ci sono stati errori
							
							if($resultSearchInExtraction->num_rows>0){ // l'id della lead è già presente in ".$db_gestione.".extraction, update
								// lead, già presente, verifico se eseguire l'update 
								while($retGestExtr = $resultSearchInExtraction->fetch_assoc()){ // ciclo su leaduni
									$dst_clienteid		=	$retGestExtr['cliente_id'];
									$dst_dataVendita	=	$retGestExtr['data_estrazione'];
									$dst_dataSblocco	=	$retGestExtr['data_sblocco'];
									$dst_tipoVendita	=	$retGestExtr['tipo_estrazione'];
								}
								// conversione date 
								$dst_dataVendita_tmstp = strtotime($dst_dataVendita);
								$src_dataVendita_tmstp = strtotime($src_dataVendita);
								
								
								// se la è una vendita, la data di sblocco potrebbe essere null
								
								$dst_dataSblocco_tmstp = 'NULL';
								if(!empty($dst_dataSblocco)){
									$dst_dataSblocco_tmstp = strtotime($dst_dataSblocco);
								}
								
								$src_dataSblocco_tmstp = 'NULL';
								if(!empty($src_dataSblocco)){
									$src_dataSblocco_tmstp = strtotime($src_dataSblocco);
								}
								$dst_tipoVendita = trim(strtolower($dst_tipoVendita));
								$src_tipoVendita = trim(strtolower($src_tipoVendita));
								
								echo "Verifica parametri: " . PHP_EOL;
								
								//echo "Destinazione cliente id:".	$dst_clienteid 			." -> Source cliente id: " . 	$src_clienteid  		. PHP_EOL;
								//echo "Destinazione dataVendita:".	$dst_dataVendita_tmstp 	." -> Source dataVendita: " . 	$src_dataVendita_tmstp  . PHP_EOL;
								//echo "Destinazione dataSblocco:".	$dst_dataSblocco_tmstp 	." -> Source dataSblocco: " . 	$src_dataSblocco_tmstp  . PHP_EOL;
								//echo "Destinazione tipoVendita:".	$dst_tipoVendita 		." -> Source tipoVendita: " . 	$src_tipoVendita  		. PHP_EOL;
								
								
								if(	$dst_clienteid	!=$src_clienteid 	||
									$dst_dataVendita_tmstp!=$src_dataVendita_tmstp	||
								    $dst_dataSblocco_tmstp!=$src_dataSblocco_tmstp	||
									$dst_tipoVendita!=$src_tipoVendita){
									
									// se almeno uno dei valori di ".$db_gestione.".extraction non è uguale a quello della tabella utility.extraction, effettuo l'update
									// AGGIORNO LA LEAD ESISTENTE CON I NUOVI VALORI
									$sqlUpdateInExtraction="UPDATE ".$db_gestione.".extraction SET 
															cliente_id=".		$src_clienteid	.",
															data_estrazione='".	$src_dataVendita."',
															data_sblocco='".	$src_dataSblocco."',
															tipo_estrazione='".	$src_tipoVendita."'
															WHERE extraction.lead_id=".$LeadUniID;
									$str = "AGGIORNO LA LEAD GIA' PRESENTE IN EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlUpdateInExtraction . PHP_EOL;
									fwrite($logfile,$str);
									echo $str;
									
									if ($mysqli->query($sqlUpdateInExtraction) !== TRUE) { // se ci sono stati errori nell'insert
										$save_history=false;
										fwrite($logfile, date("YmdHis")."Error: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error);
										echo "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error; 
										$mailErrors[] = "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error;
									}else{
										$save_history = true;
										$counters_lead['tot_ext_upd_leads']++;
										$str = "LA LEAD ".$LeadUniID." E' STATA AGGIORNATA CORRETTAMENTE" . PHP_EOL;
										fwrite($logfile,$str);
										echo $str;
									}
								}else{ // fine if controllo valori uguali nel db extraction
									$save_history = false; // non c'è bisogno di aggiornare la history, lè stato già fatto in passato.
									$str = "Non è necessario aggiornare la lead id -> ".$LeadUniID." già esistente: tutti i valori coincidono " . PHP_EOL;
									fwrite($logfile,$str);
									echo $str;
								}
							}else{ // la lead non è presente in ".$db_gestione.".extraction, insert
								// INSERISCO LA NUOVA LEAD COME ESTRATTA
								$sqlInsertInExtraction="INSERT INTO ".$db_gestione.".extraction (lead_id,cliente_id,data_estrazione,data_sblocco,tipo_estrazione)
														VALUES('".$LeadUniID."','".$src_clienteid."','".$src_dataVendita."','".$src_dataSblocco."','".$src_tipoVendita."');";
								$str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA IN EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlInsertInExtraction . PHP_EOL;
								fwrite($logfile,$str);
								echo $str;
								
								if ($mysqli->query($sqlInsertInExtraction) !== TRUE) { // se ci sono stati errori nell'insert
									$save_history=false;
									fwrite($logfile, date("YmdHis")."Error: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error);
									echo "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error; 
									$mailErrors[] = "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error;
								}else{
									$save_history=true;
									$counters_lead['tot_ext_add_leads']++;
									$str = "LA LEAD ".$LeadUniID." E' STATA INSERITA CORRETTAMENTE" . PHP_EOL;
									fwrite($logfile,$str);
									echo $str;
								}
								
							} // fine else lead non presente in extraction 
						} // fine else se non ci sono stati errori
						
						
						// AGGIORNO LA TABELLA HISTORY SE NECESSARIO
						if($save_history){
							$sqlHistory  = "INSERT INTO ".$db_config['parameters']['database_name'].".extraction_history " ;
							$sqlHistory .= " (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) " ;
							$sqlHistory .= "VALUES ('".$LeadUniID."', '".$src_clienteid."', '".$src_dataVendita."', '".$src_dataSblocco."', '".$src_tipoVendita."') " ;
							
							$str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA NELLA STORIA DELLE EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlHistory . PHP_EOL;
							fwrite($logfile,$str);
							echo $str;
							
							if ($mysqli->query($sqlHistory) !== TRUE) {
								fwrite($logfile, date("YmdHis")."Error: " . $sqlHistory . PHP_EOL . $mysqli->error);
								echo "Error: " . $sqlHistory . PHP_EOL . $mysqli->error; 
								$mailErrors[] = "Error: " . $sqlHistory . PHP_EOL . $mysqli->error;
							}
						}
						// FINE AGGIORNAMENTO TABELLA HISTORY
					 } // fine while ciclo su lead_uni
				}else{ // non è stato trovato un id associato alla lead_uni forse non è stata ancora importata la lead. salto.
					$str = "Non è stato trovato in lead_uni la lead con coordinate: "
						." | DB -> ". $src_db 
						." | tabella -> ".	$src_tab
						." | id lead -> ".	$src_leadId . PHP_EOL . "Tempo di esecuzione ricerca: ". date('d-m-Y H:i:s') . PHP_EOL;
					fwrite($logfile,$str);
					echo $str;
					$save_history=false;
					continue; // continuo il cliclo while sugli id della tabella utility.extraction
				}
				
				
			} // fine if controllo se query di ricerca id lead da leaduni non ha restituito errori
        } // FINE WHILE PRIMARIO su tutte le lead di utility extraction
    } // fine if se ci sono risultati su utility.extraction
	

	
	
     //Per ogni lead letta da src_extraction:
     // 1) ricavo l'id della lead_uni, tramite l'array prima creato
     // 2) controllo se è presente nella tabella di destinazione, tramite l'array prima creato
     // 3a) se no inseriamo il record in dest_extraction e nell'history
     // 3b) se si ed è di vendita non facciamo nulla
     // 3c) se si ed è di noleggio e la data di inserimento è successiva a quella presente, aggiorniamo il record ed aggiungiamo all'history
    return $counters_lead;
 }
 
 
$config_file = __DIR__ . "/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);
 
$mysqli_dest = new mysqli($db_config['parameters']['database_host'],
                          $db_config['parameters']['database_user'],
                          $db_config['parameters']['database_password'],
                          $db_config['parameters']['database_name']);

if ($mysqli_dest->connect_errno) {

    $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno: " . $mysqli_dest->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error: " . $mysqli_dest->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";          
    fwrite($logfile, $errorStr);
    mailError($errorStr);
    exit;
}

$mysqli_utils = new mysqli( $db_config['parameters']['utils_database_host'],
                            $db_config['parameters']['utils_database_user'],
                            $db_config['parameters']['utils_database_password'],
                            $db_config['parameters']['utils_database_name']);

if ($mysqli_utils->connect_errno) {

    $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno: " . $mysqli_utils->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error: " . $mysqli_utils->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";
            
    fwrite($logfile, $errorStr);
    
    mailError($errorStr);
            
    exit;
}

$logfile = fopen(__DIR__ ."/log/".date("Ymd")."_log_update_extraction.txt", "a"); //FILE di LOG

fwrite($logfile, date("YmdHis")."   /************************************************/");
fwrite($logfile, date("YmdHis")."   Avvio Script per l'aggiornamento della tabella EXTRACTION\n\n");
echo "\n\n/**** Avvio Script per l'aggiornamento della tabella EXTRACTION ****/\n\n";
$res = updateExtractionTbl($mysqli_utils, $mysqli_dest);

$str =	PHP_EOL . "Aggiunti: " . $res['tot_ext_add_leads'] . PHP_EOL;
$str .=	"Aggiornati: " . $res['tot_ext_upd_leads'] . PHP_EOL; 
$str .= PHP_EOL . PHP_EOL . "**** TERMINE Script per l'aggiornamento della tabella EXTRACTION ****/\n\n";
fwrite($logfile,$str);
echo $str;


