<?php
/**************************************************************************************
 * NOME DEL FILE  : create_leaduni_from_legacy.php
 * AUTORE:        : Ettore Amato
 * DATA CREAZIONE: 27/01/2017
 *
 **************************************************************************************/

error_reporting(E_ERROR);

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

$logfile = fopen($curDir."/log/".date("Ymd")."_log_create_leaduni_from_legacy.txt", "a"); //FILE di LOG

fwrite($logfile, date("YmdHis")."   /************************************************/");
fwrite($logfile, date("YmdHis")."   Avvio Script per creazione tabella Lead unificate\n");
echo "\n\n/**** Avvio Script per creazione tabella Lead unificate ****/\n";

/*************** CONNESSIONE A DB *****************/
// Qui impostiamo i parametri di connessione al DB

$config_file = "../../../app/config/parameters.yml"; //file di config di symfony
$db_config = yaml_parse_file($config_file);
//$db_name = 'symfony';
$num_insert = 0;
$campaign_insert = 0;
//var_dump($db_config);

$mysqli_dest = new mysqli($db_config['parameters']['database_host'],
                          $db_config['parameters']['database_user'],
                          $db_config['parameters']['database_password'],
                          $db_config['parameters']['database_name']);

if ($mysqli_dest->connect_errno) {

    fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n");
    fwrite($logfile, date("YmdHis")." Errno: " . $mysqli_dest->connect_errno . "\n");
    fwrite($logfile, date("YmdHis")." Error: " . $mysqli_dest->connect_error . "\n");
    fwrite($logfile, date("YmdHis")." Exiting");

    exit;
}

/*************** CONNESSIONE A DB ORIGINE *****************/
// Qui impostiamo i parametri di connessione al DB

$mysqli_src = new mysqli( $db_config['parameters']['src_database_host'],
                          $db_config['parameters']['src_database_user'],
                          $db_config['parameters']['src_database_password']);

if ($mysqli_src->connect_errno) {

    $errorStr  = date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n";
    $errorStr .= date("YmdHis")." Errno: " . $mysqli_src->connect_errno . "\n";
    $errorStr .= date("YmdHis")." Error: " . $mysqli_src->connect_error . "\n";
    $errorStr .= date("YmdHis")." Exiting";
            
    fwrite($logfile, $errorStr);
    
    //mailErrors($errorStr);
            
    exit;
}


/**************************************************/
//Recuperiamo tutti i brand già inseriti e relativi id

$existing_brands = array();

$existing_brands = getDBBrands($mysqli_dest);

/**************************************************/
//$result = $mysqli->query("SELECT * from cliente");
//
//foreach ($result as $key => $value){
//    $row    = $result->fetch_assoc();
//    print_r($row);
//}

//Apriamo il file csv
//la prima riga definisce i campi dell'array che utilizzeremo per memorizzare i parametri di conversione
/*
 *   DB SOURCE             => è il DB di origine
 *   TBL mailoperation     => la tabella campagne di origine
 *   TBL                   => mail la tabella lead
 *   DB DEST               => il db di destinazione
 *   TBL DEST              => la tabella di destinazione
 *   SRC Field	           => la colonna di origine
 *   DST Field	           => la colonna di destinazione
 *   Rules	           => la regola di conversione (copy, callback|function)
 *   Override value	   => questo valore se presente viene inserito in tabella
 *   Callback parameters   => se Rules = callback questi saranno i parametri passati alla funzione (arg1,arg2,...)
 *   Null handling	   => se il campo origine è null verrà convertito a questo
 *   Empty Handling	   => se il campo è vuoto/blank verrà convertito a questo
 *   Switch Values         => contiene delle coppia di valori con la sintassi (x1|y1;x2|y2;...) , se il primo ha un match verrà sostituito col secondo

 */

//Se il valore DB_SOURCE è diverso dal precedente dobbiamo chiudere la connessione al db e aprirne un'altra
//Leggiamo fino a che non cambia "TBL mail" per costruire la insert

$row = 1; //indice lettura csv
$num_tbl = 1; //indice tabella

$old_db = "";
$old_tbl= "";
$old_tblmo= "";

$start_time = microtime(1);

