<?php
	namespace AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Doctrine\Common\Util\Debug;
use Exporter\Source\DoctrineORMQuerySourceIterator;

use DoctrineORMEntityManager;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;

use AppBundle\CustomFunc\Handler as Handler;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Landing as Landing;
use AppBundle\Entity\Cliente as Cliente;
use AppBundle\Entity\A_landing_cliente as LandingCampagna;

use AppBundle\Entity\Fornitori as Fornitori;

use Symfony\Component\Security\Core\Security;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
//use AppBundle\CustomFunc\Pager as Pager;



class BlacklisterAdminController extends Controller
{
    //modifica Francesco 16042021
    //produzione@linkappeal.it
	public static $emailProduction = 'francescomartucci@linkappeal.it';
	// RENDER FUNCIONS
	//render listato template

    public function blacklisterAction(Request $request = null){
		$message=$request->get('messaggio');
		$res='';
		$ProdId=$request->get('ProdId');
		$ProdAmbiente=$request->get('ProdAmbiente');
		return $this->render('blacklister.html.twig', array(
			'messaggio'		=> $message,
			'res'			=> $res,
			'ProdId'		=> $ProdId,
			'ProdAmbiente'	=> $ProdAmbiente,
        ), null);
    }

    public function searchContactAllAction(Request $request){
         $firstfield=$request->get('first-field');
         $Par =array();
         $conditionQ1 =array();
         $arrvalues=array();
        if($firstfield=='email' OR $firstfield=='id'){
            $operator1=" = ";
            $values = trim($request->get('first-value'));
            $arrvalues = explode("\n",$values);
            foreach($arrvalues as $value) {
                $value = str_replace(' ', '', $value);
                $conditionQ1[] = $firstfield . $operator1 . "'" . $value . "' ";
                $Par[].=$firstfield.': '.$value;
            }
        }else{
            $operator1=" LIKE ";
            $values = trim($request->get('first-value'));
            $arrvalues= explode("\n",$values);
            foreach($arrvalues as $value) {
               // $value = str_replace(' ', '',$value);
                $md5_par = $firstfield . ': ' . "md5(" . $value . ") ";
                $conditionQ1[] = $firstfield . $operator1 . "'" . $value . "' OR " . $firstfield . $operator1 . "md5('". $value ."')";
                $Par[].=$firstfield.': '.$value.' & '.$md5_par;
            }
        }


        $queryIntOk='';
        $queryEstOk='';
        foreach ($Par as $p) {
            $this->write_history_blk('search', $p);
        }
        $queryEsterna=array();
        $queryInterna=array();
        foreach($conditionQ1 as $cond) {
            $queryInterna[] = "SELECT * FROM lead_uni lu WHERE lu." . $cond;
            $queryEsterna[] = "SELECT * FROM lead_uni_esterne WHERE " . $cond;
        }

        $contatti_intHtml = '';
        $usi_int = array();
        $usi_intHTML = '';
        $premi = array();
        $campagna = array();
        $leadUniIdsArr = array();
        $leadUniIdsArrCell = array();
        $inBl = array();
        $IntLogs = array();
        $UsiRecenti = array();
        $interneHTML = '';


            $_from_leaduni = array();
            foreach ($queryInterna as $query) {
                if (!empty($query)) {
                    $em = $this->getDoctrine()->getManager();
                    $stmt = $em->getConnection()->prepare($query);
                    $stmt->execute();
                    $_from_leaduni[] = $stmt->fetchAll();
                }
            }
            $arrcontatti=array();
            foreach ($_from_leaduni as $contatti) {
                foreach ($contatti as $contatto) {
                    $cont_id = $contatto['id'];
                    $leadUniIdsArr[] = $cont_id;
                    $IntLogs[$cont_id] = array();
                    $UsiRecenti[$cont_id] = 0;
                    $checkLogs = 0;
                    $inBlc = $this->check_if_blacklisted_int($cont_id, $contatto['cellulare']);


                    if (is_array($inBlc)) {
                        $inBl[$cont_id] = date('Y-m-d h:m', $inBlc[0]);
                    }
                    if ($contatto['source_db'] == 'premi365_db23') {
                        $premi[$cont_id] = array('source_id' => $contatto['source_id'], 'source_tbl' => $contatto['source_tbl']);
                    } else {
                        $campagna[$cont_id] = array('campagna' => $contatto['campagna_id'], 'source_tbl' => $contatto['source_tbl']);
                    }

                    ${'contatto_int_' . $cont_id} = '<table class="contatto leaduni" data-id="' . $cont_id . '"><tr>';
                    ${'contatto_int_' . $cont_id . 'th'} = '<tr>';
                    ${'contatto_int_' . $cont_id . 'td'} = '<tr>';
                    foreach ($contatto as $field => $value) {
                        if ($field == 'data' or $field == 'indirizzo_ip' or $field == 'url') {
                            if ($value != '') {
                                $IntLogs[$cont_id][$field] = $value;
                                $checkLogs++;
                            } else {
                                $IntLogs[$cont_id][$field] = '';
                            }
                        }
                        if ($field == 'cellulare') {
                            $leadUniIdsArrCell[$cont_id] = $value;
                        }
                        if ($value != '') {
// //////////////////////////////////////////////////////////////////////////////////////////////
                            if ($field == 'cellulare' && (strlen($value) > 15)) {
                                ${'contatto_int_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                                ${'contatto_int_' . $cont_id . 'td'} .= '<td style="background-color: rgb(102, 217, 255);">' . $value . '</td>';
                            } else {
                                ${'contatto_int_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                                ${'contatto_int_' . $cont_id . 'td'} .= '<td>' . $value . '</td>';
                            }
// //////////////////////////////////////////////////////////////////////////////////////////////
                        }
                    }
                    ${'contatto_int_' . $cont_id} .= ${'contatto_int_' . $cont_id . 'th'} . '</tr>' . ${'contatto_int_' . $cont_id . 'td'} . '</tr></table>';
                    if ($checkLogs == 3) {
                        $IntLogs[$cont_id]['status'] = 'log-presente';
                        $IntLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-green">log presente</span>';
                    } else {
                        $IntLogs[$cont_id]['status'] = 'Log-non-presente';
                        $IntLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-red">log non presente</span>';
                    }

                }
            }


                    if(!empty($leadUniIdsArr)) {
                        $interneHTML = '<div id="interne" name="all"><h4>Interne <small>(corrispondenze: ' . count($leadUniIdsArr) . ')</small></h4></p>';
                        //$interneHTML='<div id="interne"><h4>Interne <small>(corrispondenze: '.count($leadUniIdsArr).')</small></h4></p>'.$queryInterna.'<p><p>'.implode(',',$leadUniIdsArr).'</p>';
                        //abbiamo trovato contatti in leaduni
                        //ritrova noleggi

                        $usi_intHTML = '';
                        $QueryExtraction = "SELECT * FROM extraction_history WHERE lead_id in (" . implode(',', $leadUniIdsArr) . ");";
                        //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                        $em2 = $this->getDoctrine()->getManager();
                        $stmt2 = $em2->getConnection()->prepare($QueryExtraction);
                        $stmt2->execute();
                        $_from_extraction = $stmt2->fetchAll();
                        //echo "from extraction: ".$QueryExtraction."<br>";
                        foreach ($_from_extraction as $extracted) {
                            $cont_id = $extracted['lead_id'];
                            if (!isset(${'usi_int_' . $cont_id})) {
                                ${'usi_int_' . $cont_id} = array();
                            }
                            $cliente = $this->getclienteleaduni($extracted['cliente_id']);
                            $cliente = $cliente . ' (id:' . $extracted['cliente_id'] . ')';
                            $strToTime = strtotime($extracted['data_estrazione']);
                            $rec = $this->check_uso_recente($extracted['tipo_estrazione'], strtotime($extracted['data_sblocco']));
                            //detect uso recente and store it < 90 giorni
                            if ($rec == 'recente') {
                                $UsiRecenti[$cont_id] = 1;
                            }
                            ${'usi_int_' . $cont_id}[$strToTime] = array($extracted['tipo_estrazione'], $extracted['data_estrazione'], $extracted['data_sblocco'], $cliente, 'estrazione/landing', $rec);


                            //print_r($extracted);echo "<br>";

                        }
                        //echo "<br><br>";
                        //ritrova estrazioni sms
                        //ritrova noleggi
                        $QueryExtractionSms = "SELECT * FROM extraction_sms WHERE source_id in (" . implode(',', $leadUniIdsArr) . ") AND Tabella_origine='lead_uni' AND stato_invio=1;";
                        //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                        $em3 = $this->getDoctrine()->getManager();
                        $stmt3 = $em3->getConnection()->prepare($QueryExtractionSms);
                        $stmt3->execute();
                        $_from_extraction_sms = $stmt3->fetchAll();
                        $id_from_extractionsms = array();
                        //echo "from extraction sms: ".$QueryExtractionSms."<br>";
                        foreach ($_from_extraction_sms as $extractedSms) {
                            $cont_id = $extractedSms['source_id'];
                            //recupero dati basati sulla campagna_id: nome, cliente_id, data_lancio
                            $CSmsId = $extractedSms['campagna_id'];
                            $QueryCampagnaSms = "SELECT campagna, cliente_id, data_lancio FROM campagne_sms WHERE stato=4 AND id=" . $CSmsId;
                            $emCS = $this->getDoctrine()->getManager();
                            $stmtCS = $emCS->getConnection()->prepare($QueryCampagnaSms);
                            $stmtCS->execute();
                            $_campagne_sms = $stmtCS->fetchAll();
                            $cliente = '';
                            //echo "from CAMPAGNE sms: ".$QueryCampagnaSms."<br>";
                            foreach ($_campagne_sms as $_campagna_sms) {// row returned
                                $cliente = $this->getclienteleaduni($_campagna_sms['cliente_id']);
                            }
                            if ($cliente != '') {// campagna finded
                                if (!isset(${'usi_int_' . $cont_id})) {
                                    ${'usi_int_' . $cont_id} = array();
                                }
                                $cliente = $cliente . ' (id:' . $_campagna_sms['cliente_id'] . ')';
                                $strToTime = strtotime($_campagna_sms['data_lancio'] . ' 12:00:00');
                                //il recente è se inferiore a due mesi dall'estrazione.. ovvero vado indietro di 30 giorni dalla data di estrazione e lo passo alla func
                                $rec = $this->check_uso_recente('lancio sms', ($strToTime - 2592000));
                                //detect uso recente and store it < 90 giorni
                                if ($rec == 'recente') {
                                    $UsiRecenti[$cont_id] = 1;
                                }
                                ${'usi_int_' . $cont_id}[$strToTime] = array('lancio sms <small>(' . $_campagna_sms['campagna'] . ')</small>', $_campagna_sms['data_lancio'], '', $cliente, 'extraction sms', $rec);
                                //print_r($extractedSms);echo "<br>";
                                $id_from_extractionsms[] = $extractedSms['id'];
                                $id_from_extractionsmsDopp[$extractedSms['id']] = $extractedSms['source_id'];
                            }
                        }
                        //echo "<br><br>";
                        if (is_array($id_from_extractionsms) and !empty($id_from_extractionsms)) {
                            //ritrova risposte sms
                            $QueryRisposteSms = "SELECT * FROM sms_replies WHERE extraction_id in (" . implode(',', $id_from_extractionsms) . ");";
                            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                            $em4 = $this->getDoctrine()->getManager();
                            $stmt4 = $em4->getConnection()->prepare($QueryRisposteSms);
                            $stmt4->execute();
                            $_from_sms_replies = $stmt4->fetchAll();

                            //echo "from extraction sms: ".$QueryRisposteSms."<br>";
                            foreach ($_from_sms_replies as $replie) {
                                $cont_id = $id_from_extractionsmsDopp[$replie['extraction_id']];
                                if (!isset(${'usi_int_' . $cont_id})) {
                                    ${'usi_int_' . $cont_id} = array();
                                }
                                $cliente = $this->getclienteleaduni($replie['client_id']);
                                $cliente = $cliente . ' (id:' . $replie['client_id'] . ')';

                                $strToTime = strtotime($replie['data'] . ' ' . $replie['ora']);
                                //il recente è se inferiore a 6 mesi dall'estrazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                                $rec = $this->check_uso_recente('risposta sms', ($strToTime + 7776000));
                                //detect uso recente and store it < 90 giorni
                                $assigned = 'non accettata';
                                if ($replie['assigned'] == 1) {
                                    $assigned = 'accettata';
                                } else {
                                    $rec = 'non-recente';
                                }
                                if ($rec == 'recente') {
                                    $UsiRecenti[$cont_id] = 1;
                                }
                                ${'usi_int_' . $cont_id}[$strToTime] = array('risposta sms', $replie['data'] . ' ' . $replie['ora'], '', $cliente, 'risposta ' . $assigned, $rec);
                                //print_r($replie);echo "<br>";
                            }
                            //echo "<br><br>";
                        }
                        $RRR = 0;
                        if (is_array($premi) and !empty($premi)) {
                            $RRR = 1;
                            //ritrova usi di concorso
                            foreach ($premi as $keyLeadUni => $contattopremi) {
                                if ($contattopremi['source_tbl'] == 'lead_esterne') {
                                    $esterna = 1;
                                } else {
                                    $esterna = 0;
                                }
                                $QueryPremi = "SELECT * FROM clienti_lead WHERE lead_id = " . $contattopremi['source_id'] . " AND esterna=" . $esterna . ";";
                                //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                                $em5 = $this->getDoctrine()->getManager('concorso_man');
                                $stmt5 = $em5->getConnection()->prepare($QueryPremi);
                                $stmt5->execute();
                                $_from_concorso_lead = $stmt5->fetchAll();
                                $id_from_extractionsms = array();
                                //echo "from concorso: ".$QueryPremi."<br>";
                                $cont_id = $keyLeadUni;
                                foreach ($_from_concorso_lead as $contattoConcorso) {
                                    if (!isset(${'usi_int_' . $cont_id})) {
                                        ${'usi_int_' . $cont_id} = array();
                                    }
                                    $tipo = 'Cootitolarit&agrave;';
                                    if (array_key_exists('tipologia_noleggio', $contattoConcorso) and $contattoConcorso['tipologia_noleggio'] == 1) {
                                        $tipo = 'Noleggio';
                                    }
                                    $cliente = $this->getclienteconcorso($contattoConcorso['cliente_id']);
                                    $cliente = $cliente . ' (id concorso:' . $contattoConcorso['cliente_id'] . ')';
                                    $strToTime = strtotime($contattoConcorso['data_invio']);
                                    //il recente è se inferiore a 6 mesi dall'associazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                                    $rec = $this->check_uso_recente($tipo, ($strToTime + 7776000));
                                    //detect uso recente and store it < 90 giorni
                                    if ($rec == 'recente') {
                                        $UsiRecenti[$cont_id] = 1;
                                    }
                                    ${'usi_int_' . $cont_id}[$strToTime] = array($tipo, $contattoConcorso['data_invio'], '', $cliente, 'concorso', $rec);
                                    //print_r($contattoConcorso);echo "<br>";
                                }
                                //echo "<br><br>";
                            }
                        }

                        /*if(is_array($campagna) AND !empty($campagna)){
                            //ritrova usi di concorso
                            foreach($campagna as $keyLeadUni=>$contattocampagna){
                                $QueryCampagna="SELECT * FROM a_landing_cliente WHERE campagna_id = ".$contattocampagna['campagna']." AND mailCliente='".$contattocampagna['source_tbl']."';";
                                //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                                $em6 = $this->getDoctrine()->getManager();
                                $stmt6 = $em6->getConnection()->prepare($QueryCampagna);
                                $stmt6->execute();
                                $_from_campagna =  $stmt6->fetchAll();
                                $id_from_extractionsms=array();
                                //echo "from campagna: ".$QueryCampagna."<br>";
                                foreach($_from_campagna as $Campagna){
                                    $usi_int[$replie['data_invio']]=array($contattopremi['source_id'],$tipo, $replie['data_invio'],'',$cliente);
                                    print_r($Campagna);echo "<br>";
                                }
                                //echo "<br><br>";
                            }
                        }
                        */
                        //CREAT INT HTML FROM DATAS
                        $usi_intHTML .= '<table class="table-blk">';

                        foreach ($leadUniIdsArr as $cont_id) {
                            $usi_intHTML .= '<div id="overlay" ><div id="loader-id-blacklister" class="mini-loader1"></div></div>';
                            $blacklistedClass = 'not-blacklisted';
                            $blacklistedClassString = '<div class="show-blacklist"></div>';
                            if (array_key_exists($cont_id, $inBl)) {
                                $blacklistedClass = 'blacklisted';
                                if ($inBl[$cont_id] != 0) {
                                    $blacklistedClassString = '<div class="show-blacklist">Blacklistato il ' . $inBl[$cont_id] . '</div>';
                                } else {
                                    $blacklistedClassString = '<div class="show-blacklist">Blacklistato</div>';
                                }
                            }

                            if ($UsiRecenti[$cont_id] == 1) {
                                $recentspan = '<span  class="bl-etichetta bl-etichetta-green">uso recente</span>';
                            } else {
                                $recentspan = '<span  class="bl-etichetta bl-etichetta-red">uso non recente</span>';
                            }
                            $contactHeader = '<h4>ID INTERNO: ' . $cont_id . ' | LOG <span class="logs">Data: ' . $IntLogs[$cont_id]["data"] . ', Url: ' . $IntLogs[$cont_id]["url"] . ', Ip: ' . $IntLogs[$cont_id]["indirizzo_ip"] . '</span>' . $IntLogs[$cont_id]["statusHtml"] . $recentspan . '</h4>' . $blacklistedClassString;
                            $usi_intHTML .= '<tr class="contatto-tr-title contatto-tr ' . $blacklistedClass . ' ' . $IntLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="' . $leadUniIdsArrCell[$cont_id] . '"><td class="body-v">' . $contactHeader . '</td></tr><tr class="contatto-tr-data contatto-tr ' . $blacklistedClass . ' ' . $IntLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="' . $leadUniIdsArrCell[$cont_id] . '"><td class="body-v"><div class="contatto-dati "><div class="typoOfT"><span>Dati</span></div><div>' . ${'contatto_int_' . $cont_id} . '</div></div>';
                            if (isset(${'usi_int_' . $cont_id}) and count(${'usi_int_' . $cont_id}) > 0) {
                                $usi_intHTML .= '<div class="contatto-usi"><div class="typoOfT"><span>Usi</span></div><div><table><tr><th>Aging</th><th>Tipologia</th><th>Data Inizio</th><th>Data fine</th><th>Cliente</th><th>Additional</th></tr>';
                                krsort(${'usi_int_' . $cont_id});
                                foreach (${'usi_int_' . $cont_id} as $Data => $Dati) {
                                    $usi_intHTML .= '<tr class="uso-row ' . $Dati[5] . '">';
                                    $usi_intHTML .= '<td>' . $Dati[5] . '</td><td>' . $Dati[0] . '</td><td>' . $Dati[1] . '</td><td>' . $Dati[2] . '</td><td>' . $Dati[3] . '</td><td>' . $Dati[4] . '</td>';
                                    $usi_intHTML .= '</tr>';
                                }
                                $usi_intHTML .= '</table></div></div>';
                            } else {
                                $usi_intHTML .= '<div class="" style="padding-left:7px;border:none !important;"><p><b>Usi: </b>Nessun uso trovato per il contatto</p></div>';
                            }
                            $usi_intHTML .= '<div class="contatto-button"><input  type="text" style="opacity:0;" class="copialog-data" value="Data: ' . $IntLogs[$cont_id]["data"] . ', Url: ' . $IntLogs[$cont_id]["url"] . ', Ip: ' . $IntLogs[$cont_id]["indirizzo_ip"] . '"><button class="blk-button btn btn-primary">Blacklista tutti</button><button class="blk-button-rev btn btn-primary">Rimuovi tutti da blacklist</button><button class="blk-button-this btn btn-primary">Blacklista questo record</button><button class="blk-button-rev-this btn btn-primary">Rimuovi questo record dalla blacklist</button><button class="btn btn-primary copialog">Copia log</button></div></td></tr><tr class="empty-row"><td></td></tr>';

                        }
                        $usi_intHTML .= '</table>';

                        $interneHTML .= $usi_intHTML . '</div>';


                    }elseif(empty($leadUniIdsArr)){
                        //$interneHTML='<div id="interne"><h4>Interne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti ('.$Par.')</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con '.$Par.'. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
                        //$interneHTML = '<div id="interne"><h4>Interne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti (' . $Par . ')</p>' . $queryInterna . '<p><p>' . implode(',', $leadUniIdsArr) . '</p></p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con ' . $Par . '. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
                        $interneHTML .= "Non ho trovato nessun risultato";

                    }
                //}
            //}

    //Lancio query di ritrovamento esterne

    $esterneHTML='';
    $contatti_est=array();
    $contatti_estHTML='';
    $leadEstIdsArr=array();
    $esBlc=array();
    $EstLogs=array();

    //if($queryEstOk==1){
        $_from_leadest = array();
        foreach ($queryEsterna as $queryEst) {
            if (!empty($queryEst)) {
                $em6 = $this->getDoctrine()->getManager();
                ///echo "queryInterna".$queryInterna;
                $stmt6 = $em6->getConnection()->prepare($queryEst);
                $stmt6->execute();
                $_from_leadest[] = $stmt6->fetchAll();
            }
        }
        foreach ($_from_leadest as $contattiEst) {
            foreach ($contattiEst as $contatto) {
                $cont_id = $contatto['id'];
                $leadEstIdsArr[] = $cont_id;
                $EstLogs[$cont_id] = array();
                $checkLogs = 0;
                $estBlc = $this->check_if_blacklisted_est($cont_id);
                if (is_array($estBlc)) {
                    $esBlc[$cont_id] = date('Y-m-d h:m', $estBlc[0]);
                }
                $UsiRecentiEST[$cont_id] = 0;
                //echo "ok";print_r($contatto);
                ${'contatto_est_' . $cont_id} = '<table class="contatto leaduni"><tr>';
                ${'contatto_est_' . $cont_id . 'th'} = '<tr>';
                ${'contatto_est_' . $cont_id . 'td'} = '<tr>';
                foreach ($contatto as $field => $value) {
                    if ($field == 'data' or $field == 'indirizzo_ip' or $field == 'url') {
                        if ($value != '') {
                            $EstLogs[$cont_id][$field] = $value;
                            $checkLogs++;
                        } else {
                            $EstLogs[$cont_id][$field] = '';
                        }
                    }
////////////////////////////////////////////////////////////////
                    if ($field == 'cellulare') {
                        $leadEstIdsArrCell[$cont_id] = $value;
                    }
////////////////////////////////////////////////////////////////
                    if ($value != '') {
/////////////////////////////////////////////////////////////////////////////////////////
                        if ($field == 'cellulare' && (strlen($value) > 15)) {
                            ${'contatto_est_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_est_' . $cont_id . 'td'} .= '<td style="background-color: rgb(255, 179, 102);">' . $value . '</td>';
                        } else {
                            ${'contatto_est_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_est_' . $cont_id . 'td'} .= '<td>' . $value . '</td>';
                        }
// //////////////////////////////////////////////////////////////////////////////////////////////
                        //${'contatto_est_'.$cont_id.'th'}.='<th>'.$field.'</th>';
                        //${'contatto_est_'.$cont_id.'td'}.='<td>'.$value.'</td>';
                    }
                }
                ${'contatto_est_' . $cont_id} .= ${'contatto_est_' . $cont_id . 'th'} . '</tr>' . ${'contatto_est_' . $cont_id . 'td'} . '</tr></table>';
                if ($checkLogs == 3) {
                    $EstLogs[$cont_id]['status'] = 'log-presente';
                    $EstLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-green">log presente</span>';
                } else {
                    $EstLogs[$cont_id]['status'] = 'Log-non-presente';
                    $EstLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-red">log non presente</span>';
                }


                //per ogni match:  e metti in array_contatti est
                //select from extraction_esterne by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                //select from extraction_sms (per cellulare if we got cellulare) where tab_orig!=lead_uni, per ogni row metti in array utilizzi (data, tipologia, cliente)

            }
        }
    //}
    if(empty($leadEstIdsArr)){
        //$esterneHTML='<div id="esterne"><h4>esterne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti ('.$Par.')</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con '.$Par.'. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
        //$esterneHTML='<div id="esterne"><h4>esterne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti ('.$Par.')</p><p>'.$queryEsterna.'</p><p>'.implode(',',$leadEstIdsArr).'</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con '.$Par.'. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
    }
    else{
        //$esterneHTML='<div id="esterne"><h4>esterne <small>(corrispondenze: '.count($leadEstIdsArr).')</small></h4><p>'.$queryEsterna.'</p><p>'.implode(',',$leadEstIdsArr).'</p>';
        $esterneHTML='<div id="esterne" ><h4>esterne <small>(corrispondenze: '.count($leadEstIdsArr).')</small></h4>';
        $usi_est=array();
        $usi_estHTML='';
        //select frm extraction
        $QueryExtraction_es="SELECT * FROM extraction_lead_esterne WHERE lead_id in (".implode(',',$leadEstIdsArr).");";
        //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
        $em9 = $this->getDoctrine()->getManager();
        $stmt9 = $em9->getConnection()->prepare($QueryExtraction_es);
        $stmt9->execute();
        $_from_extraction_es =  $stmt9->fetchAll();
        //echo "from extraction: ".$QueryExtraction."<br>";
        foreach($_from_extraction_es as $extracted){
            $cont_id=$extracted['lead_id'];
            if(!isset(${'usi_est'.$cont_id})){${'usi_est'.$cont_id}=array();}
            $cliente=$this->getclienteleaduni($extracted['cliente_id']);
            $cliente=$cliente.' (id:'.$extracted['cliente_id'].')';
            $strToTime=strtotime($extracted['data_inserimento']);
            //il recente è se inferiore a 3 mesi dall'data sblocco
            $rec=$this->check_uso_recente($extracted['tipo_vendita'],strtotime($extracted['data_sblocco']));
            if($rec=='recente'){
                $UsiRecentiEST[$cont_id]=1;
            }
            ${'usi_est'.$cont_id}[$strToTime]=array($extracted['tipo_vendita'], $extracted['data_inserimento'],$extracted['data_sblocco'],$cliente,'estrazione',$rec);
            //print_r($extracted);echo "<br>";

        }
        //select frm extraction sms
        $QueryExtractionSms_es="SELECT * FROM extraction_sms WHERE source_id in (".implode(',',$leadEstIdsArr).") AND Tabella_origine='lead_uni_esterne' AND stato_invio=1;";
        //echo $QueryExtractionSms_es;
        //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
        $em7 = $this->getDoctrine()->getManager();
        $stmt7 = $em7->getConnection()->prepare($QueryExtractionSms_es);
        $stmt7->execute();
        $_from_extraction_sms_es =  $stmt7->fetchAll();
        $id_from_extractionsms_es=array();
        //echo "from extraction sms: ".$QueryExtractionSms."<br>";
        foreach($_from_extraction_sms_es as $extractedSms_es){
            $cont_id=$extractedSms_es['source_id'];
            //recupero dati basati sulla campagna_id: nome, cliente_id, data_lancio
            $CSmsId=$extractedSms_es['campagna_id'];
            $QueryCampagnaSms="SELECT campagna, cliente_id, data_lancio FROM campagne_sms WHERE stato=4 AND  id=".$CSmsId;
            $emCSe = $this->getDoctrine()->getManager();
            $stmtCSe = $emCSe->getConnection()->prepare($QueryCampagnaSms);
            $stmtCSe->execute();
            $_campagne_smse =  $stmtCSe->fetchAll();
            $cliente='';
            foreach($_campagne_smse as $_campagna_smse){// row returned
                $cliente=$this->getclienteleaduni($_campagna_smse['cliente_id']);
            }
            if($cliente!=''){// campagna finded
                if(!isset(${'usi_est'.$cont_id})){${'usi_est'.$cont_id}=array();}
                $cliente=$cliente.' (id:'.$_campagna_smse['cliente_id'].')';
                $strToTime=strtotime($_campagna_smse['data_lancio'].' 12:00:00');

                //il recente è se inferiore a due mesi dall'estrazione.. ovvero vado indietro di 30 giorni dalla data di estrazione e lo passo alla func
                $rec=$this->check_uso_recente('lancio sms',($strToTime-2592000));
                if($rec=='recente'){
                    $UsiRecentiEST[$cont_id]=1;
                }
                ${'usi_est'.$cont_id}[$strToTime]=array('lancio sms <small>('.$_campagna_smse['campagna'].')</small>', $_campagna_smse['data_lancio'],'',$cliente, 'extraction sms', $rec);
                //print_r($extractedSms);echo "<br>";
                $id_from_extractionsms_es[]=$extractedSms_es['id'];
                $id_from_extractionsmsDopp_es[$extractedSms_es['id']]=$extractedSms_es['source_id'];
            }
        }
        //echo "<br><br>";
        if(is_array($id_from_extractionsms_es) AND !empty($id_from_extractionsms_es)){
            //ritrova risposte sms
            $QueryRisposteSms_es="SELECT * FROM sms_replies WHERE extraction_id in (".implode(',',$id_from_extractionsms_es).");";
            //echo $QueryRisposteSms_es;
            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
            $em8 = $this->getDoctrine()->getManager();
            $stmt8 = $em8->getConnection()->prepare($QueryRisposteSms_es);
            $stmt8->execute();
            $_from_sms_replies_es =  $stmt8->fetchAll();

            //echo "from extraction sms: ".$QueryRisposteSms."<br>";
            foreach($_from_sms_replies_es as $replie){
                $cont_id=$id_from_extractionsmsDopp_es[$replie['extraction_id']];
                if(!isset(${'usi_est'.$cont_id})){${'usi_est'.$cont_id}=array();}
                $cliente=$this->getclienteleaduni($replie['client_id']);
                $cliente=$cliente.' (id:'.$replie['client_id'].')';
                $assigned='non accettata';
                if($replie['assigned']==1){
                    $assigned='accettata';
                }
                $strToTime=strtotime($replie['data'].' '.$replie['ora']);
                //il recente è se inferiore a 6 mesi dall'estrazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                $rec=$this->check_uso_recente('risposta sms',($strToTime + 7776000));
                if($rec=='recente'){
                    $UsiRecentiEST[$cont_id]=1;
                }
                ${'usi_est'.$cont_id}[$strToTime]=array('risposta sms', $replie['data'].' '.$replie['ora'],'',$cliente,'risposta '.$assigned, $rec);
                //print_r($replie);echo "<br>";
            }
            //echo "<br><br>";
        }
        //CREAT INT HTML FROM DATAS
        $usi_estHTML.='<table class="table-blk">';

        foreach($leadEstIdsArr as $cont_id){
            $usi_estHTML.='<div id="overlay" ><div id="loader-id-blacklister" class="mini-loader1"></div></div>';
            $blacklistedClass='not-blacklisted';
            $blacklistedClassString='';
            if(array_key_exists($cont_id, $esBlc)){
                $blacklistedClass='blacklisted';
                if($esBlc[$cont_id]!=0){
                    $blacklistedClassString='<div class="show-blacklist">Blacklistato il '.$esBlc[$cont_id].'</div>';
                }else{
                    $blacklistedClassString='<div class="show-blacklist">Blacklistato</div>';
                }
            }
            if($UsiRecentiEST[$cont_id]==1){
                $recentspan='<span  class="bl-etichetta bl-etichetta-green">uso recente</span>';
            }else{
                $recentspan='<span  class="bl-etichetta bl-etichetta-red">uso non recente</span>';
            }

            $contactHeader='<h4>ID ESTERNO: '.$cont_id.' | LOG <span class="logs">Data: '.$EstLogs[$cont_id]["data"].', Url: '.$EstLogs[$cont_id]["url"].', Ip: '.$EstLogs[$cont_id]["indirizzo_ip"].'</span>'.$EstLogs[$cont_id]["statusHtml"].$recentspan.'</h4>'.$blacklistedClassString;

            $usi_estHTML.='<tr class="contatto-tr-title contatto-tr '.$blacklistedClass.' '.$EstLogs[$cont_id]["status"].'" data-id="'.$cont_id.'" data-cell="'.$leadEstIdsArrCell[$cont_id].'"><td class="body-v">'.$contactHeader.'</td></tr><tr class="contatto-tr-data contatto-tr '.$blacklistedClass.' '.$EstLogs[$cont_id]["status"].'" data-id="'.$cont_id.'" data-cell="'.$leadEstIdsArrCell[$cont_id].'"><td class="body-v"><div class="contatto-dati"><div class="typoOfT"><span>Dati</span></div><div>'.${'contatto_est_'.$cont_id}.'</div></div>';
            if(isset(${'usi_est'.$cont_id}) AND count(${'usi_est'.$cont_id})>0){
                $usi_estHTML.='<div class="contatto-usi"><div class="typoOfT"><span>Usi</span></div><div><table><tr><th>Aging</th><th>Tipologia</th><th>Data Inizio</th><th>Data fine</th><th>Cliente</th><th>Additional</th></tr>';
                krsort(${'usi_est'.$cont_id});
                foreach(${'usi_est'.$cont_id} as $Data=>$Dati){
                    $usi_estHTML.='<tr class="uso-row '.$Dati[5].'">';
                    $usi_estHTML.='<td>'.$Dati[5].'</td><td>'.$Dati[0].'</td><td>'.$Dati[1].'</td><td>'.$Dati[2].'</td><td>'.$Dati[3].'</td><td>'.$Dati[4].'</td>';
                    $usi_estHTML.='</tr>';
                }
                $usi_estHTML.='</table></div></div>';
            }else{
                $usi_estHTML.='<div class="" style="padding-left:7px;border:none !important;"><p><b>Usi: </b>Nessun uso trovato per il contatto</p></div>';
            }
            $usi_estHTML.='<div class="contatto-button"><input type="text" style="opacity:0;" class="copialog-data" value="Data: '.$EstLogs[$cont_id]["data"].', Url: '.$EstLogs[$cont_id]["url"].', Ip: '.$EstLogs[$cont_id]["indirizzo_ip"].'"><button class="blk-button btn btn-primary">Blacklista tutti</button><button class="blk-button-rev btn btn-primary">Rimuovi tutti da blacklist</button><button class="blk-button-this btn btn-primary">Blacklista questo record</button><button class="blk-button-rev-this btn btn-primary">Rimuovi questo record dalla blacklist</button><button class="btn btn-primary copialog">Copia log</button></div></td></tr><tr class="empty-row"><td></td></tr>';
        }
        $usi_estHTML.='</table>';


        $esterneHTML.=$usi_estHTML.'</div>';

    }

    $interneHTML.=$esterneHTML;
        //$interneHTML=$toprintvar;
    $response = new Response();
    $response->setContent(json_encode(array('contatti-intHtml'=>$interneHTML, 'contatti_est'=>$contatti_est, 'usi_int'=>$usi_int)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
    }

    public function searchContactAction(Request $request){
        $filter_int=$request->get('filter-int');
        $filter_ext=$request->get('filter-ext');
        $filter_error='';
        $firstfield = $request->get('first-field');
        $Par = '';

        //if($firstfield=='cellulare' OR $firstfield=='email' OR $firstfield=='id'){
        if ($firstfield == 'email' or $firstfield == 'id') {
            //=
            $operator1 = " = ";
            $firstvalue = str_replace(' ', '', $request->get('first-value'));
            $conditionQ1 = $firstfield . $operator1 . "'" . $firstvalue . "' ";
        } else {
            //like
            $operator1 = " LIKE ";
            $firstvalue = trim($request->get('first-value'));
            $conditionQ1 = $firstfield . $operator1 . "'%" . $firstvalue . "%' ";
        }
        $Par .= $firstfield . ': ' . $firstvalue;

// ////////////////////////////////////////////////////////////////////////////
        // patch per ricerca cellulare in md5
        $check_md5 = $firstfield == 'cellulare';
        if ($check_md5) {
            $md5_par = $firstfield . ': ' . "md5(" . $firstvalue . ") ";
            $conditionQ1 .= " OR " . $firstfield . $operator1 . "md5('" . $firstvalue . "') ";
            $Par .= ', ' . $md5_par;
        }
        // patch per ricerca cellulare in md5
// ////////////////////////////////////////////////////////////////////////////

        $secondfield = $request->get('second-field');
        if (isset($secondfield) and $secondfield != '') {
            if ($secondfield == 'cellulare' or $secondfield == 'email') {
                //=
                $operator2 = " = ";
                $secondvalue = str_replace(' ', '', $request->get('second-value'));
                $conditionQ2 = $secondfield . $operator2 . "'" . $secondvalue . "' ";
            } else {
                //like
                $operator2 = " LIKE ";
                $secondvalue = trim($request->get('second-value'));
                $conditionQ2 = $secondfield . $operator2 . "'%" . $secondvalue . "%' ";
            }

            $Par .= ', ' . $secondfield . ': ' . $secondvalue;

// ////////////////////////////////////////////////////////////////////////////
            // patch per ricerca cellulare in md5
            $check_md52 = $secondfield == 'cellulare';
            if ($check_md52) {
                $md5_par2 = $secondfield . ': ' . "md5(" . $secondvalue . ") ";
                $conditionQ2 = " AND (" . $conditionQ2 . " OR " . $secondfield . $operator2 . "md5('" . $secondvalue . "') )";
                $Par .= ', ' . $md5_par2;
            } else {
                $conditionQ2 = " AND " . $conditionQ2;
            }
            // patch per ricerca cellulare in md5
// ////////////////////////////////////////////////////////////////////////////
        }
        $queryIntOk = null;
        $queryEstOk = null;

        $extraintQueryPart = '';
        if ($secondfield == 'prodambiente') {
            if ($secondvalue == 'INT') {
                $queryIntOk = 1;
                $queryEstOk = 0;
                $extraintQueryPart = ' OR lu.cellulare in (select lub.cellulare from lead_uni lub WHERE lub.id=' . $firstvalue . ')';
                unset($operator2);
            } elseif ($secondvalue == 'EST') {
                $queryIntOk = 0;
                $queryEstOk = 1;
                unset($operator2);
            } else {
                $queryIntOk = 0;
                $queryEstOk = 0;
            }
        } else {
            if ($filter_int=="int" && $filter_ext=="est"){
                $queryIntOk = 1;
                $queryEstOk = 1;
            }elseif($filter_int=="int"){
                $queryIntOk = 1;
                $queryEstOk = 0;
            }elseif($filter_ext=="est"){
                $queryIntOk = 0;
                $queryEstOk = 1;
            }else{
                $filter_error='<p><B>ATTENZIONE! :</B> La ricerca non ha prodotto risultati perchè non è stato selezionato un filtro</p>';
            }

        }
        $this->write_history_blk('search', $Par);
        //$queryInterna ="SELECT * FROM lead_uni lu WHERE lu.".$firstfield.$operator1."'".$firstvalue."' ";
        //$queryEsterna ="SELECT * FROM lead_uni_esterne WHERE ".$firstfield.$operator1."'".$firstvalue."' ";
        $queryInterna = "SELECT * FROM lead_uni lu WHERE lu." . $conditionQ1;
        $queryEsterna = "SELECT * FROM lead_uni_esterne WHERE " . $conditionQ1;

        if (isset($operator2)) {
            //$queryInterna.="AND lu.".$secondfield.$operator2."'".$secondvalue."' ";
            //$queryEsterna.="AND ".$secondfield.$operator2."'".$secondvalue."' ";
            $queryInterna .= $conditionQ2;
            $queryEsterna .= $conditionQ2;;
        };
        //Add prod research by id + cell
        $queryInterna .= $extraintQueryPart;
        //echo "int: ".$queryInterna."<br><br>";
        //echo "est: ".$queryEsterna."<br><br>";
        //var statiche interne
        $contatti_intHtml = '';
        $usi_int = array();
        $usi_intHTML = '';
        $premi = array();
        $campagna = array();
        $leadUniIdsArr = array();
        $leadUniIdsArrCell = array();
        $inBl = array();
        $IntLogs = array();
        $UsiRecenti = array();
        $interneHTML = '';

            if ($queryIntOk == 1) {
            //lancio query di ritrovamento interne
            $em = $this->getDoctrine()->getManager();
            ///echo "queryInterna".$queryInterna;
            $stmt = $em->getConnection()->prepare($queryInterna);
            $stmt->execute();
            $_from_leaduni = $stmt->fetchAll();

            foreach ($_from_leaduni as $contatto) {
                $cont_id = $contatto['id'];
                $leadUniIdsArr[] = $cont_id;
                $IntLogs[$cont_id] = array();
                $UsiRecenti[$cont_id] = 0;
                $checkLogs = 0;
                $inBlc = $this->check_if_blacklisted_int($cont_id, $contatto['cellulare']);
                if (is_array($inBlc)) {
                    $inBl[$cont_id] = date('Y-m-d h:m', $inBlc[0]);
                }
                if ($contatto['source_db'] == 'premi365_db23') {
                    $premi[$cont_id] = array('source_id' => $contatto['source_id'], 'source_tbl' => $contatto['source_tbl']);
                } else {
                    $campagna[$cont_id] = array('campagna' => $contatto['campagna_id'], 'source_tbl' => $contatto['source_tbl']);
                }

                //echo "ok";print_r($contatto);
                ${'contatto_int_' . $cont_id} = '<table class="contatto leaduni" data-id="' . $cont_id . '"><tr>';
                ${'contatto_int_' . $cont_id . 'th'} = '<tr>';
                ${'contatto_int_' . $cont_id . 'td'} = '<tr>';
                foreach ($contatto as $field => $value) {
                    if ($field == 'data' or $field == 'indirizzo_ip' or $field == 'url') {
                        if ($value != '') {
                            $IntLogs[$cont_id][$field] = $value;
                            $checkLogs++;
                        } else {
                            $IntLogs[$cont_id][$field] = '';
                        }
                    }
                    if ($field == 'cellulare') {
                        $leadUniIdsArrCell[$cont_id] = $value;
                    }
                    if ($value != '') {
// //////////////////////////////////////////////////////////////////////////////////////////////
                        if ($field == 'cellulare' && (strlen($value) > 15)) {
                            ${'contatto_int_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_int_' . $cont_id . 'td'} .= '<td style="background-color: rgb(102, 217, 255);">' . $value . '</td>';
                        } else {
                            ${'contatto_int_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_int_' . $cont_id . 'td'} .= '<td>' . $value . '</td>';
                        }
// //////////////////////////////////////////////////////////////////////////////////////////////
                    }
                }
                ${'contatto_int_' . $cont_id} .= ${'contatto_int_' . $cont_id . 'th'} . '</tr>' . ${'contatto_int_' . $cont_id . 'td'} . '</tr></table>';
                if ($checkLogs == 3) {
                    $IntLogs[$cont_id]['status'] = 'log-presente';
                    $IntLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-green">log presente</span>';
                } else {
                    $IntLogs[$cont_id]['status'] = 'Log-non-presente';
                    $IntLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-red">log non presente</span>';
                }

            }
        }
        if (empty($leadUniIdsArr) and $queryIntOk == 1) {
            //$interneHTML='<div id="interne"><h4>Interne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti ('.$Par.')</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con '.$Par.'. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
            $interneHTML = '<div id="interne" ><h4>Interne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti (' . $Par . ')</p>' . $queryInterna . '<p><p>' . implode(',', $leadUniIdsArr) . '</p></p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con ' . $Par . '. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
        } elseif ($queryIntOk == 1) {
            $interneHTML = '<div id="interne" ><h4>Interne <small>(corrispondenze: ' . count($leadUniIdsArr) . ')</small></h4></p>';
            //$interneHTML='<div id="interne"><h4>Interne <small>(corrispondenze: '.count($leadUniIdsArr).')</small></h4></p>'.$queryInterna.'<p><p>'.implode(',',$leadUniIdsArr).'</p>';
            //abbiamo trovato contatti in leaduni
            //ritrova noleggi

            $usi_intHTML = '';
            $QueryExtraction = "SELECT * FROM extraction_history WHERE lead_id in (" . implode(',', $leadUniIdsArr) . ");";
            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
            $em2 = $this->getDoctrine()->getManager();
            $stmt2 = $em2->getConnection()->prepare($QueryExtraction);
            $stmt2->execute();
            $_from_extraction = $stmt2->fetchAll();
            //echo "from extraction: ".$QueryExtraction."<br>";
            foreach ($_from_extraction as $extracted) {
                $cont_id = $extracted['lead_id'];
                if (!isset(${'usi_int_' . $cont_id})) {
                    ${'usi_int_' . $cont_id} = array();
                }
                $cliente = $this->getclienteleaduni($extracted['cliente_id']);
                $cliente = $cliente . ' (id:' . $extracted['cliente_id'] . ')';
                $strToTime = strtotime($extracted['data_estrazione']);
                $rec = $this->check_uso_recente($extracted['tipo_estrazione'], strtotime($extracted['data_sblocco']));
                //detect uso recente and store it < 90 giorni
                if ($rec == 'recente') {
                    $UsiRecenti[$cont_id] = 1;
                }
                ${'usi_int_' . $cont_id}[$strToTime] = array($extracted['tipo_estrazione'], $extracted['data_estrazione'], $extracted['data_sblocco'], $cliente, 'estrazione/landing', $rec);


                //print_r($extracted);echo "<br>";

            }
            //echo "<br><br>";
            //ritrova estrazioni sms
            //ritrova noleggi
            $QueryExtractionSms = "SELECT * FROM extraction_sms WHERE source_id in (" . implode(',', $leadUniIdsArr) . ") AND Tabella_origine='lead_uni' AND stato_invio=1;";
            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
            $em3 = $this->getDoctrine()->getManager();
            $stmt3 = $em3->getConnection()->prepare($QueryExtractionSms);
            $stmt3->execute();
            $_from_extraction_sms = $stmt3->fetchAll();
            $id_from_extractionsms = array();
            //echo "from extraction sms: ".$QueryExtractionSms."<br>";
            foreach ($_from_extraction_sms as $extractedSms) {
                $cont_id = $extractedSms['source_id'];

                //recupero dati basati sulla campagna_id: nome, cliente_id, data_lancio
                $CSmsId = $extractedSms['campagna_id'];
                $QueryCampagnaSms = "SELECT campagna, cliente_id, data_lancio FROM campagne_sms WHERE stato=4 AND id=" . $CSmsId;
                $emCS = $this->getDoctrine()->getManager();
                $stmtCS = $emCS->getConnection()->prepare($QueryCampagnaSms);
                $stmtCS->execute();
                $_campagne_sms = $stmtCS->fetchAll();
                $cliente = '';
                //echo "from CAMPAGNE sms: ".$QueryCampagnaSms."<br>";
                foreach ($_campagne_sms as $_campagna_sms) {// row returned
                    $cliente = $this->getclienteleaduni($_campagna_sms['cliente_id']);

                }
                if ($cliente != '') {// campagna finded
                    if (!isset(${'usi_int_' . $cont_id})) {
                        ${'usi_int_' . $cont_id} = array();
                    }
                    $cliente = $cliente . ' (id:' . $_campagna_sms['cliente_id'] . ')';
                    $strToTime = strtotime($_campagna_sms['data_lancio'] . ' 12:00:00');
                    //il recente è se inferiore a due mesi dall'estrazione.. ovvero vado indietro di 30 giorni dalla data di estrazione e lo passo alla func
                    $rec = $this->check_uso_recente('lancio sms', ($strToTime - 2592000));
                    //detect uso recente and store it < 90 giorni
                    if ($rec == 'recente') {
                        $UsiRecenti[$cont_id] = 1;
                    }
                    ${'usi_int_' . $cont_id}[$strToTime] = array('lancio sms <small>(' . $_campagna_sms['campagna'] . ')</small>', $_campagna_sms['data_lancio'], '', $cliente, 'extraction sms', $rec);
                    //print_r($extractedSms);echo "<br>";
                    $id_from_extractionsms[] = $extractedSms['id'];
                    $id_from_extractionsmsDopp[$extractedSms['id']] = $extractedSms['source_id'];

                }

            }
            //echo "<br><br>";
            if (is_array($id_from_extractionsms) and !empty($id_from_extractionsms)) {
                //ritrova risposte sms
                $QueryRisposteSms = "SELECT * FROM sms_replies WHERE extraction_id in (" . implode(',', $id_from_extractionsms) . ");";
                //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                $em4 = $this->getDoctrine()->getManager();
                $stmt4 = $em4->getConnection()->prepare($QueryRisposteSms);
                $stmt4->execute();
                $_from_sms_replies = $stmt4->fetchAll();

                //echo "from extraction sms: ".$QueryRisposteSms."<br>";
                foreach ($_from_sms_replies as $replie) {
                    $cont_id = $id_from_extractionsmsDopp[$replie['extraction_id']];
                    if (!isset(${'usi_int_' . $cont_id})) {
                        ${'usi_int_' . $cont_id} = array();
                    }
                    $cliente = $this->getclienteleaduni($replie['client_id']);
                    $cliente = $cliente . ' (id:' . $replie['client_id'] . ')';

                    $strToTime = strtotime($replie['data'] . ' ' . $replie['ora']);
                    //il recente è se inferiore a 6 mesi dall'estrazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                    $rec = $this->check_uso_recente('risposta sms', ($strToTime + 7776000));
                    //detect uso recente and store it < 90 giorni
                    $assigned = 'non accettata';
                    if ($replie['assigned'] == 1) {
                        $assigned = 'accettata';
                    } else {
                        $rec = 'non-recente';
                    }
                    if ($rec == 'recente') {
                        $UsiRecenti[$cont_id] = 1;
                    }
                    ${'usi_int_' . $cont_id}[$strToTime] = array('risposta sms', $replie['data'] . ' ' . $replie['ora'], '', $cliente, 'risposta ' . $assigned, $rec);
                    //print_r($replie);echo "<br>";
                }
                //echo "<br><br>";
            }
            $RRR = 0;
            if (is_array($premi) and !empty($premi)) {
                $RRR = 1;
                //ritrova usi di concorso
                foreach ($premi as $keyLeadUni => $contattopremi) {
                    if ($contattopremi['source_tbl'] == 'lead_esterne') {
                        $esterna = 1;
                    } else {
                        $esterna = 0;
                    }
                    $QueryPremi = "SELECT * FROM clienti_lead WHERE lead_id = " . $contattopremi['source_id'] . " AND esterna=" . $esterna . ";";
                    //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                    $em5 = $this->getDoctrine()->getManager('concorso_man');
                    $stmt5 = $em5->getConnection()->prepare($QueryPremi);
                    $stmt5->execute();
                    $_from_concorso_lead = $stmt5->fetchAll();
                    $id_from_extractionsms = array();
                    //echo "from concorso: ".$QueryPremi."<br>";
                    $cont_id = $keyLeadUni;
                    foreach ($_from_concorso_lead as $contattoConcorso) {
                        if (!isset(${'usi_int_' . $cont_id})) {
                            ${'usi_int_' . $cont_id} = array();
                        }
                        $tipo = 'Cootitolarit&agrave;';
                        if (array_key_exists('tipologia_noleggio', $contattoConcorso) and $contattoConcorso['tipologia_noleggio'] == 1) {
                            $tipo = 'Noleggio';
                        }
                        $cliente = $this->getclienteconcorso($contattoConcorso['cliente_id']);
                        $cliente = $cliente . ' (id concorso:' . $contattoConcorso['cliente_id'] . ')';
                        $strToTime = strtotime($contattoConcorso['data_invio']);
                        //il recente è se inferiore a 6 mesi dall'associazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                        $rec = $this->check_uso_recente($tipo, ($strToTime + 7776000));
                        //detect uso recente and store it < 90 giorni
                        if ($rec == 'recente') {
                            $UsiRecenti[$cont_id] = 1;
                        }
                        ${'usi_int_' . $cont_id}[$strToTime] = array($tipo, $contattoConcorso['data_invio'], '', $cliente, 'concorso', $rec);
                        //print_r($contattoConcorso);echo "<br>";
                    }
                    //echo "<br><br>";
                }
            }

            /*if(is_array($campagna) AND !empty($campagna)){
                //ritrova usi di concorso
                foreach($campagna as $keyLeadUni=>$contattocampagna){
                    $QueryCampagna="SELECT * FROM a_landing_cliente WHERE campagna_id = ".$contattocampagna['campagna']." AND mailCliente='".$contattocampagna['source_tbl']."';";
                    //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                    $em6 = $this->getDoctrine()->getManager();
                    $stmt6 = $em6->getConnection()->prepare($QueryCampagna);
                    $stmt6->execute();
                    $_from_campagna =  $stmt6->fetchAll();
                    $id_from_extractionsms=array();
                    //echo "from campagna: ".$QueryCampagna."<br>";
                    foreach($_from_campagna as $Campagna){
                        $usi_int[$replie['data_invio']]=array($contattopremi['source_id'],$tipo, $replie['data_invio'],'',$cliente);
                        print_r($Campagna);echo "<br>";
                    }
                    //echo "<br><br>";
                }
            }
            */
            //CREAT INT HTML FROM DATAS
            $usi_intHTML .= '<table class="table-blk">';

            foreach ($leadUniIdsArr as $cont_id) {
                $usi_intHTML .= '<div id="overlay" ><div id="loader-id-blacklister" class="mini-loader1"></div></div>';
                $blacklistedClass = 'not-blacklisted';
                $blacklistedClassString = '<div class="show-blacklist"></div>';
                if (array_key_exists($cont_id, $inBl)) {
                    $blacklistedClass = 'blacklisted';
                    if ($inBl[$cont_id] != 0) {
                        $blacklistedClassString = '<div class="show-blacklist">Blacklistato il ' . $inBl[$cont_id] . '</div>';
                    } else {
                        $blacklistedClassString = '<div class="show-blacklist">Blacklistato</div>';
                    }
                }

                if ($UsiRecenti[$cont_id] == 1) {
                    $recentspan = '<span  class="bl-etichetta bl-etichetta-green">uso recente</span>';
                } else {
                    $recentspan = '<span  class="bl-etichetta bl-etichetta-red">uso non recente</span>';
                }
                $contactHeader = '<h4>ID INTERNO: ' . $cont_id . ' | LOG <span class="logs">Data: ' . $IntLogs[$cont_id]["data"] . ', Url: ' . $IntLogs[$cont_id]["url"] . ', Ip: ' . $IntLogs[$cont_id]["indirizzo_ip"] . '</span>' . $IntLogs[$cont_id]["statusHtml"] . $recentspan . '</h4>' . $blacklistedClassString;
                $usi_intHTML .= '<tr class="contatto-tr-title contatto-tr ' . $blacklistedClass . ' ' . $IntLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="' . $leadUniIdsArrCell[$cont_id] . '"><td class="body-v">' . $contactHeader . '</td></tr><tr class="contatto-tr-data contatto-tr ' . $blacklistedClass . ' ' . $IntLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="' . $leadUniIdsArrCell[$cont_id] . '"><td class="body-v"><div class="contatto-dati "><div class="typoOfT"><span>Dati</span></div><div>' . ${'contatto_int_' . $cont_id} . '</div></div>';
                if (isset(${'usi_int_' . $cont_id}) and count(${'usi_int_' . $cont_id}) > 0) {
                    $usi_intHTML .= '<div class="contatto-usi"><div class="typoOfT"><span>Usi</span></div><div><table><tr><th>Aging</th><th>Tipologia</th><th>Data Inizio</th><th>Data fine</th><th>Cliente</th><th>Additional</th></tr>';
                    krsort(${'usi_int_' . $cont_id});
                    foreach (${'usi_int_' . $cont_id} as $Data => $Dati) {
                        $usi_intHTML .= '<tr class="uso-row ' . $Dati[5] . '">';
                        $usi_intHTML .= '<td>' . $Dati[5] . '</td><td>' . $Dati[0] . '</td><td>' . $Dati[1] . '</td><td>' . $Dati[2] . '</td><td>' . $Dati[3] . '</td><td>' . $Dati[4] . '</td>';
                        $usi_intHTML .= '</tr>';
                    }
                    $usi_intHTML .= '</table></div></div>';
                } else {
                    $usi_intHTML .= '<div class="" style="padding-left:7px;border:none !important;"><p><b>Usi: </b>Nessun uso trovato per il contatto</p></div>';
                }
                $usi_intHTML .= '<div class="contatto-button"><input  type="text" style="opacity:0;" class="copialog-data" value="Data: ' . $IntLogs[$cont_id]["data"] . ', Url: ' . $IntLogs[$cont_id]["url"] . ', Ip: ' . $IntLogs[$cont_id]["indirizzo_ip"] . '"><button class="blk-button btn btn-primary">Blacklista tutti</button><button class="blk-button-rev btn btn-primary">Rimuovi tutti da blacklist</button><button class="blk-button-this btn btn-primary">Blacklista questo record</button><button class="blk-button-rev-this btn btn-primary">Rimuovi questo record dalla blacklist</button><button class="btn btn-primary copialog">Copia log</button></div></td></tr><tr class="empty-row"><td></td></tr>';

            }
            $usi_intHTML .= '</table>';

            $interneHTML .= $usi_intHTML . '</div>';

        }

        //Lancio query di ritrovamento esterne

        $esterneHTML = '';
        $contatti_est = array();
        $contatti_estHTML = '';
        $leadEstIdsArr = array();
        $esBlc = array();
        $EstLogs = array();
        if ($queryEstOk == 1) {
            $em6 = $this->getDoctrine()->getManager();
            ///echo "queryInterna".$queryInterna;
            $stmt6 = $em6->getConnection()->prepare($queryEsterna);
            $stmt6->execute();
            $_from_leadest = $stmt6->fetchAll();

            foreach ($_from_leadest as $contatto) {
                $cont_id = $contatto['id'];
                $leadEstIdsArr[] = $cont_id;
                $EstLogs[$cont_id] = array();
                $checkLogs = 0;
                $estBlc = $this->check_if_blacklisted_est($cont_id);
                if (is_array($estBlc)) {
                    $esBlc[$cont_id] = date('Y-m-d h:m', $estBlc[0]);
                }
                $UsiRecentiEST[$cont_id] = 0;
                //echo "ok";print_r($contatto);
                ${'contatto_est_' . $cont_id} = '<table class="contatto leaduni"><tr>';
                ${'contatto_est_' . $cont_id . 'th'} = '<tr>';
                ${'contatto_est_' . $cont_id . 'td'} = '<tr>';
                foreach ($contatto as $field => $value) {
                    if ($field == 'data' or $field == 'indirizzo_ip' or $field == 'url') {
                        if ($value != '') {
                            $EstLogs[$cont_id][$field] = $value;
                            $checkLogs++;
                        } else {
                            $EstLogs[$cont_id][$field] = '';
                        }
                    }
////////////////////////////////////////////////////////////////
                    if ($field == 'cellulare') {
                        $leadEstIdsArrCell[$cont_id] = $value;
                    }
////////////////////////////////////////////////////////////////
                    if ($value != '') {
/////////////////////////////////////////////////////////////////////////////////////////
                        if ($field == 'cellulare' && (strlen($value) > 15)) {
                            ${'contatto_est_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_est_' . $cont_id . 'td'} .= '<td style="background-color: rgb(255, 179, 102);">' . $value . '</td>';
                        } else {
                            ${'contatto_est_' . $cont_id . 'th'} .= '<th>' . $field . '</th>';
                            ${'contatto_est_' . $cont_id . 'td'} .= '<td>' . $value . '</td>';
                        }
// //////////////////////////////////////////////////////////////////////////////////////////////
                        //${'contatto_est_'.$cont_id.'th'}.='<th>'.$field.'</th>';
                        //${'contatto_est_'.$cont_id.'td'}.='<td>'.$value.'</td>';
                    }
                }
                ${'contatto_est_' . $cont_id} .= ${'contatto_est_' . $cont_id . 'th'} . '</tr>' . ${'contatto_est_' . $cont_id . 'td'} . '</tr></table>';
                if ($checkLogs == 3) {
                    $EstLogs[$cont_id]['status'] = 'log-presente';
                    $EstLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-green">log presente</span>';
                } else {
                    $EstLogs[$cont_id]['status'] = 'Log-non-presente';
                    $EstLogs[$cont_id]['statusHtml'] = '<span class="bl-etichetta bl-etichetta-red">log non presente</span>';
                }


                //per ogni match:  e metti in array_contatti est
                //select from extraction_esterne by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                //select from extraction_sms (per cellulare if we got cellulare) where tab_orig!=lead_uni, per ogni row metti in array utilizzi (data, tipologia, cliente)

            }
        }

        if (empty($leadEstIdsArr) and $queryEstOk == 1) {
            //$esterneHTML='<div id="esterne"><h4>esterne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti ('.$Par.')</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con '.$Par.'. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
            $esterneHTML = '<div id="esterne" ><h4>esterne <small>(0 corrispondenze)</small></h4><p>Nessun contatto trovato con i parametri richiesti (' . $Par . ')</p><p>' . $queryEsterna . '</p><p>' . implode(',', $leadEstIdsArr) . '</p><div class="button"><a href="mailto:info@linkappeal.it?subject=DPO - contatto non trovato&body=Buongiorno, non abbiamo trovato a db il contatto con ' . $Par . '. --- Di seguito la mail di richiesta cancellazione:">Contatta Linkappeal</a></div></div>';
        } elseif ($queryEstOk == 1) {
            //$esterneHTML='<div id="esterne"><h4>esterne <small>(corrispondenze: '.count($leadEstIdsArr).')</small></h4><p>'.$queryEsterna.'</p><p>'.implode(',',$leadEstIdsArr).'</p>';
            $esterneHTML = '<div id="esterne" ><h4>esterne <small>(corrispondenze: ' . count($leadEstIdsArr) . ')</small></h4>';
            $usi_est = array();
            $usi_estHTML = '';
            //select frm extraction
            $QueryExtraction_es = "SELECT * FROM extraction_lead_esterne WHERE lead_id in (" . implode(',', $leadEstIdsArr) . ");";
            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
            $em9 = $this->getDoctrine()->getManager();
            $stmt9 = $em9->getConnection()->prepare($QueryExtraction_es);
            $stmt9->execute();
            $_from_extraction_es = $stmt9->fetchAll();
            //echo "from extraction: ".$QueryExtraction."<br>";
            foreach ($_from_extraction_es as $extracted) {
                $cont_id = $extracted['lead_id'];
                if (!isset(${'usi_est' . $cont_id})) {
                    ${'usi_est' . $cont_id} = array();
                }
                $cliente = $this->getclienteleaduni($extracted['cliente_id']);
                $cliente = $cliente . ' (id:' . $extracted['cliente_id'] . ')';
                $strToTime = strtotime($extracted['data_inserimento']);
                //il recente è se inferiore a 3 mesi dall'data sblocco
                $rec = $this->check_uso_recente($extracted['tipo_vendita'], strtotime($extracted['data_sblocco']));
                if ($rec == 'recente') {
                    $UsiRecentiEST[$cont_id] = 1;
                }
                ${'usi_est' . $cont_id}[$strToTime] = array($extracted['tipo_vendita'], $extracted['data_inserimento'], $extracted['data_sblocco'], $cliente, 'estrazione', $rec);
                //print_r($extracted);echo "<br>";

            }
            //select frm extraction sms
            $QueryExtractionSms_es = "SELECT * FROM extraction_sms WHERE source_id in (" . implode(',', $leadEstIdsArr) . ") AND Tabella_origine='lead_uni_esterne' AND stato_invio=1;";
            //echo $QueryExtractionSms_es;
            //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
            $em7 = $this->getDoctrine()->getManager();
            $stmt7 = $em7->getConnection()->prepare($QueryExtractionSms_es);
            $stmt7->execute();
            $_from_extraction_sms_es = $stmt7->fetchAll();
            $id_from_extractionsms_es = array();
            //echo "from extraction sms: ".$QueryExtractionSms."<br>";
            foreach ($_from_extraction_sms_es as $extractedSms_es) {
                $cont_id = $extractedSms_es['source_id'];
                //recupero dati basati sulla campagna_id: nome, cliente_id, data_lancio
                $CSmsId = $extractedSms_es['campagna_id'];
                $QueryCampagnaSms = "SELECT campagna, cliente_id, data_lancio FROM campagne_sms WHERE stato=4 AND  id=" . $CSmsId;
                $emCSe = $this->getDoctrine()->getManager();
                $stmtCSe = $emCSe->getConnection()->prepare($QueryCampagnaSms);
                $stmtCSe->execute();
                $_campagne_smse = $stmtCSe->fetchAll();
                $cliente = '';
                foreach ($_campagne_smse as $_campagna_smse) {// row returned
                    $cliente = $this->getclienteleaduni($_campagna_smse['cliente_id']);
                }
                if ($cliente != '') {// campagna finded
                    if (!isset(${'usi_est' . $cont_id})) {
                        ${'usi_est' . $cont_id} = array();
                    }
                    $cliente = $cliente . ' (id:' . $_campagna_smse['cliente_id'] . ')';
                    $strToTime = strtotime($_campagna_smse['data_lancio'] . ' 12:00:00');

                    //il recente è se inferiore a due mesi dall'estrazione.. ovvero vado indietro di 30 giorni dalla data di estrazione e lo passo alla func
                    $rec = $this->check_uso_recente('lancio sms', ($strToTime - 2592000));
                    if ($rec == 'recente') {
                        $UsiRecentiEST[$cont_id] = 1;
                    }
                    ${'usi_est' . $cont_id}[$strToTime] = array('lancio sms <small>(' . $_campagna_smse['campagna'] . ')</small>', $_campagna_smse['data_lancio'], '', $cliente, 'extraction sms', $rec);
                    //print_r($extractedSms);echo "<br>";
                    $id_from_extractionsms_es[] = $extractedSms_es['id'];
                    $id_from_extractionsmsDopp_es[$extractedSms_es['id']] = $extractedSms_es['source_id'];
                }
            }
            //echo "<br><br>";
            if (is_array($id_from_extractionsms_es) and !empty($id_from_extractionsms_es)) {
                //ritrova risposte sms
                $QueryRisposteSms_es = "SELECT * FROM sms_replies WHERE extraction_id in (" . implode(',', $id_from_extractionsms_es) . ");";
                //echo $QueryRisposteSms_es;
                //select from extraction by id(per ogni id leaduni): per ogni row metti in array utilizzi (data, tipologia, cliente)
                $em8 = $this->getDoctrine()->getManager();
                $stmt8 = $em8->getConnection()->prepare($QueryRisposteSms_es);
                $stmt8->execute();
                $_from_sms_replies_es = $stmt8->fetchAll();

                //echo "from extraction sms: ".$QueryRisposteSms."<br>";
                foreach ($_from_sms_replies_es as $replie) {
                    $cont_id = $id_from_extractionsmsDopp_es[$replie['extraction_id']];
                    if (!isset(${'usi_est' . $cont_id})) {
                        ${'usi_est' . $cont_id} = array();
                    }
                    $cliente = $this->getclienteleaduni($replie['client_id']);
                    $cliente = $cliente . ' (id:' . $replie['client_id'] . ')';
                    $assigned = 'non accettata';
                    if ($replie['assigned'] == 1) {
                        $assigned = 'accettata';
                    }
                    $strToTime = strtotime($replie['data'] . ' ' . $replie['ora']);
                    //il recente è se inferiore a 6 mesi dall'estrazione.. ovvero vado avanti di 90 giorni dalla data di estrazione e lo passo alla func
                    $rec = $this->check_uso_recente('risposta sms', ($strToTime + 7776000));
                    if ($rec == 'recente') {
                        $UsiRecentiEST[$cont_id] = 1;
                    }
                    ${'usi_est' . $cont_id}[$strToTime] = array('risposta sms', $replie['data'] . ' ' . $replie['ora'], '', $cliente, 'risposta ' . $assigned, $rec);
                    //print_r($replie);echo "<br>";
                }
                //echo "<br><br>";
            }
            //CREAT INT HTML FROM DATAS
            $usi_estHTML .= '<table class="table-blk">';

            foreach ($leadEstIdsArr as $cont_id) {
                $usi_estHTML .= '<div id="overlay" ><div id="loader-id-blacklister" class="mini-loader1"></div></div>';
                $blacklistedClass = 'not-blacklisted';
                $blacklistedClassString = '';
                if (array_key_exists($cont_id, $esBlc)) {
                    $blacklistedClass = 'blacklisted';
                    if ($esBlc[$cont_id] != 0) {
                        $blacklistedClassString = '<div class="show-blacklist">Blacklistato il ' . $esBlc[$cont_id] . '</div>';
                    } else {
                        $blacklistedClassString = '<div class="show-blacklist">Blacklistato</div>';
                    }
                }
                if ($UsiRecentiEST[$cont_id] == 1) {
                    $recentspan = '<span  class="bl-etichetta bl-etichetta-green">uso recente</span>';
                } else {
                    $recentspan = '<span  class="bl-etichetta bl-etichetta-red">uso non recente</span>';
                }

                $contactHeader = '<h4>ID ESTERNO: ' . $cont_id . ' | LOG <span class="logs">Data: ' . $EstLogs[$cont_id]["data"] . ', Url: ' . $EstLogs[$cont_id]["url"] . ', Ip: ' . $EstLogs[$cont_id]["indirizzo_ip"] . '</span>' . $EstLogs[$cont_id]["statusHtml"] . $recentspan . '</h4>' . $blacklistedClassString;

                $usi_estHTML .= '<tr class="contatto-tr-title contatto-tr ' . $blacklistedClass . ' ' . $EstLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="'.$leadEstIdsArrCell[$cont_id].'" ><td class="body-v">' . $contactHeader . '</td></tr><tr class="contatto-tr-data contatto-tr ' . $blacklistedClass . ' ' . $EstLogs[$cont_id]["status"] . '" data-id="' . $cont_id . '" data-cell="' . $leadEstIdsArrCell[$cont_id] . '"><td class="body-v"><div class="contatto-dati"><div class="typoOfT"><span>Dati</span></div><div>' . ${'contatto_est_' . $cont_id} . '</div></div>';
                if (isset(${'usi_est' . $cont_id}) and count(${'usi_est' . $cont_id}) > 0) {
                    $usi_estHTML .= '<div class="contatto-usi"><div class="typoOfT"><span>Usi</span></div><div><table><tr><th>Aging</th><th>Tipologia</th><th>Data Inizio</th><th>Data fine</th><th>Cliente</th><th>Additional</th></tr>';
                    krsort(${'usi_est' . $cont_id});
                    foreach (${'usi_est' . $cont_id} as $Data => $Dati) {
                        $usi_estHTML .= '<tr class="uso-row ' . $Dati[5] . '">';
                        $usi_estHTML .= '<td>' . $Dati[5] . '</td><td>' . $Dati[0] . '</td><td>' . $Dati[1] . '</td><td>' . $Dati[2] . '</td><td>' . $Dati[3] . '</td><td>' . $Dati[4] . '</td>';
                        $usi_estHTML .= '</tr>';
                    }
                    $usi_estHTML .= '</table></div></div>';
                } else {
                    $usi_estHTML .= '<div class="" style="padding-left:7px;border:none !important;"><p><b>Usi: </b>Nessun uso trovato per il contatto</p></div>';
                }
                $usi_estHTML .= '<div class="contatto-button"><input type="text" style="opacity:0;" class="copialog-data" value="Data: ' . $EstLogs[$cont_id]["data"] . ', Url: ' . $EstLogs[$cont_id]["url"] . ', Ip: ' . $EstLogs[$cont_id]["indirizzo_ip"] . '"><button class="blk-button btn btn-primary">Blacklista tutti</button><button class="blk-button-rev btn btn-primary">Rimuovi tutti da blacklist</button><button class="blk-button-this btn btn-primary">Blacklista questo record</button><button class="blk-button-rev-this btn btn-primary">Rimuovi questo record dalla blacklist</button><button class="btn btn-primary copialog">Copia log</button></div></td></tr><tr class="empty-row"><td></td></tr>';
            }
            $usi_estHTML .= '</table>';


            $esterneHTML .= $usi_estHTML . '</div>';

        }


		$interneHTML.=$esterneHTML;
        $interneHTML.=$filter_error;
		$response = new Response();
		$response->setContent(json_encode(array('contatti-intHtml'=>$interneHTML, 'contatti_est'=>$contatti_est, 'usi_int'=>$usi_int)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;

	}



	public function check_uso_recente($tipo,$data_sblocco){
		if($tipo=='Cootitolarit&agrave;' OR $tipo=='Vendita' OR $tipo=='vendita' OR $tipo=='cootitolarita' OR $data_sblocco==''){
			return 'recente';
		}else{
			//è un noleggio e ha data di sblocco.. calcoliamo se la data di sblocco è inferiore a now-90 giorni
			if($data_sblocco > (time()-7776000)){
				return 'recente';
			}else{
				return 'non-recente';
			}
		}

	}
	 public function check_if_blacklisted_int($id, $cell){
		 //check in extraction_blacklist
		 $count=0;
		 $data=0;
		 $Query="SELECT data_creazione FROM blacklist_extraction where lead_id=".$id." ORDER BY data_creazione DESC LIMIT 1";
		 $em = $this->getDoctrine()->getManager();
		 $stmt = $em->getConnection()->prepare($Query);
		 $stmt->execute();
		 $_bl_extr =  $stmt->fetchAll();
		 foreach( $_bl_extr as $bl){
			 $data=strtotime($bl['data_creazione']);
			 $count++;
			 break;
		 }
		 //check in blacklist_numeri
		 /*if($cell AND $cell!='' AND $cell!='NULL'){
//$Query="SELECT data_creazione FROM blacklist_numeri where cellulare=".$cell." ORDER BY data_creazione DESC LIMIT 1";
             $Query="SELECT data_creazione FROM blacklist_numeri where cellulare= '".$cell." ' ORDER BY data_creazione DESC LIMIT 1";
			 $em2 = $this->getDoctrine()->getManager();
			 $stmt2 = $em2->getConnection()->prepare($Query);
			 $stmt2->execute();
			 $_bl_extr_c =  $stmt2->fetchAll();
			 foreach( $_bl_extr_c as $bl){
				 $dataF=strtotime($bl['data_creazione']);
				 if($dataF>$data){
					$data=strtotime($bl['data_creazione']);
				 } $count++;
				 break;
			 }
		 }*/

		 if($count>0){
			 return array($data);
		 }
		 return false;
	 }
	 public function check_if_blacklisted_est($id){
		 $count=0;
		 $data=0;
		 $Query="SELECT data_inserimento FROM blacklist_esterne where lead_id=".$id." ORDER BY data_inserimento DESC LIMIT 1";
		 $em = $this->getDoctrine()->getManager();
		 $stmt = $em->getConnection()->prepare($Query);
		 $stmt->execute();
		 $_bl_extr =  $stmt->fetchAll();
		 foreach( $_bl_extr as $bl){
			$data=strtotime($bl['data_inserimento']);
			$count++;
			break;
		 }
		if($count>0){
			 return array($data);
		 }
		 return false;
	 }

	  public function write_history_blk($action,$opt){
		//write on blacklist_history
		$DateNow=date('Y-m-d h:i:s');
		$user=$this->getUser()->getUsername();
		if(!isset($user) OR $user==''){
			$user='non ritrovato';
		}
		$Query="INSERT INTO blacklist_history_dpo (user, action, option, data_action) VALUES ('".$user."', '".$action."','".$opt."', '".$DateNow."')";
			$em = $this->getDoctrine()->getManager();
			$stmt = $em->getConnection()->prepare($Query);
			if ($stmt->execute()) {
			   // it worked
				$statusId='ok';
			} else {
			   // it didn't
				$statusId='ko';
			}
		return true;
	 }



	 public function blacklistAction(Request $request){
		$type	 	= $request->get('type');
		$id			= $request->get('id');
		$cell		= $request->get('cell');
		$DateNow=date('Y-m-d H:i:s');
		$statusId='';
		$statusCell='';
		$idsTarget=array();
		$idsTarget_app=array();
         if ($cell != null) {
			//blacklist via id in blacklist_extraction
			//recupero source_db, source_tbl, source_id
			$QuerySelect="SELECT lu.id, lu.source_db, lu.source_tbl, lu.source_id,lu.cellulare FROM lead_uni lu where (lu.id =".$id. " or lu.cellulare='".$cell."') and lu.id not in (select be.lead_id from blacklist_extraction be)";
            $em3 = $this->getDoctrine()->getManager();
			$stmt3 = $em3->getConnection()->prepare($QuerySelect);
			$stmt3->execute();
			$_toBlacklist =  $stmt3->fetchAll();
			/* */ $source_id='';
			$source_db='';
			$source_tbl='';
			$source_id='';
			foreach($_toBlacklist as $bl) {
                /* */
                $idsTarget[] = $bl['id'];
                $bl_id = $bl['id'];
                $source_db = $bl['source_db'];
                $source_tbl = $bl['source_tbl'];
                $source_id = $bl['source_id'];
                $source_cell = $bl['cellulare'];

                    $Query = "INSERT IGNORE INTO blacklist_extraction (lead_id, source_db, source_tbl, source_id, data_creazione)
                        VALUES ('" . $bl_id . "','" . $source_db . "','" . $source_tbl . "','" . $source_id . "','" . $DateNow . "')";
                    $em = $this->getDoctrine()->getManager();
                    $stmt = $em->getConnection()->prepare($Query);
                    if ($stmt->execute()) {
                        $statusId = 'ok';
                    } else {
                        $statusId = 'ko';
                    }

                    if ($cell and $cell != '') {
                        $Query2 = "INSERT IGNORE INTO blacklist_numeri (cellulare, tipo_estrazione, data_creazione) VALUES ('" . $source_cell . " ','dpo blacklist', '" . $DateNow . "')";
                        $em2 = $this->getDoctrine()->getManager();
                        $stmt2 = $em2->getConnection()->prepare($Query2);
                        if ($stmt2->execute()) {
                            // it worked
                            $statusCell = 'ok';
                        }
                    }
                }

                    $Query_All_Esterne = " select lue.id,lue.cellulare from lead_uni_esterne lue where (lue.cellulare = '".$cell."' or lue.cellulare = md5('".$cell."')) and lue.id not in (select be.lead_id from blacklist_esterne be)";
                    $em9 = $this->getDoctrine()->getManager();
                    $stmt9 = $em9->getConnection()->prepare($Query_All_Esterne);
                    $stmt9->execute();
                    $_toBlacklist_Esterne =  $stmt9->fetchAll();
                    foreach($_toBlacklist_Esterne as $ble){
                        $DateNow=date('Y-m-d H:i:s');
                        //      Ciclo per inserimento blacklist
                        $idsTarget_app[]= $ble['id'];
                        $ide=intval($ble['id']);
                        $cell_ext=$ble['cellulare'];

                            $Query = "INSERT IGNORE INTO blacklist_esterne (lead_id, data_inserimento) VALUES (" . $ide . ",'" . $DateNow . "')";
                            $em8 = $this->getDoctrine()->getManager();
                            $stmt8 = $em8->getConnection()->prepare($Query);
                            if ($stmt8->execute()) {
                                $statusId = 'ok';
                            } else {
                                $statusId = 'ko';
                            }
                        }

             $idsTarget=array_merge($idsTarget,$idsTarget_app);
             $Par='type= '.$type.'; id= '.implode(',',$idsTarget).'; cell= '.$cell.'; statusId= '.$statusId.'; statusCell= '.$statusCell;
             $this->write_history_blk('blacklist', $Par);
             $this->send_email_bl($type, 'blacklistato', implode(",",$idsTarget)); /* */

                    }else {
                        $statusCell = 'ko';
                        $statusId = 'ko';
                    }


         $response = new Response();
         $response->setContent(json_encode(array('statusid'=>$statusId, 'statuscell'=>$statusCell, 'type'=>$type, 'id'=>$id, 'cell'=>$cell)));
         $response->headers->set('Content-Type', 'application/json');
         return $response;

	 }


	 public function send_email_bl($type, $blackdeblack, $id){
         $array_id=explode(",",$id);
         foreach ($array_id as $value) {

             $bl_url = $this->generateUrl('blacklister_blacklister', array('ProdId' => $value, 'ProdAmbiente'=>strtoupper($type)), UrlGeneratorInterface::ABSOLUTE_URL);
             // Create the Transport FUNZIONANTE
             $transport = \Swift_SmtpTransport::newInstance();
             // Create the Mailer using your created Transport
             $mailer = \Swift_Mailer::newInstance($transport);
             $emailTo= $this::$emailProduction;
             // Create a message
             $message = \Swift_Message::newInstance('Blacklist necessita un check '.date('Y-m-d H:i:s'));
             $message->setFrom(['noreply@linkappeal.it' => 'gestionale blacklister'])
                 ->setTo([$emailTo])
                 ->setBody($this->renderView(
                 // app/Resources/views/Emails/list_extracted.html.twig
                     'Emails/alert_prod_bypdo.html.twig',
                     array('intest' => $type, 'blackdeblack'=>$blackdeblack,'link'=> $bl_url)
                 ),
                     'text/html'
                 );

             // Send the message
             $result = $mailer->send($message);
         }

}

	 public function revblacklistAction(Request $request){
		$type	 	= $request->get('type');
		$id			= $request->get('id');
		$cell		= $request->get('cell');
		$DateNow=date('Y-m-d h:i:s');
		$statusId='';
		$statusCell='';
		$idsTarget=array();
		$idsTarget_app=array();

		    $Query_pre_delete_int="select lu.id from lead_uni lu where (cellulare = '".$cell."' or cellulare = md5('".$cell."')) and lu.id in (select be.lead_id from blacklist_extraction be)";
            $em_pre_int = $this->getDoctrine()->getManager();
            $stmt_pre_int = $em_pre_int->getConnection()->prepare($Query_pre_delete_int);
            $stmt_pre_int->execute();
            $_pre_int=$stmt_pre_int->fetchAll();
            foreach ($_pre_int as $preint) {
                $idsTarget[] = $preint['id'];
            }
            //blacklist via id in blacklist_extraction
            //$Query="DELETE FROM blacklist_extraction WHERE lead_id=".$id;
             $Query="DELETE from blacklist_extraction WHERE lead_id in (
                        select id from lead_uni where 
                        (cellulare = '".$cell."' or cellulare = md5('".$cell."')))";
             $em = $this->getDoctrine()->getManager();
             $stmt = $em->getConnection()->prepare($Query);
             if ($stmt->execute()) {
                 $statusId='ok';
             } else {
                 $statusId='ko';
             }
			//blacklist via number
			if($cell AND $cell!=''){
				$Query2="DELETE FROM blacklist_numeri WHERE  (cellulare = '".$cell."' or cellulare = md5('".$cell."'))";
				$em2 = $this->getDoctrine()->getManager();
				$stmt2 = $em2->getConnection()->prepare($Query2);
				if ($stmt2->execute()) {
					$statusCell='ok';
				}

                $Query_pre_delete_est="select lue.id from lead_uni_esterne lue where (lue.cellulare = '".$cell."' or lue.cellulare = md5('".$cell."')) and lue.id in (select be.lead_id from blacklist_esterne be)";
				$em_pre_est = $this->getDoctrine()->getManager();
                $stmt_pre_est = $em_pre_est->getConnection()->prepare($Query_pre_delete_est);
                $stmt_pre_est->execute();
                $_pre_est=$stmt_pre_est->fetchAll();
                foreach ($_pre_est as $prest) {
                    $idsTarget_app[] = $prest['id'];
                }


                $Query="DELETE FROM blacklist_esterne where lead_id  in ( select id from lead_uni_esterne where (cellulare = '".$cell."' or cellulare = md5('".$cell."')))";
                $em = $this->getDoctrine()->getManager();
                $stmt2 = $em->getConnection()->prepare($Query);
                $_rev_est=$stmt2->fetchAll();
                foreach ($_rev_est as $revest){
                    $idsTarget_app[]=$revest['id'];
                }
                if ($stmt2->execute()) {
                    $statusId='ok';
                } else {
                    $statusId='ko';
                }
			}

        $idsTarget=array_merge($idsTarget,$idsTarget_app);
		$Par='type= '.$type.'; id= '.implode(',',$idsTarget).'; cell= '.$cell.'; statusId= '.$statusId.'; statusCell= '.$statusCell;
		$this->write_history_blk('de-blacklist', $Par);
		$this->send_email_bl($type,'deblacklistato',implode(",",$idsTarget));
		$response = new Response();

		$response->setContent(json_encode(array('statusid'=>$statusId, 'statuscell'=>$statusCell, 'type'=>$type, 'id'=>$id, 'cell'=>$cell)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	 }

	 public function getclienteleaduni($idcliente){
		$query="SELECT name from cliente where id =".$idcliente;

		$em = $this->getDoctrine()->getManager();
		$stmt = $em->getConnection()->prepare($query);
		$stmt->execute();
		$queryRes =  $stmt->fetchAll();
					//echo "from concorso: ".$QueryPremi."<br>";
		$nome='';
		foreach($queryRes as $res){
			$nome=$res['name'];
		}
		return $nome;
	 }

	 public function getclienteconcorso($idcliente){
		$query="SELECT ragione_sociale from clienti where id =".$idcliente;
		$em = $this->getDoctrine()->getManager('concorso_man');
		$stmt = $em->getConnection()->prepare($query);
		$stmt->execute();
		$queryRes =  $stmt->fetchAll();
					//echo "from concorso: ".$QueryPremi."<br>";
		$nome='';
		foreach($queryRes as $res){
			$nome=$res['ragione_sociale'];
		}
		return $nome;
	 }

    public function thisblacklistAction(Request $request){
        $type	 	= $request->get('type');
        $id			= $request->get('id');
        $cell		= $request->get('cell');
        $camp       =$request->get('camp');
        $DateNow=date('Y-m-d H:i:s');
        if($type=='int'){
            //bl interna
            //blacklist via id in blacklist_extraction
            //recupero source_db, source_tbl, source_id
            //$QuerySelect="SELECT id, source_db, source_tbl, source_id FROM lead_uni where id =".$id;
            $QuerySel ="SELECT id, cellulare, source_db, source_tbl, source_id FROM lead_uni where id IN 
            (select id from lead_uni where (cellulare = '".$cell."' or cellulare = md5('".$cell."'))and id='".$id."')";

            $em4 = $this->getDoctrine()->getManager();
            $stmt4 = $em4->getConnection()->prepare($QuerySel);
            $stmt4->execute();
            $_toBlacklist =  $stmt4->fetchAll();
            /* */ $source_id='';
            $source_db='';
            $source_tbl='';
            $source_id='';
            foreach($_toBlacklist as $bl) {
                /* */
                $idsTarget[] = $bl['id'];
                $bl_id = $bl['id'];
                $source_db = $bl['source_db'];
                $source_tbl = $bl['source_tbl'];
                $source_id = $bl['source_id'];
                $source_cell = $bl['cellulare'];
                $statusId='ko';
                $statusCell='ko';
                if ($source_cell !=null) {
                    //      Ciclo per inserimento blacklist
                    $Queryext = "INSERT IGNORE INTO blacklist_extraction (lead_id, source_db, source_tbl, source_id, data_creazione)
                VALUES ('" . $bl_id . "','" . $source_db . "','" . $source_tbl . "','" . $source_id . "','" . $DateNow . "')";
                    $em = $this->getDoctrine()->getManager();
                    $stmt = $em->getConnection()->prepare($Queryext);
                    if ($stmt->execute()) {
                        // it worked
                        $statusId = 'ok';
                    } else {
                        // it didn't
                        $statusId = 'ko';
                    }
                    //
                    if ($cell and $cell != '') {
                        $Query2 = "INSERT IGNORE INTO blacklist_numeri (cellulare, tipo_estrazione, data_creazione) VALUES ('" . $source_cell . " ','dpo blacklist', '" . $DateNow . "')";
                        $em2 = $this->getDoctrine()->getManager();
                        $stmt2 = $em2->getConnection()->prepare($Query2);
                        if ($stmt2->execute()) {
                            // it worked
                            $statusCell = 'ok';
                        }
                    }
                }

            }
        }else{
            //bl esterna
            $statusCell='ko';
            $statusId='ko';

            $Query_All_Esterne = " select id,cellulare from lead_uni_esterne where (cellulare = '".$cell."' or cellulare = md5('".$cell."'))";
            $em9 = $this->getDoctrine()->getManager();
            $stmt9 = $em9->getConnection()->prepare($Query_All_Esterne);
            $stmt9->execute();
            $_toBlacklist_Esterne =  $stmt9->fetchAll();

            foreach($_toBlacklist_Esterne as $ble){
                $DateNow=date('Y-m-d H:i:s');
                //      Ciclo per inserimento blacklist
                $idsTarget[]= $ble['id'];
                $ide=intval($ble['id']);
                $cell_ext=$ble['cellulare'];
                if ($cell_ext !=null) {
                    $Query = "INSERT IGNORE INTO blacklist_esterne (lead_id, data_inserimento) VALUES (" . $ide . ",'" . $DateNow . "')";
                    $em8 = $this->getDoctrine()->getManager();
                    $stmt8 = $em8->getConnection()->prepare($Query);
                    if ($stmt8->execute()) {
                        // it worked
                        $statusId = 'ok';
                    } else {
                        // it didn't
                        $statusId = 'ko';
                    }
                }


            }
        }
        if ($statusCell=='ok' and $statusId=='ok'){
            $Par='type= '.$type.'; id= '.implode(',',$idsTarget).'; cell= '.$cell.'; statusId= '.$statusId.'; statusCell= '.$statusCell;
            $this->write_history_blk('blacklist', $Par);
            $this->send_email_bl($type,'blacklistato',implode(',',$idsTarget)); /* */
        }else{

        }
        $response = new Response();
        $response->setContent(json_encode(array('statusid'=>$statusId, 'statuscell'=>$statusCell, 'type'=>$type, 'id'=>$id, 'cell'=>$cell)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


        public function thisrevblacklistAction(Request $request){
            $type	 	= $request->get('type');
            $id			= $request->get('id');
            $cell		= $request->get('cell');
            $DateNow=date('Y-m-d h:i:s');
            if($type=='int'){
                //bl interna
                //blacklist via id in blacklist_extraction
                //$Query="DELETE FROM blacklist_extraction WHERE lead_id=".$id;
                $Query="DELETE from blacklist_extraction WHERE lead_id in (
                        select id from lead_uni where 
                        (cellulare = '".$cell."' or cellulare = md5('".$cell."')))";
                $em = $this->getDoctrine()->getManager();
                $stmt = $em->getConnection()->prepare($Query);
                if ($stmt->execute()) {
                    // it worked
                    $statusId='ok';
                } else {
                    // it didn't
                    $statusId='ko';
                }
                //blacklist via number
                $statusCell='ko';
                if($cell AND $cell!=''){
                    $Query2="DELETE FROM blacklist_numeri WHERE  (cellulare='".$cell."' or cellulare = md5('".$cell."'))";
                    $em2 = $this->getDoctrine()->getManager();
                    $stmt2 = $em2->getConnection()->prepare($Query2);
                    if ($stmt2->execute()) {
                        // it worked
                        $statusCell='ok';
                    }
                }

            }else{
                //bl esterna
                $statusCell='ko';
                $Query="DELETE FROM blacklist_esterne where lead_id  in ( select id from lead_uni_esterne where (cellulare = '".$cell."' or cellulare = md5('".$cell."')))";
                $em = $this->getDoctrine()->getManager();
                $stmt = $em->getConnection()->prepare($Query);
                if ($stmt->execute()) {
                    // it worked
                    $statusId='ok';
                } else {
                    // it didn't
                    $statusId='ko';
                }
            }

            $Par='type= '.$type.'; id= '.$id.'; cell= '.$cell.'; statusId= '.$statusId.'; statusCell= '.$statusCell;
            $this->write_history_blk('de-blacklist', $Par);
            $this->send_email_bl($type,'deblacklistato',$id);
            $response = new Response();
            $response->setContent(json_encode(array('statusid'=>$statusId, 'statuscell'=>$statusCell, 'type'=>$type, 'id'=>$id, 'cell'=>$cell)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

}