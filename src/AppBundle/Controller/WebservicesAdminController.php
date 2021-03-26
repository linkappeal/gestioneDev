<?php

namespace AppBundle\Controller;

use AppBundle\CustomFunc\WebServiceClient;
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

use AppBundle\CustomFunc\Handler as Handler;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Landing as Landing;
use AppBundle\Entity\Cliente as Cliente;
use AppBundle\Entity\A_landing_cliente as LandingCampagna;

use AppBundle\Entity\Fornitori as Fornitori;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

//use AppBundle\CustomFunc\Pager as Pager;

class WebservicesAdminController extends Controller
{
    const STANDARD_FIELDS = [
        'nome',
        'cognome',
        'email',
        'cellulare',
        'operatore',
        'tel_fisso',
        'sesso',
        'anno_nascita',
        'data_nascita',
        'eta',
        'luogo_nascita',
        'citta',
        'provincia',
        'indirizzo',
        'quartiere',
        'regione',
        'nazione',
        'cap',
        'forma_giuridica',
        'ragione_sociale',
        'partita_iva',
        'tipo_partita_iva',
        'codice_fiscale',
        'iban',
        'professione',
        'titolo_di_studio',
        'data_profilazione',
        'ip_profilazione',
        'url_profilazione',
        'privacy',
        'fornitore_ws',
        'data_ricezione',
    ];

    public function webservicesAction(Request $message = null)
    {
        $messageTypeM = $message->get('Messaggio');
        $IdWsM = $message->get('IdWs');
        $fornitoreM = $message->get('fornitore');

        $tokenM = $message->get('token');
        if ($messageTypeM == 'WsCrea') {
            $MessaggioHtml = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Ws creata (id ' . $IdWsM . ')</h3>';
            if (isset($fornitoreM) and $fornitoreM != '' and isset($tokenM) and $tokenM != '') {
                $MessaggioHtml .= '<p><table><tr><td>Fornitore:</td><td><b>' . $fornitoreM . '</b></td></tr><tr><td>token:</td><td><b>' . $tokenM . '</b></td></tr></table></p>';
            }
        } elseif ($messageTypeM == 'WsMod') {
            $MessaggioHtml = $MessaggioHtml = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Ws modificata (id ' . $IdWsM . ')</h3>';
        }

        $message = isset($MessaggioHtml) ? array($MessaggioHtml, $messageTypeM, $IdWsM) : FALSE;

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $webservices = $wsClient->fetchWebServices();

        $campagne = $this->getCampagne();

        $fornitori = $this->get_fornitori_dataAction();

        $wss = [];

        if (isset($webservices['items'])) {
            foreach ($webservices['items'] as $item) {
                $wss[] = [
                    'id' => isset($item['id']) ? $item['id'] : 0,
                    'nome' => isset($item['nome']) ? $item['nome'] : 0,
                    'fornitore' => !array_key_exists($item['fornitore_id'], $fornitori) ? 'FORNITORE NON TROVATO (id:' . $item['fornitore_id'] . ')' : $fornitori[$item['fornitore_id']]['nome'],
                    'campagna' => !array_key_exists($item['campagna_id'], $fornitori) ? 'CAMPAGNA NON TROVATA (id:' . $item['campagna_id'] . ')' : $campagne[$item['campagna_id']]['nome_offerta'],
                    'attiva' => isset($item['attiva']) ? $item['attiva'] : 0,
                    'ricevute' => isset($item['ricevute']) ? $item['ricevute'] : 0,
                    'accettate' => isset($item['accettate']) ? $item['accettate'] : 0,
                ];
            }
        }

        return $this->render('listato_ws.html.twig', array(
            'messaggio' => $message,
            'wss' => $wss,
        ), null);
    }

