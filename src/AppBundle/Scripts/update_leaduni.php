<?php
/**************************************************************************************
 * NOME DEL FILE  : update_leaduni.php
 * AUTORE:        : Ettore Amato, Luca Meles
 * DATA CREAZIONE:  03/04/2017
 *
 **************************************************************************************/

$lock_file = __DIR__ . '/_update_leaduni.lock';
if (file_exists($lock_file )) {
    die('Spiacente, un\'altra istanza dello Script è in esecuzione...');
    $errorStr  = date("YmdHis")." Error: Altra istanza in esecuzione: \n";
    $errorStr .= date("YmdHis")." Exiting";
    mailError($errorStr);
    exit();
}
$myfile = fopen($lock_file , "w") or die("Unable to create lock file!");

error_reporting(E_ERROR);

// VARIABILI GLOBALI
$LABEL_DATI_TERZI = 'trattamento_dati_terzi';

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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_update_leaduni.txt", "a"); //FILE di LOG

fwrite($logfile, date("YmdHis")."   /************************************************/");
fwrite($logfile, date("YmdHis")."   Avvio Script per l'aggiornamento della tabella Lead unificate\n\n");
echo "\n\n/**** Avvio Script per l'aggiornamento della tabella Lead unificate ****/\n\n";

/*************** GLOBAL VARIABLES *****************/

$databases = array(
    //'offertea_cpl', # 12/12/2017 disabilitata la lettura delle tabella dal dominio offerteadsl smarcall in quanto non più utilizzato per lead generation
    //'offertee_cpl', //# 12/12/2017 disabilitata la lettura delle tabella dal dominio offerteepromozioni.com in quanto non più utilizzato per lead generation
    'promoh3g_cpl',
    'offertep_cpl',
    'promotel_cpl',
    'promozio_db',
    'offertes_cpl',	// offertesumisura.com aggiunto il 15-03-2018
    'esclusiv_cpl',	// esclusiva-perte.it aggiunto il 23-03-2018
    //'superoff_cpl',	// superofferte-energia.it aggiunto il 23-03-2018
);

$processed_tables = array();

/*************** FIELD BLACKLIST *****************/

$field_blacklist = array('zanpid',
    'privacycliente',
    'privacyversioncliente');

/*************** jm-fix creo variabile che conterrà identificativi basati su string: source_db.source_tbl.source_id *****************/

$UnicInsert=array();




/*************** CONNESSIONE A DB DESTINAZIONE *****************/
// Qui impostiamo i parametri di connessione al DB

$config_file = $curDir."/../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);
//$db_name = 'symfony'; // database di debug
$num_insert = 0;
$campaign_insert = 0;

$mailErrors = array();

$tot_added_leads = 0;
//var_dump($db_config);

$mysqli_dest = new mysqli($db_config['parameters']['database_host'],
    $db_config['parameters']['database_user'],
    $db_config['parameters']['database_password'],
    $db_config['parameters']['database_name']);

if ($mysqli_dest->connect_errno) {

    $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno: " . $mysqli_dest->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error: " . $mysqli_dest->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";

    fwrite($logfile, $errorStr);

    mailError($errorStr);
    esci();
}

/*************** CONNESSIONE A DB ORIGINE *****************/
// Qui impostiamo i parametri di connessione al DB

$mysqli_src = new mysqli( $db_config['parameters']['src_database_host'],
    $db_config['parameters']['src_database_user'],
    $db_config['parameters']['src_database_password']);

if ($mysqli_src->connect_errno) {

    $errorStr  = date("YmdHis")." Error2: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno2: " . $mysqli_src->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error2: " . $mysqli_src->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";

    fwrite($logfile, $errorStr);

    mailError($errorStr);
    esci();
}

/*************** CONNESSIONE A DB UTILITY *****************/
// Qui impostiamo i parametri di connessione al DB

$mysqli_utils = new mysqli( $db_config['parameters']['utils_database_host'],
    $db_config['parameters']['utils_database_user'],
    $db_config['parameters']['utils_database_password'],
    $db_config['parameters']['utils_database_name']);

if ($mysqli_utils->connect_errno) {

    $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno: " . $mysqli_utils->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error: " . $mysqli_utils->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";

    fwrite($logfile, $errorStr);

    mailError($errorStr);
    esci();
}

$start_time = microtime(true);

/**************************************************/
//Recuperiamo tutti i brand già inseriti e relativi id

$existing_brands = array();

$existing_brands = getDBBrands($mysqli_dest);

/**************** LEAD UNI ****************/

$arrLeaduniFields = getLeaduniColumns($mysqli_dest, $db_config['parameters']['database_name'], 'lead_uni');

// Popoliamo una struttura dati relativa alla lead_uni
// contenente i db, le mail operation e le mail già trattate

$arrLeadUniData = getLeadUniData($mysqli_dest);

//$str = "GENERO L'ARRAY DALLA TABELLA LEADUNI" . print_r($arrLeadUniData, true) . PHP_EOL . PHP_EOL;
//fwrite($logfile, date("YmdHis").$str);
//echo $str;
//print_r($arrLeadUniData);

/**************** DATABASES DATA **********/

$arrDBStructure = getDBStructure($mysqli_src, $databases);
/*
  $tabelle => array('promolas_cpl' => Array 
                            (
                            [mail93] => Array
                                    (
                                    [0] => AZIENDA
                                    [1] => PIVA
*/

/********* CICLO PRINCIPALE SULLE MAIL OPERATION *******/

foreach ($arrDBStructure as $db => $arrMo){

    $str = " ** Processing DB ** ".$db." \n\n";
    fwrite($logfile, date("YmdHis").$str);
    echo $str;

    $orphans = $arrMo['tabelle_orfane'];
    unset($arrMo['tabelle_orfane']);

    //jm- perchè richiamare come variabili all'interno del ciclo e non prima??
    $arrLeadUniDataByMop = $arrLeadUniData['mop'];

    $arrLeadUniAllTbls = $arrLeadUniData['all'];

    //print_r(array_keys($arrLeadUniDataByMop));
    //print_r($arrLeadUniData['all']);
    //Controlliamo se il DB ha delle entries nella lead_uni
    if (in_array($db, array_keys($arrLeadUniDataByMop))){


        //Per ogni DB cicliamo su tutte le mail operations
        foreach($arrMo as $mo => $arrMail){

            $str = " Processing Mail operation: ".$mo." \n";
            fwrite($logfile, "\n".date("YmdHis").$str);
            echo $str;

            //Se c'è una nuova tabella mail operation 
            //dobbiamo aggiungere tutte le info delle campagne
            //e tutte le lead ad esse associate
            //altrimenti passiamo ad analizzarne il contenuto
            //echo "\n".$mo;
            //print_r(array_keys($arrLeadUniData[$db]));
            $str = "Verifico se la mail operation: " . $mo . " -> DB: " . $db . " è presente".PHP_EOL;
            fwrite($logfile, "\n".date("YmdHis").$str);
            echo $str;
            if (in_array($mo, array_keys($arrLeadUniDataByMop[$db]))){

                $str = "La mail operation: " . $mo . " è presente".PHP_EOL;
                fwrite($logfile, "\n".date("YmdHis").$str);
                echo $str;

                foreach($arrMail as $mail){

                    //$str = "Verifico se la tabella mail: " . $db . " > " . $mo . " > " . $mail . " è presente in : -- ".PHP_EOL . print_r($processed_tables[$db],true) . PHP_EOL;
                    //fwrite($logfile, "\n".date("YmdHis").$str);
                    //echo $str;

                    if(in_array($mail, $processed_tables[$db])){
                        $str = "La tabella mail: " . $mail . " è presente nell'array delle tabelle già processate".PHP_EOL;
                        fwrite($logfile, "\n".date("YmdHis").$str);
                        echo $str;
                        $str = " Skipping alreay processed campaign: ". $db . " > " . $mo . " > " . $mail . " \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        continue;

                    }else{
                        $str = "La tabella mail: " . $mail . " NON è presente nell'array delle tabelle già processate.".PHP_EOL;
                        fwrite($logfile, "\n".date("YmdHis").$str);
                        echo $str;
                        $processed_tables[$db][] = $mail;
                    }

                    $str = " Processing campaign: ".$db . " > " . $mo . " > " . $mail." \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

//					echo $mail ." from ".$mo."\n";
                    $str = "INFORMAZIONI SULL'ARRAY arrLeadUniDataByMop[db][mo] > ". $db . " > ".$mo . PHP_EOL;
                    //$str .= print_r(array_keys($arrLeadUniDataByMop[$db][$mo]), true) . PHP_EOL;
                    //$str .= print_r($arrLeadUniDataByMop[$db][$mo][$mail]) . PHP_EOL;
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    if(in_array($mail, array_keys($arrLeadUniDataByMop[$db][$mo]))){

                        $str = " Campaign: ".$DB . " > " . $mo . " > " . $mail." data is already present in DB \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        //Aggiungiamo tutte le lead il cui source id è maggiore di 
                        $str = " Aggiungiamo tutte le lead il cui source id è maggiore di" . $arrLeadUniDataByMop[$db][$mo][$mail]['brand_id'];
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;
                        //quello ricavato nella lead_uni
                        $str = " Adding new Campaign: ".$mail." leads in lead_uni table. Max existing id: ".$arrLeadUniDataByMop[$db][$mo][$mail]['max_id']."\n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail, $arrLeadUniDataByMop[$db][$mo][$mail]['max_id'], $arrLeadUniDataByMop[$db][$mo][$mail]['campagna_id'], $arrLeadUniDataByMop[$db][$mo][$mail]['brand_id']);

                    }
                    //Dobbiamo aggiungere le info riguardanti la campagna
                    else{
                        $str = "Dobbiamo aggiungere le info riguardanti la campagna ".$db ." > " . $mo . " > " . $mail."\n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        $str = " Campaign: ".$mail." data has no entries in lead_uni table \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        /* @edit 26-03-18
                         * Luca Meles
                         * Recupero l'id della campagna basandomi sulle campagne attive nella tabella a_landing_cliente
                         */
                        // recupero l'id della campagna
                        $id_campagna = getCampagnaIdFromLandingCliente($mysqli_dest, $db, $mail);

                        $str = "Per la campagna sul database: " . $db . " mail operation: " . $mo . " > tabella: " . $mail . " ho recuperato l'id campagna: " . $id_campagna ."\n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;


                        $infoCampaign = getCampagnaInfo($mysqli_dest, $db, $mail, $id_campagna);
                        $campaign_id = $infoCampaign['id'];
                        $brand_id = $infoCampaign['brand_id'];

                        if (!$campaign_id){
                            $str = " La CAMPAGNA SULLA TABELLA: ".$mail." NON HA INFORMAZIONI NELLA TABELLA CAMPAGNE. SCRIVO I DATI NELLA TABELLA... \n";
                            fwrite($logfile, date("YmdHis").$str);
                            echo $str;

                            $c_id = addCampaignDataToTblCampagna($mysqli_src, $mysqli_dest, $db, $mo, $mail);
                            $max_id = 'all';

                        }
                        else {
                            $str = " TROVATA LA CAMPAGNA PER LA TABELLA : ".$mail." SU " . $db . " NELLA TABELLA CAMPAGNE IN GESTIONE. \n";
                            fwrite($logfile, date("YmdHis").$str);
                            echo $str;

                            $c_id = $campaign_id;
                            $max_id = $arrLeadUniDataByMop[$db][$mo][$mail]['max_id'];

                        }

                        $str = " Adding new Campaign: ".$mail." leads in lead_uni table \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail, $max_id, $c_id, $brand_id);

                    }

                }

            }
            //dobbiamo aggiungere tutte le info delle campagne
            //e tutte le lead ad esse associate
            else {

                $str = "E' STATA TROVATA UNA NUOVA MAIL OPERATION: ".$mo." SU " . $db . "\n";
                fwrite($logfile, date("YmdHis").$str);
                echo $str;

                foreach($arrMail as $mail){

                    if(in_array($mail, $processed_tables[$db])){

                        $str = " Skipping alreay processed campaign: ". $db . " > " . $mo . " > " . $mail . " \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;

                        continue;

                    }
                    else $processed_tables[$db][] = $mail;

                    $str = "\n Processing new campaign: ".$mail." \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    /* @edit 26-03-18
                     * Luca Meles
                     * Recupero l'id della campagna basandomi sulle campagne attive nella tabella a_landing_cliente
                     */
                    // recupero l'id della campagna
                    $id_campagna = getCampagnaIdFromLandingCliente($mysqli_dest, $db, $mail);

                    $str = "Per la campagna sul database: " . $db . " mail operation: " . $mo . " > tabella: " . $mail . " ho recuperato l'id campagna: " . $id_campagna ."\n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    $infoCampaign = getCampagnaInfo($mysqli_dest, $db, $mail, $id_campagna);
                    $campaign_id = $infoCampaign['id'];
                    $brand_id = $infoCampaign['brand_id'];

                    if (!$campaign_id){
                        $str = "\n Campaign: ".$mail." has no data in DB. Writing campaign info... \n";
                        fwrite($logfile, date("YmdHis").$str);
                        echo $str;
                        $max_id = 'all';
                        $c_id = addCampaignDataToTblCampagna($mysqli_src, $mysqli_dest, $db, $mo, $mail);
                    }
                    else {//Faccio comunque una verifica, per gestire ad esempio mail associate a più campagne
                        $c_id = $campaign_id;
                        $max_id = $arrLeadUniAllTbls[$db][$mail]['max_id'];

                    }
                    addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail, $max_id, $c_id, $brand_id);

                }

            }

        }

        /**** CICLO SULLE ORFANE DEL DB ****/
        $str = " ** Processing Orphan Tables of ".$db."** \n\n";
        fwrite($logfile, date("YmdHis").$str);
        echo $str;

        //print_r($orphans);        

        foreach($orphans as $mail){

            //se la tabella orfana è già presente nella lead_uni aggiungiamo le lead nuove
            if(array_key_exists($mail['nome_tabella'], $arrLeadUniAllTbls[$db] )){

                $str = $mail['nome_tabella']." has already leads in lead_uni table. Max existing id: ".$arrLeadUniAllTbls[$db][$mail['nome_tabella']]['max_id']."\n";
                fwrite($logfile, date("YmdHis").$str);
                echo $str;

                if(empty($mail['tabella_master'])){

                    $str = " No Master campaing \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail['nome_tabella'], $arrLeadUniAllTbls[$db][$mail['nome_tabella']]['max_id'], '');
                }
                else{



                    $str = " Found a master campaign :".$mail['tabella_master']." \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    /** @edit 26-03-18
                     * Luca Meles
                     * Recupero l'id della campagna basandomi sulle campagne attive nella tabella a_landing_cliente
                     * se l'id della campagna è null, allora la funzione getCampagnaInfo recupererà le informazioni
                     * basandosi sui valori di mail operation e tabellamail
                     **/
                    // recupero l'id della campagna
                    $id_campagna = getCampagnaIdFromLandingCliente($mysqli_dest, $db, $mail['tabella_master']);

                    //La tabella master sarà già stata inserita in precedenza (a meno che non sia anch'essa orfana)
                    $infoCampaign = getCampagnaInfo($mysqli_dest, $db, $mail['tabella_master'], $id_campagna);
                    addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail['nome_tabella'], $arrLeadUniAllTbls[$db][$mail['nome_tabella']]['max_id'], $infoCampaign['id'], $infoCampaign['brand_id']);

                }

            }
            else {

                $str = $mail['nome_tabella']." has no leads in lead_uni table. Adding them all \n";
                fwrite($logfile, date("YmdHis").$str);
                echo $str;

                if(empty($mail['tabella_master'])){

                    $str = " No Master campaign \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail['nome_tabella'], 'all', '');
                }
                else{

                    $str = " Found a master campaign :".$mail['tabella_master']." \n";
                    fwrite($logfile, date("YmdHis").$str);
                    echo $str;

                    /** @edit 26-03-18
                     * Luca Meles
                     * Recupero l'id della campagna basandomi sulle campagne attive nella tabella a_landing_cliente
                     * se l'id della campagna è null, allora la funzione getCampagnaInfo recupererà le informazioni
                     * basandosi sui valori di mail operation e tabellamail
                     **/
                    // recupero l'id della campagna
                    $id_campagna = getCampagnaIdFromLandingCliente($mysqli_dest, $db, $mail['tabella_master']);

                    //La tabella master sarà già stata inserita in precedenza (a meno che non sia anch'essa orfana)
                    $infoCampaign = getCampagnaInfo($mysqli_dest, $db, $mail['tabella_master'], $id_campagna);
                    addNewLeadsToTblLeadUni($mysqli_src, $mysqli_dest, $db, $mail['nome_tabella'], 'all', $infoCampaign['id'], $infoCampaign['brand_id']);

                }
            }


        }
        /******  FINE CICLO ORFANE *****/

    }
    //Se non ci sono entry su lead_uni per il $db selezionato, dobbiamo aggiungere tutte le campagne e le lead contenute
    else{
        //jm- perchè non lo fa??
        $str = " DB ".$db." non ha ancora lead nella tabella lead_uni: bisogna aggiungere tutte le campagne e le lead contenute\n";
        fwrite($logfile, date("YmdHis").$str);
        echo $str;

    }

}