//echo $curDir."/conversion_file.csv";
if (($handle = fopen($curDir."/conversion_file.csv", "r")) !== FALSE) {
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { //Ciclo di lettura del file CSV
        
        if ($row == 1){  //Lettura Campi del CSV
             $strArrayKeys = $data;
        }
        
        else { //Tutte le altre righe

            $convData = array_combine($strArrayKeys, $data);
            //

            $db  = $convData['DB SOURCE'];
            $tbl = $convData['TBL mail'];
            $tblmo = $convData['TBL mailoperation'];
            $tblma = $convData['TBL mail master'];
            
//            echo $db."\n";
//            echo $tbl."\n";
                        
            //Se è cambiato il Db o la tabella carichiamo i nuovi dati 
            if (($db != $old_db) || ($old_tbl != $tbl)){
                                        
                    //Inseriamo nel DB i dati dell'ultima tabella
                    if (!empty($old_db) && !empty($old_tbl)){
                        
                        //Se la tabella mail è diversa dalla master, prendiamo l'id
                        //di quest'ultima e, se non esiste, inseriamo la relativa campagna(master) in tabella
                        //Inseriamo le info relative alla campagna nell'apposita tabella
                        if (empty($old_tblmo)) $info_campaign = null;
                        else{
                            $c_info = getCampagnaInfo($mysqli_dest, $old_db, $old_tblma);
                            if(empty($c_info)){
                                fwrite($logfile, date("YmdHis")." Writing campaign info of tbl: ".$old_tbl." from db: ".$old_db." \n");
                                echo " Writing campaign info from tbl: ".$old_tbl." from db: ".$old_db." \n";                                
                                $info_campaign = doInsertCampaignInfoIntoDB($mysqli_src, $mysqli_dest, $old_tblmo, $old_tbl, $old_db);
                            }
                            else $info_campaign = $c_info;                            
                        }
                                                
                        $arrSetStatementForCurrentTbl = prepareSetStatement($arrConvData, $arrSrcLeadData, $info_campaign);
                        doInsertIntoUnileadTbl($mysqli_dest, $arrSetStatementForCurrentTbl);
                        $arrConvData = array();
                        $num_tbl++;

                        //Se è cambiato il DB ci connettiamo a quello nuovo
                        if ($db != $old_db) { 
                            //Svuotiamo l'array delle tabelle lette
                            $arrLoadedTables = array();
                            echo "changing db";
                            $mysqli_src->select_db($db);

                        }                        
 
                    }
                    else if (empty($old_db)) $mysqli_src->select_db($db);

                    //Facciamo la quuery di lettura delle lead di una tabella, tramite una join 
                    //della entry di una mailoperation con la rispettiva mailxx

                    //carichiamo i dati di una nuova tabella origine
                    //se già è stata letta saltiamo
                    if (in_array($tbl, $arrLoadedTables)){
                        fwrite($logfile, date("YmdHis")." Skipping already processed table: ".$tbl." from db: ".$db." \n");
                        echo " Skipping already processed table: ".$tbl." from db: ".$db." \n";
                        
                    }
                    else {
                        fwrite($logfile, date("YmdHis")." Loading data of table: ".$tbl." from db: ".$db." \n");
                        echo " Loading data of table: ".$tbl." from db: ".$db." \n";

                        $arrSrcLeadData = loadSrcData($mysqli_src,
                                                      $convData['TBL mailoperation'],
                                                      $convData['TBL mail'],
                                                      $convData['TBL mail master']);


                        fwrite($logfile, date("YmdHis")." Rows found: ".count($arrSrcLeadData)." \n");
                        echo " Rows found: ".count($arrSrcLeadData)." \n";

                        $arrLoadedTables[] = $tbl;
                //var_dump($arrSrcLeadData);
                //echo count($arrSrcLeadData);
                //$arrSetRule[] =  
                    }

           
            }
            
            $arrConvData[] = $convData;
            $old_db = $db;
            $old_tbl = $tbl;
            $old_tblmo = $tblmo;
            $old_tblma = $tblma;
 
            
            
        }

        $num_entry++;
        $row++;

    }
    //var_dump($arrSrcLeadData);
    if (empty($old_tblmo)) $info_campaign = null;
    else{
        $c_info = getCampagnaInfo($mysqli_dest, $old_db, $old_tblma);
        if(empty($c_info)){
            fwrite($logfile, date("YmdHis")." Writing campaign info of tbl: ".$old_tbl." from db: ".$old_db." \n");
            echo " Writing campaign info from tbl: ".$old_tbl." from db: ".$old_db." \n";                                
            $info_campaign = doInsertCampaignInfoIntoDB($mysqli_src, $mysqli_dest, $old_tblmo, $old_tbl, $old_db);
        }
        else $info_campaign = $c_info;                            
    }    
    $arrSetStatementForCurrentTbl = prepareSetStatement($arrConvData, $arrSrcLeadData, $info_campaign);
    doInsertIntoUnileadTbl($mysqli_dest, $arrSetStatementForCurrentTbl); //I dati dell'ultima tabella vengono inseriti
    
    fclose($handle);
}
else {
    
    fwrite($logfile, date("YmdHis")." Error: Can't open conversion_file.csv \n");    
    
}

