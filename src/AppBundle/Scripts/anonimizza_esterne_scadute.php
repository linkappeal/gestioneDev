<?php
//riassocia l'id di leaduni alle lead di una data tabella in base a source_db,tbl e id

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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_anonimizza_lead_esterne_scadute.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n rende anonime le lead esterne che abbiano una data di distruzione precedente a oggi.\n\n");


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
    
    mailError($errorStr);
    esci();
}else{echo "connesso a gestione";}

try{
	$now=date("Y-m-d H-i-s");
	$Anonimizza="UPDATE lead_uni_esterne set nome='anonimizzato', cognome='anonimizzato', data_nascita= NULL, indirizzo='anonimizzato', partita_iva ='anonimizzato', customdata1='anonimizzato', customdata2='anonimizzato', customdata3='anonimizzato', anoniminizzato=1 WHERE anonimizzato=0 AND data_distruzione < '".$now."'";
	
	if ($mysqli_dest->query($Anonimizza) === TRUE) {
		//inserisco il campo appena inserito nell'array
		//echo " \n __v__v__campo inserito in a_lead_uni_copy_extra_value ";
	
		echo "update riusciuto \n";
	}else{
		echo "update fallito 0 \n";
	}
}catch(PDOException $e){
	echo "Errore connessione: ";
}

