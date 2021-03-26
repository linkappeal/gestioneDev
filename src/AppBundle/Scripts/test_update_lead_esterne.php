<?php

/*
//file che gestisce ed esegue il flusso di importazione da lead.premi365 (o da concorsi in generale) a lead_uni.la_gestione chiamato da un cron settato sul server
MIGLIORI E TEST LIMITI
caso particolare: TESTARE SE SU LEAD CON SESSO EMPTY E TITOLO NON EMPTY VIENE CAPITO IL SESSO E TRASCRITTO DA testare
casi futuri in caso di piu concorsi: SETTARE I LIMIT eventualemte per lead totali, o tempo di esecuzione del file... fix futuro per evitare in futuro se ci dovessero essere piu concorsi  e il limite moltiplicato il numero di concorsi sia troppo alto.....inoltre potremmo randomizzare l'array dei concorsi per evitare che inizialmente importi sempre dallo stesso
*/




	
$lock_file = __DIR__ . '/_update_leaduni_from_concorsiest.lock';
if (file_exists($lock_file )) {
		die('Spiacente, un\'altra istanza dello Script è in esecuzione...');
		$errorStr  = date("Y-m-d,H-i-s")." Error: Altra istanza in esecuzione: \n";		
		$errorStr .= date("Y-m-d,H-i-s")." Exiting";
		mailError($errorStr);
		exit();
		}
$myfile = fopen($lock_file , "w") or die("Unable to create lock file!");
error_reporting(E_ERROR);


//settaggio del file di log
//Directory corrente
$curDir = dirname(__FILE__) ;
$arrPath = explode("/", $curDir) ;

//Directory manager
$pathScript = "/" ;
for ($i=1; $i<sizeof($arrPath)-3; $i++) {
	$pathScript .= $arrPath[$i] . "/" ;
}

if (strlen($pathScript) > 1) {
	$pathScript = substr($pathScript,0,-1) ;
}

$logfile = fopen($curDir."/log/".date("Ymd")."_log_update_leaduni_from_concorsiEst.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n Avvio Script per l'aggiornamento della tabella Lead unificate da concorsi\n\n");
echo "\n\n /**** Avvio Script per l'aggiornamento della tabella Lead unificate  da concorsi ****/\n\n";


/*************** FIELD BLACKLIST *****************/

$field_blacklist = array('zanpid',
                         'privacycliente',
                         'privacyversioncliente');
					 
					 
					 

/*************** 3)  variabile che elenca tutti i concorsi per db *************/
/*$concorsi = array( 
					'premi365' => array('host'=>'46.254.38.170', 'db'=>'premi365_db23','username'=>'premi365_utente2', 'password'=>'x?h{JoE3AC6W', 'idcampagna' => 684, 'tbl'=>'lead'),
					'premi365esterne' => array('host'=>'46.254.38.170', 'db'=>'premi365_db23','username'=>'premi365_utente2', 'password'=>'x?h{JoE3AC6W', 'idcampagna' => 684, 'tbl'=>'lead_esterne'),	
);*/
$concorsi = array( 
					'premi365esterne' => array('host'=>'46.254.38.170', 'db'=>'premi365_db23','username'=>'premi365_utente2', 'password'=>'x?h{JoE3AC6W', 'idcampagna' => 684, 'tbl'=>'lead_esterne'),	
);


/*************** 3) CONNESSIONE A DB DESTINAZIONE *****************/
// Qui impostiamo i parametri di connessione al DB
$config_file = $curDir."/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);


$mailErrors = array();

$tot_added_leads = 0;
//var_dump($db_config);


$mysqli_dest = new mysqli($db_config['parameters']['database_host'],
                          $db_config['parameters']['database_user'],
                          $db_config['parameters']['database_password'],
                          $db_config['parameters']['database_name']);


	
	
