<?php 

function updateContatoreTbl($mysqli_utils, $mysqli){
         
    global $logfile;
    global $db_config;
    global $mailErrors;
    
	$db_gestione = "la_gestione"; //"symfony";
    $counters_lead =  0;
    
	$lastdata = date('Y-m-d 00:00:00');
    $arrLeaduniConv = array();
    $destExtrData = array();
          
    $mysqli_utils->set_charset("utf8");

	//PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN contatore SU GESTIONE.contatore, partendo da questa data, effettuo una query su utility.contatore
	$strSql = "SELECT data_lead FROM ".$db_gestione.".contatore ORDER BY data_lead DESC limit 0,1"; // PRELEVO LA LEAD ESTRATTA PIù RECENTE
	$str = "PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN contatore SU GESTIONE.contatore:" . PHP_EOL . $strSql .PHP_EOL;
	fwrite($logfile, $str . PHP_EOL);
	echo $str;
	
	if (!$result = $mysqli->query($strSql)) {     
     
        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        
        echo "Errore select iniziale da utility.contatore: " . $strSql . PHP_EOL . $mysqli->error; 
        $mailErrors[] = "Errore select estrazione ultima data da utility.contatore: " . $strSql . PHP_EOL . $mysqli->error;
        exit;
    }else{
		while($ret = $result->fetch_assoc()){
            $lastdata= $ret['data_lead'];
			$str = "DATA ULTIMA LEAD INSERITA: " . $lastdata . PHP_EOL;
			fwrite($logfile,$str);
			echo $str;
        }  
	}

	//Prendo tutte le lead effettuo query su utility contatore per prelevare le ultime arrivate con data_inserimento > dell'ultima lead ricevuta in gestione.contatore
	$sqlExtUti = "SELECT * FROM utilslinkappeal_cpl.contatore WHERE data_lead > '".$lastdata."' ORDER BY data_lead ASC";
	
	echo "##########". PHP_EOL . "Prendo tutte le lead su utility.contatore con data > ".$lastdata . PHP_EOL . "Effettuo QUERY: " . $sqlExtUti . PHP_EOL . "##########". PHP_EOL; 
	fwrite($logfile, $str . PHP_EOL);
	echo $str;
		
    if (!$result = $mysqli_utils->query($sqlExtUti)) {     
     
        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sqlExtUti . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_utils->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli_utils->error . "\n");
        
        echo "Errore select iniziale da utility.contatore: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL; 
        $mailErrors[] = "Errore select iniziale da utility.contatore: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL;
        exit;
    }else{
		if($result->num_rows>0){
			$indice=0;
			$str = "########## - La Query ha prodotto " . $result->num_rows . " risultati." . PHP_EOL; 
			fwrite($logfile, $str . PHP_EOL);
			echo $str;
			while($ret = $result->fetch_assoc()){ //	CICLO INIZIALE DI PRELIEVO LEAD
				$indice++;
				echo PHP_EOL . "-------- Lavorando " . $indice . " di " . $result->num_rows . PHP_EOL; 
				$save_history = false; // variabile inizializzata ad ogni lead, mi darà l'informazione se salvare la storia di estrazione o meno.
				$src_db 			= $ret['source_db'];
				$src_tab			= $ret['source_tbl'];
				$src_leadId 		= $ret['source_id'];
				$src_clienteid 		= $ret['cliente_id']; // lo riporto durante la insert o l'update
				$src_campagna_id 	= $ret['campagna_id'];
				$src_landing_id 	= $ret['landing_id'];
				$src_offtarget 		= $ret['offtarget'];
				$src_dataLead 		= $ret['data_lead'];
				
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
					
					echo "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL; 
					$mailErrors[] = "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL;
					exit;
				}else{ // la query di ricerca id lead da leaduni è andata a buon fine
					if($resultGetIdFromLeaduni->num_rows>0){ // se è stato trovato un id associato
						 while($retLeadUni = $resultGetIdFromLeaduni->fetch_assoc()){ // ciclo su leaduni
							$LeadUniID = $retLeadUni['id'];
							// trovato id da lead_uni, verifico se inserire o aggiornare la tabella ".$db_gestione.".contatore
							$sqlSearchLeadInContatore = "SELECT * FROM ".$db_gestione.".contatore WHERE contatore.lead_id=".$LeadUniID;
							if (!$resultSearchInContatore = $mysqli->query($sqlSearchLeadInContatore)) {     
								fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
								fwrite($logfile, date("YmdHis")." Query: " . $sqlSearchLeadInContatore . "\n");
								fwrite($logfile, date("YmdHis")." Errno: " . $mysqli->errno . "\n");
								fwrite($logfile, date("YmdHis")." Error: " . $mysqli->error . "\n");
								
								echo "Errore esecuzione query: " . $sqlSearchLeadInContatore . PHP_EOL . $mysqli->error . PHP_EOL; 
								$mailErrors[] = "Errore esecuzione query: " . $sqlSearchLeadInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
								exit;
							}else{ // se non ci sono stati errori
								
								if($resultSearchInContatore->num_rows<=0){ // l'id della leadnon è presente in ".$db_gestione.".contatore, update
									// INSERISCO LA NUOVA LEAD COME contata
									$sqlInsertInContatore =  "INSERT INTO ".$db_gestione.".contatore (lead_id,cliente_id,campagna_id,landing_id,offtarget,data_lead)";
									$sqlInsertInContatore .= "VALUES ('".$LeadUniID."',
																		'".$src_clienteid."',
																		'".$src_campagna_id."',
																		'".$src_landing_id."',
																		'".$src_offtarget."',
																		'".$src_dataLead."');";
									$str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA IN contatore CON LA QUERY: " . PHP_EOL . $sqlInsertInContatore . PHP_EOL;
									fwrite($logfile,$str);
									echo $str;
									
									if ($mysqli->query($sqlInsertInContatore) !== TRUE) { // se ci sono stati errori nell'insert
										
										fwrite($logfile, date("YmdHis")."Error: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL);
										echo "Errore inserimento lead in ".$db_gestione.".contatore: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL; 
										$mailErrors[] = "Errore inserimento lead in ".$db_gestione.".contatore: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
									}else{
							
										$counters_lead++;
										$str = "LA LEAD ".$LeadUniID." E' STATA INSERITA CORRETTAMENTE" . PHP_EOL;
										fwrite($logfile,$str);
										echo $str;
									}
									
								} // fine else lead non presente in Contatore 
							} // fine else se non ci sono stati errori
						 } // fine while ciclo su lead_uni
					}else{ // non è stato trovato un id associato alla lead_uni forse non è stata ancora importata la lead. salto.
						$str = "Non è stato trovato in lead_uni la lead con coordinate: "
							." | DB -> ". $src_db 
							." | tabella -> ".	$src_tab
							." | id lead -> ".	$src_leadId . PHP_EOL . "Tempo di esecuzione ricerca: ". date('d-m-Y H:i:s') . PHP_EOL;
						fwrite($logfile,$str);
						echo $str;
						continue; // continuo il cliclo while sugli id della tabella utility.Contatore
					}
					
					
				} // fine if controllo se query di ricerca id lead da leaduni non ha restituito errori
			} // FINE WHILE PRIMARIO su tutte le lead di utility Contatore
		}else{ // se la query in base alla data > lastdata non ha prodotto risultati
			// non c'è nulla da importare
			$str = "La Query non ha prodotto risultati." . PHP_EOL . " ######### - NIENTE DA IMPORTARE- ######### " . PHP_EOL; 
			fwrite($logfile, $str . PHP_EOL);
			echo $str;
		}
	} // fine if se ci non ci sono errori su query risultati in base alla dara  su utility.Contatore
    return $counters_lead;
 }


$config_file = __DIR__ . "/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);
//$db_name = 'symfony';
$num_insert = 0;
$campaign_insert = 0;

$mailErrors = array();

$tot_added_leads = 0;
//var_dump($db_config);

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

/*************** CONNESSIONE A DB UTILITY *****************/
// Qui impostiamo i parametri di connessione al DB

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



$logfile = fopen( __DIR__ ."/log/".date("Ymd")."_log_update_contatore.txt", "a"); //FILE di LOG
fwrite($logfile, date("YmdHis")."   /************************************************/");
fwrite($logfile, date("YmdHis")."   Avvio Script per l'aggiornamento della tabella CONTATORE\n\n");
echo "\n\n/**** Avvio Script per l'aggiornamento della tabella Contatore ****/\n\n";
$resCont = updateContatoreTbl($mysqli_utils, $mysqli_dest);

$str =	PHP_EOL . "Aggiunti: " . $resCont . PHP_EOL;

$str .= PHP_EOL . PHP_EOL . "**** TERMINE Script per l'aggiornamento della tabella Contatore ****/\n\n";
fwrite($logfile,$str);
echo $str;