$str =  "Totale lead aggiunte: ".$tot_added_leads."\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

/*** AGGIORNAMENTO LEAD VENDUTE DA TABELLA ESTERNA EXTRACTION ***/

$str = "\n\nUpdating Extraction data with external table\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

$res = updateExtractionTbl($mysqli_utils, $mysqli_dest);
//$res['tot_ext_add_leads'] = 0;
//$res['tot_ext_upd_leads'] = 0;

$str =  "Totale lead estratte aggiunte: ".$res['tot_ext_add_leads']."\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

$str =  "Totale lead estratte aggiornate: ".$res['tot_ext_upd_leads']."\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

/****************************************************************/
/*** AGGIORNAMENTO LEAD VENDUTE DA TABELLA ESTERNA EXTRACTION ***/

$str = "\n\nSto aggiornando il Contatore\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

$resCont = updateContatoreTbl($mysqli_utils, $mysqli_dest);

$str =  "Totale lead aggiunte nel contatore: ".$resCont."\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

$str =  "Totale lead estratte aggiornate: ".$res['tot_ext_upd_leads']."\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;

/****************************************************************/

if (count($mailErrors)>0) {
    $str = " Sending Mail with reported errors \n";
    fwrite($logfile, date("YmdHis").$str);
    echo $str;
    mailError($mailErrors);
    // distruggo il file di lock
    unlink($lock_file);
}

updateHistoryScripts($mysqli_dest);

$mysqli_src->close();
$mysqli_dest->close();
$mysqli_utils->close();

$end_time = microtime(true);
$total_time_run = ($end_time - $start_time)/60;
$total_time_run = number_format($total_time_run, 2);
$str =  "Tempo di esecuzione: ". $total_time_run ." Min\n";
fwrite($logfile, date("YmdHis").$str);
echo $str;
// distruggo il file di lock
unlink($lock_file);
/****************************************** FINE ESECUZIONE SCRIPTS ************************************************************************************/





/****************************************** SEZIONE FUNZIONI UTILI  ************************************************************************************/

// Crea una struttura dati arrLeadUniDataByMop così definita:
// [db1] -> [mailop1] -> [mail1] -> (isActive = 1, max_id = 56)
//                    -> [mail2] -> (isActive = 0, max_id = 156)
//                    -> [mailN] -> (isActive = 1, max_id = 12)
//       ->   [mailopN] -> [mail] -> (isActive = 1, max_id = 456)
//       -> [mail_orfana1] -> max_id = 12
//       -> [mail_orfana2] -> max_id = 1
//       -> [mail_orfanaN] -> max_id = 57
// [dbN] ->
// ed una arrAllDBTables che per ogni db riporta tutte le tabelle
// la struttura viene creata sui dati presenti in lead_uni e non sul database source
function getLeadUniData($mysqli){

    global $logfile;

    $errStr = " Loading data from lead_uni \n";
    fwrite($logfile, date("YmdHis").$errStr);
    echo $errStr;

    //scrittura nella tabella delle campagne delle info relative
    $strSql  = "select lead_uni.source_db as db, 
				dbtabmo as mo, source_tbl as tbl, 
				campagna.brand_id as brand_id, 
				max(lead_uni.source_id) as maxid, 
				is_active, 
				campagna.id as idcampagna ";
    $strSql .= " from lead_uni left join campagna on campagna_id = campagna.id";
    $strSql .= " group by db, dbtabmo, source_tbl";

    //echo $strSql;

    if (!$result = $mysqli->query($strSql)) {

        $errorStr  = date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n";
        $errorStr .= date("YmdHis")." Query: " . $strSql . "\n";
        $errorStr .= date("YmdHis")."Errno: " . $mysqli->errno . "\n";
        $errorStr .= date("YmdHis")."Error: " . $mysqli->error . "\n";

        fwrite($logfile, $errorStr);

        mailError($errorStr);
        unlink($lock_file);
        esci();
    }

    else {

        while ($ret = $result->fetch_assoc()){

            if (!empty($ret['mo'])){

                $arrLeadUniDataByMop[$ret['db']][$ret['mo']][$ret['tbl']] = array('isActive' => $ret['is_active'], 'max_id' => $ret['maxid'], 'campagna_id' => $ret['idcampagna'], 'brand_id' => $ret['brand_id']);

            }

            $arrAllDBTables[$ret['db']][$ret['tbl']] = array('isActive' => $ret['is_active'], 'max_id' => $ret['maxid'], 'campagna_id' => $ret['idcampagna']);


        }

    }

    $arrayFinale = array("mop" => $arrLeadUniDataByMop, "all" => $arrAllDBTables);
    $textArrayGen = '////##################### ARRAY DI MAIL OPERATION GENERATO DALLA FUNZIONE ####################//'.PHP_EOL;
    //$arrayGenerato = print_r($arrayFinale, true);  
    //$textArrayGen .=  $arrayGenerato .PHP_EOL;
    $textArrayGen .= '////##################### FINE ARRAY DI MAIL OPERATION GENERATO DALLA FUNZIONE ####################//'.PHP_EOL;
    fwrite($logfile, $textArrayGen);

    return $arrayFinale;

}