if ($mysqli_dest->connect_errno) {

    $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("Y-m-d,H-i-s")." Errno: " . $mysqli_dest->connect_errno . "\n";
    $errorStr .= date("Y-m-d,H-i-s")." Error: " . $mysqli_dest->connect_error . "\n";
    $errorStr .= date("Y-m-d,H-i-s")." Exiting";
            
    fwrite($logfile, $errorStr);
    
    mailError($errorStr);
    esci();
}
$start_time = microtime(true);

//creo un array che mappa i campi di lead(concorso) con le key dei campi di lead uni...campolead=campoleaduni O campolead=extrafield copia in extrafield extrafiled, alcuni casi particolari verranno invece getiti "hardcoded", skippa farà saltare la value, se non esiste vuol dire che il nome del campo da mappare è uguale
$Mapper=array('id'=>'source_id', 'titolo'=> 'skippa', 'operatore_cell' => 'operatore', 'piva'=>'skippa', 'data_nascita'=>'extrafield', 'luogo_nascita'=>'extrafield', 'residente_citta'=>'citta', 'residente_prov'=>'provincia',  'residente_regione'=>'regione', 'residente_cap'=>'cap', 'residente_latitudine'=>'latitudine', 'residente_longitudine'=>'longitudine', 'privacy_partner'=>'skippa',  'codice_premio'=>'extrafield', 'ip'=>'indirizzo_ip','media'=>'editore', 'residente_via'=>'skippa', 'residente_civ'=>'skippa','campi_esterni'=>'skippa', 'tipo_inserimento'=>'skippa', 'fornitore'=>'skippa', 'ordini_accettati'=>'skippa');
			

//recupero la tabella del campi del gestionale e me la tengo in memoria per evitare di lanciare query per ogni extrafield da controllare
$GestionaleExtrafieldLabels= "select id, name from lead_uni_extra_fields";
//array che conterra tutti gli extrafield label del concorso
$GestionaleExtrafieldLabelsArray=array();

fwrite($logfile, "\n Eseguita query di recupero degli attuali extrafield del gestionale;");
//logga ********


if (!$result = $mysqli_dest->query($GestionaleExtrafieldLabels)) {
	echo "errore extrafield";
	$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
	$errorStr .= date("Y-m-d,H-i-s")." Query: " . $strSql . "\n";
	$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
	$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
	fwrite($logfile, $errorStr);
	mailError($errorStr);
	esci();
}else {
	while ($ret = $result->fetch_assoc()){
		$GestionaleExtrafieldLabelsArray[$ret['id']]=addslashes($ret['name']);
	}
}
// variabile che imposta il limite di recupero per ogni concorso delle lead, ci serve come stringa
$LimitImportLeads='1000';			
/*************** CICLO PER OGNI CONCORSO DATO *****************/
// Qui impostiamo i parametri di connessione al DB

