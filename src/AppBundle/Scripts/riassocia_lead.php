<?php
//riassocia delle lead con nuovo time

$lock_file = __DIR__ . '/_riassocia_lead.lock';
if (file_exists($lock_file )) {
		die('Spiacente, un\'altra istanza dello Script � in esecuzione...');
		$errorStr  = date("Y-m-d,H-i-s")." Error: Altra istanza in esecuzione: \n";		
		$errorStr .= date("Y-m-d,H-i-s")." Exiting";
		exit();
		mailError('Attenzione il file miscelata_light non � partito perche � presente un file di lock. elimina manualmente il file di lock.');	
		}
$myfile = fopen($lock_file , "w") or die("Unable to create lock file!");
error_reporting(E_ERROR);


//settaggio del file di log
//Directory corrente
$curDir = dirname(__FILE__) ;
$arrPath = explode("/", $curDir) ;

$logfile = fopen($curDir."/log/".date("Ymd")."_riassocia_lead.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, "\n Avvio Script per riassocia_lead\n\n");
echo "\n\n /****  Avvio Script per riassocia_lead****/\n\n";

$data=date("Y-m-d H:i:s");
//DBS


/*************** 3) CONNESSIONE A DB DESTINAZIONE *****************/
// Qui impostiamo i parametri di connessione al DB
$config_file = $curDir."/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);

$camp="tiscali-internet";
$tabFrom='mail18_copy_before_17genn2020';
$tabTo='mail18';
fwrite($logfile, "\n Camp: ".$camp." \n\n");
	
//esclusiva per te:
$DBesclusivaperte =  array('host'=>'46.16.95.229', 'db'=>'esclusiv_cpl','username'=>'esclusiv_cplus', 'password'=>'HYpN%Ep!Mhxg');

//test connections:

	$dbvarname=$DBesclusivaperte;
	$mysqli_par = new mysqli($dbvarname['host'],
                          $dbvarname['username'],
						  $dbvarname['password'],
                          $dbvarname['db']);
	if ($mysqli_par->connect_errno) {
		echo "Non siamo riusciti a conneterci al db \n\n";
		fwrite($logfile, "Non siamo riusciti a conneterci a: ".$DBName."; \n\n");
		Smail("Non siamo riusciti a conneterci al db");
	}else{
		echo "Siamo riusciti a conneterci  al db \n\n ";
		fwrite($logfile, "Siamo riusciti a conneterci al db \n\n ");
		
		$rand = rand(3,4);
		$QUeryRec="SELECT * FROM ".$tabFrom." WHERE PHONE1 NOT IN (SELECT PHONE1 from ".$tabTo.") AND DOWNLOAD=0 LIMIT ".$rand;
		echo "query:".$QUeryRec;
		$mysqli_par_sel = new mysqli($dbvarname['host'],
                          $dbvarname['username'],
						  $dbvarname['password'],
                          $dbvarname['db']);

		if (!$Sresult = $mysqli_par_sel->query($QUeryRec)) {
			echo "errore recupero ana \n";
			Smail("abbiamo avuto un errore sul recupero delle anagrafiche nella riassociazione della camp:".$camp);
			fwrite($logfile, "errore recupero ana \n");
			$errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
			$errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectMiscelata . "\n";
			$errorStr .= date("Y-m-d,H-i-s")."Errno: " . $mysqli_get_misc->errno . "\n";
			$errorStr .= date("Y-m-d,H-i-s")."Error: " . $mysqli_get_misc->error . "\n";
			fwrite($logfile, $errorStr);
			esci();
		}else {
			$countANA=0;
			$iNSERTqueRy="INSERT INTO ".$tabTo." ";
			$FIELDS='(';
			$valuesArr=array();
			$VALUES='VALUES ';
			while ($ana = $Sresult->fetch_assoc()){
				//range di 89 minuti in secondi
				$subTimeRand=rand(0,5340);
				$RandTime=time()-$subTimeRand;
				$newdat=date("Y-m-d H:i:s",$RandTime);
				$thisArr=array();
				$tVALUES='';
				
				
				$countField=0;
				$tVALUES.=' (';
				
				
				foreach($ana as $key=>$val){
					if($key!='ID'){
						if($countANA==0){
							if($countField==0){
								$FIELDS.=$key;
							}else{
								$FIELDS.=', '.$key;
							}
						}
						if($key=='DATA'){
							$value=$newdat;
						}else{
							$value=$val;
						}
						if($countField==0){
							$tVALUES.="'".$value."'";
						}else{
							$tVALUES.=", '".$value."'";
						}
						
						$countField++;
					}
				}
				$tVALUES.=')';
				$valuesArr[$RandTime]=$tVALUES;
				$countANA++;
			}
			echo "recuperate ".$countANA." anagrafiche \n\n";
			fwrite($logfile, "recuperate ".$countANA." anagrafiche \n\n");
			if($countANA>0){
				
				ksort($valuesArr);
				$counTval=0;
				foreach($valuesArr as $K=>$v){
					if($counTval==0){
						$VALUES.=$v;
					}else{
						$VALUES.=",".$v;
					}
					$counTval++;
				}
				$FIELDS.=') ';
				$iNSERTqueRy.=$FIELDS.$VALUES;
				$mysqli_parI = new mysqli($dbvarname['host'],
                          $dbvarname['username'],
						  $dbvarname['password'],
                          $dbvarname['db']);
				if ($mysqli_parI->query($iNSERTqueRy) === TRUE) {
					//success
					echo "inserite con successo:".$countANA." \n\n\n";
					fwrite($logfile, "inserite con successo:".$countANA." \n\n\n");
				}else{
					//error send email
					Smail("Attenzione insert di riassociazione di ".$camp." non andato a buon termine");
					fwrite($logfile, "Attenzione insert di riassociazione di ".$camp." non andato a buon termine \n\n");
				}
			}else{
				//invia email fine dell import
				 Smail("riassociazione di ".$camp." terminata");
				 fwrite($logfile, "riassociazione di ".$camp."  terminata \n\n");
			}
			
		}	
	}

esci();





 function Smail($Msg){
     
   $to      = 'francesca@linkappeal.it,joseorlando@linkappeal.it';
    $subject = 'Script alert: riassocia_lead.php';
    $message = 'Results: ' . print_r( $Msg, true );
    $headers = 'From: esclusivaperte@gestione.linkappeal.it' . "\r\n" .
    'Reply-To: joseorlando@linkappeal.it' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);  
     
 }

function esci(){
	echo '----Stiamo uscendo dal file \n\n';
	fwrite($logfile, '----Stiamo uscendo dal file \n\n');
	 global $lock_file;
	 unlink($lock_file);
	 exit();
}
?>