    public function get_specific_fieldsAction()
    {
        //recupero i fornitore
        $Query = "SELECT id, slug, tipo FROM campi_specifici";
        $em = $this->getDoctrine()->getManager('webservices_man');
        $stmt = $em->getConnection()->prepare($Query);
        $stmt->execute();
        $_campiselect = $stmt->fetchAll();
        $SpecificFieldsArray = array();
        foreach ($_campiselect as $_camposelect) {
            $SpecificFieldsArray[] = array('id' => $_camposelect['id'], 'slug' => $_camposelect['slug'], 'tipo' => $_camposelect['tipo']);
        }
        return $SpecificFieldsArray;
    }

    public function get_fornitori_dataAction()
    {
        //recupero i fornitore
        $Qfornitori = "SELECT nome, id, ragionesociale FROM fornitori";
        $emF = $this->getDoctrine()->getManager();
        $stmtF = $emF->getConnection()->prepare($Qfornitori);
        $stmtF->execute();
        $_fornitoriSelect = $stmtF->fetchAll();
        $FornitoriArray = array();
        foreach ($_fornitoriSelect as $_fornitoreSelect) {
            $FornitoriArray[$_fornitoreSelect['id']] = array('nome' => $_fornitoreSelect['nome'], 'ragione' => $_fornitoreSelect['ragionesociale'], 'id' => $_fornitoreSelect['id']);
        }
        return $FornitoriArray;
    }

    private function getCampagne()
    {
        //recupero i fornitore
        $Qfornitori = "SELECT nome_offerta, id FROM campagna";
        $emF = $this->getDoctrine()->getManager();
        $stmtF = $emF->getConnection()->prepare($Qfornitori);
        $stmtF->execute();
        $rows = $stmtF->fetchAll();
        $campange = [];
        foreach ($rows as $row) {
            $campange[$row['id']] = ['nome_offerta' => $row['nome_offerta'], 'id' => $row['id']];
        }
        return $campange;
    }

    public function generateTokenAction()
    {
        return md5(uniqid(rand(), true));
    }

    public function creaAction($message = null)
    {
        $token = '';

        $FornitoriArray = $this->get_fornitori_dataAction();
        $campagne = $this->getCampagne();

        $standardFields = [];

        $specificFieldsArray = $this->fetchSpecificFieldsArray();

        $routesArray = $this->fetchRoutesArray();

        $ws = FALSE;

        return $this->render('crea_ws.html.twig', array(
            'token' => $token,
            'fornitori' => $FornitoriArray,
            'campagne' => $campagne,
            'standardfields' => $standardFields,
            'specificfields' => $specificFieldsArray,
            'routes' => $routesArray,
            'ws' => $ws,
            'WssNames' => [],
        ), null);
    }

