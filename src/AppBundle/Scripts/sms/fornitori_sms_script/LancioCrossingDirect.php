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


$send_data='username='.$username.'&password='.$password.'&ani='.$ani.'&dnis='.$recipients.'&message='.$Cmessaggio.'&command=submit&serviceType=&longMessageMode=';


echo "stringa in invio:     ". $send_data;

//$send_data=json_encode($send_data);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);

//http autenticate
//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded', 'Content-Length: ' . strlen($send_data)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data);


$result = curl_exec($ch);
$info = curl_getinfo($ch);
$crossingstato=$info["http_code"];
echo '\n info http status:'.$crossingstato;

curl_close($ch);
                        
echo "RISPOSTA LATO SERVER : " . $result .'<br>';


if($crossingstato!=200){
    echo "\n errore:".$ResultDec->message;
    $LancioResult['statoLancio']=0;
    //$LancioResult['ErroreLancio']='errore fornitore non presente';
	$LancioResult['ErroreLancio']=$result;
}else{
    echo "\n ok";
	                        
	$ResultDec=json_decode($result, true);
	/*echo "print-r: ";
	print_r($ResultDec);*/
	
    $LancioResult['statoLancio']=1;
    $LancioResult['UpdateNumeri']=1;
	//$LancioResult['IdcampagnaByfornitore']=$ResultDec->details[0]->mr_uuid;
	$LancioResult['UpdateNumeriDati']=array();
	//se abbiamo lanciato piu di un numero luppiamo recuperando il cell altrimenti
	if(count($recipientsArray)>1){
		foreach($ResultDec as $NumberResult){
			if(array_key_exists($NumberResult['dnis'], $recipientsArray)){
				$LancioResult['UpdateNumeriDati'][]=array('id_by_fornitore'=>$NumberResult['message_id'], 'stato_invio'=>1,'id'=>$recipientsArray[$NumberResult['dnis']]);
			}
		}
	}else{
		$LancioResult['UpdateNumeriDati'][]=array('id_by_fornitore'=>$ResultDec['message_id'], 'stato_invio'=>1,'id'=>$recipientsArray[$recipients]);
	}
	
}

//dati da restituire
/*
$LancioResult
    statoLancio 1/0
    IdcampagnaByfornitore se il fornitore la fornisce quella con prefisso fornitore altrimenti campagna id nostra con prefisso fornitore
    UpdateNumeri (id, cellulare,  stato_invio, id_by_fornitore, )
    ErroreLancio
    RispostaFornitore serialize della risposta se vogliamo scriverla /dipende dal fornitore

// result test data
$LancioResult['statoLancio']=1;
$LancioResult['UpdateNumeri']=0;
$LancioResult['IdcampagnaByfornitore']=$ResultDec->details[0]->mr_uuid;*/
?>