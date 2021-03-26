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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_crossing_to_sms_replies.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n Script di crossing to sms_replies\n\n");


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
    
    echo "non siamo riusciti a conneterci a gestione \n";
	exit();
}else{echo "connesso a gestione \n";}

$count2=0;
$count1=0;
try{
	$getRisposteQuery='SELECT id, id_crossing, cellulare, risposta FROM risposte_sms_crossing where id_crossing not in (select a.id_crossing from risposte_crossing_analizate a) AND risposta is not null AND risposta!="";';

	if (!$resultQ = $mysqli_dest->query($getRisposteQuery)) {
		echo "errore recupero ana";
		$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
		$errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectAna . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
		fwrite($logfile, $errorStr);
		exit();
	}else {
		while ($row = $resultQ->fetch_assoc()){
			$count1++;
			//cerco il cap nella tabella cap_da_a
			$RispostaId=$row['id'];
			$RispostaCrossId=$row['id_crossing'];
			$RispostaCellulare=substr($row['cellulare'], 2);//rimuovo le prime due cifre(39) dal numero (crossing prevede l'aggiunta del 39
			$RispostaRisposta=$row['risposta'];
			$writeLog= "message id: ".$RispostaId."; cellulare: ".$RispostaCellulare."; risposta:".$RispostaRisposta;
			echo $writeLog;
			fwrite($logfile, $writeLog);
			//chiama sms_replies.php VIA CURL
			$url = 'http://esclusiva-perte.it/landing/SMS/sms_replies_new.php?Mittente='.$RispostaCellulare.'&Testo='.urlencode($RispostaRisposta).'&Piattaforma=crossing';
			echo "chiamata: ".$url;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			$result = curl_exec($ch);
			curl_close($ch);
			$writeLog= "RISPOSTA LATO SERVER : " . $result .'<br>\n';
			echo $writeLog;
			fwrite($logfile, $writeLog);
			$ResultDec=json_decode($result);
			//print_r($ResultDec);
			if($ResultDec and isset($ResultDec->status) AND $ResultDec->status=='OK'){
				$writeLog= "ok. lead passata \n";
				echo $writeLog;
				fwrite($logfile, $writeLog);
				$UpdateQuery='INSERT INTO risposte_crossing_analizate (id_crossing) VALUES("'.$RispostaCrossId.'")';
				//echo $UpdateQuery;
				if ($mysqli_dest->query($UpdateQuery) === TRUE) {
								//inserisco il campo appena inserito nell'array
								//echo " \n __v__v__campo inserito in a_lead_uni_extra_value ";
					$count2++;
					$writeLog= "update riusciuto: ".$RispostaCrossId. "\n";
					echo $writeLog;
					fwrite($logfile, $writeLog);
				}else{
					$writeLog= "update fallito0: ".$RispostaCrossId. "\n";
					echo $writeLog;
					fwrite($logfile, $writeLog);
				}
				
			}else{
				$writeLog ="Ko: nessuna risposta da replies_sms.php \n";
				echo $writeLog;
				fwrite($logfile, $writeLog);
			}
			sleep(3);
		}
	}
	
}catch(PDOException $e){
	$writeLog="Errore connessione:";
	echo $writeLog;
	fwrite($logfile, $writeLog);
}
if($count1==0){
	$writeLog= "nessuna risposta recuperata";
	echo $writeLog;
	fwrite($logfile, $writeLog);
}else{
	$writeLog= "recuperati ".$count1." di cui con succsso: ".$count2;
	echo $writeLog;
	fwrite($logfile, $writeLog);
}
//echo "uplodate: ".$count." anagrafiche";
?>










?>