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

$logfile = fopen($curDir."/log/".date("Ymd")."_trasforma_extraction_sms.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n aggiunge campagna_id a extraction sms in base alla nuova tabella campagne_sms id\n\n");


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

    //select id, campagna from campagne_sms
	$SelectCampagne = "select id, campagna, data_lancio from campagne_sms"; // LEAD DA inviare oggi
	if (!$result = $mysqli_dest->query($SelectCampagne)) {
		echo "errore recupero campagne";
		$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
		$errorStr .= date("Y-m-d,H-i-s")." Query: " . $strSql . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
		fwrite($logfile, $errorStr);
		exit();
	}else {
		$count=0;
		while ($row = $result->fetch_assoc()){
            //cerco il cap nella tabella cap_da_a
				echo 'sto per uplodare la campagna: '.$Cname;
                $Cid=$row['id'];
                $Cname=$row['campagna'];
                $Cdata=$row['data_lancio'];
               
				$UpdateExtraxtionSms = "UPDATE extraction_sms set campagna_id = ".$Cid." WHERE  Campagna= '".$Cname."' AND data_estrazione like '".$Cdata."%';" ;// 
				if ( $mysqli_dest->query($UpdateExtraxtionSms)  === TRUE ) {
                    echo "update riusciuto \n";
				}else {
					echo "update fallito0 \n";
				}
                echo "<br>";
            
		}
	}
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

