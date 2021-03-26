<?php 

	/** LOGICA:
		1 - CREAZIONE DELLE CAMPAGNE CONCORSO SU GESTIONE (UNA CAMPAGNA PER OGNI TEMPLATE/URL CONCORSO)
			|- le campagne verranno generate in base al titolo del concorso
			|- brand_id = '53'; (senza brand) 
			|- nome_offerta = $titolo_concorso 
			|- settore: 'Concorso';
			|- tipo_campagna = 'Altro';
			|- Target_campagna: altro
			
		2 - PRELEVO TUTTE LE LEAD DALLA TABELLA LEAD SU premi365_db23 $conn_cnc 
		3 - Mappo tutti i campi per l'importazione della lead
		4 - setto i campi statici:
			|- $rowGest["campagna_id"]; // creare campagna_id concorso su gestione 
			|- $rowGest["source_db"]; // premi365_db23
		5 - PER OGNI LEAD PRELEVO I CAMPI CUSTOM 
		6 - DEFINIZIONE DI UNA FUNZIONE PER IMPORTAZIONE CAMPI CUSTOM: 
			6.0 - i campi custom sul concorso sono nella tebelle:
				|- lead_campi_value lead_id, campo_id, valore
				|- campi.identificativo
			6.1 - IL DB gestione utilizza le tabelle:
				|- lead_uni_extra_fields id, name (da inserire una sola volta e prelevare l'id)
				|- lead_uni_extra_values field_id, name (valore)
				|- a_lead__extra_values lead_id, value_id (valore)
		7 - importazione dei dati.
	**/

// controllo esecuzione script solo da qui
error_reporting(E_ERROR);

$db_cnc = array(	'host' 		=> '46.254.38.137',
					'db' 		=> 'premi365_db23',
					'user' 		=> 'premi365_utente2',
					'pass' 		=> 'x?h{JoE3AC6W',
				);
$db_gest = array(	'host' 		=> '127.0.0.1',
					'db' 		=> 'la_gestione',
					'user' 		=> 'la_gest_userdibi',
					'pass' 		=> ';X8#Ola(pQ*3@T?f',
);



	
	
