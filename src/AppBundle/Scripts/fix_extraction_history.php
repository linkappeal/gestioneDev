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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_fix_extraction_history.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n riassocia l'id di leaduni alle lead di una data tabella in base a source_db,tbl e id\n\n");


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
	$SelectAna = "select id, id_lead_uni, cliente_id, data_inserimento, tipo_vendita, data_sblocco from extraction_utility where controllato=1  AND cliente_id > 0"; // LEAD DA inviare oggi
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
		$countFail=0;
		$count2=0;
		$countFail2=0;
		$countFail3=0;
		$totcount=0;
		while ($row = $result->fetch_assoc()){
				if($totcount==500){
					sleep(1);
					$totcount=0;
				}
				$totcount++;
                $Aid=$row['id'];
				$LeadUniId=$row['id_lead_uni'];
				$Cliente_Id=$row['cliente_id'];
				$data_ins=$row['data_inserimento'];
				$data_sblocco=$row['data_sblocco'];
				$tipo_vendita=$row['tipo_vendita'];
				echo 'trovata estrazione con questi dati: id lead_uni:'.$LeadUniId.', cliente_id: '.$Cliente_Id.', data_estrazione'.$data_ins.', id '.$Aid.'<br> \n\n';
				$FindLeadID = "SELECT id FROM extraction_history WHERE lead_id='".$LeadUniId."' AND  cliente_id='".$Cliente_Id."' AND data_estrazione='".$data_ins."' limit 1 "; // 
					
					
				if (!$result2 = $mysqli_dest->query($FindLeadID)) {
					echo "errore select";
					$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
					$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
					fwrite($logfile, $errorStr);
					exit();
				}else {
					echo "INSERT  \n";
					if(!$result2->num_rows >0){
						//non abbiamo trovato il corrispondente in extraction history, procediamo all insert
						$Insert="INSERT INTO extraction_history (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) VALUES('".$LeadUniId."','".$Cliente_Id."', '".$data_ins."', '".$data_sblocco."', '".strtolower($tipo_vendita)."' )";
						echo "query di insert: ".$Insert. "\n\n";
						if ($mysqli_dest->query($Insert) === TRUE) {
							$count++;
							echo "INSERT riusciuto \n";
							
							//update del controllato per non riprocessare le processate
							$update="UPDATE extraction_utility SET controllato =2 where id= ".$Aid;
							echo "query di update: ".$update. "\n\n";
							if ($mysqli_dest->query($update) === TRUE) {
								$count2++;
								echo "UPDATE riusciuto \n";
							}else{
								echo "UPDATE fallito \n";
								$countFail2++;
							}
							
							
							
							
						}else{
							echo "INSERT fallito \n";
							$countFail++;
						}
						
					}else{
						echo "l'estrazione è corretta non inserisco niente \n\n";
						$countFail3++;
					}
					
					
				} 
                echo "<br>";
            
		}
	}
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

echo "INSERITE: ".$count." anagrafiche, fallite: ".$countFail. "\n";
echo "UPLODATE: ".$count2." anagrafiche, fallite: ".$countFail2." \n";
echo "estrazioni corrette non finalizzate:".$countFail3;
