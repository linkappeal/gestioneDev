<?php


$lock_file = __DIR__ . '/_miscelatore_light.lock';
if (file_exists($lock_file )) {
		die('Spiacente, un\'altra istanza dello Script è in esecuzione...');
		$errorStr  = date("Y-m-d,H-i-s")." Error: Altra istanza in esecuzione: \n";		
		$errorStr .= date("Y-m-d,H-i-s")." Exiting";
		exit();
		mailError('Attenzione il file miscelata_light non è partito perche è presente un file di lock. elimina manualmente il file di lock.');	
		}
$myfile = fopen($lock_file , "w") or die("Unable to create lock file!");
error_reporting(E_ERROR);


//settaggio del file di log
//Directory corrente
$curDir = dirname(__FILE__) ;
$arrPath = explode("/", $curDir) ;

$logfile = fopen($curDir."/log/".date("Ymd")."_miscelatore_light.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n Avvio Script per Il miscelatore light di campagna\n\n");
echo "\n\n /****  Avvio Script per Il miscelatore light di campagna ****/\n\n";

$data=date("Y-m-d H:i:s");
//DBS


/*************** 3) CONNESSIONE A DB DESTINAZIONE *****************/
// Qui impostiamo i parametri di connessione al DB
$config_file = $curDir."/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);


//MISCELATA LIGHT SCRIPT
//connessioni al server

//sintassi dell'array delle tabelle di source e di destinaz array('nometabella'=>'nomedb','nometabella2'=>'nomedb')
$HotSourceTable=array();//SERIALIZZATO nometabella=>nomedb i db possono essere: concorso(leadout), offertesumisura,esclusivaperte,offertepromozioni,gestione, leadout
	
	
	
// DATABASE ALTRI
	
//esclusiva per te:
$DBesclusivaperte =  array('host'=>'46.16.95.229', 'db'=>'esclusiv_cpl','username'=>'esclusiv_cplus', 'password'=>'HYpN%Ep!Mhxg', 'nome'=>'NAME','cognome'=>'SURNAME','cellulare'=>'PHONE1', 'data'=>'DATA', 'media'=>'MEDIA', 'refid'=>'REFID', 'id'=>'ID');
$DBoffertesumisura=  array('host'=>'46.16.95.229', 'db'=>'offertes_cpl','username'=>'offertes_ucps', 'password'=>';2mgK6y7sZQg', 'nome'=>'NAME','cognome'=>'SURNAME','cellulare'=>'PHONE1', 'data'=>'DATA', 'media'=>'MEDIA', 'refid'=>'REFID', 'id'=>'ID');
$DBoffertepromozioni=  array('host'=>'46.16.95.34', 'db'=>'offertep_cpl','username'=>'offertep_cpl', 'password'=>'UW[J8ScXWUJC', 'nome'=>'NAME','cognome'=>'SURNAME','cellulare'=>'PHONE1', 'data'=>'DATA', 'media'=>'MEDIA', 'refid'=>'REFID', 'id'=>'ID');
$DBleadout=  array('host'=>'46.16.95.34', 'db'=>'leadoutl_dbb','username'=>'leadoutl_usbd', 'password'=>'A%2)8s!JTkBp','nome'=>'NAME','cognome'=>'SURNAME','cellulare'=>'PHONE1', 'data'=>'DATA', 'media'=>'MEDIA', 'refid'=>'REFID', 'id'=>'ID');
$DBconcorso =  array('host'=>'46.254.38.170', 'db'=>'leadoutcnc_db562','username'=>'leadoutcnc_usrbd', 'password'=>'pB?HzC4GPZQi','nome'=>'nome','cognome'=>'cognome','cellulare'=>'cellulare', 'data'=>'data', 'media'=>'media', 'refid'=>'', 'id'=>'id');
$DBgestione= array('host'=>$db_config['parameters']['database_host'], 'db'=>$db_config['parameters']['database_name'],'username'=>$db_config['parameters']['database_user'], 'password'=>$db_config['parameters']['database_password'],'nome'=>'nome','cognome'=>'cognome','cellulare'=>'cellulare', 'data'=>'data', 'media'=>'reference_id', 'refid'=>'', 'id'=>'id');

