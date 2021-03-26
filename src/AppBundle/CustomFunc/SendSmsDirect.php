<?php


namespace AppBundle\CustomFunc;


class SendSmsDirect
{

    const USER_SERVICE_DIRECT           = 'LinkApp_Dir';
    const PASSWORD_SERVICE_DIRECT       = 'drhwxvic';
    const URL_SERVICE_DIRECT            = 'http://212.83.171.135:8001/api';
    const PREFIX_PHONE_NUMBER           = "39";
    const SMS_TYPE                      = "SMS";   // SMS=SENZA RISPOSTA ---  $Lead_target_ani='393202042229';=CON
    const SMS_MESSAGE                   =  "Abbiamo provveduto ad inoltrare la richiesta di cancellazione nei database nostri o dei nostri partner. Per avere info scrivere a privacy@linkappeal.it";


    private $phone_number       ;
    private $target_telefono    ;

    /**
     * SendSmsDirect constructor.
     * @param  $phone_number
     */
    public function __construct($phone_number)
    {
        $this->phone_number     =   $phone_number;
        $this->target_telefono  =   self::PREFIX_PHONE_NUMBER . $this->phone_number;

    }

//self::USER_SERVICE_DIRECT

    public function Send()
    {
        $curDir = dirname(__FILE__) ;
        $logfile = fopen($curDir. "/../../../var/logs/SmsCrossingDirectAll.txt", "a");

        $LancioResult		=array();
        $send_data='username='.self::USER_SERVICE_DIRECT.'&password='.self::PASSWORD_SERVICE_DIRECT.'&ani='.self::SMS_TYPE.'&dnis='.$this->target_telefono.'&message='.trim(rawurlencode(self::SMS_MESSAGE)).'&command=submit&serviceType=&longMessageMode=';

        fwrite($logfile, date("Y-m-d,H-i-s")."/================================================/".PHP_EOL);
        fwrite($logfile, date("Y-m-d,H-i-s")." Log per le chiamate api effettuate con Fornitore SMS CrossingDirect".PHP_EOL);
        fwrite($logfile, date("Y-m-d,H-i-s")." Chiamo URL ".self::URL_SERVICE_DIRECT." con i parametri".PHP_EOL . $send_data .PHP_EOL);


        // Invio chiamata curl per call sms service Direct
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL_SERVICE_DIRECT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded', 'Content-Length: ' . strlen($send_data)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$send_data);
        $result = curl_exec($ch);

        // **************************************
// check errori curl
        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {

                case 200:
                    // call riusciuta
                    $ResultDec=json_decode($result, true);
                    fwrite($logfile, date("Y-m-d,H-i-s")." Chiamata Api: SERVER_HTTP_CODE :  SUCCESS ## ". $http_code ." ##". PHP_EOL);
                    //fwrite($logfile, date("Y-m-d,H-i-s")."/*************************************************/ \n");
                    //fwrite($logfile, date("Y-m-d,H-i-s")."/*************************************************/ \n\n\n");
                    //$LancioResult['statoLancio']					=	1;		// SERVER_HTTP_CODE =200 -->
                    $LancioResult['SERVER_HTTP_CODE_API_RESPONSE'] = $http_code;
                    $LancioResult['id_by_fornitore'] 				= 	$ResultDec['message_id'];
                    break;
                default:
                    // call restituisce http_status_code !=200
                    fwrite($logfile, date("Y-m-d,H-i-s")." Chiamata Api: SERVER_HTTP_CODE :  ERRORE --> ## ". $http_code ." ##".PHP_EOL);
                    //fwrite($logfile, date("Y-m-d,H-i-s")."/*************************************************/ \n");
                    //fwrite($logfile, date("Y-m-d,H-i-s")." API FALLITA  :  *********************************/ \n");
                    //fwrite($logfile, date("Y-m-d,H-i-s")."/*************************************************/ \n");

            } // switch
        }else{
            // verificato errore curl
            fwrite($logfile, date("Y-m-d,H-i-s")." ERRORE CURL 			--> ## ". curl_error($ch) ." ##".PHP_EOL);
            fwrite($logfile, date("Y-m-d,H-i-s")." Number Error CURL 	--> ## ". curl_errno($ch) ." ##".PHP_EOL);
            //$LancioResult['statoLancio']					=	2;

            $LancioResult['SERVER_HTTP_CODE_API_RESPONSE']	=	999;
            $LancioResult['ErroNumber'] 					= 	curl_errno($ch);
            $LancioResult['ErrorMessage'] 					= 	curl_error($ch);

        }

        curl_close($ch);
        // FINE CHIAMATA AL SERVICE

        return $LancioResult;
        /*  OK INVIATO CON SUCCESSO
         * $LancioResult['SERVER_HTTP_CODE_API_RESPONSE']	= 200;
         *$LancioResult['id_by_fornitore'] 				    = $ResultDec['message_id'];
         *
         *          KO - SERVER_HTTP_CODE_API_RESPONSE NON 200
         * SERVER_HTTP_CODE_API_RESPONSE    = <DIVERSO DA 200>;
         *
         *          KO - ERRORE CURL
         *  $LancioResult['SERVER_HTTP_CODE_API_RESPONSE']	=	999;
         *  $LancioResult['ErroNumber'] 					= 	curl_errno($ch);
         *  $LancioResult['ErrorMessage'] 					= 	curl_error($ch);
         */


    }

}