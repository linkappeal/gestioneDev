<?php
     
    $to      = 'joseorlando@linkappeal.it';
    $subject = 'Script Error: update_lead_uni.php';
    $message = 'test mail ciao secondo test ';
    $headers = 'From: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
    'Reply-To: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	mail($to, $subject, $message, $headers);  
	echo "<b>invio mail errore ".$message."</b>";
 ?>