function getDBStructure($conn, $databases){

    $tabelle = array();

    /* CREAZIONE DELL'ARRAY UNICO $tabelle CON I VALORI DELLE COLONNE */
    /* Per ogni DB riportiamo tutte le tabelle contenute
    /*
    $tabelle => array('promolas_cpl' => Array 
                            (
                            [mail93] => Array
                                    (
                                    [0] => AZIENDA
                                    [1] => PIVA
    */

    foreach($databases as $database){
        $showdatabase = "SHOW TABLES FROM ".$database.";";
        $result = $conn->query($showdatabase);
        if ($result->num_rows > 0) {
            // output data of each row
            while($row = $result->fetch_assoc()){
                $nome_tabella = $row['Tables_in_' . $database];
                //$tabelle[$database][] = $nome_tabella;
                $query_columns = "SELECT DISTINCT COLUMN_NAME 
                                                                    FROM INFORMATION_SCHEMA.COLUMNS
                                                                    WHERE TABLE_NAME = '".$nome_tabella."';";
                $result_col = $conn->query($query_columns);
                if ($result_col) {
                    if ($result_col->num_rows > 0) {
                        // output data of each row
                        while($row_col = $result_col->fetch_assoc()){
                            $tabelle[$database][$nome_tabella][] = $row_col['COLUMN_NAME'];
                        }
                    }
                }else{
                    echo "errore";
                }
            }
        } else {
            //echo "0 results";
        }
    }
    //print_r($tabelle);
    /**
     * GENERO L'ARRAY RESULT PER RICAVARE TUTTE LE TBELLE PROVENIENTI DALLE MAIL_OPERATION

    $result => array(
    [mail_operation] => array( nome_tabella_associata1, nome_tabella_associata2, nome_tabella_associata3)
    )
     *
     **/
    $result=array();
    $tab_fatte = array();
    $all = array(); // contenente i database -> mail_operation -> tabella1, tabella2, tabella3, ...
    $tabellemail = array();
    foreach($tabelle as $database => $tabella){
        //echo $database . "";
        $mail_operation_tmp = array();
        foreach($tabella as $tab => $col){
            //echo $tab . "";

            if (strpos($tab, 'mail_operation') !== false) {

                if (strpos($tab, '_old') !== false) continue;
                //echo $tab . " è una mail operation". PHP_EOL;
                // prelevo la tabella  dalla mail operation:
                $sql_mo = "SELECT DBTAB FROM ".$tab;
                $conn->select_db($database);
                $result_mo = $conn->query($sql_mo);
                //echo mysqli_error($conn);
                if ($result_mo->num_rows > 0) { // se la mail_operation ha tabelle mail all'interno le salvo in result
                    while($row_mo = $result_mo->fetch_assoc()){
                        $result[$database][$tab][] = $row_mo['DBTAB']; // prendo tutte le tab associate alle mail_operation
                        // creo l'arrey delle mail_operation
                        $mail_operation_tmp[$tab][]=$row_mo['DBTAB'];

                        // salvo le tabelle già calcolate
                        $tab_fatte[] = $row_mo['DBTAB'];
                        //echo $row_mo['DBTAB'] . PHP_EOL;
                        //$nome_tabella = $row_mo['Tables_in_' . $database];
                    }
                }
            }else{ // se è una mail normale??
                //if(!in_array($tab,$tab_fatte)){ // se la tabella non è stata già lavorata
                if (strpos($tab, 'mail') !== false) {
                    $tabellemail[$database][] = $tab; // solo tabelle mail, non mail_operation
                }
                //}
            }
        } // fine foreach tabella
        /* CREO L'ARRAY PER DEFINIRE A QUALE TABELLA è ASSOCIATA LA MAIL_OPERATION */
        if(!empty($mail_operation_tmp)){
            $all[$database][] = $mail_operation_tmp;
        }

        $result[$database]['tabelle_orfane'] = getDBTabelleOrfane($conn, $database, $tabellemail[$database], $all[$database][0]);
    }

    //print_r($tab_fatte);
    //print_r($tabellemail);
    //print_r($result);
    //print_r($all);

    return $result;

}

function addCampaignDataToTblCampagna($mysql_src, $mysql_dest, $db_src, $table_mo_src, $table_mail_src){

    global $logfile;
    global $existing_brands;
    global $mailErrors;

    echo "Sto elaborando il db: " . $db_src . PHP_EOL;
    fwrite($logfile, "Sto elaborando il db: " . $db_src . PHP_EOL);
    $mysql_src ->select_db($db_src);

    $mysql_src->set_charset("utf8");

    $sql   = "SELECT * FROM ".$table_mo_src;
    $sql  .= " where dbtab = '".$table_mail_src."'";

    fwrite($logfile, $sql);
    echo $sql;

    if (!$result = $mysql_src->query($sql)) {

        $errorStr  = date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n";
        $errorStr .= date("YmdHis")." Query: " . $sql . "\n";
        $errorStr .= date("YmdHis")."Errno: " . $mysql_src->errno . "\n";
        $errorStr .= date("YmdHis")."Error: " . $mysql_src->error . "\n";

        fwrite($logfile, $errorStr);

        mailError($errorStr);
        unlink($lock_file);
        esci();
    }

    else {

        while ($ret = $result->fetch_assoc()){

            $id_brand = 0;
            //Se il brand non è presente in DB lo aggiungiamo ed aggiorniamo l'array dei valori esistenti
            //echo strtolower($ret['BRAND']);
            //var_dump($existing_brands);

            if (!array_key_exists(strtolower($ret['BRAND']), $existing_brands)){

                echo "Il brand: " . $ret['BRAND'] . " non esiste nella tabella brand. Lo inserisco" . PHP_EOL;
                fwrite($logfile, "Il brand: " . $ret['BRAND'] . " non esiste nella tabella brand. Lo inserisco". PHP_EOL);

                $id_brand = addBrandToDB($mysql_dest, $ret['BRAND']);
                if(!empty($id_brand)){ $existing_brands[strtolower($ret['BRAND'])]['id'] = $id_brand; }
                //var_dump($existing_brands);
                //echo $id_brand;
            }
            else {
                $id_brand = $existing_brands[strtolower($ret['BRAND'])]['id'];
                //echo $id_brand;
                echo "Il brand: " . $ret['BRAND'] . " è già presente nella tabella brand." . PHP_EOL;
                fwrite($logfile, "Il brand: " . $ret['BRAND'] . " è già presente nella tabella brand.". PHP_EOL);
            }

            //print_r($ret); 
            //scrittura nella tabella delle campagne delle info relative
            $strSql  = "insert into campagna  ";
            $strSql .= " (brand_id, settore, nome_offerta, source_db, source_id, dbtabmo, dbtab, tipo_campagna, target_campagna,";
            $strSql .= " data_start, data_end, leadout_path, shot_path, is_active, optin, ";
            if (isset($ret['IDPRIVACY'])) $strSql .= " id_privacy,";
            $strSql .= " disable_js_validation, disable_php_validation, is_published)";
            $strSql .= " values ";
            $strSql .= " ('".$id_brand."','".$ret['SETTORE']."','".addslashes($ret['NAME'])."','".$db_src."','".$ret['ID']."','".$table_mo_src."','".$table_mail_src."','".strtolower($ret['TYPE'])."','".addslashes(strtolower($ret['TIPOLOGIA_CAMPAGNA']))."', ";
            $strSql .= "  '".$ret['STARTDATE']."','".$ret['ENDDATE']."','".$ret['LEADOUTPATH']."','".$ret['SHOTPATH']."','".$ret['ACTIVE']."','".$ret['OPTIN']."', ";
            if (isset($ret['IDPRIVACY'])) $strSql .= " '".$ret['IDPRIVACY']."',";
            $strSql .= "  '".$ret['DISABLE_JS_VALIDATION']."','".$ret['DISABLE_PHP_VALIDATION']."','".$ret['PUBLISHED']."') ";

            fwrite($logfile, date("YmdHis")."SQL Inserting campaign: ".$strSql."\n");

            if ($mysql_dest->query($strSql) === TRUE) {
                $id = $mysql_dest->insert_id;
            } else {
                fwrite($logfile, date("YmdHis")."Error inserting campaign: " . $strSql . PHP_EOL . $mysql_dest->error);
                echo "Error inserting campaign: " . $strSql . PHP_EOL . $mysql_dest->error;

                $errorStr = date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n";
                $errorStr = date("YmdHis")." Query: " . $sql . "\n";
                $errorStr = date("YmdHis")."Errno: " . $mysql_src->errno . "\n";
                $errorStr = date("YmdHis")."Error: " . $mysql_src->error . "\n";

                fwrite($logfile, $errorStr);

                $mailErrors[] = $errorStr;

            }

        }
    }

    return $id; //da vedere    


}

function addNewLeadsToTblLeadUni($mysql_src, $mysql_dest, $db_src, $table_mail_src, $last_id, $campagna_id = 0, $brand_id){

    global $logfile;
    global $tot_added_leads;
    global $mailErrors;

    $mysql_src->select_db($db_src);

    $sql   = "SELECT * FROM ".$table_mail_src;
    if(($last_id != 'all') && ($last_id != '')) $sql  .= " where id > ".$last_id;

    echo $sql;

    $str = "Controllo se ci sono nuove lead dalla tabella: ".$db_src . " > " . $table_mail_src . PHP_EOL . "Last_id passato alla funzione: |" . $last_id . "|" . PHP_EOL;
    $str .= "con la query: " . PHP_EOL . $sql;
    fwrite($logfile, date("YmdHis").$str);
    echo $str;

    if (!$result = $mysql_src->query($sql)) {

        $errorStr  = date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n";
        $errorStr .= date("YmdHis")." Query: " . $sql . "\n";
        $errorStr .= date("YmdHis")."Errno: " . $mysql_src->errno . "\n";
        $errorStr .= date("YmdHis")."Error: " . $mysql_src->error . "\n";

        fwrite($logfile, $errorStr);

        mailError($errorStr);
        unlink($lock_file);
        esci();
    }

    else {

        $str = " La query ha prodotto risultati" . PHP_EOL;
        fwrite($logfile, date("YmdHis").$str);
        echo $str;
        //print_r($result);
        $str = " Found ".$result->num_rows." new leads to add \n";
        fwrite($logfile, date("YmdHis").$str);
        echo $str;

        while ($ret = $result->fetch_assoc()){

            //print_r($ret);
            $ret = array_change_key_case($ret, CASE_LOWER);

            $parent_id = 0;
            $_parent_id = false;
            if(isset($ret['phone1']) && !empty($ret['phone1']) && $ret['phone1']!='non presente'){
                $_parent_id = checkForDuplicates('cellulare',$ret['phone1'], $mysql_dest);
            }else{ // se il phone1 (cellulare è vuoto) provo l'email.
                if(isset($ret['email']) && !empty($ret['email']) && $ret['email']!='non presente'){
                    $_parent_id = checkForDuplicates('email',$ret['email'], $mysql_dest);
                }
            }
            if($_parent_id){
                $parent_id = $_parent_id;
            }
            $ret['parent_id'] = $parent_id;

            $retval = prepareInsertStatement($mysql_dest, $db_src, $table_mail_src, $ret, $campagna_id, $brand_id);

            if(isset($retval['sql']) AND $retval['sql']!=''){ //jm-fix : evito di scrivere una nuova entruy nell'array se 'sql'ovvero la query è vuota
                $arrSqlInsert[] = $retval;
            }

        }

        //Scriviamo nel db
        $num_leads = 0;
        foreach($arrSqlInsert as $sqlInsert){
            //echo $sqlInsert;
            fwrite($logfile, $sqlInsert['sql']);
            if ($mysql_dest->query($sqlInsert['sql']) === TRUE) {
                $id = $mysql_dest->insert_id;

                //print_r($sqlInsert['gia_cliente']);
                if(!empty($sqlInsert['gia_cliente'])) setGiaClienteSi($mysql_dest, $id, $sqlInsert['gia_cliente']);
                if(!empty($sqlInsert['extra_values'])) foreach($sqlInsert['extra_values'] as $key => $val){
                    associateExtraFieldValueToLead($mysql_dest, $id, $val);
                }

                $num_leads++;
                $tot_added_leads++;

            } else {
                fwrite($logfile, date("YmdHis")."Error: " . $sqlInsert['mysql'] . PHP_EOL . $mysql_dest->error);
                echo "Error: " . $sqlInsert . PHP_EOL . $mysql_dest->error;

                $errorStr = date("YmdHis")."ERROR INSERTING LEAD: ". $sqlInsert['mysql'] . "\n" . PHP_EOL ." QUERY INSERIMENTO:\n " . PHP_EOL .  $sqlInsert['sql'] .  PHP_EOL . $mysql_dest->error;

                $mailErrors[] = $errorStr;

            }

        }


        $str = " Added ".$num_leads." new leads\n\n";
        fwrite($logfile, date("YmdHis").$str);
        echo $str;
    }

    return ; //$arrResults;  

}


function connectToDb($db_name){

    global $db_config;
    global $logfile;

    $mysqli = new mysqli($db_config['parameters']['database_host'],
        $db_config['parameters']['database_user'],
        $db_config['parameters']['database_password'],
        $db_name);

    if ($mysqli->connect_errno) {

        $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
        $errorStr .= date("YmdHis")." Errno: " . $mysqli->connect_errno . "\n";
        $errorStr .= date("YmdHis")." Error: " . $mysqli->connect_error . "\n";
        $errorStr .= date("YmdHis")." Exiting";

        fwrite($logfile, $errorStr);
        mailError($errorStr);
        esci();
    }

    else return $mysqli;


}

function returnMailOperation($allMops, $nome_tabella){
    //echo "nome_tabella".$nome_tabella;
    //print_r($allMops);
    foreach($allMops as  $mop => $arrTabelle){

        if(in_array($nome_tabella,$arrTabelle)){
            //echo "\n mail operation: ".$mop."\n";
            return $mop;
        }
    }
    return false;
}

