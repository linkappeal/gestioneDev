<?php
//Lancia test (e campagne) sms

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

$logfile = fopen($curDir."/log/".date("Ymd")."test_campagna_sms.txt", "a"); //FILE di LOG

fwrite($logfile, date("Y-m-d,H-i-s")."\n\n\n\n/************************************************/ \n");
fwrite($logfile, date("Y-m-d,H-i-s")."\n File che lancia i test per le campagne sms che lo richiedono \n\n");


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
echo "connesso a gestione";
fwrite($logfile, "\n Connesso a gestione! \n\n");

/* LOGICA

- Seleziona i numeri di test associati alla campgna da extraction_sms 
- lancia il mess. campagna ai numeri trovati tramite script del fornitore

*/

//var stabili
$oggi=date("Y-m-d");
try{
    //- Selezioniamo tutte le campagne in campagne_sms che abbiano lo stato a 0 e data lancio =oggi setta lo stato a uno
    //select id, campagna from campagne_sms
	$SelectCampagne = "select id, campagna, messaggio, id_fornitore_sms, con_risposta  from campagne_sms where stato=0 AND data_lancio ='".$oggi."' LIMIT 1;"; // LEAD DA inviare oggi
	if (!$resultCampagne = $mysqli_dest->query($SelectCampagne)) {
        echo "errore SelectCampagne";
        fwrite($logfile, "\n errore SelectCampagne.  \n\n");
		$errorStr  = "Error: Failed to make a MySQL query, here is why: \n";
		$errorStr .= " Query: " . $SelectCampagne . "\n Errno: " . $mysqli_dest->errno . "\n Error: " . $mysqli_dest->error . "\n";
		fwrite($logfile, $errorStr);
		esci();
	}else {
		$Ccount=0;
		while ($Campagna = $resultCampagne->fetch_assoc()){
                $Ccount++;
                $Cid=$Campagna['id'];
                $Cname=$Campagna['campagna'];
                $Cmessaggio=trim(utf8_encode($Campagna['messaggio']));
                $IdFornitoreSms=$Campagna['id_fornitore_sms'];
                $CconRisposta=$Campagna['con_risposta'];
                fwrite($logfile, "\n Abbiamo recuperato la campagna: ".$Cname." con id ".$Cid.", risposta: ".$CconRisposta.".\n\n");
                echo "Abbiamo recuperato la campagna: ".$Cname." con id ".$Cid.", risposta: ".$CconRisposta;
                if(update_stato_campagna(1, $Cid)){

                    //controllo i dati campagna
                    //$datiCampagnaOK=1;
                    if(!strlen($Cmessaggio)>0){
                        update_stato_campagna(6, $Cid, 'errore: manca il messaggio');
                        esci();
                    }
                    if(!$IdFornitoreSms>0){
                        update_stato_campagna(6, $Cid, 'errore: manca il fornitore');
                        esci();
                    }
                    
                    //recuperiamo fornitore
                    $SelectFornitoreSms="SELECT * FROM fornitori_sms WHERE id= ".$IdFornitoreSms;
                    echo "Recuperiamo fornitore con id: ".$IdFornitoreSms."  \n\n";
                    fwrite($logfile, "\n Recuperiamo fornitore con id: ".$IdFornitoreSms."  \n\n");
                    if (!$resultFornitore = $mysqli_dest->query($SelectFornitoreSms)) {
                        echo "errore SelectFornitoreSms";
                        fwrite($logfile, "\n errore SelectFornitoreSms.  \n\n");
                        $errorStr  = "Error: Failed to make a MySQL query, here is why: \n";
                        $errorStr .= " Query: " . $SelectFornitoreSms . "\n Errno: " . $mysqli_dest->errno . "\n Error: " . $mysqli_dest->error . "\n";
                        fwrite($logfile, $errorStr);
                        update_stato_campagna(6, $Cid, 'errore: il fornitore sms con id: '.$IdFornitoreSms.'non è stato trovato a db.');
                        esci();
                    }
                    while ($Fornitore = $resultFornitore->fetch_assoc()){
                        $ScriptLancio=$Fornitore['script_lancio'];
                        $maxSmsS=$Fornitore['max_sms_s'];
                        //setto un default max sms/s
                        if(!$maxSmsS>0){
                            echo "il max sms/s del fornitore era vuoto. setto default a 50.  \n\n";
                            fwrite($logfile, "\n il max sms/s del fornitore era vuoto. setto default a 50.  \n\n");
                            $maxSmsS=50;
                        }
                        $nomeFornitore=$Fornitore['nome'];
                    }
                    if(!strlen($ScriptLancio)>0){
                        update_stato_campagna(6, $Cid, 'errore: manca lo script di lancio del fornitore a db');
                        esci();
                    }
                    
                    
                    //convertiamo il messaggio in gsm7 e controlliamo lunghezza
                    if($messLenght=is_gsm0338($Cmessaggio)){
                        echo "\n messaggio gsm ok \n\n";
                        fwrite($logfile, "\n messaggio gsm ok \n\n");
                        //check lenght not over 160
                        if ($messLenght>160){
                            echo "<br>messaggio sopra i 160 caratteri gsm";
                            update_stato_campagna(6, $Cid, 'errore: messaggio sopra i 160 caratteri gsm');
                            esci();
                        }
                    }else{
                        echo "<br> messaggio in gsm KO, controllo che non superi i 70 caratteri \n\n";
                        fwrite($logfile, "\n messaggio in gsm KO, controllo che non superi i 70 caratteri  \n\n");
                        if (strlen($Cmessaggio)>70){
                            update_stato_campagna(6, $Cid, 'errore: Messaggio non compatibile con gsm e superiore a 70 caratteri');
                            esci();
                        }
                    }


                    echo "Preleviamo numeri di test.  \n\n";
                    fwrite($logfile, "\n Preleviamo numeri di test.  \n\n");
                    // recupera il fornitore
                    //- Seleziona i numeri di test associati alla campgna da extraction_sms 
                    //seleziona i numeri di test
                    $SelectTest="SELECT id, cellulare  FROM extraction_sms WHERE campagna_id='".$Cid."' AND test=1 AND stato_invio=0";
                    $CicloNumeri=array();
                    $Numeri=array();
                    if (!$resultNumeri = $mysqli_dest->query($SelectTest)) {
                        echo "errore recupero numeri di test";
                        fwrite($logfile, "\n errore recupero numeri di test. Passiamo alla prossima campagna  \n\n");
                        $errorStr  = date("Y-m-d,H-i-s")." Error: Failed to make a MySQL query, here is why: \n";
                        $errorStr .= date("Y-m-d,H-i-s")." Query: " . $SelectTest . "\n Errno: " . $mysqli_dest->errno . "\n Error: " . $mysqli_dest->error . "\n";
                        fwrite($logfile, $errorStr);
                        update_stato_campagna(6, $Cid, 'errore: query di recupero numeri non andata a buon fine');
                        esci();
                    }else {
                        $countNumeri=0;
                        $countNumeriAss=0;
                        while ($numero = $resultNumeri->fetch_assoc()){
                            //dividiamo numeri per $maxSmsS 
                            $cellulare=preg_replace('/\s+/', '', $numero['cellulare']);
                            if(is_numeric($cellulare)){
                                $Numeri[$numero['id']]=array($cellulare, $numero['id']);
                                $countNumeri++;
                                $countNumeriAss++;
                                if($countNumeri==$maxSmsS){
                                    $countNumeri=0;
                                    $CicloNumeri[]=$Numeri;
                                    $Numeri=array();
                                }
                            }
                        }
                        //aggiungo gli ultimi numeri a ciclo numeri
                        if(count($Numeri)>0){
                            $CicloNumeri[]=$Numeri;
                        }
                        //se abbiamo ritrovato dei numeri procedo a luuppare lo script del fornitore con una pausa di un sec
                        if(count($CicloNumeri)>0){
                            echo "Abbiamo recuperato ".$countNumeriAss." numeri di test divisi in ".count($CicloNumeri)." lanci. \n\n";
                            fwrite($logfile, "\n Abbiamo recuperato ".$countNumeriAss." numeri di test  divisi in ".count($CicloNumeri)." lanci.  \n\n");
                            $countLanci=0;
                            $IdCampagnaByFornitore='';
                            $lanciOk=0;
                            foreach($CicloNumeri as $numeri){
                                $countLanci++;
                                echo "\n Lancio n. ".$countLanci."  \n\n";
                                fwrite($logfile, "\n Lancio n. ".$countLanci."  \n\n");
                                echo "Lanciamo lo script del fornitore: ".$nomeFornitore.".php \n\n";
                                fwrite($logfile, "\n Lanciamo lo script del fornitore: ".$nomeFornitore.".php \n\n");
                                //preset di $LancioResult
                                $LancioResult=array();
                                $LancioResult['statoLancio']=0;
                                include('fornitori_sms_script/'.$ScriptLancio.'.php');
                                //il file del fornitore restituira un json chiamato $LancioResult contente le seguenti info in forma di array: $statoLancio lo stato del lancio restituito dal fornitore o settato dallo script, $IdcampagnaByfornitore eventuale id assegnato dal fornitore a questa campagna, $ResultNumeri stato dello invio per ogni numero e eventuali id assegnati dal fornitore al numero, UpdateNumeri stabilisce se si richiede 1 upload di ogni numero singolarmente o in massa

                                if($LancioResult['statoLancio']==1){
                                    echo "\n Il lancio è andato OK  \n\n";
                                    fwrite($logfile, "\n Il lancio è andato OK \n\n");
                                    $lanciOk=1;

                                    //vedo se è sato assegnato un id campagna
                                    if(array_key_exists('IdcampagnaByfornitore',$LancioResult)){
										$IdCampagnaByFornitore=$LancioResult['IdcampagnaByfornitore'];
                                    }

                                    //controllo se il fornitore ci restituisce dati che debbano essere uplodati 1 a 1 per numero O UPLODARE TUTTI I NUMERI CONTEMPORANEAMENTE
                                    if($LancioResult['UpdateNumeri']==1){
                                        //update stato numeri e eventuale id numeri
                                        foreach($LancioResult['UpdateNumeriDati'] as $NumeroDato){
                                            $UpdateNumeroIdForntore="";
                                            if(array_key_exists('id_by_fornitore', $NumeroDato)){
                                                $UpdateNumeroIdForntore=", id_by_fornitore='".$NumeroDato['id_by_fornitore']."' ";
                                                
                                            }else{
                                                $NumeroDato['id_by_fornitore']= "non presente";
                                            }
                                            $UpdateStatoNumero="UPDATE extraction_sms set stato_invio = ".$NumeroDato['stato_invio']." ".$UpdateNumeroIdForntore." WHERE id =".$NumeroDato['id'];//TODO BIND PARAM
                                            if ( $mysqli_dest->query($UpdateStatoNumero)  === TRUE ) {
                                                echo "update stato numero RIUSCITO per il numero con id: ".$NumeroDato['id'].", id del fornitore: ".$NumeroDato['id_by_fornitore']."<br>";
                                                fwrite($logfile, "\n update stato numero RIUSCITO per il numero con id: ".$NumeroDato['id'].", id del fornitore: ".$NumeroDato['id_by_fornitore']."  \n\n");
                                            }else{
                                                echo "Attenzione stato numero FALLITO per il numero con id: ".$NumeroDato['id'].", id del fornitore: ".$NumeroDato['id_by_fornitore']."<br>";
                                                fwrite($logfile, "\n Attenzione stato numero FALLITO per il numero con id: ".$NumeroDato['id'].", id del fornitore: ".$NumeroDato['id_by_fornitore'].".  \n\n");
                                            }
                                        }
                                    }else{
                                        $UpdateStatoNumeri="UPDATE extraction_sms set stato_invio = 1 WHERE id in (".implode(',', array_keys($Numeri)).")";
                                        if ( $mysqli_dest->query($UpdateStatoNumeri)  === TRUE ) {
                                            echo "update stato numerii RIUSCITO <br>";
                                            fwrite($logfile, "\n update stato numerii RIUSCITO  \n\n");
                                        }else{
                                            echo "Attenzione stato numerii FALLITO <br>";
                                            fwrite($logfile, "\n Attenzione stato numerii FALLITO .  \n\n");
                                        }
                                    }
                                    //scrivo eventuale id_campagna_by fornitore nella tabella apposita
                                    //scrivo il numero di messaggi inviati nella tabella campagne_sms_reports?
                                }else{
                                    echo "\n Il lancio è andato KO  setto a 6 e esco\n\n";
                                    fwrite($logfile, "\n Il lancio è andato KO setto a 6 e esco \n\n");
                                    $lanciOk=0;
                                    break;
                                }
								sleep(1);
                            }
                            
                            
                            

                            $UpdateIdCampagnaByFornitore='';
                            if($IdCampagnaByFornitore!=''){
                                $UpdateIdCampagnaByFornitore=", id_by_fornitore_sms='".$IdCampagnaByFornitore."' ";//TODO BIND PARAM
                            }
                            //update lancio result sulla base dello stato restituito,  update eventual id campagna fornitore 
                            if($lanciOk==1){
                                //lancio ok 
                                
                                //update della campagna
                               
                                $UpdateStato2 = "UPDATE campagne_sms set stato =2 ".$UpdateIdCampagnaByFornitore." WHERE id= ".$Cid ;//
									echo "\n $UpdateStato2 \n\n";
                                if ( $mysqli_dest->query($UpdateStato2)  === TRUE ) {
                                    echo "update campagna stato a 2 RIUSCITO <br>";
                                    fwrite($logfile, "\n update campagna stato a 2 RIUSCITO.  \n\n");
                                }else{
                                    echo "Attenzione update campagna stato a 2 FALLITO <br>";
                                    fwrite($logfile, "\n Attenzione update campagna stato a 2 FALLITO.  \n\n");
                                }
                            }else{
                                //lancio fallito
                                echo "Lo stato del lancio  restituito dal forniotre è un errore. Settiamo la campagna con stato a 6 passo alla prossima campagna. Errore restituito dal fornitore: ".$LancioResult['ErroreLancio']." \n\n";
                                fwrite($logfile, "\n Non abbiamo recuperato nessun numero di test. Settiamo la campagna con stato a 6 passo alla prossima campagna . Errore restituito dal fornitore: ".$LancioResult['ErroreLancio']."  \n\n");
                                 //update lo stato a 6
                                $erroreLancio="il fornitore ha dato KO al lancio n ".$countLanci." con il seguente errore: ".$LancioResult['ErroreLancio'];
                                $UpdateStato6 = "UPDATE campagne_sms set stato =6 ".$UpdateIdCampagnaByFornitore.", info='".$erroreLancio."' WHERE id= ".$Cid ;// 
                                if ( $mysqli_dest->query($UpdateStato6)  === TRUE ) {
                                    echo "update campagna stato a 6 RIUSCITO <br>";
                                    fwrite($logfile, "\n update campagna stato a 6 RIUSCITO.  \n\n");
                                }else{
                                    echo "update campagna stato a 6 FALLITO <br>";
                                    fwrite($logfile, "\n update campagna stato a 6 FALLITO.  \n\n");
                                }
                                esci();
                            }

                             
                        }else{
                            echo "Non abbiamo recuperato nessun numero di test. Settiamo la campagna con stato a 6.  \n\n";
                            fwrite($logfile, "\n Non abbiamo recuperato nessun numero di test. Settiamo la campagna con stato a 6.  \n\n");
                             //update lo stato a 6
                            update_stato_campagna(6, $Cid, 'Errore: Non abbiamo recuperato nessun numero di test');
                            
                            esci();
                        }

                    }
				}else {
                    esci();
                }
                

                echo "<br>";
            
		}
	}
	
}catch(PDOException $e){
	echo "Errore connessione: ";
}

if($Ccount==0){
	echo "\n nessuna campagna trovata. \n ";
}
esci();


//FUNZIONI
function update_stato_campagna($stato, $Cid, $info=''){
    global $logfile, $mysqli_dest;
    
    $UpdateStato6 = "UPDATE campagne_sms set stato =".$stato.", info='".$info."' WHERE id= '".$Cid."'" ;// 
    if ( $mysqli_dest->query($UpdateStato6)  === TRUE ) {
        echo "update campagna-".$Cid." allo stato ".$stato." RIUSCITO <br>";
        fwrite($logfile, "\n update campagna-".$Cid." allo stato ".$stato." RIUSCITO... info: ".$info."  \n\n");
        return true;
    }else{
         echo "update campagna-".$Cid." allo stato ".$stato." FALLITO <br>";
        fwrite($logfile, "\n update campagna-".$Cid." allo stato ".$stato." FALLITO... info: ".$info."  \n\n");
        return false;
    }

}


//return false if is not gsm 7 bit | message lenght in gsm if OK
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



function esci(){
    global $logfile;
    echo "\n Esco <br>\n\n";
    fwrite($logfile, "\n Esco  \n\n");
    //unloock the file
    die();
}