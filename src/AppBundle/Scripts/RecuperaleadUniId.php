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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_recuperaleaduniId.txt", "a"); //FILE di LOG

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
	$SelectAna = "select id, source_db, source_tbl, source_id from extraction_utility where id_lead_uni is null order by rand()"; // LEAD DA inviare oggi
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
		$countZero=0;
		while ($row = $result->fetch_assoc()){
            //cerco il cap nella tabella cap_da_a
                $oid=$row['id'];
                $db=$row['source_db'];
                $tbl=$row['source_tbl'];
                $sid=$row['source_id'];
               
				$FindLeadID = "SELECT id FROM lead_uni WHERE source_db='".$db."' AND  source_tbl='".$tbl."' AND source_id='".$sid."'  limit 1 "; //
				//echo $FindLeadID;
				//die();
				if (!$result2 = $mysqli_dest->query($FindLeadID)) {
					echo "errore recupero id leaduni";
					$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
					$errorStr .= date("Y-m-d,H-i-s")." Query: " . $strSql . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_dest->errno . "\n";
					$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_dest->error . "\n";
					fwrite($logfile, $errorStr);
					exit();
				}else {
					echo "UPLODO  \n";
					$R=0;
					$lid=0;
					while ($row2 = $result2->fetch_assoc()){
						$lid=$row2['id'];
						echo "Trovata lid: ".$lid." \n";
					}
					if($lid==0){
						$countZero++;
						echo 'Non abbiamo trovato la lead in leaduni \n';
					}
					$Uplo = "UPDATE extraction_utility SET id_lead_uni =".$lid." WHERE id=".$oid.""; //
					try{
						if ($mysqli_dest->query($Uplo) === TRUE) {
							//inserisco il campo appena inserito nell'array
							//echo " \n __v__v__campo inserito in a_lead_uni_copy_extra_value ";
							$count++;
							echo "update riusciuto \n";
						}else{
							echo "update fallito 0 \n";
							$countFail++;
						}
					}catch(PDOException $e){
						echo "update fallito \n";
						$countFail++;
					}
				}
				sleep(1);
			} 
		}
	
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

echo "uplodate: ".$count." anagrafiche, fallite: ".$countFail.", non trovate: ".$countZero;