$mysqli_src->close();
$mysqli_dest->close();

$end_time = microtime(1);

echo "Tempo di esecuzione: ".date("H:i:s", $end_time - $start_time)."\n";

//$prepositions = [];

//$articles     = [];

//abstract class enTagOrigin
//{
//    const undefined      = 0;
//    const description    = 1;
//    const attribute      = 2;
//    const colour         = 3;
//    const catnav         = 4;
//
//}


//function connectToDb($db_name){
//    
//    global $db_config;
//    global $logfile;
//    
//    $mysqli = new mysqli($db_config['parameters']['database_host'],
//                         $db_config['parameters']['database_user'],
//                         $db_config['parameters']['database_password'],
//                         $db_name);
//
//    if ($mysqli->connect_errno) {
//
//        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL connection, here is why: \n");
//        fwrite($logfile, date("YmdHis")." Errno: " . $mysqli->connect_errno . "\n");
//        fwrite($logfile, date("YmdHis")." Error: " . $mysqli->connect_error . "\n");
//        fwrite($logfile, date("YmdHis")." Exiting");
//
//        exit;
//    }
//
//    else return $mysqli;    
//    
//    
//}


//Restituisce un array con tutti dati delle lead presi dalla tabella passata
function loadSrcData($mysqli, $mailop_tbl, $mail_tbl, $mail_master_tbl){
    
    global $logfile;
    
    if(!empty($mailop_tbl)){
        $sql  = "SELECT * FROM ".$mailop_tbl." join ".$mail_tbl;
        $sql .= " WHERE DBTAB = '".$mail_master_tbl."' ";
    }
    else {
        $sql  = "SELECT * FROM ".$mail_tbl;    
    }
    //echo $sql;
    
    $mysqli->set_charset("utf8");
    
    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");

        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        
        exit;
    }
    
    else {
        
        while ($ret = $result->fetch_assoc()){
            
            $arrResults[] = array_change_key_case($ret, CASE_UPPER);
            
        }
        
        return $arrResults;
        
    }
    
    
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
        
        exit;
    }
    
    else {
        
        while ($ret = $result->fetch_assoc()){
            
            $arrResults[strtolower($ret['name'])] = array('id' => $ret['id'], 'parent_id' => $ret['parent_id']);
            
        }
        
        return $arrResults;
        
    }
    
    
}

