<?php

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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_cripto_leaduni_table_ultradecennali.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")." - /************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")." cripta dal db gestione i records con campo data confrontato ad oggi maggiore di 10 anni .\n");


// Qui impostiamo i parametri di connessione al DB
$config_file = $curDir."/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);

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
}else{echo "connesso a gestione\n";}

// -----------------------------------------------------------------------
try{
	
	// 1.	Seleziono id Target da LeadUni con data maggiore di 10 anni
	$SelectLeadTarget="select	lu.id from lead_uni lu 
					   where (truncate(datediff(curdate(),lu.data )/365.25,0)) > 9";
		   
	if (!$result = $mysqli_dest->query($SelectLeadTarget)) {
        $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query to lead uni inside while, here is why: \n";
        $errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectLeadTarget . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";   
        fwrite($logfile, $errorStr);
		}
    else {
			if(mysqli_num_rows($result) > 1) {
				$tlead_uni_id_comma_separated= "";
				while ($ret = $result->fetch_assoc()){		
					$tlead_uni_id_comma_separated .= $ret["id"] .",";		
				}
			}
			else {
				fwrite($logfile, "**** Non sono state trovate Lead. Processo terminato.\n--Ultima Query\n".$SelectLeadTarget."\n");
				echo "**** Non sono state trovate Lead. Processo terminato.\n--Ultima Query\n".$SelectLeadTarget."\n";
				fwrite($logfile, date("Y-m-d,H-i-s")." - /************************************************/ \n\n");
				exit();		
			}
		$lead_uni_id_comma_separated = substr($tlead_uni_id_comma_separated, 0, -1);	// lead_uni id target string

		echo "\tSelezionati Lead Target riuscito: ". $lead_uni_id_comma_separated . "\n" ;
		fwrite($logfile, "** Trovati id in lead_uni: ". $lead_uni_id_comma_separated . "\n");
    }
	mysqli_free_result($result);
	$ret	="";

// ----------------------------------------------------------------------------------
	// 2.	Crypto Campi in LeadUni con data maggiore di 10 anni
	$CryptoLeadTarget="update	lead_uni lu 
						set		nome=				md5(nome), 
								cognome=			md5(cognome),
								ragione_sociale=	md5(ragione_sociale),
								sesso=				md5(sesso),
								email=				md5(email),
								cellulare=			md5(cellulare),
								operatore=			md5(operatore),
								tel_fisso=			md5(tel_fisso),
								anno_nascita=		md5(anno_nascita),
								citta=				md5(citta),
								provincia=			md5(provincia),
								indirizzo=			md5(indirizzo),
								nazione=			md5(nazione), 
								quartiere=			md5(quartiere), 
								regione=			md5(regione), 
								cap=				md5(cap),
								forma_giuridica=	md5(forma_giuridica),
								partita_iva=		md5(partita_iva),
								tipo_partita_iva=	md5(tipo_partita_iva),
								cliente=			md5(cliente),
								codice_fiscale=		md5(codice_fiscale),
								importo_richiesto=	md5(importo_richiesto),
								indirizzo_ip=		md5(indirizzo_ip)
						where id in (" . $lead_uni_id_comma_separated .")";
	if ($mysqli_dest->query($CryptoLeadTarget) === TRUE) {             	        
		echo "\tCrypto Lead Target riuscito. \n" ;
		fwrite($logfile, "** Crypto id in lead_uni: ". $lead_uni_id_comma_separated . "\n");
	}else{
		echo "\tCrypto Fallito Lead Target: ". $lead_uni_id_comma_separated . "\n";
		fwrite($logfile, "**** Crypto Fallito Lead Target: ". $lead_uni_id_comma_separated . "\n");
	}
// ----------------------------------------------------------------------------------	
	// 3.	Crypto Cellulare in extraction
	$CryptoExtraction="update		extraction e
						set			e.cellulare = md5(cellulare)
						where e.lead_id in ( ". $lead_uni_id_comma_separated . ")";
	
	if ($mysqli_dest->query($CryptoExtraction) === TRUE) { 
		fwrite($logfile, "** Crypto id in Extraction: ". $lead_uni_id_comma_separated . "\n");
		echo "\tCrypto Extraction riuscito. \n" ; 
	}else{
		fwrite($logfile, "**** Crypto Fallito Extraction: ". $lead_uni_id_comma_separated . "\n");
		echo "\tCrypto Fallito Extraction: \n";
	}
// ----------------------------------------------------------------------------------	
	
	// 4.	Recupero id values di id target
	$SelectIdToArrayValues="select value_id from a_lead_extra_values
						    where lead_id in (". $lead_uni_id_comma_separated . ")";
	
	if (!$result = $mysqli_dest->query($SelectIdToArrayValues)) {
        $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query to lead extra values inside while, here is why: \n";
        $errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectIdToArrayValues . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
        $errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";   
        fwrite($logfile, $errorStr);
	}
    else {
		if(mysqli_num_rows($result) > 1) {
			$tvalues_id_comma_separated="";
			while ($ret = $result->fetch_assoc()){
				$tvalues_id_comma_separated .= $ret["value_id"] .",";		
			}
			echo "\tSelezionati Values per Lead Target riuscito: " . $tvalues_id_comma_separated ."\n" ;
			fwrite($logfile, "** Selezionati Values: ". $tvalues_id_comma_separated ."\n");
			$values_id_comma_separated = substr($tvalues_id_comma_separated, 0, -1);	// values id target string
		}
		else {
			fwrite($logfile, "**** Non sono state trovate Lead Extra Values. \n--Ultima Query\n". $SelectIdToArrayValues."\n");
			echo "**** Non sono state trovate Lead Extra Values. \n--Ultima Query\n". $SelectIdToArrayValues."\n";
			fwrite($logfile, date("Y-m-d,H-i-s")." - /************************************************/ \n\n");
			//exit();		
		}	
	}
	
	mysqli_free_result($result);
	$ret	="";	

	if (empty($values_id_comma_separated)){
		exit();
	}
	
	
	// 5.	Elimino righe Extra Values per Lead Target
					
	$EliminoExtraValues="update lead_uni_extra_values
							set name=			md5(name)
						where id in (". $values_id_comma_separated . ")";				
	
	if ($mysqli_dest->query($EliminoExtraValues) === TRUE) {             	        
		fwrite($logfile, "** Cripto Extra Values riuscito per id values: ". $values_id_comma_separated . "\n");
		echo "\tCripto Extra Values riuscito per id values: ". $values_id_comma_separated . "\n"; 
	}else{
		fwrite($logfile, "**** Cripto Extra Values Fallito per id values: ". $values_id_comma_separated . "\n");
		echo "\tCripto Extra Fields Fallito per id lead:  ". $values_id_comma_separated . "\n";
	}

	// ------------------------------------------------------------------------
fwrite($logfile, date("Y-m-d,H-i-s")." - /************************************************/ \n\n");
}catch(PDOException $e){
	echo "Errore connessione: ";
}

