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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_recupera_campo_leads.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n Avvio Script per la riassociazione di un determinato campo custom\n\n");


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
    echo "non siamo riusciti a conneterci a gestione";
	exit();
}else{echo "connesso a gestione";}

$field=19;
$TabToAss="lead_uni_confrontoDD_leaduni";
$LeadUniId_field="provenienza";


$count=0;
try{
	$SelectAna = 'SELECT '.$LeadUniId_field.' FROM '.$TabToAss.' lu WHERE lu.'.$LeadUniId_field.' in (select ale.lead_id from a_lead_extra_values ale where ale.value_id in (select lue.id from lead_uni_extra_values lue where lue.field_id='.$field.' and lue.name != "")) limit 50000'; // LEAD DA inviare oggi
echo "query select: ".$SelectAna;
	if (!$result = $mysqli_dest->query($SelectAna)) {
		echo "errore recupero ana";
		$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
		$errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectAna . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
		$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
		fwrite($logfile, $errorStr);
		exit();
	}else {
		while ($row = $result->fetch_assoc()){
			//cerco il cap nella tabella cap_da_a
			echo "lead_uni id:".$row[$LeadUniId_field];
				
				$FindField = 'select name as field_value from lead_uni_extra_values where id in (select value_id from a_lead_extra_values where lead_id='.$row[$LeadUniId_field].') AND field_id='.$field; // 
				if (!$result2 = $mysqli_dest->query($FindField)) {
					echo "errore recupero value";
					$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
					$errorStr .= date("Y-m-d,H-i-s")." Query: " . $FindField . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
					fwrite($logfile, $errorStr);
					exit();
				}else {
					while ($row2 = $result2->fetch_assoc()){
						echo "Trovata value: ".$row2['field_value']."per l'anagrafica con id ".$row[$LeadUniId_field];
							$Val=$row2['field_value'];
							$AddValueQuery = 'UPDATE '.$TabToAss.' SET custom_data ="'.$Val.'" WHERE '.$LeadUniId_field.'='.$row[$LeadUniId_field]; //
						ECHO $AddValueQuery;
					
						try{
						    if ($mysqli_dest->query($AddValueQuery) === TRUE) {
								//inserisco il campo appena inserito nell'array
								//echo " \n __v__v__campo inserito in a_lead_uni_extra_value ";
								$count++;
								echo "update riusciuto: ".$row[$LeadUniId_field]. "\n";
							}else{
								echo "update fallito0: ".$row[$LeadUniId_field]. "\n";
							}
						}catch(PDOException $e){
							echo "update fallito: ".$row[$LeadUniId_field]. "\n";
						}
					}
				}
				
			
			
			echo "<br>";
		}
	}
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

echo "uplodate: ".$count." anagrafiche";

?>