function prepareSetStatement($arrConvData, $tblValues, $info_campagna){
    
    
 /*  DB SOURCE             => è il DB di origine
 *   TBL mailoperation     => la tabella campagne di origine
 *   TBL mail              => la tabella lead
 *   DB DEST               => il db di destinazione
 *   TBL DEST              => la tabella di destinazione
 *   SRC Field	           => la colonna di origine
 *   DST Field	           => la colonna di destinazione
 *   Rules	           => la regola di conversione (copy, callback|function)
 *   Override value	   => questo valore se presente viene inserito in tabella
 *   Callback parameters   => se Rules = callback questi saranno i parametri passati alla funzione (arg1,arg2,...)
 *   Null handling	   => se il campo origine è null verrà convertito a questo
 *   Empty Handling	   => se il campo è vuoto/blank verrà convertito a questo
 *   Switch Values         => contiene delle coppia di valori con la sintassi (x1|y1;x2|y2;...) , se il primo ha un match verrà sostituito col secondo
 */ 

    //var_dump($arrConvData);
    //var_dump($tblValues); 
    
    foreach($tblValues as $key => $val){ //Ciclo per le lead di origine
        
        $skipline = 0;
        $i = 1;
        //if ($val['ID']!='447') continue;
        $strSet  = "set ";    
        $strSet .= "source_db  = '".$arrConvData[0]['DB SOURCE']."',";    
        $strSet .= "source_tbl = '".$arrConvData[0]['TBL mail']."', ";   
        $strSet .= "source_id  = '".$val['ID']."' ";
        if (!empty($info_campagna)) $strSet .= ", campagna_id = '".$info_campagna['id']."' ";

        foreach($arrConvData as $j => $z){ //Ciclo per le regole di conversione
            
            //var_dump($z);

            if (($z['Override value']) != '') {

                $strSet .= ",".$z['DST Field']." = ".$z['Override value'];        

            } 

            else {

                $switchVal = array();

                switch($z['Rules']) {

                    case 'copy':

                        if ($z['Switch Values'] != ''){ //Per ora consideriamo il caso di una singola di valori di scambio

                            $switchVal[] = explode('|', $z['Switch Values']);

                        }
                        
                        if ($z['Null handling'] != ''){
                            
                            $switchVal[] = array(NULL, $z['Null handling']);

                        }
                        
                        if ($z['Empty handling'] != ''){
                            
                            $switchVal[] = array('', $z['Empty handling']);

                        } 
                        
                        $num_sw = count($switchVal);
                        if ( $num_sw > 0 ){
                            
                            $ix = 1;

                            $strSet .= ",".$z['DST Field']." = ";
                            
                            foreach($switchVal as $swk => $swv ){

                                
                                $strSet .= " if (";
                                if (is_null($val[strtoupper($z['SRC Field'])])){
                                    
                                    $strSet .= " NULL ";
                                    
                                }
                                else $strSet .= "'".addslashes($val[strtoupper($z['SRC Field'])])."' ";

                                if (($swv[0] == 'NULL') || is_null($swv[0])){

                                    $strSet .=  "is NULL ";
                                    
                                }
                                else  $strSet .=  " = '".$swv[0]."' ";
                                
                                $strSet .= ",";
                                if ($swv[1] == 'NULL') $strSet .= $swv[1]; //Se il valore è NULL non aggiungiamo gli apici
                                else  $strSet .=  " '".$swv[1]."' ";
                                if ($ix ==  $num_sw){
                                    $strSet .= ", '".addslashes($val[strtoupper($z['SRC Field'])])."') ";
                                }
                                else {
                                    $strSet .= ",";
                                    $ix++;
                                    continue;
                                }
                                    
                                $ix++;
                            }
                            
                            if ($num_sw>1) for($y=1;$y<$num_sw;$y++) $strSet .= ")";
                            
                        }
                        else $strSet .= ",".$z['DST Field']." = '".addslashes($val[strtoupper($z['SRC Field'])])."' "; 

                        break;

                    case stristr($z['Rules'],'callback'):

                        $func_name = substr($z['Rules'], strpos($z['Rules'], "|") + 1);

                        $func_args = explode('|', $z['Callback parameters']);
                        //var_dump($func_args);
                        foreach ($func_args as $k => $v) { 
                            //echo $v;
                            if($v[0] == '$') $func_args[$k] = str_replace('$','', $v);
                            else {
                                $func_args[$k] = addslashes($val[strtoupper($v)]);
                            }
                            
                        };
                        //var_dump($func_args);
                                 
                        $ret_value = $func_name($func_args);
                        
                        if ($ret_value === null){
                            $strSet .= ",".$z['DST Field']." = null "; 
                        }
                        else if ($ret_value === '$skip$') {$skipline=1;}
                        else {
                            $strSet .= ",".$z['DST Field']." = '".$ret_value."' "; 
                        }
                     
                        break;
                        
                    case stristr($z['Rules'],'postinsert'):
                    
                        $func_name = substr($z['Rules'], strpos($z['Rules'], "|") + 1);
                        $arrPost['postinsert']['function'] = $func_name;
                        $arrPost['postinsert']['data'] = $val[strtoupper($z['SRC Field'])];
                        if (!empty($info_campagna)) $arrPost['postinsert']['campaign'] = $info_campagna;
                        
                        break;
                    
                    default:  break;


                }          

            } // fine else                       
            
            $i++;
        
        } 
       //echo $strSet."\n";
        
        if ($skipline == 0) { $arrReturn[] =  array('strset' => $strSet, 'post' => $arrPost);}
        //else echo "skipping";

        
    }
    
    return $arrReturn;
    
}