foreach($concorsi as $concorsoname => $dbconcorso){
	
	//prendo date now per questo ciclo evitando di ricalcolarla per frazioni troppo basse di temppo
	$date_now=date_now();
	
	//echooes
	fwrite($logfile, "\n\n Inizio loop su concorsi....concorso in esame:".$concorsoname."\n\n");
	echo "\n\n/**** inizio loop su concorsi....concorso in esame:".$concorsoname."\n\n";
	//echo 'key';print_r($concorsoname);echo '/n val';print_r($dbconcorso);
	
	//mi connetto al database del concorso
	$mysqli_concorso = new mysqli( $dbconcorso['host'],
                          $dbconcorso['username'],
                          $dbconcorso['password'],
						  $dbconcorso['db']);
	
	if ($mysqli_concorso->connect_errno) {

		$errorStr  = date("Y-m-d,H-i-s")." Error2: Failed to make a MySQL connection to ".$concorsoname.", here is why: \n";
		$errorStr .= date("Y-m-d,H-i-s")." Errno2: " . $mysqli_concorso->connect_errno . "\n";
		$errorStr .= date("Y-m-d,H-i-s")." Error2: " . $mysqli_concorso->connect_error . "\n";
		$errorStr .= date("Y-m-d,H-i-s")." Exiting";
				
		fwrite($logfile, $errorStr);
		mailError($errorStr);
		esci();
	}
	
	
	//recupero la tabella del concorso campi e me la tengo in memoria per evitare di lanciare query per ogni extrafield
	$ConcorsoExtrafieldLabels= "select id, label from campi";
	//array che conterra tutti gli extrafield label del concorso
	$ConcorsoExtrafieldLabelsArray=array();
	
	if (!$result = $mysqli_concorso->query($ConcorsoExtrafieldLabels)) {
		echo "errore extrafield";
		$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, alle extrafield del concorsohere is why: \n";
		$errorStr .= date("Y-m-d,H-i-s")." Query: " .$ConcorsoExtrafieldLabels . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_concorso->errno . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_concorso->error . "\n";
		fwrite($logfile, $errorStr);
		mailError($errorStr);
		esci();
	}
	else {
		fwrite($logfile, "\n Eseguita query di recupero dei campi di extrafield del concorso;");
		while ($ret = $result->fetch_assoc()){
			$ConcorsoExtrafieldLabelsArray[$ret['id']]=addslashes($ret['label']);
		}
	}
				
	
	//max id è dato dalla lettura di lead_uni con .source_db =$dbconcorso['db']
	//estraggo max id dal database select tutte le righe con id inferiore a max id
	$maxIdQuery  =NULL;
	$maxIdQuery  = "select max(source_id) as maxid from lead_uni_test where source_db = '".$dbconcorso["db"]."' and source_tbl = '".$dbconcorso["tbl"]."'";
	
	$maxId=NULL;
	if (!$result = $mysqli_dest->query($maxIdQuery)) {
        $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query to lead uni inside while, here is why: \n";
        $errorStr .= date("Y-m-d,H-i-s")." Query: " . $maxIdQuery . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";   
        fwrite($logfile, $errorStr);      
        mailError($errorStr);
        esci();
    }
    else {
        while ($ret = $result->fetch_assoc()){
			$maxId =$ret['maxid']; 
        }
    }
	
	//echo "MAX ID : ".$maxId;
	if(isset($maxId) AND $maxId!= NULL AND $maxId>0){ 
		//abbiamo gia delle entry nella tabella lead... creo la query settando il max id
		fwrite($logfile, "\n Recuperato il max id per questo concorso... max-id:".$maxId.";");
		$QueryLeadsConcorso='select * from '.$dbconcorso["tbl"].' where id > '.$maxId.' order by id ASC limit 0,'.$LimitImportLeads;
		
		
		// logga *****
		
	}else{
		fwrite($logfile, "\n Il concorso non aveva ancora mai popolato lead_uni iniziamo con il primo id del concorso;");
		//non abbiamo ancora nessuna lead da questo db.. la query non prevede maxid ma solo il classico limit
		$QueryLeadsConcorso='select * from '.$dbconcorso["tbl"].' order by id ASC limit 0,'.$LimitImportLeads;
		
		// logga *****
		
		//crea campagna eventuale
	}
	fwrite($logfile, "\n Numero limite di leads da recuperare per concorso: ".$LimitImportLeads.";");
	//recuperiamo le lead
	
	echo 'RECUPERIAMO LEAD DA CONCORSO';
	if (!$result = $mysqli_concorso->query($QueryLeadsConcorso)) {
        $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query to LEAD DEL CONCORSO inside while, here is why: \n";
        $errorStr .= date("Y-m-d,H-i-s")." Query: " . $QueryLeadsConcorso . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_concorso->errno . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_concorso->error . "\n";      
        fwrite($logfile, $errorStr);     
        mailError($errorStr);
        esci();
	}else{
		fwrite($logfile, "\n RECUPERATE LEAD INIZIO TRATTAMENTO/MAPPING DEI DATI DELLE LEADS; \n");
		while ($ret = $result->fetch_assoc()){
			//stiamo ciclando le leads che andranno poi uplodate
			//iniziamo a creare il mapping
			// array che contiene tutti gli extra fields, nome campo=>valore campo e successivamente creeremo anche idcampo(come leadun_extra_fields) => valore campo
			fwrite($logfile, "\n".date("Y-m-d,H-i-s")."	processo/mappo lead n.".$ret['id']."\n");
			echo "	processo/mappo lead n..".$ret['id']."\n";
			
			$LeadExtraData=array();
			$LeadExtraDataWithId=array();
			
			//vediamo se abbiamo duplicati
			$parent_id = 0;
			$_parent_id = false;
			if(isset($ret['cellulare']) && !empty($ret['cellulare']) && $ret['cellulare']!='non presente'){
				$_parent_id = checkForDuplicates('cellulare',$ret['cellulare'], $mysqli_dest);
			}else{ // se il phone1 (cellulare è vuoto) provo l'email.
				if(isset($ret['email']) && !empty($ret['email']) && $ret['email']!='non presente'){
					$_parent_id = checkForDuplicates('email',$ret['email'], $mysqli_dest);
				}
			}
			if($_parent_id){
				fwrite($logfile, "La lead è un doppione. id parent: ".$_parent_id."; \n");
				echo "La lead è un doppione. id parent: ".$_parent_id."\n";
				$parent_id = $_parent_id;
			}
			
				
			//elaborazioni particolari delle value
			$ret['campagna_id']=$dbconcorso['idcampagna'];
			$ret['source_db']=$dbconcorso['db'];
			$ret['source_tbl']=$dbconcorso['tbl'];
			$ret['parent_id'] = $parent_id;
			
			//ricavo anno di nascita da data nascita
			if(isset($ret['data_nascita']) AND $ret['data_nascita']!=NULL or $ret['data_nascita']!=''){
				$ret['anno_nascita']= date("Y",  strtotime($ret['data_nascita']));
			}
			
			//forma giuridica è sempre 0??
			if(!array_key_exists('forma_giuridica', $ret)){
				$ret['forma_giuridica']=0;
			}
			
			//se non è settato direttamente il sesso vedo se posso capirlo da titolo(Sig., Sig.na,Sign.ra)
			if((!isset($ret['sesso']) OR $ret['sesso']==NULL OR $ret['sesso']=='') AND (isset($ret['titolo']) AND $ret['titolo']!=NULL or $ret['titolo']!='')){if($ret['titolo']=='Sig.'){$ret['sesso']='M';}elseif($ret['titolo']=='Sig.ra' OR $ret['titolo']=='Sig.na'){$ret['sesso']='F';}}
			
			//se posso cerco di costruirmi l'indirizzo con via, spazio civ  da trimmare
			$ret['indirizzo']='';
			if(isset($ret['residente_via']) AND $ret['residente_via']!=NULL AND $ret['residente_via']!=''){
				$ret['indirizzo']=trim($ret['residente_via']);
				if(isset($ret['residente_civ']) AND $ret['residente_civ']!=NULL AND $ret['residente_civ']!=''){
					$ret['indirizzo'].=', '.trim($ret['residente_civ']);
				}
			}
			
			//se è presente la parola terz setta a 1 la privacy terzi altrimenti 0
			if(isset($ret['privacy']) AND $ret['privacy']!=NULL AND $ret['privacy']!='' AND (strpos($ret['privacy'], 'terzi') !== false  OR strpos($ret['privacy'], 'terzi') !== false )){
				$ret['privacy_terzi']=1;
			}else{
				$ret['privacy_terzi']=0;
			}
			//abbiamo finito di settare casi particolari
			
			
			//prepare query for insert
			 $strSql  = "insert into lead_uni_test set ";
			 $countValues=0;
			 $LogStringOfLead='';
			 foreach($ret as $Lkey => $Lvalue){
				 $skippa=0;
				
				 //vediamo cosa fare di questo campo
				 if(array_key_exists($Lkey, $Mapper)){
					 
					 if($Mapper[$Lkey]!='skippa'){
						//il campo è stato trovato nel mapper  e non è da skippare... controlliamo se va trattato come extrafield
						 if($Mapper[$Lkey]=='extrafield'){
							 //aggiungo il campo a quelli di extrafield e skippo il campo
							 if(isset($Lvalue) AND $Lvalue!=NULL AND $Lvalue!=''){
								  $LeadExtraData[addslashes($Lkey)]=addslashes($Lvalue);
							 }
							  
							 $skippa=1;
						 }else{
							 //settiamo il campo nella giusta maniera tramite il mapper
							 //echo "campo mappato ".$Lkey." come ".$Mapper[$Lkey]. " \n\n ";
							 $Lkey=$Mapper[$Lkey];
							
						 }
					}else{
						//il campo è da skippare secondo il mapper$skippa
						$skippa=1;
					}
				}
				 //controllo se la value è vuota se si skyppo il campo tranne cellulare che va vuoto ed evito di considerare 0 come vuoto
				 if($Lvalue == 'non presente' OR empty($Lvalue)) { if($Lvalue!==0){if($Lkey=='cellulare'){$Lvalue = "";}else{$skippa=1;}}}
				
				 //se non è stata skippata aggiungo il set alla query di inser della lead
				 if($skippa==0){
					 echo "entrato 2 \n\n";
					$LogStringOfLead.=" ".$Lkey." => ".$Lvalue."\n";
					//aggiungo la virgola prima di ogni key-value che non sia la prima
					if($countValues>0){
						$strSql  .=', ';
					}
					$strSql.= $Lkey."  = '".addslashes($Lvalue)."'";
					$countValues++; 
				 }
			 }
			//a questo punta la stringa di query per l'inserimento è creata e la mandiamo al db ricavandoci l'id di inserimento
			//andiamo a ritrovare i vari ulteriori extrafield
			fwrite($logfile, " dati della lead: \n".$LogStringOfLead."\n");
			
            if ($mysqli_dest->query($strSql) === TRUE) {
                $id = $mysqli_dest->insert_id;
				echo "\n\n";
				fwrite($logfile, " LEAD INSERITA CORRETTAMENTE! con id:".$id);
				$LogStringOfLeadEF.="";
				//EXTRAFIELD
				fwrite($logfile, "\n Extra field della lead: \n");
				//procediamo a inserire gli extrafiled base nella tabella campi se non sono gia stati inseriti altrimenti ne ritroviamo l'attuale id nella tabella
				foreach($LeadExtraData as $keyexf=>$valueexf){
					$LogStringOfLeadEF.=$keyexf." => ".$valueexf."\n";
					$NumerodelCampo=get_campo_id($keyexf);
					$LeadExtraDataWithId[$NumerodelCampo]=$valueexf;
				}
				
				//recuperiamo gli extrafield "VERI"
				
				//se tbl= lead allora fai query di recupero altrimenti analizza campi_esterni
				if($dbconcorso["tbl"]=='lead'){
					$QueryGetLeadExtrafield= "select campo_id, valore from lead_campi_value where lead_id = ".$ret['id'];
					if (!$result2 = $mysqli_concorso->query($QueryGetLeadExtrafield)) {
						$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, dalla tabella lead_campi_value del concorso: \n";
						$errorStr .= date("Y-m-d,H-i-s")." Query: " . $QueryGetLeadExtrafield . "\n";
						$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_concorso->errno . "\n";
						$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_concorso->error . "\n";
						fwrite($logfile, $errorStr);
						mailError($errorStr);
						esci();
					}
					else {
						while ($ExtraOfLead = $result2->fetch_assoc()){
							//di questo extrafield recupero la stringa tramite $ConcorsoExtrafieldLabelsArray
							$LogStringOfLeadEF.=$ExtraOfLead['campo_id']." => ".$ExtraOfLead['valore']."\n";
							$stringaLabel=$ConcorsoExtrafieldLabelsArray[$ExtraOfLead['campo_id']];
							//controlliamo se la stringalabel esiste gia nel gestionale.. se si recuperiamo l'id altimenti la inseriamo
							$NumerodelCampo=get_campo_id($stringaLabel);
							$LeadExtraDataWithId[$NumerodelCampo]=addslashes($ExtraOfLead['valore']);
						}
					}
				}else{
					//analizza campi_esterni
					if(isset($ret['campi_esterni']) AND $ret['campi_esterni']!='' AND strpos($ret['campi_esterni'], '=>') !== false){
						//esistono campi esterni
						$campiEsterniArr=explode('[SEP]', $ret['campi_esterni']);
						foreach($campiEsterniArr as $campoEsterno){
							if(strpos($campoEsterno, '=>') !== false){
								$CampoArr=explode('=>',$campoEsterno);
								if(array_key_exists(0,$CampoArr) AND array_key_exists(1, $CampoArr) AND $CampoArr[0]!='' AND $CampoArr[1]!=''){
									//il campo/valore è settato correttamente
									$NumerodelCampo=get_campo_id($CampoArr[0]);
									$LeadExtraDataWithId[$NumerodelCampo]=addslashes($CampoArr[1]);
								}
							}
						}
					}
						
				}
				fwrite($logfile, $LogStringOfLeadEF."\n");
				//ora abbiamo un array pronto per essere usato nell'inserimento dei valori $LeadExtraDataWithId
				
				update_lead_uni_extrafileds($LeadExtraDataWithId, $id);
				fwrite($logfile, "\n extra field della lead inseriti correttamente....\n INSERIMENTO LEAD CORRETTA \n\n");
                 $tot_added_leads++;
            } else {
                fwrite($logfile, date("Y-m-d,H-i-s")."Errore : nella query di inserimento della lead " . $ret['id'] . PHP_EOL . $mysqli_dest->error);
                echo "Errore sull'inserimento della lead: " . $ret['id']  . PHP_EOL . $mysqli_dest->error;  
                $errorStr = date("Y-m-d,H-i-s")."Errore sull'inserimento della lead: ". $ret['id'] . "\n" . PHP_EOL ." QUERY INSERIMENTO:\n " . PHP_EOL .  $strSql .  PHP_EOL . $mysqli_dest->error;
                $mailErrors[] = $errorStr;
                
            }
        }
	}
	
	
}
$end_time = microtime(true);
$processtime=$start_time-$end_time;
fwrite($logfile, date("Y-m-d,H-i-s")."\n\n LEAD UPLODATE : ". $tot_added_leads. " ....DURATA".$processtime." secondi. \n END OF PROCESS \n ******************************************* \n");
echo "LEAD UPLODATE : ". $tot_added_leads. " ....FINISCHED in ".$processtime." secondi"; 
esci();