//Restituiamo per il DB specificato le tabelle senza una mail operation associata
function getDBTabelleOrfane($mysqli, $database_name, $arrDBMailTables, $allDb){

    $arrOrphanTbls = array();
    //print_r($arrDBMailTables);
    foreach($arrDBMailTables as $k => $nome_tabella){
        //itero tutte le tabelle
        //echo  $nome_tabella . PHP_EOL;
        //ricavo la mail operation per ogni tabella
        $mail_operation_name = returnMailOperation($allDb, $nome_tabella);
        //echo $mail_operation_name;
        $mysqli->select_db($database_name);
        //$conn->select_db($database_name);
        if($mail_operation_name){ // la tabella è presente in una mail_operation (molto probabilmente non è una notpassed)
            // recupero tabella master nel caso _notpassed
            if (strpos($nome_tabella, '_notpassed') !== false) { // se è una notpassed, verifico che ci sia la tabella di associazione
                $sql_np = "SELECT DBTAB FROM ".$mail_operation_name ." WHERE DBTAB = '".$nome_tabella."'";
                $result_np = $mysqli->query($sql_np);
                //echo mysqli_error($conn);
                if ($result_np->num_rows <= 0) { // la notpassed non è dichiarata in mail_operation, recuperaro la tabella mailXX di riferimento
                    $atab_master = explode('_',$nome_tabella,2);
                    $tab_master = $atab_master[0];
                }else{ // se la notpassed è dichiarata nella mail_operation, essa diventa la sua tabella master
                    $tab_master = $nome_tabella;
                }
            }else{ // se non è una _notpassed non eseguo controllo sulla sua tabella di riferimento e lascio il suo nome come tabella master
                $tab_master = $nome_tabella;
            }
            // -- 	fine recupero tabella master --

        }else{ // la tabella non è presente in nessuna mail_operation (molto probabilmente è una _notpassed)

            if (strpos($nome_tabella, '_notpassed') !== false) { //se è notpassed recupero la tabella principale
                $atab_master = explode('_',$nome_tabella,2);
                $tab_master = $atab_master[0];
                $arrOrphanTbls[] = array('nome_tabella' => $nome_tabella, 'tabella_master' => $tab_master);
            }else{ // tabella ORFANA
                // se è una tabella mail oppure mailxx la includo nella lista (escludendo le mailtoken).
                if (preg_match("/"."^mail$|^mail[0-9]{0,}(?!token)"."/is", $nome_tabella)===1){
                    // controllo se la tabella è vuota: se vuota non la includo nel listato
                    $sql_tv = "SELECT * FROM ".$nome_tabella;
                    $result_tv = $mysqli->query($sql_tv);
                    //echo mysqli_error($conn);
                    if ($result_tv->num_rows <= 0) { // tabella vuota: salto!
                        continue;
                    }
                    $arrOrphanTbls[] = array('nome_tabella' => $nome_tabella, 'tabella_master' => '');
                }else{
                    continue; //tabella non mailxx: salto!
                }
            }

        }



    }

    //$conn->close();
    //print_r($arrOrphanTbls);
    return $arrOrphanTbls;


}

/**
 * @Pars: mysqli $mysqli string $db, string $mail (mailXXX)
 * la funzione retituisce l'id_campagna associato alla landing attualmente attiva
 * Ogni cliente ha una propria tabella mail associata, la funzione preleva i dati partendo dai clienti
 * attivi, nel caso non trova nulla restituisce come valore id_campagna NULL. La funzione solitamente
 * viene richiamata prima della funzione getCampagnaInfo che porta come parametro opzionale l'id della campagna.
 * Nel caso alla funzione getCampagnaInfo viene passato un id campagna NULL, viene prelevato l'id in base al
 * database e alla tabella email passata.
 */

function getCampagnaIdFromLandingCliente($mysqli, $db, $mail){


    global $logfile;

    $sql   = "SELECT campagna_id FROM a_landing_cliente ";
    $sql  .= " where dbCliente = '".$db."' and mailCliente = '".$mail."' ";
    $sql  .= " AND clienteAttivo = 1";
    $sql  .= " ORDER BY id DESC LIMIT 0,1 ";

    //echo $sql;
    //fwrite($logfile, $sql);

    $str = "Prelevo l'id della campagna dalla tabella a_landing_cliente con la query:" . PHP_EOL;
    $str .= $sql . PHP_EOL;
    fwrite($logfile, $str);
    echo $str;

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");

        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        //writeMailforFailed();

        esci();
    }

    else {

        $ret = $result->fetch_assoc();

        //$str = "Informazioni reperite: " . print_r($ret, true) . PHP_EOL;
        //fwrite($logfile, $str);
        //echo $str;

    }

    if(!empty($ret)) {
        $campagna_id = $ret['campagna_id'];
    }else {
        $campagna_id = null;
    }

    return $campagna_id;

}


function getCampagnaInfo($mysqli, $db, $mail, $id_campagna = null){

    global $logfile;

    $sql   = "SELECT id, brand_id FROM campagna ";
    if(!empty($id_campagna) && is_numeric($id_campagna)){
        $sql  .= " where id = " . $id_campagna;
    }else{
        $sql  .= " where source_db = '".$db."' and dbtab = '".$mail."'";
    }

    //echo $sql;
    //fwrite($logfile, $sql);

    $str = "Prelevo le informazioni della campagna con la query:" . PHP_EOL;
    $str .= $sql . PHP_EOL;
    fwrite($logfile, $str);
    echo $str;

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");

        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        //writeMailforFailed();

        esci();
    }

    else {

        $ret = $result->fetch_assoc();

        //$str = "Informazioni reperite: " . print_r($ret, true) . PHP_EOL;
        //fwrite($logfile, $str);
        //echo $str;

    }

    if(!empty($ret)) {$id = $ret['id']; $brand_id = $ret['brand_id'];}
    else { $id = 0; $brand_id = 0;}

    return array('id' => $id, 'brand_id' => $brand_id);

}

function prepareInsertStatement($mysqli, $db_src, $table_mail_src, $arrValues, $campagna_id, $brand_id){

    global $logfile;
    global $arrLeaduniFields;
    global $field_blacklist;
    global $LABEL_DATI_TERZI;
    global $UnicInsert; //jm-fix richiamo un array contente le chiavi univoche delle lead per controllare doppioni
    $extra_values = array();

    $giacliente = '';



    //-- jm-fix QUERY INSERT IN LEAD UNI
    //creo identificativo
    /*************** jm-fix creo variabile che conterrà identificativi basati su string: source_db.source_tbl.source_id *****************/
    $identificativoInsert=$db_src.$table_mail_src.$arrValues['id'];
    if(!in_array($identificativoInsert, $UnicInsert)){
        $UnicInsert[]=$identificativoInsert;


        $strSql  = "insert into lead_uni ";
        $strSql .= "set ";
        $strSql .= "source_db  = '".$db_src."',";
        $strSql .= "source_tbl = '".$table_mail_src."', ";
        $strSql .= "source_id  = '".$arrValues['id']."', ";
        if (!empty($campagna_id)) $strSql .= "campagna_id = '".$campagna_id."', ";

        foreach($arrValues as $column => $value){

            switch(strtolower($column)){

                case 'id':              break;

                case 'name':            if(($value == 'non presente') || empty($value)) $strSql .= "nome  = null,";
                else if ((empty($arrValues['surname'])) ||
                    ($arrValues['surname'] == 'non presente') ||
                    ($arrValues['surname'] == 'NAME-BINDED')){

                    $strSql .= "nome  = '".addslashes(splitNome(array(0 => $value)))."',";
                    $strSql .= "cognome  = '".addslashes(splitCognome(array(0 => $value)))."',";
                }
                else {
                    $strSql .= "nome  = '".addslashes($value)."',";
                }
                    break;

                case 'surname':         if(!empty($value) && ($value != 'non presente') && ($value != 'NAME-BINDED')){
                    $strSql .= "cognome  = '".addslashes($value)."',";
                }
                    break;
                case 'azienda':         if(($value == 'non presente') || empty($value)) $strSql .= "ragione_sociale  = null,";
                else $strSql .= "ragione_sociale  = '".addslashes($value)."',";
                    break;

                case 'piva':            if(($value == 'non presente') || empty($value)) $strSql .= "partita_iva  = null,";
                else $strSql .= "partita_iva  = '".addslashes($value)."',";
                    break;

                case 'codicefiscale':   if(($value == 'non presente') || empty($value)) $strSql .= "codice_fiscale  = null,";
                else $strSql .= "codice_fiscale  = '".addslashes($value)."',";
                    break;

                case 'phonefisso':      if(($value == 'non presente') || empty($value)) $strSql .= "tel_fisso  = null,";
                else $strSql .= "tel_fisso  = '".addslashes($value)."',";
                    break;

                case 'phone1':          if(($value == 'non presente') || empty($value)) $strSql .= "cellulare  = '',";
                else $strSql .= "cellulare  = '".addslashes(trim($value))."',";
                    break;

                case 'email':           if(($value == 'non presente') || empty($value)) $strSql .= "email  = null,";
                else $strSql .= "email  = '".addslashes($value)."',";
                    break;

                case 'strada':          if(($value == 'non presente') || empty($value)) $address = '';
                else $address = addslashes($value);
                    break;

                case 'civico':          if(($value == 'non presente') || empty($value)) $civico = '';
                else $civico = addslashes($value);
                    break;

                case 'restown':         if(($value == 'non presente') || empty($value)) $strSql .= "citta  = null,";
                else $strSql .= "citta  = '".addslashes($value)."',";
                    break;

                case 'cap':             if(($value == 'non presente') || empty($value)) $strSql .= "cap  = null,";
                else $strSql .= "cap  = '".addslashes($value)."',";
                    break;

                case 'resprovince':     if(($value == 'non presente') || empty($value)) $strSql .= "provincia  = null,";
                else $strSql .= "provincia  = '".addslashes($value)."',";
                    break;

                case 'quartiere':       if(($value == 'non presente') || empty($value)) $strSql .= "quartiere  = null,";
                else $strSql .= "quartiere  = '".addslashes($value)."',";
                    break;

                case 'regione':         if(($value == 'non presente') || empty($value)) $strSql .= "regione  = null,";
                else $strSql .= "regione  = '".addslashes($value)."',";
                    break;

                case 'nazione':         if(($value == 'non presente') || empty($value)) $strSql .= "nazione  = null,";
                else $strSql .= "nazione  = '".addslashes($value)."',";
                    break;

                case 'latitudine':      if(($value == 'non presente') || empty($value)) $strSql .= "latitudine  = null,";
                else $strSql .= "latitudine  = '".addslashes($value)."',";
                    break;

                case 'longitudine':     if(($value == 'non presente') || empty($value)) $strSql .= "longitudine  = null,";
                else $strSql .= "longitudine  = '".addslashes($value)."',";
                    break;

                case 'cliente':         if($value == 'SI') {
                    $giacliente = $brand_id;
                    echo $brand_id;
                }
                    if(($value == 'non presente') || empty($value)) $strSql .= "cliente  = null,";
                    else $strSql .= "cliente  = '".addslashes($value)."',";

                    break;

                case 'longitudine':     if(($value == 'non presente') || empty($value)) $strSql .= "longitudine  = null,";
                else $strSql .= "longitudine  = '".addslashes($value)."',";
                    break;

                case 'tipoazienda':     if(($value == 'non presente') || empty($value) || ($value == 'NO'))  $strSql .= "forma_giuridica  = 0,";
                else $strSql .= "forma_giuridica  = 1,";
                    break;

                case 'operatoreprov':   if(($value == 'non presente') || empty($value)) $strSql .= "operatore  = null,";
                else $strSql .= "operatore  = '".addslashes($value)."',";
                    break;

                case 'privacy':         if(($value == 'non presente')|| empty($value)) {
                    $strSql .= "privacy  = null,";
                    $strSql .= "privacy_terzi  = 1,";
                }
                else {
                    $strSql .= "privacy  = '".addslashes($value)."',";
                    $priv = explode(',',$value);
                    if (in_array($LABEL_DATI_TERZI, $priv)) $strSql .= "privacy_terzi  = 1,";
                    else $strSql .= "privacy_terzi  = 0,";
                }
                    break;

                case 'privacyversion':  if(($value == 'non presente') || empty($value)) $strSql .= "privacy_version  = null,";
                else $strSql .= "privacy_version  = '".addslashes($value)."',";
                    break;

                case 'data':            if(($value == 'non presente') || empty($value)) $strSql .= "data  = null,";
                else $strSql .= "data  = '".addslashes($value)."',";
                    break;

                case 'ip':              if(($value == 'non presente') || empty($value)) $strSql .= "indirizzo_ip  = null,";
                else $strSql .= "indirizzo_ip  = '".addslashes($value)."',";
                    break;

                case 'url':             $strSql .= "url  = '".addslashes($value)."',";
                    break;

                case 'media':           if(($value == 'non presente') || empty($value)) $strSql .= "editore  = null,";
                else $strSql .= "editore  = '".addslashes($value)."',";
                    break;

                case 'submedia':        if(($value == 'non presente') || empty($value)) $strSql .= "submedia  = null,";
                else $strSql .= "submedia  = '".addslashes($value)."',";
                    break;

                case 'code':            if(($value == 'non presente') || empty($value)) $strSql .= "code  = null,";
                else $strSql .= "code  = '".addslashes($value)."',";
                    break;

                case 'refid':           if(($value == 'non presente') || empty($value)) $strSql .= "reference_id  = null,";
                else $strSql .= "reference_id  = '".addslashes($value)."',";
                    break;

                case 'bannerid':        if(($value == 'non presente') || empty($value)) $strSql .= "banner_id  = null,";
                else $strSql .= "banner_id  = '".addslashes($value)."',";
                    break;

                case 'download':        if(($value == 'non presente') || empty($value)) $strSql .= "download  = null,";
                else $strSql .= "download  = '".addslashes($value)."',";
                    break;

                case 'token_verified':  if(($value == 'non presente') || empty($value)) $strSql .= "token_verified  = null,";
                else $strSql .= "token_verified  = '".addslashes($value)."',";
                    break;

                case 'email_verified':  if(($value == 'non presente') || empty($value)) $strSql .= "email_verified  = null,";
                else $strSql .= "email_verified  = '".addslashes($value)."',";
                    break;

                case 'eta':             if(($value == 'non presente') || empty($value)) $strSql .= "eta  = null,";
                else $strSql .= "eta  = '".addslashes($value)."',";
                    break;

                case 'professione':     if(($value == 'non presente') || empty($value)) $strSql .= "professione  = null,";
                else $strSql .= "professione  = '".addslashes($value)."',";
                    break;

                case 'cabina':          if(($value == 'non presente') || empty($value)) $strSql .= "cabina  = null,";
                else $strSql .= "cabina  = '".addslashes($value)."',";
                    break;

                case 'importorichiesto':if(($value == 'non presente') || empty($value)) $strSql .= "importo_richiesto  = null,";
                else $strSql .= "importo_richiesto  = '".addslashes($value)."',";
                    break;

                case 'iban':            if(($value == 'non presente') || empty($value)) $strSql .= "iban  = null,";
                else $strSql .= "iban  = '".addslashes($value)."',";
                    break;

                case 'annonascita':     if(($value == 'non presente') || empty($value)) $strSql .= "anno_nascita  = null,";
                else $strSql .= "anno_nascita  = '".addslashes($value)."',";
                    break;

                case 'titolodistudio':  if(($value == 'non presente') || empty($value)) $strSql .= "titolo_di_studio  = null,";
                else $strSql .= "titolo_di_studio  = '".addslashes($value)."',";
                    break;

                case 'parent_id':  		$strSql .= "parent_id  = '".addslashes($value)."',";
                    break;
            // aggiunti da FM 8/4/2021 - inserimento di due cases nei campi dal momento che sono presenti in lead_uni
                case 'sesso':           if(($value=='non presente') || empty($value)) $strSql.="sesso = null,";
                else $strSql.="sesso='".addslashes($value)."',";
                    break;

                /*  case 'datanascita':     if(($value=='non presente') || empty($value)) $strSql.="datanascita = null";
                                          else $strSql.="datanascita='".addslashes($value)."',";
                                          break;*/

                default:                //check se nella lead_uni esiste una colonna omonima
//                                    if(($value == 'non presente') || empty($value)) $strSql .= "titolo_di_studio  = null,";
//                                    else $strSql .= "titolo_di_studio  = '".$value."',";
                    if (in_array(strtolower($column), $arrLeaduniFields)){
                        if(($value == 'non presente') || empty($value)) $strSql .= strtolower($column)." = null,";
                        else $strSql .= strtolower($column)." = '".$value."',";
                    }
                    else {

                        //modifica Francesco 08042021 controlliamo se il valore che stiamo per inserire è nullo oppure "non presente" e..
                        //se il campo è nella blacklist andiamo oltre
                        if((in_array(strtolower($column), $field_blacklist)) || ($value == 'non presente') || empty($value)) {
                            //continue;
                        } else {
                            //controlliamo se il campo è presente nei campi extra della lead_uni
                            //se no lo aggiungiamo, se si prendiamo l'id
                            $id_field = getLeaduniExtraField($mysqli, $column);
                            //controlliamo se il valore da inserire per tale campo è presente, se no lo aggiungiamo
                            $id_value = getLeaduniExtraValue($mysqli, $id_field, $value);
                            //nella tabella associativa dovremo inserire i seguenti id
                            $extra_values[] = $id_value;
                        }
                    }

                    break;

                //tipo partita iva
                //parent id
                //privacy terzi


            }

            if(!empty($address)) $strSql .= "indirizzo  = '".$address." ".$civico."',";


        }

        $strSql = rtrim($strSql,",");
    } /*fine fix jm ... nonho popolato $strSql*/
    else{
        $strSql='';
    }

    //print_r($giaclienti);
    return array('sql' => $strSql, 'gia_cliente' => $giacliente, 'extra_values' =>$extra_values);

}