function doInsertIntoUnileadTbl($mysqli, $arrSet){
    
    //var_dump($arrSet);
    global $logfile;
    global $num_insert;
    $row_inserted = 0;
    $bool_inserted = 0;
    
    if(!$num_insert){
        fwrite($logfile, date("YmdHis")." Truncating table lead_uni \n");
        $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
        $mysqli->query('truncate table lead_uni');
        $mysqli->query('truncate a_giacliente_brand');
        $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    $mysqli->set_charset("utf8");
     
    fwrite($logfile, date("YmdHis")." Inserting rows into lead_uni \n");
    echo " Inserting rows into lead_uni \n";
    //var_dump($arrSet);
    foreach($arrSet as $k => $v){
           
        $strSql  = "insert into lead_uni ";
        $strSql .= $v['strset'];
    
        //echo $strSql."\n";     
        
        if ($mysqli->query($strSql) === TRUE) {
            $row_inserted++;
            $bool_inserted = 1;
            //echo "New record created successfully";
            $id = $mysqli->insert_id;
            
            //Post Insert
            if (!empty($v['post'])){
                
                $func_name = $v['post']['postinsert']['function'];
                $func_args = $v['post']['postinsert']['data'];
                $func_args_campaign = $v['post']['postinsert']['campaign'];
                $func_name($mysqli,$id,$func_args,$func_args_campaign);
                
            }            
            
        } else {
            fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli->error);  
            echo "Error: " . $strSql . "<br>" . $mysqli->error;
        }
    }
        
    if ($bool_inserted) $num_insert++;
    fwrite($logfile, date("YmdHis")." Inserted rows :".$row_inserted."\n");  
    echo " Inserted rows :".$row_inserted."\n";
}

function doInsertCampaignInfoIntoDB($mysqli_src, $mysqli_dest, $tblmo, $tbl, $db_name){
    
    //var_dump($arrSet);
    global $logfile;
    global $campaign_insert;
    global $existing_brands;
        
    $mysqli_src->set_charset("utf8");
    $mysqli_dest->set_charset("utf8");
    
    //echo "campaign_insert".$campaign_insert;
    if(!$campaign_insert){
        fwrite($logfile, date("YmdHis")." Truncating table campagna \n");
        $mysqli_dest->query('SET FOREIGN_KEY_CHECKS=0');
        $mysqli_dest->query('truncate table campagna');
        $mysqli_dest->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    //Leggiamo dalla tabella di origine
    
    $strSql  = "select ";
    $strSql .= " * ";
    $strSql .= " from ".$tblmo;
    $strSql .= " where dbtab= '".$tbl."' ";
    
    //echo $strSql;
     
    if (!$result = $mysqli_src->query($strSql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");

        fwrite($logfile, date("YmdHis")." Query: " . $strSql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli_src->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli_src->error . "\n");
        
        exit;
    }
    
    else {
        
        $ret = $result->fetch_assoc();
        
    }
    
    //var_dump($ret);
    
    $id_brand = 0;
    //Se il brand non è presente in DB lo aggiungiamo ed aggiorniamo l'array dei valori esistenti
    if (!array_key_exists(strtolower($ret['BRAND']), $existing_brands)){
     
        $id_brand = addBrandToDB($mysqli_dest, $ret['BRAND']);
        if(!empty($id_brand)){ $existing_brands[strtolower($ret['BRAND'])]['id'] = $id_brand; }
        //var_dump($existing_brands);
        //echo $id_brand;
    }    
    else {
        $id_brand = $existing_brands[strtolower($ret['BRAND'])]['id']; 
        //echo $id_brand;
    }
     
    fwrite($logfile, date("YmdHis")." Inserting rows into campagna \n");
    echo " Inserting rows into campagna \n";
      
    //scrittura nella tabella delle campagne delle info relative
    $strSql  = "insert into campagna  ";
    $strSql .= " (brand_id, settore, nome_offerta, source_db, source_id, dbtabmo, dbtab, tipo_campagna, target_campagna,";
    $strSql .= " data_start, data_end, leadout_path, shot_path, is_active, optin, ";
    if (isset($ret['IDPRIVACY'])) $strSql .= " id_privacy,";
    $strSql .= " disable_js_validation, disable_php_validation, is_published)";
    $strSql .= " values ";
    $strSql .= " ('".$id_brand."','".$ret['SETTORE']."','".addslashes($ret['NAME'])."','".$db_name."','".$ret['ID']."','".$tblmo."','".$tbl."','".strtolower($ret['TYPE'])."','".addslashes(strtolower($ret['TIPOLOGIA_CAMPAGNA']))."', ";
    $strSql .= "  '".$ret['STARTDATE']."','".$ret['ENDDATE']."','".$ret['LEADOUTPATH']."','".$ret['SHOTPATH']."','".$ret['ACTIVE']."','".$ret['OPTIN']."', ";
    if (isset($ret['IDPRIVACY'])) $strSql .= " '".$ret['IDPRIVACY']."',";
    $strSql .= "  '".$ret['DISABLE_JS_VALIDATION']."','".$ret['DISABLE_PHP_VALIDATION']."','".$ret['PUBLISHED']."') ";
    
    //echo $strSql;
    fwrite($logfile, date("YmdHis")."SQL Inserting campaign: ".$strSql."\n");
    
    if ($mysqli_dest->query($strSql) === TRUE) {
        $campaign_insert++;
        $id = $mysqli_dest->insert_id;
    } else {
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli_dest->error);
        echo "Error: " . $strSql . "<br>" . $mysqli_dest->error;
        
    }
    //echo  "ecco l'id".$id;
    return array('id' => $id, 'id_brand' => $id_brand, 'campaign_info' => $ret);

 }
 
 function addBrandToDB($mysqli, $brand_name){
     
    global $logfile;
     
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
        fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli->error);
        echo "Error: " . $strSql . "<br>" . $mysqli->error;        
    }
    
    return $id;
          
 }