$DbsNameArray=array('gestione', 'concorso', 'offertesumisura', 'esclusivaperte', 'offertepromozioni', 'leadout');

//test connections:
foreach($DbsNameArray as $DBName) {
	$dbvarname=${'DB'.$DBName};
	$mysqli_par = new mysqli($dbvarname['host'],
                          $dbvarname['username'],
						  $dbvarname['password'],
                          $dbvarname['db']);
	if ($mysqli_par->connect_errno) {
		echo "Non siamo riusciti a conneterci a: ".$DBName."; \n\n";
		fwrite($logfile, "Non siamo riusciti a conneterci a: ".$DBName."; \n\n");
		mailError("Non siamo riusciti a conneterci a: ".$DBName);
	}else{
		echo "Siamo riusciti a conneterci a: ".$DBName."; \n\n ";
		fwrite($logfile, "Siamo riusciti a conneterci a: ".$DBName."; \n\n ");
	}
}


//recupero row da tabella per ogni row ciclo:

$SelectMiscelata= "select * from miscelate_light where attiva=1";
//array che conterra tutti gli extrafield label del concorso
$mysqli_get_misc = new mysqli($DBgestione['host'],
                          $DBgestione['username'],
						  $DBgestione['password'],
                          $DBgestione['db']);

if (!$Mresult = $mysqli_get_misc->query($SelectMiscelata)) {
	echo "errore recupero miscelate";
	mailError("abbiamo avuto un errore sul recupero delle miscelate");
	fwrite($logfile, "errore recupero miscelate");
	$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
	$errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectMiscelata . "\n";
	$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_get_misc->errno . "\n";
	$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_get_misc->error . "\n";
	fwrite($logfile, $errorStr);
	esci();
}else {
	$countMisc=0;
	while ($misc = $Mresult->fetch_assoc()){
		$MiscId=$misc['id'];
		$MiscName=$misc['nome'];
		$NewLeads=array();
		$HotSources=$misc['hot_sources'];
		$ColdSource=$misc['cold_source'];
		$ColdSource=unserialize($ColdSource);
		$MixedTable=$misc['mixed_table'];
		$PercentualeFredde=$misc['percentuale_fredde'];
		$limite=$misc['limite'];
		$LandingId=$misc['landing_id'];
		$CampagnaId=$misc['campagna_id'];
		$ClienteId=$misc['cliente_id'];
		if($LandingId>0 AND $CampagnaId>0 AND $ClienteId>0){
			$contatore=1;
			echo "Abbiamo trovato le impostazioni per il contatore.. uoploderemo anche il contatore\n\n";
		}else{
			$contatore=0;
			echo "Non abbiamo trovato le impostazioni per il contatore.. skipperemo il contatore\n\n";
		}
		
		echo "------ Stiamo lavorando la miscelata con id: ".$MiscId . "\n\n";
		fwrite($logfile, "------ Stiamo lavorando la miscelata con id: ".$MiscId . "\n\n");
		
	
		if (empty($HotSources) OR empty($MixedTable)){
			echo "la miscelata con id ".$MiscId." ha dei campi mancanti.. salto alla prossima miscelata\n\n";
			fwrite($logfile, "la miscelata con id ".$MiscId." ha dei campi mancanti.. salto alla prossima miscelata\n\n");
			mailError("la miscelata con id ".$MiscId." ha dei campi mancanti.. salto alla prossima miscelata");
			continue;
		}
		
		$MixedTable=unserialize($MixedTable);
		if(!is_array($MixedTable) OR !isset($MixedTable[1]) OR !in_array($MixedTable[1],$DbsNameArray)){
			echo "il campo mixed_table della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima miscelata \n\n";
			fwrite($logfile, "il campo mixed_table della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima miscelata \n\n");
			mailError("il campo mixed_table della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima miscelata \n\n");
			continue;
		}
		
		
		//unserialize
		$HotSources=unserialize($HotSources);
		if(!is_array($HotSources)){
			echo "il campo hotsources della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima\n\n";
			fwrite($logfile, "il campo hotsources della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima\n\n");
			mailError("il campo hotsources della miscelata ".$MiscId." non è correttamente settato... deve essere array.. salto alla prossima miscelata \n\n");
			continue;
		}
		
		
		//get total leads in mixed table
		//recupero dalla mixed table tutte le chiavi primarie cellulare e le metto in comma separatedstring
		$dbvarname=${'DB'.$MixedTable[1]};
		$FieldCellulareInMixed=$dbvarname['cellulare'];
		$QueryAlreadyPassedKeys="select ".$dbvarname['cellulare']." from ".$MixedTable[0]; 
		echo "query recupero gia passate:".$QueryAlreadyPassedKeys;
		
		$mysqli_par = new mysqli($dbvarname['host'],
							  $dbvarname['username'],
							  $dbvarname['password'],
								$dbvarname['db']);
		$KeyAlreadyPassed='';
		$alreadyMixedLeadsNumber=0;
		if (!$Aresult = $mysqli_par->query($QueryAlreadyPassedKeys)) {
			echo "errore recupero dati gia passati \n\n";
			fwrite($logfile, "errore recupero DATI GIA PASSATI \n\n");
		}else {
			$countKey=0;
			while ($Cellmisc = $Aresult->fetch_assoc()){
				$alreadyMixedLeadsNumber++;
				if($countKey==0){
					$KeyAlreadyPassed='"'.$Cellmisc[$FieldCellulareInMixed].'"';
				}else{
					$KeyAlreadyPassed.=', "'.$Cellmisc[$FieldCellulareInMixed].'"';
				}
				$countKey++;
			}
			//parte delle query che evitano di ripetere l'import su chiave primaria
			if($KeyAlreadyPassed!=''){
				$KeyAlreadyPassed=" where cellulare not in (".$KeyAlreadyPassed.")";
			}
		}
		echo "already passed number: ".$alreadyMixedLeadsNumber."\n\n";
		fwrite($logfile, "already passed number: ".$alreadyMixedLeadsNumber."\n\n");
		
		if($limite>0 AND $limite > $alreadyMixedLeadsNumber){
			echo "entro nel recupero delle lead... limite non settato o ancora non raggiunto \n\n";
			fwrite($logfile, "entro nel recupero delle lead... limite non settato o ancora non raggiunto \n\n");
			$stopLeads=0;
			//unserializzo hotsourcetable, per ogni hotsourcetable controllo se il db è presente fra la rosa dei db, tento di connettermi alla tabella indicata con query_:
			$CountHotNewLead=0;
			$LimitQueryStringBase=' limit 0,';
			$LimitQueryedNumber=0;
			$countHS=-1;
			foreach($HotSources as $HotSourceName=>$HotSourceData){
				$countHS++;
				if(!is_array($HotSourceData)){
					echo "La hotsource ".$HotSourceName." per la miscelata ".$MiscName." con id ".$MiscId." non è un array.. salto alla prossima hs.\n\n";
					fwrite($logfile, "La hotsource ".$HotSourceName." per la miscelata ".$MiscName." con id ".$MiscId." non è un array.. salto alla prossima hs.\n\n");
					mailError("La hotsource ".$countHS." per la miscelata ".$MiscName." con id ".$MiscId." non è un array.. salto alla prossima hs.");
					continue;
				}
				$HotSourceName=$HotSourceData[0];
				$HotSourceDb=$HotSourceData[1];
				$HotSourceEt=$HotSourceData[2];
				echo "sto analizzando la hotsource:".$HotSourceName.", con db: ".$HotSourceDb . " ed etichetta: ".$HotSourceEt."\n\n";
				fwrite($logfile, "sto analizzando la hotsource:".$HotSourceName.", con db: ".$HotSourceDb . "\n\n");
				if(!in_array($HotSourceDb, $DbsNameArray)){
					echo "il db ".$HotSourceDb." della hotsource ".$HotSourceName." non è fra la rosa dei db possibili.. salto alla prossima hs.\n\n";
					fwrite($logfile, "il db ".$HotSourceDb." della hotsource ".$HotSourceName." non è fra la rosa dei db possibili.. salto alla prossima hs.\n\n");
					mailError("il db ".$HotSourceDb." della hotsource ".$HotSourceName." non è fra la rosa dei db possibili, miscelata id: ".$MiscId);
					continue;
				}
				$dbvarname=${'DB'.$HotSourceDb};
				$mysqli_par = new mysqli($dbvarname['host'],
							  $dbvarname['username'],
							  $dbvarname['password'],
							  $dbvarname['db']);
				//recupero le lead
				//controllo i limiti
				if($limite!=0){
					$limiteQuery=$limite-$alreadyMixedLeadsNumber-$LimitQueryedNumber;
					if($limiteQuery<1){
						$stopLeads=1;
						$LimitQueryStringFinal='';
						echo "abbiamo stoppato il processo di questa miscelata per raggiungimento limite \n\n";
						fwrite($logfile, "abbiamo stoppato il processo di questa miscelata per raggiungimento limite  \n\n");
						mailError("abbiamo stoppato il processo di questa miscelata per raggiungimento limite  \n\n");
					}else{
						$LimitQueryStringFinal=$LimitQueryStringBase.$limiteQuery;
					}
			    }else{
					$LimitQueryStringFinal='';
				}
				if($stopLeads==0){
					$KeyAlreadyPassedToQuery=str_replace("cellulare", $dbvarname['cellulare'], $KeyAlreadyPassed);
					if($HotSourceDb!='concorso' AND $HotSourceDb!='gestione'){
						$selectHotDataQuery="select ".$dbvarname['id'].", ".$dbvarname['nome'].", ".$dbvarname['cognome'].", ".$dbvarname['cellulare'].", ".$dbvarname['data'].", ".$dbvarname['media'].", ".$dbvarname['refid']." from ".$HotSourceName.$KeyAlreadyPassedToQuery.$LimitQueryStringFinal;
					}else{
						$selectHotDataQuery="select ".$dbvarname['id'].", ".$dbvarname['nome'].", ".$dbvarname['cognome'].", ".$dbvarname['cellulare'].", ".$dbvarname['data']." from ".$HotSourceName.$KeyAlreadyPassedToQuery.$LimitQueryStringFinal;
					}
					
					echo "query calda: ".$selectHotDataQuery. "\n\n";
					fwrite($logfile, "query calda: ".$selectHotDataQuery. "\n\n");
					if (!$Hresult = $mysqli_par->query($selectHotDataQuery)) {
						echo "errore recupero dati dalla hotsource \n\n";
						mailError("errore recupero dati dalla hotsource, miscelata id: ".$MiscId);
						fwrite($logfile, "errore recupero dati dalla hotsource \n\n");
						$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
						$errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectMiscelata . "\n";
						$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_get_misc->errno . "\n";
						$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_get_misc->error . "\n";
						fwrite($logfile, $errorStr);
						continue;
					}else {
						while ($Datas = $Hresult->fetch_assoc()){
							$LimitQueryedNumber++;
							$LeadName=$Datas[$dbvarname['nome']];
							$LeadCognome=$Datas[$dbvarname['cognome']];
							$LeadCellulare=$Datas[$dbvarname['cellulare']];
							$LeadOriginalData=$Datas[$dbvarname['data']];
							$LeadOriginalId=$Datas[$dbvarname['id']];
							$dbvarname=${'DB'.$HotSourceDb};
							if($HotSourceDb!='concorso' AND $HotSourceDb!='gestione'){
								$LeadMedia=$Datas[$dbvarname['media']];
								$LeadRefid=$Datas[$dbvarname['refid']];
								if($LeadRefid=='non presente' OR $LeadRefid==''){
									$LeadFinalMedia=$LeadMedia;
								}else{
									$LeadFinalMedia=$LeadMedia.' - '.$LeadRefid;
								}
							}else{
								$LeadFinalMedia="nessuna fonte";
							}
							
							//echo "lead datas: nome:".$LeadName."; cognome: ".$LeadCognome."; cellulare: ".$LeadCellulare . " \n\n";
							$NewLeads[]=array($LeadName, $LeadCognome,$LeadCellulare,$HotSourceName,$dbvarname['db'],$HotSourceEt,"calda",$LeadOriginalData,$LeadFinalMedia, $LeadOriginalId);
							$CountHotNewLead++;
						}
					}
				}
			}
			if($CountHotNewLead>0){
				echo "abbiamo recuperato ".$CountHotNewLead." lead calde, procediamo alle fredde \n\n";
				fwrite($logfile, "abbiamo recuperato ".$CountHotNewLead." lead calde, procediamo alle fredde \n\n");
			}else{
				echo "non abbiamo recuperato nessuna lead calda, passiamo alla prossima miscelata \n\n";
				fwrite($logfile, "non abbiamo recuperato nessuna lead calda, passiamo alla prossima miscelata \n\n");
				continue;
			}
			echo "lead calde trovate !!!!!!!: ".$CountHotNewLead."; percent: ".$PercentualeFredde."\n\n\r";
			fwrite($logfile, "lead calde trovate !!!!!!!: ".$CountHotNewLead."; percent: ".$PercentualeFredde."\n\n\r");
			
			$NumberOfCold=floor(($CountHotNewLead*$PercentualeFredde)/(100-$PercentualeFredde));
			echo "numero di fredde da recuperare:".$NumberOfCold."\n\n\r";
			fwrite($logfile, "numero di fredde da recuperare:".$NumberOfCold."\n\n\r");
			//selezioniamo le fredde
			
			if(is_array($ColdSource)){
				echo "cold source isarray: yes <br>\n";
			}else{
				echo "cold source isarray:  no <br>\n";
			}
			if(isset($ColdSource[1])){
				echo "cold source isset: yes <br>\n";
			}else{
				echo "cold source isset: no <br>\n";
			}
			if(isset($ColdSource[2])){
				echo "cold source etichetta isset: yes <br>\n";
			}else{
				echo "cold source etichetta isset: no <br>\n";
			}
			if(in_array($ColdSource[1],$DbsNameArray)){
				echo "cold source inarray: yes <br>\n";
			}else{
				echo "cold source inarray: no <br>\n";
			}
		
			if($NumberOfCold>0 AND is_array($ColdSource) AND isset($ColdSource[1]) AND in_array($ColdSource[1],$DbsNameArray) AND isset($ColdSource[2])){
				echo "numero di fredde che speriamo di ritrovare in base alla percentuale: ".$NumberOfCold . "\n\n";
				fwrite($logfile, "numero di fredde che speriamo di ritrovare in base alla percentuale: ".$NumberOfCold . "\n\n");
				//controllo i limiti
				if($limite!=0){
					$limiteQuery=$limite-$alreadyMixedLeadsNumber-$LimitQueryedNumber;
					if($limiteQuery<1){
						$stopLeads=1;
						$LimitQueryStringFinal='';
						echo "abbiamo stoppato il processo di questa miscelata per raggiungimento limite \n\n";
						fwrite($logfile, "abbiamo stoppato il processo di questa miscelata per raggiungimento limite \n\n");
					}else{
						$LimiteColdIntel=min($NumberOfCold,$limiteQuery);
						$LimitQueryStringFinal=$LimitQueryStringBase.$LimiteColdIntel;
					}
			    }else{
					$LimitQueryStringFinal=' limit 0,'.$NumberOfCold;
				}
				if($stopLeads==0){
					$dbvarname=${'DB'.$ColdSource[1]};
					echo "fredde db: ".$ColdSource[1];
					$KeyAlreadyPassedToQueryF=str_replace("cellulare", $dbvarname['cellulare'], $KeyAlreadyPassed);
					if($ColdSource[1]=='concorso' OR $ColdSource[1]=='gestione'){
						echo " no -media ";
						$QueryRecuperoFredde="select ".$dbvarname['id'].", ".$dbvarname['nome'].", ".$dbvarname['cognome'].", ".$dbvarname['cellulare'].", ".$dbvarname['data']." from ".$ColdSource[0].$KeyAlreadyPassedToQueryF." order by rand() ".$LimitQueryStringFinal;
					}else{
						echo " si -media ";
						$QueryRecuperoFredde="select ".$dbvarname['id'].", ".$dbvarname['nome'].", ".$dbvarname['cognome'].", ".$dbvarname['cellulare'].", ".$dbvarname['data'].", ".$dbvarname['media'].", ".$dbvarname['refid']." from ".$ColdSource[0].$KeyAlreadyPassedToQueryF." order by rand() ".$LimitQueryStringFinal;
					}
					
					echo "query recupero fredde: ".$QueryRecuperoFredde." \n\n";
					fwrite($logfile, "query recupero fredde: ".$QueryRecuperoFredde." \n\n");
					
					
					$mysqli_par = new mysqli($dbvarname['host'],
								  $dbvarname['username'],
								  $dbvarname['password'],
								  $dbvarname['db']);
							  
					if (!$Cresult = $mysqli_par->query($QueryRecuperoFredde)) {
						echo "errore recupero dati dalla coldsource \n\n";
						mailError("errore recupero dati dalla coldsource, miscelata id: ".$MiscId);
						fwrite($logfile, "errore recupero dati dalla coldsource: ".$mysqli_par->errno." \n\n");
						echo $mysqli_par->errno;
						continue;
					}else {
						 $countCold=0;
						while ($Datas = $Cresult->fetch_assoc()){
							$LimitQueryedNumber++;
							$LeadName=$Datas[$dbvarname['nome']];
							$LeadCognome=$Datas[$dbvarname['cognome']];
							$LeadCellulare=$Datas[$dbvarname['cellulare']];
							$LeadOriginalData=$Datas[$dbvarname['data']];
							$LeadOriginalId=$Datas[$dbvarname['id']];
							if($ColdSource[1]!='concorso' AND $ColdSource[1]!='gestione'){
								$LeadMedia=$Datas[$dbvarname['media']];
								$LeadRefid=$Datas[$dbvarname['refid']];
								if($LeadRefid=='non presente' OR $LeadRefid==''){
									$LeadFinalMedia=$LeadMedia;
								}else{
									$LeadFinalMedia=$LeadMedia.' - '.$LeadRefid;
								}
							}else{
								$LeadFinalMedia="nessuna fonte";
							}
							//echo "lead datas: nome:".$LeadName."; cognome: ".$LeadCognome."; cellulare: ".$LeadCellulare . " \n\n";
							$NewLeads[]=array($LeadName, $LeadCognome,$LeadCellulare,$ColdSource[0],$ColdSource[1],$ColdSource[2],"fredda",$LeadOriginalData, $LeadFinalMedia,$LeadOriginalId);
							$countCold++;
						}
						echo "Abbiamo recuperato ".$countCold." leads fredde\n\n";
						fwrite($logfile, "Abbiamo recuperato ".$countCold." leads fredde\n\n");
					}
				}
				
			}else{
				echo "le fredde non verranno recuperate, perche o il numero da recuperare è 0 o l'array non è settato bene a db \n\n";
				fwrite($logfile, "le fredde non verranno recuperate, perche o il numero da recuperare è 0 o l'array non è settato bene a db \n\n");
			}
			if(count($NewLeads)>0){
				//randomiziamo l'ordine dell array di insert
				//print_r($NewLeads);
				//echo "\n\n"; 
				$NewLeadsRand=array();
				$keys = array_keys($NewLeads);
				shuffle($keys);

				foreach ($keys as $key)
				{
					$NewLeadsRand[$key] = $NewLeads[$key];
				}
				$NewLeads=$NewLeadsRand;
				//insert new lead
				echo "Procediamo all'inserimento di ".count($NewLeads)."leads \n\n";
				fwrite($logfile, "Procediamo all'inserimento di ".count($NewLeads)." leads \n\n");
				//print_r($NewLeads);
				$dbvarname=${'DB'.$MixedTable[1]};
				$NL_Data_Miscelata=date('Y-m-d H:i:s');
				foreach($NewLeads as $NewLead){
					//print_r($NewLead);
					$NLnome=$NewLead[0];
					$NLcognome=$NewLead[1];
					$NLcellulare=$NewLead[2];
					$NL_Table=$NewLead[3];
					$NL_Db=$NewLead[4];
					$NL_Etichetta=$NewLead[5];
					$NL_Type=$NewLead[6];
					$NL_OriginalData=$NewLead[7];
					$NL_FinalMedia=$NewLead[8];
					$NL_OriginalId=$NewLead[9];
					
					//INSERIMENTO DELLA LEAD NELLA MIXED TABLE
					$QueryInsertNL='insert into '.$MixedTable[0].' set ' . $dbvarname['nome']. ' = "'.$NLnome.'", ' . $dbvarname['cognome']. ' = "'.$NLcognome.'", ' . $dbvarname['cellulare']. ' = "'.$NLcellulare.'"';
					
					
					
					//echo "query di insert".$QueryInsertNL;
					$dbvarname=${'DB'.$MixedTable[1]};
					$mysqli_par = new mysqli($dbvarname['host'],
										  $dbvarname['username'],
										  $dbvarname['password'],
										  $dbvarname['db']);
					if ($mysqli_par->query($QueryInsertNL) === TRUE) {
						$id_lead_mixed = $mysqli_par->insert_id;
						echo "query di inserimento in mixed table riuscita \r\n\n";
						fwrite($logfile, "query di inserimento in mixed table riuscita \r\n\n");
						
						//INSERIMENTO DELLA LEAD NELLA REPORT TABLE
						$QueryInsertReportNL='insert into miscelate_light_report set id_miscelata = "'.$MiscId.'", id_lead = "'.$id_lead_mixed.'", lead_type = "'.$NL_Type.'", source_tbl = "'.$NL_Table.'", source_db ="'.$NL_Db.'", source_et ="'.$NL_Etichetta.'", data_miscelazione="'.$NL_Data_Miscelata.'", data_lead="'.$NL_OriginalData.'", cpl="'.$NL_FinalMedia.'" , id_origine="'.$NL_OriginalId.'"';
						
						$mysqli_parG = new mysqli($DBgestione['host'],
										 $DBgestione['username'],
										 $DBgestione['password'],
										 $DBgestione['db']);
						if ($mysqli_parG->query($QueryInsertReportNL) === TRUE) {
							echo "query di inserimento in miscelata_light_report riuscita \r\n\n";
							fwrite($logfile, "query di inserimento in miscelata_light_report riuscita \r\n\n");
						}else{
							echo "query di inserimento in  miscelata_light_report non riuscita \n\n\r";
							mailError("query di inserimento in miscelata_light_report della lead non riuscita, miscelata id: ".$MiscId.", leadId: ".$id_lead_mixed);
							fwrite($logfile, "query di inserimento in miscelata_light_report della lead non riuscita, miscelata id: ".$MiscId.", leadId: ".$id_lead_mixed." \n\n\r");
						}
						//INSERIMENTO DELLA LEAD NEL CONTATORE
						if($contatore==1){
						
							$mysqli_parG = new mysqli($DBgestione['host'],
										  $DBgestione['username'],
										  $DBgestione['password'],
										  $DBgestione['db']);
							$QueryContatore='INSERT INTO contatore (cliente_id, campagna_id, landing_id, offtarget, data_lead) VALUES ('.$ClienteId.', '.$CampagnaId.', '.$LandingId.', 0, "'.$data.'");';
							echo "query contatore: ".$QueryContatore;
							if ($mysqli_parG->query($QueryContatore) === TRUE) {
								echo "lead inserita a contatore \n\n";
							}else{
								echo "lead non inserita a contatore \n\n";
							}
						
					}
					}else{
						echo "query di inserimento in mixed table non riuscita \n\n\r";
						mailError("query di inserimento finale in mixed table delle lead non riuscita, miscelata id: ".$MiscId.", leadId: ".$id_lead_mixed);
						fwrite($logfile, "query di inserimento non riuscita,  miscelata id: ".$MiscId.", leadId: ".$id_lead_mixed."\n\n\r");
					}
					
				}
				
			}else{
				echo "non abbiamo ritrovato nessuna lead da importare per la miscelata ".$MiscId . " \n\n\r";
				fwrite($logfile, "non abbiamo ritrovato nessuna lead da importare per la miscelata ".$MiscId . " \n\n\r");
				
			}
			
			
		}else{ //fine if limite non ragiunto 1
			$stopLeads=1;
			//limite raggiunto1.. stop della campagna e invio email di avvertimento
		}
		
		//se abbiamo raggiunto il limite procediamo allo stop della campagna e a invio emil
		if($stopLeads==1){
			//send mail
			$StopMiscelataQuery='update miscelate_light set attiva = 0 where id='.$MiscId;
			$mysqli_get_misc = new mysqli($DBgestione['host'],
                          $DBgestione['username'],
						  $DBgestione['password'],
                          $DBgestione['db']);

			if ($mysqli_get_misc->query($StopMiscelataQuery) !== TRUE) {
				echo 'abbiamo provato a stoppare la miscelata con id '.$MiscId.' per raggiungimento limite ma la query è fallita \n\n';
				fwrite($logfile, 'abbiamo provato a stoppare la miscelata con id '.$MiscId.' per raggiungimento limite ma la query è fallita \n\n');
				mailError('abbiamo provato a stoppare la miscelata con id '.$MiscId.' per raggiungimento limite ma la query è fallita ');
			}else{
				echo 'abbiamo stoppato la miscelata con id '.$MiscId.' per raggiungimento limite \n\n';
				fwrite($logfile, 'abbiamo stoppato la miscelata con id '.$MiscId.' per raggiungimento limite \n\n');
				mailError('abbiamo stoppato la miscelata con id '.$MiscId.' per raggiungimento limite');
			}
			
		}
	}//fine while miscelate
}


esci();


function esci(){
	echo '----Stiamo uscendo dal file \n\n';
	fwrite($logfile, '----Stiamo uscendo dal file \n\n');
	 global $lock_file;
	 unlink($lock_file);
	 exit();
}
//recupero row da tabella per ogni row ciclo:
function date_now(){
	$date_now = new DateTime();
	$date_now = $date_now->format('Y\-m\-d\ H:i:s');
	return $date_now;
}


 function mailError($errorMsg){
     
    $to      = 'info@linkappeal.it';
    $subject = 'Script Error: miscelatalight.php';
    $message = 'Results: ' . print_r( $errorMsg, true );
    $headers = 'From: miscelatalight@gestione.linkappeal.it' . "\r\n" .
    'Reply-To: miscelatalight@gestione.linkappeal.it' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    //mail($to, $subject, $message, $headers);  
     
 }




?>