function splitNome($args){

    $trimmed = trim($args[0]);

    $output = preg_replace('/\s+/', ' ', $trimmed);

    $ret = explode(' ', $output, 2);

    return $ret[0];

}

function splitCognome($args){

    $trimmed = trim($args[0]);

    $output = preg_replace('/\s+/', ' ', $trimmed);

    $ret = explode(' ', $output, 2);

    return $ret[1];

}

function setGiaClienteSi($mysqli,$id_giacliente, $id_brand){

    global $logfile;
    global $mailErrors;

    //scrittura nella tabella delle campagne delle info relative
    $strSql  = "insert ignore into a_giacliente_brand  ";
    $strSql .= " (lead_id, brand_id) ";
    $strSql .= " values ";
    $strSql .= " ('".$id_giacliente."', '".$id_brand."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $id = $mysqli->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

    }

    return;

}

function getDBBrands($mysqli){

    global $logfile;

    $sql  = "SELECT id, name, parent_id FROM brand  ";

    //echo $sql;

    $mysqli->set_charset("utf8");

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        esci();
    }

    else {

        while ($ret = $result->fetch_assoc()){

            $arrResults[strtolower($ret['name'])] = array('id' => $ret['id'], 'parent_id' => $ret['parent_id']);

        }

        //print_r(array_combine($arrResults));
        return $arrResults;

    }


}

function addBrandToDB($mysqli, $brand_name){

    global $logfile;
    global $mailErrors;

    $date_now = new DateTime();
    $date_now = $date_now->format('Y\-m\-d\ h:i:s');

    fwrite($logfile, date("YmdHis")." Adding brand ".$brand_name." to DB \n");
    echo " Adding brand ".$brand_name." to DB \n";

    //scrittura nella tabella delle campagne delle info relative
    $strSql  = "insert into brand  ";
    $strSql .= " (name, creation_date) ";
    $strSql .= " values ";
    $strSql .= " ('".strtolower($brand_name)."', '".$date_now."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $id = $mysqli->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;
    }

    return $id;

}

function mailError($errorMsg){

    $to      = 'info@linkappeal.it';
    $subject = 'Script Error: update_lead_uni.php';
    $message = 'Results: ' . print_r( $errorMsg, true );
    $headers = 'From: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
        'Reply-To: syncLeadUni@gestione.linkappeal.it' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);

}

function updateHistoryScripts($mysqli){

    global $logfile;
    global $mailErrors;

    $date_now = new DateTime();
    $date_now = $date_now->format('Y\-m\-d\ H:i:s');

    fwrite($logfile, date("YmdHis")." Updating scripts history \n");

    $strSql  = "insert into scripts_history  ";
    $strSql .= " (name, launch_date) ";
    $strSql .= " values ";
    $strSql .= " ('".basename(__FILE__)."', '".$date_now."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $value = 1;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $value = 0;
    }

    return $value;

}

function getLeaduniColumns($mysqli, $db, $tbl){

    global $logfile;

    $sql = "select COLUMN_NAME from information_schema.columns where table_schema = '".$db."' and table_name = '".$tbl."' ";

    $mysqli->set_charset("utf8");

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        esci();
    }

    else {

        while ($ret = $result->fetch_assoc()){

            $arrResults[] = strtolower($ret['COLUMN_NAME ']);

        }

        //print_r(arrResults);
        return $arrResults;

    }

}

function getLeaduniExtraField($mysqli, $field){

    global $logfile;

    $sql  = "SELECT id FROM lead_uni_extra_fields where name = '".strtolower($field)."' ";

    //echo $sql;

    $mysqli->set_charset("utf8");

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        esci();
    }

    else {

        if ($result->num_rows == 0) {

            $id = insertNewExtraField($mysqli, $field);

        }
        else{

            $ret = $result->fetch_assoc();

            $id = $ret['id'];

        }

    }

    return $id;

}

function insertNewExtraField($mysqli, $field){

    global $logfile;
    global $mailErrors;

    $date_now = new DateTime();
    $date_now = $date_now->format('Y\-m\-d\ H:i:s');

    fwrite($logfile, date("YmdHis")." Adding New Extra field ".$field." to Lead_uni_extra_fields table \n");

    $strSql  = "insert into lead_uni_extra_fields  ";
    $strSql .= " (name, creation_date) ";
    $strSql .= " values ";
    $strSql .= " ('".$field."', '".$date_now."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $id = $mysqli->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $id = 0;
    }

    return $id;
}

function getLeaduniExtraValue($mysqli, $field_id, $value){

    global $logfile;
    $value = addslashes($value);
    $sql  = "SELECT id FROM lead_uni_extra_values where field_id = '".$field_id."' and name = '".$mysqli->real_escape_string($value)."' ";

    //echo $sql;

    $mysqli->set_charset("utf8");

    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        $mailErrors[] = "Errore inserimento campi aggiuntivi: " .PHP_EOL . "Query:" . PHP_EOL . $sql . PHP_EOL . "Errore CF001:" . $mysqli->error;
        //esci();
    }else { // se l'inserimento dei custom fields è andata a buon fine

        if ($result->num_rows == 0) {
            $id = insertNewExtraValue($mysqli, $field_id, $value);
        }else{
            $ret = $result->fetch_assoc();
            $id = $ret['id'];
        }
    }

    return $id;
}

function insertNewExtraValue($mysqli, $field_id, $value){

    global $logfile;
    global $mailErrors;

    $date_now = new DateTime();
    $date_now = $date_now->format('Y\-m\-d\ H:i:s');

    fwrite($logfile, date("YmdHis")." Adding New Extra value ".$value." to Lead_uni_extra_fields table \n");

    $strSql  = "insert into lead_uni_extra_values  ";
    $strSql .= " (name, field_id, creation_date) ";
    $strSql .= " values ";
    $strSql .= " ('".$value."','".$field_id."', '".$date_now."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $id = $mysqli->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $id = 0;
    }

    return $id;

}