//Concatena nome e cognome e la ragione sociale se il tipo azienda è valorizzato
function concatName1($args){
    
    if (!array_filter($args)) return '';
    
    $strCat = $args[0]." ".$args[1];

    if (!empty($args[2])) $strCat .= " ".$args[3];
    
    //echo $strCat;
    return addslashes($strCat);  
    
}

//Concatena nome, cognome e ragione sociale se presente
function concatName2($args){
    
    if (!array_filter($args)) return '';
    
    $strCat = $args[0]." ".$args[1];
    
    //Concatena la ragione sociale
    if (!empty($args[2])) $strCat .= " ".$args[2];
    
    //echo $strCat;
    return addslashes($strCat);  
    
}

function getTargetCampagna($args){
    
    return 1;
   
}

//Restituisce 0 se il tipo azienda è vuoto o null
function getFormaGiuridica($args){
    
    if (empty($args[0])) return 0;
    else return 1;  
        
}

function getFormaGiuridicaFromString($args){
    
    switch($args[0]){
        
        case 'Per Privati':
        case 'NO':
                                $val = 0;
                                break;
                            
        case 'Per possessore di partita IVA':
        case 'SI':
                                $val = 1;
                                break;
                            
                            
        default:        
                                $val = null;
                                break;
    }
    
    return $val;
            
            
}

function getFormaGiuridicaFromStringNullSi($args){
    
    switch($args[0]){
        
        case 'Per Privati':
        case 'NO':
                                $val = 0;
                                break;
                            
        case 'Per possessore di partita IVA':
        case 'SI':
        case 'non presente':
                                $val = 1;
                                break;
                            
                            
        default:        
                                $val = null;
                                break;
    }
    
    return $val;
            
            
}

function getFormaGiuridicaFromStringNullNo($args){
    
    switch($args[0]){
        
        case 'Per Privati':
        case 'NO':
        case 'non presente':
                                $val = 0;
                                break;
                            
        case 'Per possessore di partita IVA':
        case 'SI':
        
                                $val = 1;
                                break;
                            
                            
        default:        
                                $val = null;
                                break;
    }
    
    return $val;
            
            
}

function getFormaGiuridicaFromStringNullNull($args){
    
    if ($args[0] === null) return 0;
    else {
        switch($args[0]){

            case 'Per Privati':
            case 'NO':
            case 'non presente':
                                    $val = 0;
                                    break;

            case 'Per possessore di partita IVA':
            case 'SI':

                                    $val = 1;
                                    break;


            default:        
                                    $val = null;
                                    break;
        }

        return $val;
    }
            
}

function concatTypeAddressNumber($args){
    
    return addslashes($args[0]." ".$args[1]." ".$args[2]);    
    
}

function getGiaCliente($args){
    
    global $existing_brands;
    
    switch($args[0]){
        
        case 'NO':
                    $val = null;
                    break;
        case 'SI':  
                    $val = $existing_brands[$args[0]]['id'];
                    break;
                
        default:    $val = null;
                    break;
        
        
    }
    
    return $val;
    
}

function filterPhones($args){

//        $matches = array();
//        $args[0] =str_replace('+', "", $args[0]);
//
//	$pattern = "/^0[0-9]{6,11}$/";
//	
//	$ret = preg_match($pattern, $args[0], $matches);
        
        //echo "\n".$ret." ".$args[0];
//	if ($ret)
        if(!empty($args[0]) && $args[0] !== 'non presente') return $args[0];
        else return null;
    
}

