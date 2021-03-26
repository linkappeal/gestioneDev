<?php

//settaggio del file di log
//Directory corrente
$curDir = dirname(__FILE__) ;
$arrPath = explode("/", $curDir) ;
// Qui impostiamo i parametri di connessione al DB
$config_file = $curDir."/../../../../app/config/parameters.yml"; //file di config di symfony
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
}
echo "connesso a gestione, RECUPERO MESSAGGIO DI TEST";

$SelectCampagne = "select id, campagna, messaggio, id_fornitore_sms, con_risposta  from campagne_sms where ID=67 LIMIT 1;"; // LEAD DA inviare oggi
	if (!$resultCampagne = $mysqli_dest->query($SelectCampagne)) {
        echo "errore SelectCampagne";

	}else {
		$Ccount=0;
		while ($Campagna = $resultCampagne->fetch_assoc()){
                $Ccount++;
                $Cid=$Campagna['id'];
                $Cname=$Campagna['campagna'];
                $Cmessaggio=trim(utf8_encode($Campagna['messaggio']));
			    $CconRisposta=$Campagna['con_risposta'];
                echo "\n Abbiamo recuperato la campagna: ".$Cname." con id ".$Cid.", risposta: ".$CconRisposta.".\n\n";
        }
    }
echo "recuperato messaggio. testo: \n\n".$Cmessaggio;

if($messLenght=is_gsm0338($Cmessaggio)){
    echo "\n\n messaggio gsm ok \n\n\n\n";
    //check lenght not over 160
    if ($messLenght>160){
        echo "\n\nmessaggio sopra i 160 caratteri gsm";
        
    }
}else{
    echo "\n\n messaggio in gsm KO, controllo che non superi i 70 caratteri";
    if (strlen($Cmessaggio)>70){
       echo '\n\nerrore: Messaggio non compatibile con gsm e superiore a 70 caratteri\n\n';
    }
}



/*
$cell='3   
 20 9898899 ';
echo "test sanitize cellulare: prima:".$cell.'\n\n';
$cellulareSTR=str_replace(' ', '', $cell);
$cellularePREG=preg_replace('/\s+/', '', $cell);
echo "STR:".$cellulareSTR.', PREG:'.$cellularePREG.'\n\n\n\n';
*/
$C2messaggio="Vuoi ottenere 2000 € a zero spese?In più per te 10% di sconto su polizza RCA ConTe! Clicca qui https://bit.ly/2jynt e scopri l'offerta Younited fino al 28/06";
echo "test hardcoded... messaggio. testo: \n\n".$C2messaggio;

if($messLenght=is_gsm0338($C2messaggio)){
    echo "\n\n messaggio gsm ok \n\n\n\n";
    //check lenght not over 160
    if ($messLenght>160){
        echo "\n\nmessaggio sopra i 160 caratteri gsm";
        
    }
}else{
    echo "\n\n messaggio in gsm KO, controllo che non superi i 70 caratteri";
    if (strlen($C2messaggio)>70){
        echo '\n\nerrore: Messaggio non compatibile con gsm e superiore a 70 caratteri\n\n';
    }
}



function is_gsm0338( $utf8_string ) {
    $gsm0338 = array(
        '@','Δ',' ','0','¡','P','¿','p',
        '£','_','!','1','A','Q','a','q',
        '$','Φ','"','2','B','R','b','r',
        '¥','Γ','#','3','C','S','c','s',
        'è','Λ','¤','4','D','T','d','t',
        'é','Ω','%','5','E','U','e','u',
        'ù','Π','&','6','F','V','f','v',
        'ì','Ψ','\'','7','G','W','g','w',
        'ò','Σ','(','8','H','X','h','x',
        'Ç','Θ',')','9','I','Y','i','y',
        "\n",'Ξ','*',':','J','Z','j','z',
        'Ø',"\x1B",'+',';','K','Ä','k','ä',
        'ø','Æ',',','<','L','Ö','l','ö',
        "\r",'æ','-','=','M','Ñ','m','ñ',
        'Å','ß','.','>','N','Ü','n','ü',
        'å','É','/','?','O','§','o','à'
     );
    $doublespace=array('|', '^', '€', '{', '}', '[', ']', '~');
    $len = mb_strlen( $utf8_string, 'UTF-8');
    $length=0;
    for( $i=0; $i < $len; $i++)
        if (!in_array(mb_substr($utf8_string,$i,1,'UTF-8'), $gsm0338)){
            if(in_array(mb_substr($utf8_string,$i,1,'UTF-8'), $doublespace)){
                $length++;
                $length++;
            }else{
                return false; 
            }
        }else{
            $length++;
        }

    return $length;
}
?>