function associateExtraFieldValueToLead($mysqli, $lead_id, $value_id){

    global $logfile;
    global $mailErrors;

    $date_now = new DateTime();
    $date_now = $date_now->format('Y\-m\-d\ H:i:s');

    fwrite($logfile, date("YmdHis")." Associating extra value to lead ".$lead_id." \n");

    $strSql  = "insert into a_lead_extra_values  ";
    $strSql .= " (lead_id, value_id, creation_date) ";
    $strSql .= " values ";
    $strSql .= " ('".$lead_id."', '".$value_id."', '".$date_now."') ";

    //echo $strSql;

    if ($mysqli->query($strSql) === TRUE) {
        $id = $mysqli->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
        echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

        $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

    }

    return;

}

/**
 * LA SEGUENTE FUNZIONE LEGGE LE LEAD ESTRATTE DA UTILSLINKAPPEAL_CPL E LE INSERISCE (SE NON PRESENTI) O AGGIORNA (SE GIA' PRESENTI) IN LA_GESTIONE.EXTRACTION
 * RICAVANDO L'ID DELLA LEAD DALLA TABELLA LA_GESTIONE.LEAD_UNI. LA FUNZIONE DEVE PREVEDERE CASO DI LEAD NON ESISTENTE IN LEAD_UNI E SALTARE QUELLA LEAD (VERRA' POI AGGIUNTA NEL PROSSIMO GIRO)
 *
 **/