/********************** FUNZIONI GENERALI ****************/

	
//richiede un array con id del campo (come scritto in lead_uni_extra_fields)=> valore...
function update_lead_uni_extrafileds($ExtraDataWithId, $LeadId){
	global $mysqli_dest;
	global $date_now;
	//per ogni campo=id=>valore scriviamo nella tabella lead_uni_extra_value e andiamo poi a scrivere anche nella tabella a_lead_uni_extra_values che associa l'id della lead all'id dell'entry appena effettuata
	foreach($ExtraDataWithId as $key => $value){
		//scrivo in lead_uni_extra_values
		$querystring= "insert into lead_uni_extra_values set field_id = ".$key.", name = '".$value."', creation_date = '".$date_now."'";
		if ($mysqli_dest->query($querystring) === TRUE) {
			$id = $mysqli_dest->insert_id;
			//inserisco il campo appena inserito nell'array
			//echo " \n ______campo inserito in lead_uni extrafield con id:".$id;
			//scrivo in a_lead_uni_extra_values
			$querystring2= "insert into a_lead_extra_values set lead_id = ".$LeadId.", value_id = '".$id."', creation_date = '".$date_now."'";
			if ($mysqli_dest->query($querystring2) === TRUE) {
				//inserisco il campo appena inserito nell'array
				//echo " \n __v__v__campo inserito in a_lead_uni_extra_value ";
			}
			else {
				//non siamo riusciti a fare la query
				echo "errore nell inserimento in a_leadextra_values ";
				fwrite($logfile, date("Y-m-d,H-i-s")."Errore nell inserimento in a_leadextra_values ". PHP_EOL . $mysqli_dest->error);
				echo "Errore nell inserimento in a_leadextra_values". PHP_EOL . $mysqli_dest->error;  
				$errorStr = date("Y-m-d,H-i-s")."Errore nell inserimento in a_leadextra_values \n" . PHP_EOL ." QUERY INSERIMENTO:\n " . PHP_EOL .  $querystring .  PHP_EOL . $mysqli_dest->error;
				$mailErrors[] = $errorStr;
				esci();
					
			}
			
		}
		else {
			//non siamo riusciti a fare la query
			echo "errore nell inserimento in lead_uni_extra_values ";
            fwrite($logfile, date("Y-m-d,H-i-s")."Errore sull'inserimento nella tabella lead_uni_extra_values ". PHP_EOL . $mysqli_dest->error);
            echo "Errore sull'inserimento nella tabella lead_uni_extra_values". PHP_EOL . $mysqli_dest->error;  
            $errorStr = date("Y-m-d,H-i-s")."Errore sull'inserimento nella tabella lead_uni_extra_values \n" . PHP_EOL ." QUERY INSERIMENTO:\n " . PHP_EOL .  $querystring .  PHP_EOL . $mysqli_dest->error;
            $mailErrors[] = $errorStr;
			esci();
                
        }
	}
	
	//scrivo in a_lead_uni_extra_values
	
}