    //SAVE FUNCTIONS
    public function salvaWebserviceAction(Request $request)
    {
        $nome = $request->get('nome');
        $fornitore = $request->get('fornitore');
        $campagna = $request->get('campagna');
        $attiva = $request->get('attiva');
        $routes = $request->get('routes') ? explode(',', $request->get('routes')) : [];

        $StandardFields = $request->get('cobb');
        $SpecificFields = $request->get('cspec');
        $limitetipo = $request->get('limitetipo');
        $limite = $request->get('limite');
        if (!$limite or $limite == '') {
            $limite = 0;
        }
        $deduplicatipo = $request->get('deduplicatipo');
        $deduplicacampi = $request->get('deduplicacampi');
        //create deduplica for db
        if ($deduplicatipo == 0 or $deduplicacampi == '') {
            $deduplica = serialize(array());
        } else {
            $deduplicacampi = explode(',', $deduplicacampi);
            $deduplica = serialize(array($deduplicatipo => $deduplicacampi));
        }

        $ips = $request->get('ips');
        //  $ips = $ips == '' ? serialize(array()) : serialize(explode(';', $ips));
        $ips = $ips == '' ? [] : explode(';', $ips);

        $privacy_tags = $request->get('privacy_tags');
        $privacy_tags = $privacy_tags == '' ? [] : explode(';', $privacy_tags);

        $token = $this->generateTokenAction();

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $result = $wsClient->storeWebService([
                'nome' => $nome,
                'fornitore_id' => $fornitore,
                'campagna_id' => $campagna,
                'token' => $token,
                'attiva' => $attiva,
                //'routing_id' => $routings,
                'campi_obbligatori' => $StandardFields,
                'campi_specifici' => $SpecificFields,
                'tipo_deduplica' => $deduplica,
                'tipo_limite' => $limitetipo,
                'limite' => $limite,
                'whitelist_ips' => $ips,
                'privacy_tags' => $privacy_tags,
                'route_ids' => $routes
            ]
        );

        $newId = $result['id'];

        $fornitori = $this->get_fornitori_dataAction();
        $campagne = $this->getCampagne();

        $response = new Response();
        $response->setContent(json_encode([
            'result' => $newId,
            'fornitore' => !array_key_exists($fornitore, $fornitori) ? 'FORNITORE NON TROVATO (id:' . $result['fornitore_id'] . ')' : $fornitori[$fornitore]['nome'],
            'campagna' => !array_key_exists($campagna, $campagne) ? 'CAMPAGNA NON TROVATA (id:' . $result['campagna_id'] . ')' : $campagne[$campagna]['nome_offerta'],
            'token' => $token
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }

    // render pagina di modifica ordine payout clienti
    public function modificaAction(Request $request, $message = null)
    {
        $id = $request->get('ws_id');

        //genera un token
        $token = '';

        $FornitoriArray = $this->get_fornitori_dataAction();
        $campagne = $this->getCampagne();

        $standardFieldsArray = [];

        $specificFieldsArray = $this->fetchSpecificFieldsArray();

        $routesArray = $this->fetchRoutesArray();

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $ws = $wsClient->fetchWebService($id);

        return $this->render('crea_ws.html.twig', array(
            'token' => $token,
            'fornitori' => $FornitoriArray,
            'campagne' => $campagne,
            'standardfields' => $standardFieldsArray,
            'specificfields' => $specificFieldsArray,
            'routes' => $routesArray,
            'ws' => $ws,
            'WssNames' => [],
        ), null);
    }

    public function editWebserviceAction(Request $request)
    {
        $id = $request->get('id');
        $nome = $request->get('nome');
        $fornitore = $request->get('fornitore');
        $campagna = $request->get('campagna');
        $attiva = $request->get('attiva');
        $routes = $request->get('routes') ? explode(',', $request->get('routes')) : [];
        $StandardFields = $request->get('cobb');
        $SpecificFields = $request->get('cspec');
        $limitetipo = $request->get('limitetipo');
        $limite = $request->get('limite');
        if (!$limite or $limite == '') {
            $limite = 0;
        }
        $deduplicatipo = $request->get('deduplicatipo');
        $deduplicacampi = $request->get('deduplicacampi');
        //create deduplica for db
        if ($deduplicatipo == 0 or $deduplicacampi == '') {
            $deduplica = [];
        } else {
            $deduplicacampi = explode(',', $deduplicacampi);
            $deduplica = [$deduplicatipo => $deduplicacampi];
        }

        $ips = $request->get('ips');
        //  $ips = $ips == '' ? serialize(array()) : serialize(explode(';', $ips));
        $ips = $ips == '' ? [] : explode(';', $ips);

        $privacy_tags = $request->get('privacy_tags');
        $privacy_tags = $privacy_tags == '' ? [] : explode(';', $privacy_tags);

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $result = $wsClient->storeWebService([
                'id' => $id,
                'nome' => $nome,
                'fornitore_id' => $fornitore,
                'campagna_id' => $campagna,
                'attiva' => $attiva,
                'campi_obbligatori' => $StandardFields,
                'campi_specifici' => $SpecificFields,
                'tipo_deduplica' => $deduplica,
                'tipo_limite' => $limitetipo,
                'limite' => $limite,
                'whitelist_ips' => $ips,
                'privacy_tags' => $privacy_tags,
                'route_ids' => $routes
            ]
        );

        $newId = $result['id'];

        $fornitori = $this->get_fornitori_dataAction();
        $campagne = $this->getCampagne();

        $response = new Response();
        $response->setContent(json_encode([
            'result' => $newId,
            'fornitore' => !array_key_exists($fornitore, $fornitori) ? 'FORNITORE NON TROVATO (id:' . $result['fornitore_id'] . ')' : $fornitori[$fornitore]['nome'],
            'campagna' => !array_key_exists($campagna, $campagne) ? 'CAMPAGNA NON TROVATA (id:' . $result['campagna_id'] . ')' : $campagne[$campagna]['nome_offerta'],
            'token' => $token
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    public function getWebserviceReportAction(Request $request)
    {
        $id = $request->get('ws_id');
        $granulosita = $request->get('granulosita') ? $request->get('granulosita') : 'mese';
        $group = $granulosita == 'mese' ? 'month' : 'day';

        $presetdata = $request->get('presetdata') ? $request->get('presetdata') : 'completo';
        if ($presetdata == 'personale') {
            $start = $request->get('start');
            $end = $request->get('end');
            $start = new \DateTime($start);
            $end = new \DateTime($end);
            if (!$start or !$end) {
                $presetdata = 'completo';
            }
        }
        switch ($presetdata) {
            case 'personale':
                break;
            case 'sette':
                //ultimi sette giorni
                $QueryDateRestrict = 'date_add(data_miscelazione, interval 7 day) >= NOW()';
                $QueryDateFrontend = 'Ultimi 7 giorni';
                $start = new \DateTime('7 days ago');
                $end = new \DateTime('today');
                break;
            case 'trenta':
                //ultimi 30 giorni
                $QueryDateRestrict = 'date_add(data_miscelazione, interval 30 day) >= NOW()';
                $QueryDateFrontend = 'Ultimi 30 giorni';
                $start = new \DateTime('30 days ago');
                $end = new \DateTime('today');
                break;
            case 'mese':
                $start = new \DateTime('first day of this month');
                $end = new \DateTime('today');
                break;
            case 'mesepre':
                $start = new \DateTime('first day of previous month');
                $end = new \DateTime('last day of previous month');
                break;
            default:
            case 'completo':
                $start = new \DateTime('2020-09-01');
                $end = new \DateTime('today');
                break;
        }

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $report = $wsClient->fetchWebServiceReports($id, $start, $end, $group);

        $table = $this->prepareReport($report);

        $reportHTML = '<p style="margin-bottom:30px;"><b>Dati per il periodo <i>"' . $start->format('Y-m-d') . ' - ' . $end->format('Y-m-d') . '"</i></b></p>';

        $reportHTML .= '<table id="ReportTable" class="table table-bordered" data-period="' . $presetdata . '" data-da="' . $start->format('Y-m-d') . '" data-a="' . $end->format('Y-m-d') . '"><tbody>';

        $reportHTML .= '<tr class="Rheadtable"><th class="Rgranulo"></th><th>Ricevute</th><th>Accettate</th><th>Rifiutate</th><th>Duplicate</th></tr>';

        $received = $table['totals']['received'];
        $accepted = $table['totals']['accepted'];
        $refused = $table['totals']['refused'];
        $duplicated = $table['totals']['duplicated'];
        $reportHTML .= sprintf('<tr class="Rbodytable RbodytableTotals"><td class="Rgranulo">Totali  periodo</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $received, $accepted, $refused, $duplicated);

        foreach ($table['rows'] as $index => $item) {
            $received = isset($item['received']) ? $item['received'] : 0;
            $accepted = isset($item['accepted']) ? $item['accepted'] : 0;
            $refused = isset($item['refused']) ? $item['refused'] : 0;
            $duplicated = isset($item['duplicated']) ? $item['duplicated'] : 0;
            $reportHTML .= sprintf('<tr class="Rbodytable"><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $index, $received, $accepted, $refused, $duplicated);
        }
        $reportHTML .= '</tbody></table>';

        $response = new Response();
        $response->setContent(json_encode(['report' => $reportHTML]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    public function reportWebserviceAction(Request $request)
    {
        $id = $request->get('ws_id');

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $wsFromList = $wsClient->fetchWebServices($id);
        $ws = $wsClient->fetchWebService($id);

        $totals = [];
        if (isset($wsFromList['items'][0])) {
            $totals = [
                'ricevute' => isset($wsFromList['items'][0]['ricevute']) ? $wsFromList['items'][0]['ricevute'] : 0,
                'accettate' => isset($wsFromList['items'][0]['accettate']) ? $wsFromList['items'][0]['accettate'] : 0,
                'rifiutate' => isset($wsFromList['items'][0]['rigettate']) ? $wsFromList['items'][0]['rigettate'] : 0,
                'duplicate' => isset($wsFromList['items'][0]['duplicate']) ? $wsFromList['items'][0]['duplicate'] : 0,
            ];
        }

        return $this->render('report_ws.html.twig', array(
            'ws' => $ws,
            'report' => '',
            'totals' => $totals
        ), null);
    }


    public function specificFieldsAction(Request $request)
    {
        $message = isset($MessaggioHtml) ? array($MessaggioHtml, $messageTypeM, $IdWsM) : FALSE;

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $result = $wsClient->fetchSpecificFields();

        $list = [];

        if (isset($result['items'])) {
            foreach ($result['items'] as $item) {
                $list[] = [
                    'id' => isset($item['id']) ? $item['id'] : 0,
                    'slug' => isset($item['slug']) ? $item['slug'] : '',
                    'tipo' => isset($item['tipo']) ? $item['tipo'] : '',
                    'sanificazione' => isset($item['sanificazione']) ? $item['sanificazione'] : [],
                    'validazione' => isset($item['validazione']) ? $item['validazione'] : [],
                ];
            }
        }

        return $this->render('specificfields.html.twig', array(
            'messaggio' => $message,
            'list' => $list,
        ), null);
    }

    public function mappingAction(Request $request)
    {
        $schema = $request->get('schema');
        $table = $request->get('table');

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $standard = $wsClient->fetchStandardFieldsMapping($schema, $table);
        if (empty($standard)) {
            $standard = [
                'schema' => $schema,
                'table' => $table
            ];
        }

        $specific = $wsClient->fetchSpecificFieldsMappings($schema, $table);

        $items = $specific['items'];
        foreach ($items as $key => $item) {
            $sf = $wsClient->fetchSpecificField($item['specific_field_id']);
            $items[$key]['slug'] = $sf['slug'];
            $items[$key]['tipo'] = $sf['tipo'];
        }

        return $this->render('mapping.html.twig', array(
            'messaggio' => $message,
            'standard' => $standard,
            'specific' => $items,
        ), null);
    }

    public function routesAction(Request $request)
    {
        $message = $request->get('message');
        $id = $request->get('id');
        if ($message == 'new') {
            $banner = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Rotta creata (id ' . $id . ')</h3>';
        } elseif ($message == 'edit') {
            $banner = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Rotta modificata (id ' . $id . ')</h3>';
        } elseif ($message == 'editMap') {
            $banner = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i> Mappatura aggiornata</h3>';
        }

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $routes = $wsClient->fetchRoutes();

        $list = [];

        if (isset($routes['items'])) {
            foreach ($routes['items'] as $item) {
                $list[] = [
                    'id' => isset($item['id']) ? $item['id'] : 0,
                    'name' => isset($item['name']) ? $item['name'] : '',
                    'schema' => isset($item['schema']) ? $item['schema'] : '',
                    'table' => isset($item['table']) ? $item['table'] : '',
                    'enabled' => isset($item['enabled']) ? $item['enabled'] : 0,
                ];
            }
        }

        return $this->render('routes.html.twig', array(
            'messaggio' => isset($banner) ? [$banner, $message, $id] : FALSE,
            'list' => $list,
        ), null);
    }

    public function editRouteAction(Request $request)
    {
        $id = $request->get('id') ? $request->get('id') : 0;

        if ($id) {
            $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
            $result = $wsClient->fetchRoute($id);
        } else {
            $result = false;
        }

        return $this->render('route_edit.html.twig', array(
            'entity' => $result,
        ), null);
    }

    public function editSpecificFieldAction(Request $request)
    {
        $id = $request->get('id') ? $request->get('id') : 0;

        if ($id) {
            $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
            $result = $wsClient->fetchSpecificField($id);
        } else {
            $result = false;
        }

        return $this->render('specificfield_edit.html.twig', array(
            'entity' => $result,
        ), null);
    }

    public function saveRouteAction(Request $request)
    {
        $id = $request->get('id') ? $request->get('id') : 0;
        $name = $request->get('name') ? $request->get('name') : '';
        $schema = $request->get('schema') ? $request->get('schema') : '';
        $table = $request->get('table') ? $request->get('table') : '';
        $enabled = $request->get('enabled') ? $request->get('enabled') : 0;

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $result = $wsClient->storeRoute([
                'id' => $id,
                'name' => $name,
                'schema' => $schema,
                'table' => $table,
                'enabled' => $enabled,
            ]
        );

        $newId = $result['id'];

        $response = new Response();
        $response->setContent(json_encode([
            'result' => $newId,
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function saveSpecificFieldAction(Request $request)
    {
        $id = $request->get('id') ? $request->get('id') : 0;
        $slug = $request->get('slug') ? $request->get('slug') : '';
        $tipo = $request->get('tipo') ? $request->get('tipo') : '';
        $sanificazione = $request->get('sanificazione') ? $request->get('sanificazione') : [];

        $validazione = [];
        if ($request->get('validate-check_max_length') && $request->get('validate-check_max_length-number')) {
            $validazione['check_max_length'] = $request->get('validate-check_max_length-number');
        }
        if ($request->get('validate-check_min_length') && $request->get('validate-check_min_length-number')) {
            $validazione['check_min_length'] = $request->get('validate-check_min_length-number');
        }
        if ($request->get('validate-check_exact_length') && $request->get('validate-check_exact_length-number')) {
            $validazione['check_exact_length'] = $request->get('validate-check_exact_length-number');
        }
        if ($request->get('validate-check_is_greather_than') && $request->get('validate-check_is_greather_than-number')) {
            $validazione['check_is_greather_than'] = $request->get('validate-check_is_greather_than-number');
        }
        if ($request->get('validate-check_is_less_than') && $request->get('validate-check_is_less_than-number')) {
            $validazione['check_is_less_than'] = $request->get('validate-check_is_less_than-number');
        }
        if ($request->get('validate-check_equal') && $request->get('validate-check_equal-number')) {
            $validazione['check_equal'] = $request->get('validate-check_equal-number');
        }
        if ($request->get('validate-check_in_values') && $request->get('validate-check_in_values-number')) {
            $validazione['check_in_values'] = $request->get('validate-check_in_values-number');
        }
        if ($request->get('validate-check_is_email')) {
            $validazione['check_is_email'] = true;
        }
        if ($request->get('validate-check_is_url')) {
            $validazione['check_is_url'] = true;
        }
        if ($request->get('validate-check_is_ip')) {
            $validazione['check_is_ip'] = true;
        }
        if ($request->get('validate-check_datetime_format') && $request->get('validate-check_datetime_format-number')) {
            $validazione['check_datetime_format'] = $request->get('validate-check_datetime_format-number');
        }
        if ($request->get('validate-check_in_comuni')) {
            $validazione['check_in_comuni'] = true;
        }

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        $result = $wsClient->storeSpecificField([
                'id' => $id,
                'slug' => $slug,
                'tipo' => $tipo,
                'sanificazione' => $sanificazione,
                'validazione' => $validazione,
            ]
        );

        $newId = $result['id'];

        $response = new Response();
        $response->setContent(json_encode([
            'result' => $newId,
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function saveMappingAction(Request $request)
    {
        $schema = $request->get('schema');
        $table = $request->get('table');
        $nome = $request->get('nome');
        $cognome = $request->get('cognome');
        $email = $request->get('email');
        $cellulare = $request->get('cellulare');
        $operatore = $request->get('operatore');
        $tel_fisso = $request->get('tel_fisso');
        $sesso = $request->get('sesso');
        $anno_nascita = $request->get('anno_nascita');
        $data_nascita = $request->get('data_nascita');
        $eta = $request->get('eta');
        $luogo_nascita = $request->get('luogo_nascita');
        $citta = $request->get('citta');
        $provincia = $request->get('provincia');
        $indirizzo = $request->get('indirizzo');
        $quartiere = $request->get('quartiere');
        $regione = $request->get('regione');
        $nazione = $request->get('nazione');
        $cap = $request->get('cap');
        $forma_giuridica = $request->get('forma_giuridica');
        $ragione_sociale = $request->get('ragione_sociale');
        $partita_iva = $request->get('partita_iva');
        $tipo_partita_iva = $request->get('tipo_partita_iva');
        $codice_fiscale = $request->get('codice_fiscale');
        $iban = $request->get('iban');
        $professione = $request->get('professione');
        $titolo_di_studio = $request->get('titolo_di_studio');
        $data_profilazione = $request->get('data_profilazione');
        $ip_profilazione = $request->get('ip_profilazione');
        $url_profilazione = $request->get('url_profilazione');
        $privacy = $request->get('privacy');
        $fornitore_ws = $request->get('fornitore_ws');
        $data_ricezione = $request->get('data_ricezione');

        $specific = $request->get('specific');

        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        try {
            $wsClient->storeStandardFieldsMapping([
                    'schema' => $schema,
                    'table' => $table,
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'email' => $email,
                    'cellulare' => $cellulare,
                    'operatore' => $operatore,
                    'tel_fisso' => $tel_fisso,
                    'sesso' => $sesso,
                    'anno_nascita' => $anno_nascita,
                    'data_nascita' => $data_nascita,
                    'eta' => $eta,
                    'luogo_nascita' => $luogo_nascita,
                    'citta' => $citta,
                    'provincia' => $provincia,
                    'indirizzo' => $indirizzo,
                    'quartiere' => $quartiere,
                    'regione' => $regione,
                    'nazione' => $nazione,
                    'cap' => $cap,
                    'forma_giuridica' => $forma_giuridica,
                    'ragione_sociale' => $ragione_sociale,
                    'partita_iva' => $partita_iva,
                    'tipo_partita_iva' => $tipo_partita_iva,
                    'codice_fiscale' => $codice_fiscale,
                    'iban' => $iban,
                    'professione' => $professione,
                    'titolo_di_studio' => $titolo_di_studio,
                    'data_profilazione' => $data_profilazione,
                    'ip_profilazione' => $ip_profilazione,
                    'url_profilazione' => $url_profilazione,
                    'privacy' => $privacy,
                    'fornitore_ws' => $fornitore_ws,
                    'data_ricezione' => $data_ricezione,
                ]
            );

            foreach ($specific as $id => $destination_slug) {
                $wsClient->storeSpecificFieldMapping([
                    'schema' => $schema,
                    'table' => $table,
                    'specific_field_id' => $id,
                    'destination_slug' => $destination_slug,
                ]);
            }

            $result = 'OK';
        } catch (\Exception $e) {
            $result = 'KO';
        }

        $response = new Response();
        $response->setContent(json_encode([
            'result' => $result,
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function prepareReport(array $report)
    {
        $table = [
            'rows' => [],
            'totals' => $report['totals']
        ];

        if (isset($report['by_day']) && is_array($report['by_day'])) {
            $byDay = $report['by_day'];
            foreach ($byDay as $type => $list) {
                foreach ($list as $item) {
                    $day = $item[0];
                    $count = $item[1];
                    $table['rows'][$day][$type] = $count;
                }
            }
        } elseif (isset($report['by_month'])) {
            $byMonth = $report['by_month'];
            foreach ($byMonth as $type => $list) {
                foreach ($list as $item) {
                    $year = $item[0];
                    $month = $item[1];
                    $count = $item[2];
                    $table['rows']["{$year}-{$month}"][$type] = $count;
                }
            }
        }

        ksort($table['rows']);
        return $table;
    }

    public function unroutedAction(Request $request)
    {
        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));

        if ($id = $request->get('id')) {
            $res = $wsClient->fetchContact($id);
            if (!empty($res['data'])) {
                return $this->render('unrouted_contact.html.twig', array(
                    'retry' => "{$request->get('route')}-{$request->get('id')}",
                    'entity' => $res['data'],
                ), null);
            }
        }

        if ($retry = $request->get('retry')) {
            $res = $wsClient->retryUnrouted($retry);
            $message = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i>Anagrafica candidata per nuovo tentativo</h3>';
        }

        if ($trash = $request->get('trash')) {
            $res = $wsClient->hideUnrouted($trash);
            $message = '<h3><i class="fa fa-check-circle-o" aria-hidden="true"></i>Anagrafica scartata</h3>';
        }

        $contacts = $wsClient->fetchUnrouted();

        $list = [];
        if (isset($contacts['items'])) {
            foreach ($contacts['items'] as $item) {
                $list[] = [
                    'id' => isset($item['id']) ? $item['id'] : 0,
                    'id_route' => isset($item['id_route']) ? $item['id_route'] : 0,
                    'retry_id' => isset($item['id_route'], $item['id_contact']) ? "{$item['id_route']}-{$item['id_contact']}" : 0,
                    'date_routing' => isset($item['date_routing']) ? $item['date_routing'] : '',
                    'destination_schema' => isset($item['destination_schema']) ? $item['destination_schema'] : '',
                    'destination_table' => isset($item['destination_table']) ? $item['destination_table'] : '',
                    'errors' => isset($item['errors']) ? $item['errors'] : '',
                ];
            }
        }

        return $this->render('unrouted.html.twig', [
            'list' => $list,
            'messaggio' => isset($message) ? [$message, '', ''] : ''
        ], null);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function fetchRoutesArray()
    {
        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $routes = $wsClient->fetchRoutes();
        $routesArray = [];
        if (isset($routes['items'])) {
            foreach ($routes['items'] as $item) {
                if (isset($item['id'])) {
                    $routesArray[$item['id']] = [
                        'id' => $item['id'],
                        'nome' => isset($item['name']) ? $item['name'] : '',
                    ];
                }
            }
        }
        return $routesArray;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function fetchSpecificFieldsArray()
    {
        $wsClient = new WebServiceClient($this->getParameter('ws_baseurl'), $this->getParameter('ws_token'));
        $specificFieldsArray = [];
        $specificFields = $wsClient->fetchSpecificFields();
        if (isset($specificFields['items'])) {
            foreach ($specificFields['items'] as $item) {
                if (isset($item['slug'])) {
                    $specificFieldsArray[$item['slug']] = [
                        'slug' => (isset($item['slug']) ? $item['slug'] : ''),
                    ];
                }
            }
        }
        return $specificFieldsArray;
    }

}