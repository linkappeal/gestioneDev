<?php
//apri uno specifico file di log?


//dati di test
/*
$Cmessaggio="messaggio di test";
$Cid="test1";
*/

//CONVER NUMBER WITH +39 and put ina good var $Numeri[$numero['id']]=array($numero['cellulare'], $numero['id']);
$recipients=array();
foreach($numeri as $numero){
    $recipients[]='+39'.$numero[0];
}

//login data
$username='linkappeal';
$password='c4!£adnc094d';

//url
$url='https://api.mediariver.it/sendSmsBulk/send';

//Messagge obj
$MessageObj = new stdClass();
$MessageObj->recipients=$recipients;
//$MessageObj->recipients=array('+393208503794', '+39111222323');
if(!$CconRisposta OR $CconRisposta==0){
    $MessageObj->quality='S';
}else{
    $MessageObj->quality='A';
}

$MessageObj->message=$Cmessaggio;
$MessageObj->uuid=$Cid;

//purebross obj
$PureBrossObj = new stdClass();
$PureBrossObj->messages=array($MessageObj);

echo "stringa in invio:     ". json_encode($PureBrossObj);
//{"messages":[{"recipients":["+39111222333","+39111222323"],"quality":"S","message":"this message will be sent to one recipient in high quality with an alias","uuid":"xxxx-yyyy-zzzz"}]}
 
//Encode the array into JSON.
$jsonDataEncoded = json_encode($PureBrossObj);


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);

//http autenticate
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password); 
//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Content-Length: ' . strlen($jsonDataEncoded)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS,$jsonDataEncoded);


$result = curl_exec($ch);
                
curl_close($ch);
                        
echo "RISPOSTA LATO SERVER : " . $result .'<br>';
                        
$ResultDec=json_decode($result); echo "print-r: ";
print_r($ResultDec);
if(property_exists($ResultDec,'message')){
    echo "\n errore:".$ResultDec->message;
    $LancioResult['statoLancio']=0;
    //$LancioResult['ErroreLancio']='errore fornitore non presente';
	$LancioResult['ErroreLancio']=$ResultDec->message;
}else{
    echo "\n ok, id:".$ResultDec->details[0]->mr_uuid;
    $LancioResult['statoLancio']=1;
    $LancioResult['UpdateNumeri']=0;
	$LancioResult['IdcampagnaByfornitore']=$ResultDec->details[0]->mr_uuid;
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