function date_now(){
	$date_now = new DateTime();
	$date_now = $date_now->format('Y\-m\-d\ H:i:s');
	return $date_now;
}



//passato il label(domanda o stringa) del campo extrafield, controlla se esiste gia nella tabella lead_uni_extra_fields se si restituisce l'id del campo altrimenti lo inserisce e restituisce l'id
function get_campo_id($stringaLabel){
	global $GestionaleExtrafieldLabelsArray;
	global $mysqli_dest;
	global $mailErrors;
	global $date_now;
	
	if(in_array($stringaLabel, $GestionaleExtrafieldLabelsArray)){
		$NumerodelCampo=$key = array_search ($stringaLabel, $GestionaleExtrafieldLabelsArray);
	}else{
		//performo un insert sulla tabella lead_uni_extra_field
		$QueryInsertExtrafieldLabel="insert into lead_uni_extra_fields set name = '".$stringaLabel."', creation_date = '".$date_now."' ";
		if ($mysqli_dest->query($QueryInsertExtrafieldLabel) === TRUE) {
			$NumerodelCampo = $mysqli_dest->insert_id;
			//inserisco il campo appena inserito nell'array
			$GestionaleExtrafieldLabelsArray[$NumerodelCampo]=$stringaLabel;
			//TOCONTINUE
		}
		else {
			//non siamo riusciti a fare la query
            fwrite($logfile, date("Y-m-d,H-i-s")."Errore sull'inserimento nella tabella lead_uni_extra_fields con valore: " . $stringaLabel. PHP_EOL . $mysqli_dest->error);
            echo "Errore sull'inserimento nella tabella lead_uni_extra_fields con valore: " . $stringaLabel . PHP_EOL . $mysqli_dest->error;  
            $errorStr = date("Y-m-d,H-i-s")."Errore sull'inserimento nella tabella lead_uni_extra_fields con valore: ". $stringaLabel . "\n" . PHP_EOL ." QUERY INSERIMENTO:\n " . PHP_EOL .  $QueryInsertExtrafieldLabel .  PHP_EOL . $mysqli_dest->error;
            $mailErrors[] = $errorStr;
			esci();
                
        }
	}
	return $NumerodelCampo;
}


