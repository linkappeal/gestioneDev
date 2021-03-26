<?php
//apri uno specifico file di log?


//dati di test
/*
$Cmessaggio="messaggio di test";
$Cid="test1";
*/

//CONVER NUMBER WITH +39 and put ina good var $Numeri[$numero['id']]=array($numero['cellulare'], $numero['id']);
$recipients='';
$recipientsArray=array();
foreach($numeri as $idN=>$numero){
	if($recipients==''){
		$recipients.='39'.$numero[0];
	}else{
		$recipients.=',39'.$numero[0];
	}
	$recipientsArray['39'.$numero[0]]=$idN;
}

//login data
	$username='LinkApp_Dir';
	$password='drhwxvic';

//url
$url='http://212.83.171.135:8001/api';
//mittente
if(!$CconRisposta OR $CconRisposta==0){
    $ani='SMS';
}else{
    $ani='393202042229';
}

$Cmessaggio = rawurlencode($Cmessaggio);
$send_data='username='.$username.'&password='.$password.'&ani='.$ani.'&dnis='.$recipients.'&message='.$Cmessaggio.'&command=submit&serviceType=&longMessageMode=';

echo "--------------------------------------------------------------\n";
echo "stringa in invio:     ". $send_data;
echo "\n--------------------------------------------------------------\n";
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded', 'Content-Length: ' . strlen($send_data)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data);

		$result = curl_exec($ch);
echo "RISPOSTA LATO SERVER : " . $result .'\n';	
echo "\n##################################################################\n\n";	
	
// **************************************
// check errori curl
	if (!curl_errno($ch)) {
		switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
			case 200:  
				// call riusciuta
				$ResultDec=json_decode($result, true);
				echo " Chiamata Api: SERVER_HTTP_CODE :  200 --> ## ". $http_code ." ##\n";
				echo "\n ok";          
				$ResultDec=json_decode($result, true);
				
				$LancioResult['statoLancio']=1;
				$LancioResult['UpdateNumeri']=1;
				$LancioResult['UpdateNumeriDati']=array();
				if(count($recipientsArray)>1){
					foreach($ResultDec as $NumberResult){
						if(array_key_exists($NumberResult['dnis'], $recipientsArray)){
							$LancioResult['UpdateNumeriDati'][]=array('id_by_fornitore'=>$NumberResult['message_id'], 'stato_invio'=>1,'id'=>$recipientsArray[$NumberResult['dnis']]);
						}
					}
				}else{
					$LancioResult['UpdateNumeriDati'][]=array('id_by_fornitore'=>$ResultDec['message_id'], 'stato_invio'=>1,'id'=>$recipientsArray[$recipients]);
				}

			break;
			default: 
			// call restituisce http_status_code !=200
				echo " Chiamata Api: SERVER_HTTP_CODE :  200 --> ## ". $http_code ." ##\n";
				echo "\n errore:".$ResultDec->message;
				$LancioResult['statoLancio']=0;
				//$LancioResult['ErroreLancio']='errore fornitore non presente';
				$LancioResult['ErroreLancio']=$result;


								
		} // switch
	}else{
		// verificato errore curl
		echo " ERRORE CURL 			--> ## ". curl_error($ch) ." ##\n";
		echo " Number Error CURL 	--> ## ". curl_errno($ch) ." ##\n";

		
	}

		curl_close($ch);

		
		
	
	
// *********************************************************************************************************                        
?>