try {
	// connessione al db dei concorsi
	$conn_cnc = new \PDO("mysql:host=".$db_cnc['host'].";dbname=".$db_cnc['db']."", $db_cnc['user'],  $db_cnc['pass']);
	$conn_cnc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn_cnc->exec("set names utf8");
	
	// connessione al db dei concorsi
	$conn_gest = new \PDO("mysql:host=".$db_gest['host'].";dbname=".$db_gest['db']."", $db_gest['user'],  $db_gest['pass']);
	$conn_gest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn_gest->exec("set names utf8");
	
	// prelevo l'ultimo id inserito da gestione
	$sql_gest_getLastId = "SELECT source_id FROM lead_uni WHERE source_db ='premi365_db23' AND source_tbl='lead' ORDER BY source_id DESC LIMIT 0,1";
	$stmt_gest = $conn_gest->prepare($sql_gest_getLastId);
	$stmt_gest->execute();

	if($stmt_gest->rowCount()>0){
		while ($row  = $stmt_gest->fetch()) {
			$lastIdGestione = $row['source_id'];
		}
	}else{
		$lastIdGestione = 1;
	}
	
	// PRELEVO LE LEAD NON LAVORATE
	$sql_cnc_getLead = "SELECT l.*, c.titolo as titolo_concorso  FROM lead l   
						INNER JOIN concorso c ON l.codice_premio = c.codice_premio
						WHERE l.id > " . $lastIdGestione ." ORDER BY id ASC";
	$stmt_cnc = $conn_cnc->prepare($sql_cnc_getLead);
	//$stmt_cnc->bindparam(1, $slug);
	$stmt_cnc->execute();

	if($stmt_cnc->rowCount()>0){
		$lead_cnc = array();
		while ($row_cnc  = $stmt_cnc->fetch()) {
			$lead_cnc["source_db"] 				= 'premi365_db23';
			$lead_cnc["source_tbl"] 			= 'lead'; 
			$lead_cnc["source_id"]				= $row_cnc["id"];
			$lead_cnc["nome"]					= $row_cnc["nome"];
			$lead_cnc["cognome"]				= $row_cnc["cognome"];
			$lead_cnc["email"]					= $row_cnc["email"];
			$lead_cnc["cellulare"]				= $row_cnc["cellulare"];
			$lead_cnc["sesso"]					= $row_cnc["sesso"];
			$lead_cnc["titolo"]					= $row_cnc["titolo"];
			$lead_cnc["tel_fisso"]				= $row_cnc["tel_fisso"];
			$lead_cnc["operatore_cell"]			= $row_cnc["operatore_cell"];
			$lead_cnc["forma_giuridica"]		= $row_cnc["forma_giuridica"];
			$lead_cnc["piva"]					= $row_cnc["piva"];
			$lead_cnc["data_nascita"]			= $row_cnc["data_nascita"];
			$lead_cnc["luogo_nascita"]			= $row_cnc["luogo_nascita"];
			$lead_cnc["professione"]			= $row_cnc["professione"];
			$lead_cnc["codice_fiscale"]			= $row_cnc["codice_fiscale"];
			$lead_cnc["residente_citta"]		= $row_cnc["residente_citta"];
			$lead_cnc["residente_prov"]			= $row_cnc["residente_prov"];
			$lead_cnc["residente_regione"]		= $row_cnc["residente_regione"];
			$lead_cnc["residente_cap"]			= $row_cnc["residente_cap"];
			$lead_cnc["residente_via"]			= $row_cnc["residente_via"];
			$lead_cnc["residente_civ"]			= $row_cnc["residente_civ"];
			$lead_cnc["residente_latitudine"]	= $row_cnc["residente_latitudine"];
			$lead_cnc["residente_longitudine"]	= $row_cnc["residente_longitudine"];
			$lead_cnc["privacy"]				= $row_cnc["privacy"];
			$lead_cnc["privacy_terzi"]			= $row_cnc["privacy_terzi"];
			$lead_cnc["privacy_partner"]		= $row_cnc["privacy_partner"];
			$lead_cnc["codice_premio"]			= $row_cnc["codice_premio"];
			$lead_cnc["ip"]						= $row_cnc["ip"];
			$lead_cnc["url"]					= $row_cnc["url"];
			$lead_cnc["data"]					= $row_cnc["data"];
			$lead_cnc["media"]					= $row_cnc["media"];
			$lead_cnc["code"]					= $row_cnc["code"];
			$lead_cnc["titolo_concorso"]		= $row_cnc["titolo_concorso"];
			$lead_cnc["campagna_id"]			= getCampagnaByCncCod($lead_cnc["titolo_concorso"]); // creare campagna_id concorso
				
			// custom field per la lead su concorsi che userò dopo aver generato la lead in lead_uni:
			// genero una array di chiavi=>valori:
				
			$customFields = getCustomFields($lead_cnc["source_id"],$conn_cnc);
			
			
			// scrittura in lead_uni
			$lead_uni_id = insertIntoLeadUni($lead_cnc,$conn_gest);
			if($lead_uni_id && !empty($customFields)){ // se ho ricevuto un id dalla funzione di inserimento
				// scrivo i custom fields per la lead
				foreach($customFields as $nome_campo => $valoreCampo){
					saveCustomField($lead_id,$nome_campo,$valoreCampo,$conn_gest); // salvataggio del campo custom
				}
			}
		}
	}else{
		// non sono state trovate lead con id maggiore di . $lastIdGestione;
	}

}catch(PDOException $e){
	echo "Error: " . $e->getMessage();
}
		
		
		/** la funzione preleva dal DB del concorso i campi custom da inserire in gestione */
		function getCustomFields($lead_id,$conn_cnc){
				$arrayCustomFields = array();
				try{
				// select della campagna
				$sql_select_campiCustom = "select c.identificativo, lcv.valore from lead_campi_value lcv 
											inner join campi c on lcv.campo_id = c.id
											where lcv.lead_id =" . $lead_id . "";
				$stmt_cnc = $conn_cnc->prepare($sql_select_campiCustom);
				//$stmt_cnc->bindparam(1, $slug);
				$stmt_cnc->execute();
				if($stmt_cnc->rowCount()>0){
					// la campagna esiste, prelevo l'id
					while ($row_cmp  = $stmt_cnc->fetch()) {
						$arrayCustomFields[$row_cmp['identificativo']] = $row_cmp['valore'];
					}
				}else{
					// la lead non ha campi custom
				}
			}catch(PDOException $e){
				echo "Errore lettura Gestione getCampagnaByCncCod : " . $e->getMessage();
			}
			return $arrayCustomFields;
		}
		
		/**
			La funzione verifica l'esistenza di una campagna con nome_offerta = titolo concorso
			in caso esista, restutisce l'id della campagna, nel caso non esista, la genera e restituisce l'id inserito.
		*/
		function getCampagnaByCncCod($titoloConcorso, $conn_gest){
			$id_campagna = 0;
			
			try{
				// select della campagna
				$sql_select_campagna = "SELECT id from campagna where nome_offerta ='" . $titoloConcorso . "'";
				$stmt_ges = $conn_gest->prepare($sql_cnc_getLead);
				//$stmt_cnc->bindparam(1, $slug);
				$stmt_ges->execute();

				if($stmt_ges->rowCount()>0){
					// la campagna esiste, prelevo l'id
					while ($row_cmp  = $stmt_ges->fetch()) {
						$id_campagna = $row_cmp['id'];
					}
				}else{
					// la campagna non esiste, devo generarla
					$brand_id = 53; // id per "nessun brand"
					$settore = 'Concorsi';
					$target_cmp = 'consumer';
					$tipo_cmp = 'altro';
					
					$sql_insert_cmp = "INSERT INTO campagna (brand_id, settore,nome_offerta,target_campagna,tipo_campagna)
										VALUES('". $brand_id ."','". $settore ."','". $titoloConcorso ."','". $target_cmp ."','". $tipo_cmp ."')";
					$stmt_ges = $conn_gest->prepare($sql_insert_cmp);
					//$stmt_cnc->bindparam(1, $slug);
					$stmt_ges->execute();
					$id_campagna = $conn_gest->lastInsertId();
				}
			}catch(PDOException $e){
				echo "Errore lettura Gestione getCampagnaByCncCod : " . $e->getMessage();
			}
			
			return $id_campagna;
		}
		
		function insertIntoLeadUni($lead,$conn_gest){
			$id_leadLead_uni = false;
			if(is_array($lead)){
				try{
					// le chiavi dell'array $lead sono identiche ai nomi delle colonne di lead_uni
					$colonne_a =  array_keys($lead); // creo un array di nomi colonna
					$colonne = implode(',',$colonne_a);
					$valori = "'" . implode("','", $lead) . "'"; 

					// select della campagna
					$sql_insert_lead_uni = "INSERT INTO lead_uni (".$colonne.") values(".$valori.")";
					$stmt_ges = $conn_gest->prepare($sql_insert_lead_uni);
					$stmt_ges->execute();
					$id_leadLead_uni = $conn_gest->lastInsertId();
				}catch(PDOException $e){
					echo "Errore scrittura su Gestione insertIntoLeadUni: " . $e->getMessage();
				}
			} // fine controllo lead is array
				return $id_leadLead_uni;
		}
		
		function checkFieldIsPresent($nome_campo,$conn_gest){ 
			// verifico se il campo è già presente in lead_uni_extra_fields
			$sql_select_fields = "select id from lead_uni_extra_fields where name = '". $nome_campo. "';";
			$id_campo = false;
			try{
				$stmt_ges = $conn_gest->prepare($sql_select_fields);
				$stmt_ges->execute();
				if($stmt_ges->rowCount()>0){ 
					// il campo esiste, prelevo l'id
					while ($row_cmp  = $stmt_ges->fetch()) {
						$id_campo = $row_cmp['id'];
					}
				}else{
					// il campo non esiste, devo generarla
					$sql_insert_campo = "INSERT INTO lead_uni_extra_fields (name, creation_date)
										VALUES('". $nome_campo ."',NOW())";
					$stmt_ges = $conn_gest->prepare($sql_insert_campo);
					$stmt_ges->execute();
					$id_campo = $conn_gest->lastInsertId();
				}
			}catch(PDOException $e){
				echo "Errore scrittura su Gestione checkFieldIsPresent: " . $e->getMessage();
			}
			return $id_campo;
		}
		
		function insertCustomValueById($field_id,$valoreCampo,$conn_gest){
			$id_valore = false;
			try{
					// il campo non esiste, devo generarla
					$sql_insert_campo = "INSERT INTO lead_uni_extra_values (field_id, name, creation_date)
										VALUES('".$field_id ."','". $nome_campo ."',NOW())";
					$stmt_ges = $conn_gest->prepare($sql_insert_campo);
					$stmt_ges->execute();
					$id_valore = $conn_gest->lastInsertId();
			}catch(PDOException $e){
				echo "Errore scrittura su Gestione checkFieldIsPresent: " . $e->getMessage();
			}
			return $id_valore;
		}
		function associateCustomValueField($lead_id,$value_id,$conn_gest){
			$id_valore = false;
			try{
					// il campo non esiste, devo generarla
					$sql_insert_campo = "INSERT INTO a_lead_extra_values (lead_id, value_id, creation_date)
										VALUES('".$lead_id ."','". $value_id ."',NOW())";
					$stmt_ges = $conn_gest->prepare($sql_insert_campo);
					$stmt_ges->execute();
					$id_valore = $conn_gest->lastInsertId();
			}catch(PDOException $e){
				echo "Errore scrittura su Gestione checkFieldIsPresent: " . $e->getMessage();
			}
			return $id_valore;
		}
		
		function saveCustomField($lead_id,$nome_campo,$valoreCampo,$conn_gest){
			$field_id = checkFieldIsPresent($nome_campo,$conn_gest); // verifico se il campo è già presente in lead_uni_extra_fields, in caso positivo recupero il suo id
			// recuperato l'id inserisco il valore in lead_uni_extra_values field_id, name 
			$value_id = insertCustomValueById($field_id,$valoreCampo,$conn_gest);
			associateCustomValueField($lead_id,$value_id,$conn_gest);
		}
		
?>