//controlla dato il campo, in lead uni se esiste gia un entrY e restituisce l'id
function checkForDuplicates($targetField,$value,$mysqli){
	
	global $logfile;
    global $db_config;
	global $mailErrors;
	
	$result_id = false;
	
	$sql_check = "SELECT id from lead_uni_test where ". $targetField." = '" . $value . "' ORDER BY data ASC LIMIT 0,1";
	$result = $mysqli->query($sql_check);
	if($result){ // la query è andata a buon fine
		if($result->num_rows>0){ // la query ha restituito risultati
			$indice=0;
			$str = "########## - La Query di ricerca doppione per " . $targetField . ": " . $value . " ha prodotto risultati: esiste doppione." . PHP_EOL; 
			fwrite($logfile, $str . PHP_EOL);
			echo $str;
			while($ret = $result->fetch_assoc()){
				$result_id = $ret['id'];
			
			} // fine while
		} // fine if 
	}else{
		fwrite($logfile, date("Y-m-d,H-i-s")." Errore: Query fallita per ricerca duplicato " . $targetField . ": " . $value . ": \n");
		fwrite($logfile, date("Y-m-d,H-i-s")." Query: " . $sql_check . "\n");
		fwrite($logfile, date("Y-m-d,H-i-s")."Errno: " . $mysqli->errno . "\n");
		fwrite($logfile, date("Y-m-d,H-i-s")."Error: " . $mysqli->error . "\n");
		
		echo "Errore Select ricerca duplicato: " . $sql_check . PHP_EOL . $mysqli->error . PHP_EOL; 
		$mailErrors[] = "Errore ricerca duplicato su " . $targetField. " " . $value . " - " . PHP_EOL . $sql_check . PHP_EOL . $mysqli->error . PHP_EOL;
		esci();
	}
	
	return $result_id;
}


