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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_cancella_leaduni_table_ultradecennali.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")." - /************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")." cancella dal db gestione i records con campo data confrontato ad oggi maggiore di 10 anni .\n\n");


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
    
    //mailError($errorStr);
    //esci();
}else{echo "connesso a gestione";}

try{
	$Elimina="delete from lead_uni_copy where (truncate(datediff(curdate(),lead_uni_copy.data )/365.25,0)) > 9";
	
	if ($mysqli_dest->query($Elimina) === TRUE) {             	        
		echo "\tdelete riusciuto: \n" ; 
	}else{
		echo "delete fallito 0 \n";
	}
}catch(PDOException $e){
	echo "Errore connessione: ";
}

