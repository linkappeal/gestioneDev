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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_riassociax.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n Avvio Script per l'aggiornamento della tabella Lead unificate da concorsi\n\n");


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
	$SelectAna = "select provincia from lead_uni_operations where cap is null and provincia is not null group by provincia"; // LEAD DA inviare oggi
	if (!$result = $mysqli_dest->query($SelectAna)) {
		echo "errore recupero ana";
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
            $prov=$row['provincia'];
                echo "provincia:".$row['provincia']." \n";
				$FindCap = 'SELECT cap_da FROM cap_da_a WHERE provincia="'.$prov.'" Order By cap_da Desc limit 1 '; // 
				if (!$result2 = $mysqli_dest->query($FindCap)) {
					echo "errore recupero cap";
					$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
					$errorStr .= date("Y-m-d,H-i-s")." Query: " . $strSql . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
					fwrite($logfile, $errorStr);
					exit();
				}else {
					echo "UPLODO  \n";
					$R=0;
					while ($row2 = $result2->fetch_assoc()){
						$R++;
						echo "Trovata cap: ".$row2['cap_da']."per la provincia ".$row['provincia'];
							$cap=$row2['cap_da'];
							$AddRegEProv = "UPDATE lead_uni_operations SET cap ='".$cap."' WHERE provincia='".$prov."'"; //
						ECHO $AddRegEProv." \n";
					
						try{
						    if ($mysqli_dest->query($AddRegEProv) === TRUE) {
								//inserisco il campo appena inserito nell'array
								//echo " \n __v__v__campo inserito in a_lead_uni_copy_extra_value ";
								$count++;
								echo "update riusciuto \n";
							}else{
								echo "update fallito0 \n";
							}
						}catch(PDOException $e){
							echo "update fallito \n";
						}
					}
					ECHO "trovata regione: ".$R. "\n\n";
				}
				
			
			
			echo "<br>";
		}
	}
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

echo "uplodate: ".$count." anagrafiche";

?>