//INVIA MAIL DI ERRORE
function mailError($errorMsg){
     
    $to      = 'info@linkappeal.it';
    $subject = 'Script Error: update_lead_uni_concorso.php';
    $message = 'Results: ' . print_r( $errorMsg, true );
    $headers = 'From: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
    'Reply-To: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	mail($to, $subject, $message, $headers);  
	echo "<b>invio mail errore ".$message."</b>";
     
 }

 // la funzione esce dall'esecuzione, ma prima elimina il file lock
 function esci(){
	 global $lock_file;
	 unlink($lock_file);
	 exit();
  }
/* MAPPING CONCEPT
			nome	->  nome
			cognome 	-> cognome
			email	-> email
			cellulare	-> cellulare
			sesso	 -> sesso
			titolo 	-> skip
			tel_fisso  -> tel_fisso
			operatore_cell	-> operatore
			forma_giuridica -> classifica tutte le anagrafiche da concorso come forma_giuridica = 0 
			piva	-> skippa
			data_nascita -> extra value
			luogo_nascita -> extra value
			professione	 -> professione
			codice_fiscale	 ->	codice_fiscale
			residente_citta	-> citta
			residente_prov	-> provincia
			residente_regione	-> regione
			residente_cap	-> cap
			residente_via	-> elabborare indirizzo con residente civ (trimma le virgole se preesistono es: trim(via) , trim (civ)
			residente_civ	-> come sopra
			residente_latitudine 	-> latitudine
			residente_longitudine	-> longitudine
			privacy		-> privacy sei privacy c'è la parola terzi set terzi =1 dovrebbe gia essere fatto in privacy terzi
			privacy_terzi -> privacy terzi
			privacy_partner -> skippa
			codice_premio	-> extra field
			ip		->		indirizzo_ip
			url		-> url
			data	-> data
			media	-> editore
			code	-> code

			//field di lead_uni che non vengono popolati o popolati statici
			campagna_id : crea un nuovo record in campagne se non esiste  ho dubbi al riguardo
			source_db : database del concorso come da variabile
			source_tbl : tabella lead del concorso
			source_id : id del lead nella tabella lead del concorso
			anno_nascita: da ricavarsi dal campo data_nascita
			download : set =1
			parent_id da ricavarsi da leaduni
*/
 