function filterCellPhones($args){

//	$matches = array();
//        $args[0] =str_replace('+', "", $args[0]);
//        
//	$pattern = "/^3[2-9]{1}[0-9]{7,9}/";
//        $ret = preg_match($pattern, $args[0], $matches);
//        
//        //echo "\n".$ret." ".$args[0];
//        if ($ret) 
          if(!empty($args[0]) && $args[0] !== 'non presente') return $args[0];
          else return null;
//        else return null;   
    
    
}

function filterRagsoc($args){

	$matches = array();
        
	$patternCF = "/^(.*)([a-zA-Z]{6}[0-9]{2}[abcdehlmprstABCDEHLMPRST]{1}[0-9]{2}([a-zA-Z]{1}[0-9]{3})[a-zA-Z]{1})(.*)$/";
        $ret = preg_match($patternCF, $args[0], $matches);
        
        //if ($ret) $args[0] =str_replace($matches[2], "", $args[0]);
        
        $patternPiva = "/^(.*)([0-9]{11})(.*)$/";
        $ret = preg_match($patternPiva, $args[0], $matches);
        
        //if ($ret) $args[0] =str_replace($matches[2], "", $args[0]);
        
        //echo "\n".$ret." ".$args[0];
        if (!empty($args[0])) return addslashes ($args[0]);
        else return null;   
    
    
}

function filterPivaCodfisc($args){

	$matches = array();
        
	$patternCF = "/^(.*)([a-zA-Z]{6}[0-9]{2}[abcdehlmprstABCDEHLMPRST]{1}[0-9]{2}([a-zA-Z]{1}[0-9]{3})[a-zA-Z]{1})(.*)$/";
        $patternPiva = "/^(.*)([0-9]{11})(.*)$/";
        
        $ret1 = preg_match($patternPiva, $args[0], $matches1); 
        $ret2 = preg_match($patternCF, $args[0], $matches2);
        
        if ($ret1) return $matches1[2];
        else if ($ret2) return $matches2[2];
        else return null;     
    
}

function filterCodfisc($args){
    
    	$matches = array();
        
	$patternCF = "/^(.*)([a-zA-Z]{6}[0-9]{2}[abcdehlmprstABCDEHLMPRST]{1}[0-9]{2}([a-zA-Z]{1}[0-9]{3})[a-zA-Z]{1})(.*)$/";
        
        $ret1 = preg_match($patternCF, $args[0], $matches1); 
        
        if ($ret1) return $matches1[2];
        else return null;   
    
}

function skipInvalidPhones($args){
    
    	$pattern = "/^0[0]*0$/";
        $ret = preg_match($pattern, $args[0], $matches);
        
        if ($ret) return '$skip$';
        else return $args[0];                

}

function joinNumbers($args){
    
    $arr[0] = $args[0].$args[1];
    return filterPhones($arr);  
    
}

function filter_null($var){
    
    if (($var == null)||($var == '')) return 0;
    else return 1;
    
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

function splitMixCognome($args){
    
    if ($args[1] === 'NAME_BINDED') return splitCognome(array('0' => $args[0]));
    else return $args[1];
}

function completeURL($args){
    
    switch($args[0]){
        
        case 'offertea_cpl' :
                                $domain = 'http://offerteadsl.smart-call.it';
                                break;

        case 'offertee_cpl' :
                                $domain = 'http://offerteepromozioni.com';
                                break;          

        case 'offerteo_cpl' :
                                $domain = 'http://offerte.orarisparmio.it';
                                break;  

        case 'offertet_cpl' :
                                $domain = 'http://offerte.tnoleggio.it';
                                break;      
                            
        case 'offertep_cpl' :
                                $domain = 'http://offerte-promozioni.com';
                                break;                            
                            
        case 'promoh3g_cpl' :
                                $domain = 'http://promoh3g.it';
                                break; 
                            
        case 'promolas_cpl' :
                                $domain = 'http://promo-lastminute.com';
                                break; 

        case 'promonew_cpl' :
                                $domain = 'http://promo-news.it';
                                break;                              
                            
        case 'promotel_cpl' :
                                $domain = 'http://promotelefonia.it';
                                break;   

        case 'promozio_cpl' :
                                $domain = 'http://promozioniazienda.it';
                                break;   
                            
        case 'vodafone_cpl' :
                                $domain = 'http://vodafoneazienda.it';
                                break;                               
                            
        default:
                                break;
        
    }
    //echo $args[0].$domain.$args[1];
    return $domain.$args[1];
}

function setGiaCliente($mysqli,$id,$args,$args_campaign){
    
    global $logfile;
    
    if ($args === 'SI'){
        $mysqli->set_charset("utf8");

    //scrittura nella tabella delle campagne delle info relative
        $strSql  = "insert ignore into a_giacliente_brand  ";
        $strSql .= " (lead_id, brand_id) ";
        $strSql .= " values ";
        $strSql .= " ('".$id."', '".$args_campaign['id_brand']."') ";

        //echo $strSql;

        if ($mysqli->query($strSql) === TRUE) {
            $id = $mysqli->insert_id;
        } else {
            fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli->error);
            echo "Error: " . $strSql . "<br>" . $mysqli->error;        
        }
        
    }
    
    return;
    
}