function updateExtractionTbl($mysqli_utils, $mysqli){

    global $logfile;
    global $db_config;
    global $mailErrors;

    $db_gestione = "la_gestione"; //"symfony";
    $counters_lead = array('tot_ext_upd_leads' => 0, 'tot_ext_add_leads' => 0);

    $lastdata = 'NULL';
    $arrLeaduniConv = array();
    $destExtrData = array();

    $mysqli_utils->set_charset("utf8");


    /*

    //Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id
    $strSql = "select id, source_db, source_tbl, source_id from ".$db_gestione.".lead_uni";
    $str = "Prendo tutte le lead dalla tabella gestione - lead_uni con la QUERY:" . PHP_EOL . $strSql .PHP_EOL;
    fwrite($logfile, $str . PHP_EOL);
    echo $str;

     if (!$result = $mysqli->query($strSql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        echo "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        $mailErrors[] = "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        esci();
    }else{
        while($ret = $result->fetch_assoc()){
            $arrLeaduniConv[$ret['source_db']][$ret['source_tbl']][$ret['source_id']] = $ret['id'];
        }
    }
    //print_r($arrLeaduniConv);
    //echo count($arrLeaduniConv);

    */
    //PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN EXTRACTION SU GESTIONE.EXTRACTION, partendo da questa data, effettuo una query su utility.extraction
    $strSql = "SELECT data_estrazione FROM ".$db_gestione.".extraction ORDER BY data_estrazione DESC limit 0,1"; // PRELEVO LA LEAD ESTRATTA PIù RECENTE
    $str = "PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN EXTRACTION SU GESTIONE.EXTRACTION:" . PHP_EOL . $strSql .PHP_EOL;
    fwrite($logfile, $str . PHP_EOL);
    echo $str;

    if (!$result = $mysqli->query($strSql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        echo "Errore select iniziale da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        $mailErrors[] = "Errore select estrazione ultima data da utility.extraction: " . $strSql . PHP_EOL . $mysqli->error;
        esci();
    }else{
        while($ret = $result->fetch_assoc()){
            $lastdata= $ret['data_estrazione'];
            $str = "DATA ULTIMA LEAD INSERITA: " . $lastdata . PHP_EOL;
            fwrite($logfile,$str);
            echo $str;
        }
    }

    //Prendo tutte le lead effettuo query su utility extraction per prelevare le ultime arrivate con data_inserimento > dell'ultima lead ricevuta in gestione.extraction
    $sqlExtUti = "SELECT * FROM utilslinkappeal_cpl.extraction WHERE data_inserimento > '".$lastdata."' ORDER BY data_inserimento ASC";

    echo "##########". PHP_EOL . "Prendo tutte le lead su utility.extraction con data > ".$lastdata . PHP_EOL . "Effettuo QUERY: " . $sqlExtUti . PHP_EOL . "##########". PHP_EOL;
    fwrite($logfile, $str . PHP_EOL);
    echo $str;

    if (!$result = $mysqli_utils->query($sqlExtUti)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sqlExtUti . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_utils->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli_utils->error . "\n");

        echo "Errore select iniziale da utility.extraction: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL;
        $mailErrors[] = "Errore select iniziale da utility.extraction: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL;
        esci();
    }else{
        if($result->num_rows>0){
            $indice=0;
            $str = "########## - La Query ha prodotto " . $result->num_rows . " risultati." . PHP_EOL;
            fwrite($logfile, $str . PHP_EOL);
            echo $str;
            while($ret = $result->fetch_assoc()){ //	CICLO INIZIALE DI PRELIEVO LEAD
                $indice++;
                echo PHP_EOL . "-------- Lavorando " . $indice . " di " . $result->num_rows . PHP_EOL;
                $save_history = false; // variabile inizializzata ad ogni lead, mi darà l'informazione se salvare la storia di estrazione o meno.
                $src_db 			= $ret['nome_db'];
                $src_tab			= $ret['nome_tabella'];
                $src_leadId 		= $ret['lead_id'];
                $src_clienteid 		= $ret['cliente_id']; // lo riporto durante la insert o l'update
                $src_tipoVendita 	= $ret['tipo_vendita'];
                $src_dataVendita 	= $ret['data_inserimento'];
                $src_dataSblocco 	= $ret['data_sblocco'];
                $src_dataSblocco = !empty($src_dataSblocco) ? $src_dataSblocco : "NULL"; // imposta la data di sblocco a null se vuota

                // per ogni record prelevo il suo id in lead_uni

                $sqlGetIdFromLeaduni = "SELECT id,cellulare FROM ".$db_gestione.".lead_uni 
										WHERE source_db='". $src_db 	."' 
										AND	source_tbl='". 	$src_tab 	."' 
										AND source_id='". 	$src_leadId ."'";

                if (!$resultGetIdFromLeaduni = $mysqli->query($sqlGetIdFromLeaduni)) {
                    fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
                    fwrite($logfile, date("YmdHis")." Query: " . $sqlGetIdFromLeaduni . "\n");
                    fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
                    fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

                    echo "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL;
                    $mailErrors[] = "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL;
                    esci();
                }else{ // la query di ricerca id lead da leaduni è andata a buon fine
                    if($resultGetIdFromLeaduni->num_rows>0){ // se è stato trovato un id associato
                        while($retLeadUni = $resultGetIdFromLeaduni->fetch_assoc()){ // ciclo su leaduni
                            $LeadUniID 	 = $retLeadUni['id'];
                            $LeadUniCell = $retLeadUni['cellulare'];
                            // trovato id da lead_uni, verifico se inserire o aggiornare la tabella ".$db_gestione.".extraction
                            $sqlSearchLeadInExtraction = "SELECT * FROM ".$db_gestione.".extraction WHERE extraction.lead_id=".$LeadUniID;
                            if (!$resultSearchInExtraction = $mysqli->query($sqlSearchLeadInExtraction)) {
                                fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
                                fwrite($logfile, date("YmdHis")." Query: " . $sqlSearchLeadInExtraction . "\n");
                                fwrite($logfile, date("YmdHis")." Errno: " . $mysqli->errno . "\n");
                                fwrite($logfile, date("YmdHis")." Error: " . $mysqli->error . "\n");

                                echo "Errore esecuzione query: " . $sqlSearchLeadInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                $mailErrors[] = "Errore esecuzione query: " . $sqlSearchLeadInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                esci();
                            }else{ // se non ci sono stati errori

                                if($resultSearchInExtraction->num_rows>0){ // l'id della lead è già presente in ".$db_gestione.".extraction, update
                                    // lead, già presente, verifico se eseguire l'update
                                    while($retGestExtr = $resultSearchInExtraction->fetch_assoc()){ // ciclo su leaduni
                                        $dst_clienteid		=	$retGestExtr['cliente_id'];
                                        $dst_dataVendita	=	$retGestExtr['data_estrazione'];
                                        $dst_dataSblocco	=	$retGestExtr['data_sblocco'];
                                        $dst_tipoVendita	=	$retGestExtr['tipo_estrazione'];
                                    }
                                    // conversione date
                                    $dst_dataVendita_tmstp = strtotime($dst_dataVendita);
                                    $src_dataVendita_tmstp = strtotime($src_dataVendita);


                                    // se la è una vendita, la data di sblocco potrebbe essere null

                                    $dst_dataSblocco_tmstp = 'NULL';
                                    if(!empty($dst_dataSblocco)){
                                        $dst_dataSblocco_tmstp = strtotime($dst_dataSblocco);
                                    }

                                    $src_dataSblocco_tmstp = 'NULL';
                                    if(!empty($src_dataSblocco)){
                                        $src_dataSblocco_tmstp = strtotime($src_dataSblocco);
                                    }
                                    $dst_tipoVendita = trim(strtolower($dst_tipoVendita));
                                    $src_tipoVendita = trim(strtolower($src_tipoVendita));

                                    echo "Verifica parametri: " . PHP_EOL;

                                    //echo "Destinazione cliente id:".	$dst_clienteid 			." -> Source cliente id: " . 	$src_clienteid  		. PHP_EOL;
                                    //echo "Destinazione dataVendita:".	$dst_dataVendita_tmstp 	." -> Source dataVendita: " . 	$src_dataVendita_tmstp  . PHP_EOL;
                                    //echo "Destinazione dataSblocco:".	$dst_dataSblocco_tmstp 	." -> Source dataSblocco: " . 	$src_dataSblocco_tmstp  . PHP_EOL;
                                    //echo "Destinazione tipoVendita:".	$dst_tipoVendita 		." -> Source tipoVendita: " . 	$src_tipoVendita  		. PHP_EOL;


                                    if(	$dst_clienteid	!=$src_clienteid 	||
                                        $dst_dataVendita_tmstp!=$src_dataVendita_tmstp	||
                                        $dst_dataSblocco_tmstp!=$src_dataSblocco_tmstp	||
                                        $dst_tipoVendita!=$src_tipoVendita){

                                        // se almeno uno dei valori di ".$db_gestione.".extraction non è uguale a quello della tabella utility.extraction, effettuo l'update
                                        // AGGIORNO LA LEAD ESISTENTE CON I NUOVI VALORI
                                        $sqlUpdateInExtraction="UPDATE ".$db_gestione.".extraction SET 
																cliente_id=".		$src_clienteid	.",
																cellulare='".		$LeadUniCell	."',
																data_estrazione='".	$src_dataVendita."',
																data_sblocco='".	$src_dataSblocco."',
																tipo_estrazione='".	$src_tipoVendita."'
																WHERE extraction.lead_id=".$LeadUniID;
                                        $str = "AGGIORNO LA LEAD GIA' PRESENTE IN EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlUpdateInExtraction . PHP_EOL;
                                        fwrite($logfile,$str);
                                        echo $str;

                                        if ($mysqli->query($sqlUpdateInExtraction) !== TRUE) { // se ci sono stati errori nell'insert
                                            $save_history=false;
                                            fwrite($logfile, date("YmdHis")."Error: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error . PHP_EOL);
                                            echo "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                            $mailErrors[] = "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlUpdateInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                        }else{
                                            $save_history = true;
                                            $counters_lead['tot_ext_upd_leads']++;
                                            $str = "LA LEAD ".$LeadUniID." E' STATA AGGIORNATA CORRETTAMENTE" . PHP_EOL;
                                            fwrite($logfile,$str);
                                            echo $str;
                                        }
                                    }else{ // fine if controllo valori uguali nel db extraction
                                        $save_history = false; // non c'è bisogno di aggiornare la history, lè stato già fatto in passato.
                                        $str = "Non è necessario aggiornare la lead id -> ".$LeadUniID." già esistente: tutti i valori coincidono " . PHP_EOL;
                                        fwrite($logfile,$str);
                                        echo $str;
                                    }
                                }else{ // la lead non è presente in ".$db_gestione.".extraction, insert
                                    // INSERISCO LA NUOVA LEAD COME ESTRATTA
                                    $sqlInsertInExtraction =  "INSERT INTO ".$db_gestione.".extraction (lead_id,cellulare,cliente_id,data_estrazione,data_sblocco,tipo_estrazione)";
                                    $sqlInsertInExtraction .= "VALUES ('".$LeadUniID."','".$LeadUniCell."','".$src_clienteid."','".$src_dataVendita."','".$src_dataSblocco."','".$src_tipoVendita."');";
                                    $str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA IN EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlInsertInExtraction . PHP_EOL;
                                    fwrite($logfile,$str);
                                    echo $str;

                                    if ($mysqli->query($sqlInsertInExtraction) !== TRUE) { // se ci sono stati errori nell'insert
                                        $save_history=false;
                                        fwrite($logfile, date("YmdHis")."Error: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error . PHP_EOL);
                                        echo "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                        $mailErrors[] = "Errore inserimento lead in ".$db_gestione.".extraction: " . $sqlInsertInExtraction . PHP_EOL . $mysqli->error . PHP_EOL;
                                    }else{
                                        $save_history=true;
                                        $counters_lead['tot_ext_add_leads']++;
                                        $str = "LA LEAD ".$LeadUniID." E' STATA INSERITA CORRETTAMENTE" . PHP_EOL;
                                        fwrite($logfile,$str);
                                        echo $str;
                                    }

                                } // fine else lead non presente in extraction
                            } // fine else se non ci sono stati errori


                            // AGGIORNO LA TABELLA HISTORY SE NECESSARIO
                            if($save_history){
                                $sqlHistory  = "INSERT INTO ".$db_config['parameters']['database_name'].".extraction_history " ;
                                $sqlHistory .= " (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) " ;
                                $sqlHistory .= "VALUES ('".$LeadUniID."', '".$src_clienteid."', '".$src_dataVendita."', '".$src_dataSblocco."', '".$src_tipoVendita."') " ;

                                $str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA NELLA STORIA DELLE EXTRACTION CON LA QUERY: " . PHP_EOL . $sqlHistory . PHP_EOL;
                                fwrite($logfile,$str);
                                echo $str;

                                if ($mysqli->query($sqlHistory) !== TRUE) {
                                    fwrite($logfile, date("YmdHis")."Error: " . $sqlHistory . PHP_EOL . $mysqli->error . PHP_EOL);
                                    echo "Error: " . $sqlHistory . PHP_EOL . $mysqli->error . PHP_EOL;
                                    $mailErrors[] = "Error: " . $sqlHistory . PHP_EOL . $mysqli->error . PHP_EOL;
                                }
                            }
                            // FINE AGGIORNAMENTO TABELLA HISTORY
                        } // fine while ciclo su lead_uni
                    }else{ // non è stato trovato un id associato alla lead_uni forse non è stata ancora importata la lead. salto.
                        $str = "Non è stato trovato in lead_uni la lead con coordinate: "
                            ." | DB -> ". $src_db
                            ." | tabella -> ".	$src_tab
                            ." | id lead -> ".	$src_leadId . PHP_EOL . "Tempo di esecuzione ricerca: ". date('d-m-Y H:i:s') . PHP_EOL;
                        fwrite($logfile,$str);
                        echo $str;
                        $save_history=false;
                        continue; // continuo il cliclo while sugli id della tabella utility.extraction
                    }


                } // fine if controllo se query di ricerca id lead da leaduni non ha restituito errori
            } // FINE WHILE PRIMARIO su tutte le lead di utility extraction
        }else{ // se la query in base alla data > lastdata non ha prodotto risultati
            // non c'è nulla da importare
            $str = "La Query non ha prodotto risultati." . PHP_EOL . " ######### - NIENTE DA IMPORTARE- ######### " . PHP_EOL;
            fwrite($logfile, $str . PHP_EOL);
            echo $str;
        }
    } // fine if se ci non ci sono errori su query risultati in base alla dara  su utility.extraction

    //Per ogni lead letta da src_extraction:
    // 1) ricavo l'id della lead_uni, tramite l'array prima creato
    // 2) controllo se è presente nella tabella di destinazione, tramite l'array prima creato
    // 3a) se no inseriamo il record in dest_extraction e nell'history
    // 3b) se si ed è di vendita non facciamo nulla
    // 3c) se si ed è di noleggio e la data di inserimento è successiva a quella presente, aggiorniamo il record ed aggiungiamo all'history
    return $counters_lead;
}


function updateContatoreTbl($mysqli_utils, $mysqli){

    global $logfile;
    global $db_config;
    global $mailErrors;

    $db_gestione = "la_gestione"; //"symfony";
    $counters_lead =  0;

    $lastdata = date('Y-m-d 00:00:00');
    $arrLeaduniConv = array();
    $destExtrData = array();

    $mysqli_utils->set_charset("utf8");

    //PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN contatore SU GESTIONE.contatore, partendo da questa data, effettuo una query su utility.contatore
    $strSql = "SELECT data_lead FROM ".$db_gestione.".contatore ORDER BY data_lead DESC limit 0,1"; // PRELEVO LA LEAD ESTRATTA PIù RECENTE
    $str = "PRELEVO L'ULTIMA DATA DI INSERIMENTO LEAD IN contatore SU GESTIONE.contatore:" . PHP_EOL . $strSql .PHP_EOL;
    fwrite($logfile, $str . PHP_EOL);
    echo $str;

    if (!$result = $mysqli->query($strSql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        echo "Errore select iniziale da utility.contatore: " . $strSql . PHP_EOL . $mysqli->error;
        $mailErrors[] = "Errore select estrazione ultima data da utility.contatore: " . $strSql . PHP_EOL . $mysqli->error;
        esci();
    }else{
        while($ret = $result->fetch_assoc()){
            $lastdata= $ret['data_lead'];
            $str = "DATA ULTIMA LEAD INSERITA: " . $lastdata . PHP_EOL;
            fwrite($logfile,$str);
            echo $str;
        }
    }

    //Prendo tutte le lead effettuo query su utility contatore per prelevare le ultime arrivate con data_inserimento > dell'ultima lead ricevuta in gestione.contatore
    $sqlExtUti = "SELECT * FROM utilslinkappeal_cpl.contatore WHERE data_lead > '".$lastdata."' ORDER BY data_lead ASC";

    echo "##########". PHP_EOL . "Prendo tutte le lead su utility.contatore con data > ".$lastdata . PHP_EOL . "Effettuo QUERY: " . $sqlExtUti . PHP_EOL . "##########". PHP_EOL;
    fwrite($logfile, $str . PHP_EOL);
    echo $str;

    if (!$result = $mysqli_utils->query($sqlExtUti)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sqlExtUti . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_utils->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli_utils->error . "\n");

        echo "Errore select iniziale da utility.contatore: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL;
        $mailErrors[] = "Errore select iniziale da utility.contatore: " . $sqlExtUti . PHP_EOL . $mysqli_utils->error . PHP_EOL;
        esci();
    }else{
        if($result->num_rows>0){
            $indice=0;
            $str = "########## - La Query ha prodotto " . $result->num_rows . " risultati." . PHP_EOL;
            fwrite($logfile, $str . PHP_EOL);
            echo $str;
            while($ret = $result->fetch_assoc()){ //	CICLO INIZIALE DI PRELIEVO LEAD
                $indice++;
                echo PHP_EOL . "-------- Lavorando " . $indice . " di " . $result->num_rows . PHP_EOL;
                $save_history = false; // variabile inizializzata ad ogni lead, mi darà l'informazione se salvare la storia di estrazione o meno.
                $src_db 			= $ret['source_db'];
                $src_tab			= $ret['source_tbl'];
                $src_leadId 		= $ret['source_id'];
                $src_clienteid 		= $ret['cliente_id']; // lo riporto durante la insert o l'update
                $src_campagna_id 	= $ret['campagna_id'];
                $src_landing_id 	= $ret['landing_id'];
                $src_offtarget 		= $ret['offtarget'];
                $src_dataLead 		= $ret['data_lead'];

                // per ogni record prelevo il suo id in lead_uni

                $sqlGetIdFromLeaduni = "SELECT id FROM ".$db_gestione.".lead_uni 
										WHERE source_db='". $src_db 	."' 
										AND	source_tbl='". 	$src_tab 	."' 
										AND source_id='". 	$src_leadId ."'";

                if (!$resultGetIdFromLeaduni = $mysqli->query($sqlGetIdFromLeaduni)) {
                    fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
                    fwrite($logfile, date("YmdHis")." Query: " . $sqlGetIdFromLeaduni . "\n");
                    fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
                    fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

                    echo "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL;
                    $mailErrors[] = "Errore Select from lead_uni: " . $sqlGetIdFromLeaduni . PHP_EOL . $mysqli->error . PHP_EOL;
                    esci();
                }else{ // la query di ricerca id lead da leaduni è andata a buon fine
                    if($resultGetIdFromLeaduni->num_rows>0){ // se è stato trovato un id associato
                        while($retLeadUni = $resultGetIdFromLeaduni->fetch_assoc()){ // ciclo su leaduni
                            $LeadUniID = $retLeadUni['id'];
                            // trovato id da lead_uni, verifico se inserire o aggiornare la tabella ".$db_gestione.".contatore
                            $sqlSearchLeadInContatore = "SELECT * FROM ".$db_gestione.".contatore WHERE contatore.lead_id=".$LeadUniID;
                            if (!$resultSearchInContatore = $mysqli->query($sqlSearchLeadInContatore)) {
                                fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
                                fwrite($logfile, date("YmdHis")." Query: " . $sqlSearchLeadInContatore . "\n");
                                fwrite($logfile, date("YmdHis")." Errno: " . $mysqli->errno . "\n");
                                fwrite($logfile, date("YmdHis")." Error: " . $mysqli->error . "\n");

                                echo "Errore esecuzione query: " . $sqlSearchLeadInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
                                $mailErrors[] = "Errore esecuzione query: " . $sqlSearchLeadInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
                                esci();
                            }else{ // se non ci sono stati errori

                                if($resultSearchInContatore->num_rows<=0){ // l'id della leadnon è presente in ".$db_gestione.".contatore, update
                                    // INSERISCO LA NUOVA LEAD COME contata
                                    $sqlInsertInContatore =  "INSERT INTO ".$db_gestione.".contatore (lead_id,cliente_id,campagna_id,landing_id,offtarget,data_lead)";
                                    $sqlInsertInContatore .= "VALUES ('".$LeadUniID."',
																		'".$src_clienteid."',
																		'".$src_campagna_id."',
																		'".$src_landing_id."',
																		'".$src_offtarget."',
																		'".$src_dataLead."');";
                                    $str = "INSERISCO LA LEAD nel contatore CON LA QUERY: " . PHP_EOL . $sqlInsertInContatore . PHP_EOL;
                                    fwrite($logfile,$str);
                                    echo $str;

                                    if ($mysqli->query($sqlInsertInContatore) !== TRUE) { // se ci sono stati errori nell'insert

                                        fwrite($logfile, date("YmdHis")."Error: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL);
                                        echo "Errore inserimento lead in ".$db_gestione.".contatore: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
                                        $mailErrors[] = "Errore inserimento lead in ".$db_gestione.".contatore: " . $sqlInsertInContatore . PHP_EOL . $mysqli->error . PHP_EOL;
                                    }else{

                                        $counters_lead++;
                                        $str = "LA LEAD ".$LeadUniID." E' STATA INSERITA CORRETTAMENTE" . PHP_EOL;
                                        fwrite($logfile,$str);
                                        echo $str;
                                    }

                                } // fine else lead non presente in Contatore
                            } // fine else se non ci sono stati errori
                        } // fine while ciclo su lead_uni
                    }else{ // non è stato trovato un id associato alla lead_uni forse non è stata ancora importata la lead. salto.
                        $str = "Non è stato trovato in lead_uni la lead con coordinate: "
                            ." | DB -> ". $src_db
                            ." | tabella -> ".	$src_tab
                            ." | id lead -> ".	$src_leadId . PHP_EOL . "Tempo di esecuzione ricerca: ". date('d-m-Y H:i:s') . PHP_EOL;
                        fwrite($logfile,$str);
                        echo $str;
                        continue; // continuo il cliclo while sugli id della tabella utility.Contatore
                    }


                } // fine if controllo se query di ricerca id lead da leaduni non ha restituito errori
            } // FINE WHILE PRIMARIO su tutte le lead di utility Contatore
        }else{ // se la query in base alla data > lastdata non ha prodotto risultati
            // non c'è nulla da importare
            $str = "La Query non ha prodotto risultati." . PHP_EOL . " ######### - NIENTE DA IMPORTARE- ######### " . PHP_EOL;
            fwrite($logfile, $str . PHP_EOL);
            echo $str;
        }
    } // fine if se ci non ci sono errori su query risultati in base alla dara  su utility.Contatore
    return $counters_lead;
}

/** la funzione esegue una verifica sul numero di cellulare e restituisce in caso positivo l'id della lead con cellulare duplicato.
 *	La lead dovrà essere memorizzata in lead_uni con parent_id = id restituito dalla funzione.
 * in caso negativo la funzione retituirà false.
 */

function checkForDuplicates($targetField,$value,$mysqli){

    global $logfile;
    global $db_config;
    global $mailErrors;

    $result_id = false;

    $sql_check = "SELECT id from lead_uni where ". $targetField." = '" . $value . "' ORDER BY data ASC LIMIT 0,1";
    $result = $mysqli->query($sql_check);
    if($result){ // la query è andata a buon fine
        if($result->num_rows>0){ // la query ha restituito risultati
            $indice=0;
            $str = "########## - La Query di ricerca doppione per " . $targetField . ": " . $value . " ha prodotto risultati: esiste doppione." . PHP_EOL;
            fwrite($logfile, $str . PHP_EOL);
            echo $str;
            while($ret = $result->fetch_assoc()){
                $result_id = $ret['id'];

            } // fine while
        } // fine if
    }else{
        fwrite($logfile, date("YmdHis")." Errore: Query fallita per ricerca duplicato " . $targetField . ": " . $value . ": \n");
        fwrite($logfile, date("YmdHis")." Query: " . $sql_check . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

        echo "Errore Select ricerca duplicato: " . $sql_check . PHP_EOL . $mysqli->error . PHP_EOL;
        $mailErrors[] = "Errore ricerca duplicato su " . $targetField. " " . $value . " - " . PHP_EOL . $sql_check . PHP_EOL . $mysqli->error . PHP_EOL;
        esci();
    }

    return $result_id;
}

/*
function updateExtractionTbl($mysqli_utils, $mysqli){

   global $logfile;
   global $db_config;
   global $mailErrors;

   $counters_lead = array('tot_ext_upd_leads' => 0, 'tot_ext_add_leads' => 0);

   $arrLeaduniConv = array();
   $destExtrData = array();

   $mysqli->set_charset("utf8");
//    $sql  = "select e.cliente_id, e.tipo_vendita, e.data_inserimento, e.data_sblocco";
//    $sql .= "from ".$db_config['parameters']['utils_database_name'].".extraction e left join ".$db_config['parameters']['database_name'].".lead_uni l ";
//    $sql .= " on e.nome_db = l.source_bd and e.nome_tabella = l.source_tbl and e.lead_id = l.source_id ";

    //Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id
   $strSql = "select id, source_db, source_tbl, source_id from ".$db_config['parameters']['database_name'].".lead_uni";

   $str = "Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id da lead_uni con la query:" . PHP_EOL . $strSql .PHP_EOL;
   fwrite($logfile, $str . PHP_EOL);
   echo $str;

   if (!$result = $mysqli->query($strSql)) {

       fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
       fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
       fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
       fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

       echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

       esci();
   }

   else {

       while($ret = $result->fetch_assoc()){

           $arrLeaduniConv[$ret['source_db']][$ret['source_tbl']][$ret['source_id']] = $ret['id'];

       }
       //$str = "Array creato: " . PHP_EOL . print_r($arrLeaduniConv,true) . PHP_EOL;
       //fwrite($logfile,$str);
       //echo $str;
   }


   //Prendo tutte le lead presenti nella tabella dest_extraction con id, tipo vendita e data inserimento

   $strSql = "select lead_id, tipo_estrazione, data_estrazione from ".$db_config['parameters']['database_name'].".extraction";


   $str = "Prendo tutte le lead_id organizzate in modo tale da accederci per db->tbl->id da EXTRACTION con la query:" . PHP_EOL . $strSql .PHP_EOL;
   fwrite($logfile, $str . PHP_EOL);
   echo $str;

    if (!$result = $mysqli->query($strSql)) {

       fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
       fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
       fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
       fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");

       echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

       esci();
   }

   else {

       while($ret = $result->fetch_assoc()){

           //In ogni istante nella tabella extraction può esserci solo un record per lead
           $destExtrData[$ret['lead_id']] = array("tipo_vendita" => $ret['tipo_estrazione'], "data_inserimento" => $ret['data_estrazione']);

       }
       //$str = "Array creato: " . PHP_EOL . print_r($destExtrData,true) . PHP_EOL;
       //fwrite($logfile,$str);
       //echo $str;
   }

    //Per ogni lead letta da src_extraction:
    // 1) ricavo l'id della lead_uni, tramite l'array prima creato
    // 2) controllo se è presente nella tabella di destinazione, tramite l'array prima creato
    // 3a) se no inseriamo il record in dest_extraction e nell'history
    // 3b) se si ed è di vendita non facciamo nulla
    // 3c) se si ed è di noleggio e la data di inserimento è successiva a quella presente, aggiorniamo il record ed aggiungiamo all'history

   //Facendo la join prendiamo solo il record con la data di inserimento più aggiornata
   $strSql  = " select e1.nome_db as nomedb, e1.nome_tabella as nometbl, e1.lead_id as lead, e1.cliente_id, e1.tipo_vendita, e1.data_inserimento, e1.data_sblocco";
   $strSql .= " from ".$db_config['parameters']['utils_database_name'].".extraction e1 left join ".$db_config['parameters']['utils_database_name'].".extraction e2 ";
   $strSql .= " on e1.nome_db = e2.nome_db and e1.nome_tabella = e2.nome_tabella and e1.lead_id = e2.lead_id and e1.data_inserimento < e2.data_inserimento ";
   $strSql .= " where e2.data_inserimento is null ";


   $str = "ESEGUO LA QUERY: " . PHP_EOL . $strSql . PHP_EOL . "Facendo la join prendiamo solo il record con la data di inserimento più aggiornata.".PHP_EOL;
   fwrite($logfile,$str);
   echo $str;

    if (!$result = $mysqli_utils->query($strSql)) {

       fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");
       fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
       fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_utils->errno . "\n");
       fwrite($logfile, date("YmdHis")."Error: " . $mysqli_utils->error . "\n");

       echo "Error: " . $strSql . PHP_EOL . $mysqli_utils->error;

       esci();
   }

   else {

       while($ret = $result->fetch_assoc()){

           $id_leaduni = $arrLeaduniConv[$ret['nomedb']][$ret['nometbl']][$ret['lead']];

           //Se non c'è l'id nella lead_uni passiamo oltre
           if(empty($id_leaduni)) {
               $str = "Id: |" . $id_leaduni . "| NON TROVATO in lead_uni per il DB: ".$ret['nomedb']." tbl: ".$ret['nometbl']." lead: ".$ret['lead'] .PHP_EOL;
               fwrite($logfile,$str);
               echo $str;
               //$mailErrors[] = "Id not found in lead_uni table for db: ".$ret['nomedb']." tbl: ".$ret['nometbl']." lead: ".$ret['lead'];
               continue;

           }

           if (array_key_exists($id_leaduni, $destExtrData)){

               $str = "La lead id: " . $id_leaduni . " è  presente nella tabella extract di destinazione" . PHP_EOL;
               fwrite($logfile,$str);
               echo $str;

               $dest_dt   = new DateTime($destExtrData['$id_leaduni']['data_inserimento']);
               $source_dt = new DateTime($ret['data_inserimento']);

               if ((strtolower($destExtrData['$id_leaduni']['tipo_vendita']) != 'vendita') &&
                   ($source_dt > $dest_dt))

               {

                   $str = "La data della lead id: " . $id_leaduni . " = " . $source_dt . " è > della data di destinazione " . $dest_dt . PHP_EOL;
                   fwrite($logfile,$str);
                   echo $str;

                   //$str = "LA LEAD PRELEVATA " . print_r($destExtrData['$id_leaduni'], true) . " NON E' VENDUTA: ". PHP_EOL;
                   //fwrite($logfile,$str);
                   //echo $str;

                   $strSql  = " update ".$db_config['parameters']['database_name'].".extraction " ;
                   $strSql .= " set data_estrazione = '".$ret['data_inserimento']."', data_sblocco = '".$ret['data_sblocco']."', tipo_estrazione = '".strtolower($ret['tipo_vendita'])."' " ;

                   $str = "AGGIORNO LA TABELLA EXTRACTION CON LA QUERY: " . PHP_EOL . $strSql . PHP_EOL;
                   fwrite($logfile,$str);
                   echo $str;

                   if ($mysqli->query($strSql) !== TRUE) {
                       fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
                       echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

                       $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;
                   }

                   else $counters_lead['tot_ext_upd_leads']++;

                   $strSql  = " insert into ".$db_config['parameters']['database_name'].".extraction_history " ;
                   $strSql .= " (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) " ;
                   $strSql .= " values ('".$id_leaduni."', '".$ret['cliente_id']."', '".$ret['data_inserimento']."', '".$ret['data_sblocco']."', '".$ret['tipo_vendita']."') " ;

                   $str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA NELLA STORIA DELLE EXTRACTION CON LA QUERY: " . PHP_EOL . $strSql . PHP_EOL;
                   fwrite($logfile,$str);
                   echo $str;

                   if ($mysqli->query($strSql) !== TRUE) {
                       fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
                       echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

                       $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;

                   }

               }

           }
           else { // LA LEAD NON è PRESENTE NELL'ARRAY, LA DEVO INSERIRE

               //$str = "LA LEAD PRELEVATA " . print_r($destExtrData['$id_leaduni'], true) . " NON ESISTE, DEVO INSERIRLA: ". PHP_EOL;
               //fwrite($logfile,$str);
               //echo $str;

               $strSql  = " insert into ".$db_config['parameters']['database_name'].".extraction " ;
               $strSql .= " (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) " ;
               $strSql .= " values ('".$id_leaduni."', '".$ret['cliente_id']."', '".$ret['data_inserimento']."', '".$ret['data_sblocco']."', '".$ret['tipo_vendita']."') " ;

               $str = "INSERISCO LA LEAD COME VENDUTA/NOLEGGIATA NELLA TABELLA EXTRACTION CON LA QUERY: " . PHP_EOL . $strSql . PHP_EOL;
               fwrite($logfile,$str);
               echo $str;

               if ($mysqli->query($strSql) !== TRUE) {
                   fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
                   echo "Error: " . $strSql . PHP_EOL . $mysqli->error . "for source id :" . $ret['lead'] ."and source tbl".$ret['nometbl']." in db ".$ret['nomedb']."\n";

                   $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;
               }
               else $counters_lead['tot_ext_add_leads']++;

               $strSql  = " insert into ".$db_config['parameters']['database_name'].".extraction_history " ;
               $strSql .= " (lead_id, cliente_id, data_estrazione, data_sblocco, tipo_estrazione) " ;
               $strSql .= " values ('".$id_leaduni."', '".$ret['cliente_id']."', '".$ret['data_inserimento']."', '".$ret['data_sblocco']."', '".$ret['tipo_vendita']."') " ;

               if ($mysqli->query($strSql) !== TRUE) {
                   fwrite($logfile, date("YmdHis")."Error: " . $strSql . PHP_EOL . $mysqli->error);
                   echo "Error: " . $strSql . PHP_EOL . $mysqli->error;

                   $mailErrors[] = "Error: " . $strSql . PHP_EOL . $mysqli->error;
               }

           }

       }

   }    echo $sql;

   return $counters_lead;

}
*/
// la funzione esce dall'esecuzione, ma prima elimina il file lock
function esci(){
    global $lock_file;
    unlink($lock_file);
    exit();
}
