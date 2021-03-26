<?php

$PErr=array("errore generico","provenienza mancante o non valida","numero di telefono non valido", "numero di telefono già inserito nel sistema");


//for($d=0;$d<10;$d++){


//login data
$username='LinkApp_Dir';
$password='drhwxvic';
$dnis='393208503794';
$message='questi sono 161 caratteriquesti sono 161 caratteriquesti sono 160 caratteriquesti sono 161 caratteriquesti sono 160 caratteriquesti sono 161 caratteri12345678912';
$ani='sms test';

//url
$url='http://212.83.171.135:8001/api?';

$send_data='username='.$username.'&password='.$password.'&messageId=CRONET-9dd712824b05-a8dda02e9ab4&command=query';


						
	echo 'post contents: '.$send_data.'<br>';
	try{
	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data);

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		echo '\n info http status:'.$info["http_code"];	
		
		curl_close($ch);
								
		echo "<br><br>RISPOSTA LATO SERVER : " . $result .'<br><br><br>';
								
		$ResultDec=json_decode($result);
		print_r($ResultDec);
		
	} catch (Exception $ex) {
		echo "non riuscito il try";
	}
//}
						
?>