function setGiaClienteSi($mysqli,$id,$args,$args_campaign){
    
    global $logfile;

    //scrittura nella tabella delle campagne delle info relative
        $strSql  = "insert ignore into a_giacliente_brand  ";
        $strSql .= " (lead_id, brand_id) ";
        $strSql .= " values ";
        $strSql .= " ('".$id."', '".$args_campaign['id_brand']."') ";

        //echo $strSql;

        if ($mysqli->query($strSql) === TRUE) {
            $id = $mysqli->insert_id;
        } else {
            fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli->error);
            echo "Error: " . $strSql . "<br>" . $mysqli->error;        
        }
            
    return;
    
}

function setGiaClienteSkyFastweb($mysqli,$id,$args,$args_campaign){
    
    global $logfile;
    global $existing_brands;
    //var_dump($existing_brands);
    //echo $args;
    
    if ($args !== 'No, nessuno dei due'){
        
        
            if (($args === 'Si, solo Sky') ||
                ($args === 'SI_FASTWEB_SKY') || 
                ($args === 'Si, di entrambi')){
                
                if (!array_key_exists('sky', $existing_brands)){
                        $ids['sky'] = addBrandToDB($mysqli, 'sky');
                        if(!empty($ids['sky'])){ $existing_brands['sky']['id'] = $ids['sky']; }
                }
                else $ids['sky'] = $existing_brands['sky']['id'];
            }
            
            if (($args === 'Si, solo Fastweb') ||
                ($args === 'SI') ||
                ($args === 'Si, di entrambi')){
                
                if (!array_key_exists('fastweb', $existing_brands)){
                        $ids['fastweb'] = addBrandToDB($mysqli, 'fastweb');
                        if(!empty($ids['fastweb'])){ $existing_brands['fastweb']['id'] = $ids['fastweb']; }
                }
                else $ids['fastweb'] = $existing_brands['fastweb']['id'];
            }
            
         //var_dump($ids);
        
        $mysqli->set_charset("utf8");

    //scrittura nella tabella delle campagne delle info relative
        $strSql  = "insert ignore into a_giacliente_brand  ";
        $strSql .= " (lead_id, brand_id) ";
        $strSql .= " values ";
        
        $num_ids = count($ids);
        $i=1;
        foreach($ids as $key => $val){
            
            $strSql .= " ('".$id."', '".$val."') ";
            if ($i<$num_ids) $strSql .= ",";
            $i++;
            
        }       
        
        //echo $strSql;

        if ($mysqli->query($strSql) === TRUE) {
            $id = $mysqli->insert_id;
        } else {
            fwrite($logfile, date("YmdHis")."Error: " . $strSql . "<br>" . $mysqli->error);
            echo "Error: " . $strSql . "<br>" . $mysqli->error;        
        }

    }
    
    return;
    
}

function getCampagnaInfo($mysqli, $db, $mail){
    
    global $logfile;
    
    $sql   = "SELECT * FROM campagna ";
    $sql  .= " where source_db = '".$db."' and dbtab = '".$mail."'";
    
    //echo $sql;
    
    if (!$result = $mysqli->query($sql)) {

        fwrite($logfile, date("YmdHis")." Error: Failed to make a MySQL query, here is why: \n");

        fwrite($logfile, date("YmdHis")." Query: " . $sql . "\n");
        fwrite($logfile, date("YmdHis")."Errno: " . $mysqli->errno . "\n");
        fwrite($logfile, date("YmdHis")."Error: " . $mysqli->error . "\n");
        
        //writeMailforFailed();
        
        exit;
    }
    
    else {
        
        $ret = $result->fetch_assoc();
            
        
        //print_r($ret); 
            
        
    }
    
    if(!empty($ret))  return array('id' => $ret['id'], 'id_brand' => $ret['brand_id'], 'campaign_info' => $ret);         
    else return null;
    
}