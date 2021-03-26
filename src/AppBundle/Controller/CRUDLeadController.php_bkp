<?php

namespace AppBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Doctrine\Common\Util\Debug;
use Exporter\Source\DoctrineORMQuerySourceIterator;

use DoctrineORMEntityManager;

use Exporter\Source\SourceIteratorInterface;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\JsonWriter;
use Exporter\Writer\XlsWriter;
use Exporter\Writer\XmlWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

use AppBundle\CustomFunc\Handler as Handler;
use AppBundle\Entity\Extraction as Extraction;
use AppBundle\Entity\Extraction_history as Extraction_history;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Cliente as Cliente;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CRUDLeadController extends Controller
{
    
     /**
     * Export data to specified format.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws AccessDeniedException If access is not granted
     * @throws \RuntimeException     If the export format is invalid
     */
    
    private $nested_checkboxes = [
        'Anagrafica' => ['Nome' => 'nome',
                         'Cognome' => 'cognome',
                         'Ragione sociale' => 'ragione_sociale',
                         'E-mail' => 'email',
                         'Cellulare' => 'cellulare',
                         'Tel Fisso' => 'tel_fisso',
                         'Età' => 'eta',
                         'Codice Fiscale' => 'codice_fiscale',
                         'Forma giuridica' => 'forma_giuridica',
                         'P.IVA' => 'partita_iva',
                         'Tipo P.IVA' => 'tipo_partita_iva' 
                        ],
        'Campagna' => ['Brand Campagna' => 'campagna.brand.name',
                       'Nome offerta' => 'campagna.nome_offerta',
                        'Settore' => 'campagna.settore',
                        'Tipo' => 'campagna.tipo_campagna',
                        'Target' => 'campagna.target_campagna',
                        'Data inizio' => 'campagna.data_start',
            ],
        'Localizzazione' => ['Città' => 'citta',
                         'Indirizzo' => 'indirizzo',
                         'Provincia' => 'provincia',
                         'CAP' => 'cap',
                         'Nazione' => 'nazione',
                         'Regione' => 'regione',
                         'Quartiere' => 'quartiere'
                        ],
        'Campi uso interno' => ['Reference ID' => 'reference_id',
                                'Banner ID' => 'banner_id',
                                'Editore' => 'editore',
                                'Source ID' => 'source_id',
                                'Source TBL' => 'source_tbl',
                                'Source DB' => 'source_db',
                                'ID Campagna' => 'campagna.id',
                               ],
        'Campi di sistema' => [ 'Indirizzo IP' => 'indirizzo_ip',
                                'Download' => 'download',
                                'URL' => 'url',
                                'Token verified' => 'token_verified',
                                'Data' => 'data',
                                'Code' => 'code'
                            ],                
        'Campi addizionali' => ['Già cliente' => 'gia_cliente',
                                'Importo richiesto' => 'importo_richiesto',
                                'Operatore' => 'operatore'                                        
                              ], 


        ];

    private $campi_default = 
            ['nome', 'cognome', 'ragione_sociale', 'cellulare', 'campagna.nome_offerta', 'data'];
    
    
    public function exportAction(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $id_user = $user->getId();
        $username_user = $user->getUsername();
        $email_user = $user->getEmail();
        $admin_mails = $this->getAdminMails();
                       
        //$this->denyAccessUnlessGranted('ROLE_ESTRAZIONE_LEAD', null, 'Attenzione! Non hai i permessi per effettuare l\'estrazione!');  
        if (!$this->isGranted('ROLE_ESTRAZIONE_LEAD', null)) {
            return $this->render('access_denied.html.twig');            
        }       
        
        $session = $request->getSession();
        
        if($request->get('save_url')==1){
            $session->set('referer', $this->getRequest()->headers->get('referer').'?'.$this->getRequest()->getQueryString());            
        }

        //var_dump($this->getRequest());
        $ip_address = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
                      
        $askConfirmation = true;     
        
        $listFields = $request->get('campi_export');
        if(!empty($listFields)) $this->admin->setListFields($listFields);
        
        $num_lead = $request->get('limit');
        $max_lead = $request->get('max_lead');
        if (!empty($request->get('filter')['type'])) $extraction_type = $request->get('filter')['type']['value'];
        else $extraction_type = ''; 

        $datagrid = $this->admin->getDatagrid();
       
        //Salviamo in sessione le info per il paginatore
        if($request->get('filter')['_page'] == 0){
            if (!empty($num_lead)) $session->set('limit', $num_lead);
            else  $session->set('limit', 0);
            $session->set('max_lead', $max_lead);
            if ($extraction_type == 'nol'){
                $session->set('is_noleggio', 1);
            }
            else $session->set('is_noleggio', 0);
        }
        else {
            $num_lead = $session->get('limit');
            $max_lead = $session->get('max_lead');
        }
        
        if (!empty($num_lead)) {
             $datagrid->setLeadLimit($num_lead);
             //$lead_limit = $num_lead;
        }
        else {
            $datagrid->setLeadLimit($max_lead);
            
        }
        $lead_limit = $max_lead;
        
        if(empty($num_lead)) $num_lead = $max_lead;
             
        $datagrid->buildPager();
        
        $queryBuilder = $datagrid->getQuery();
        //$queryBuilder->orderBy('o.data', 'DESC');

        $query = $this->getDoctrine()->getManager()->createQueryBuilder();
        $nots = $query->select('ext')
                      ->from('AppBundle:Extraction', 'ext')
                      ->where('ext.data_sblocco >= CURRENT_TIMESTAMP()')
                      ->getQuery()
                      ->getArrayResult();
       
        $nots_val = array_map(function($val){return $val['lead_id'];}, $nots);
        
        if ($askConfirmation ) {
            $actionLabel = 'export';
            //$batchTranslationDomain ='bau';

            $ext_type = $request->get('type');
            if(isset($ext_type) && ($ext_type != '')){
                
                if (!empty($nots_val)) $queryBuilder->andWhere($queryBuilder->expr()->notIn('o.id', array_unique($nots_val)));
                
                switch($request->get('type')){
                    case "0": 
                        $queryBuilder->andWhere('o.tel_fisso is not null or o.cellulare is not null');
                        break;
                    case "1":
                        $queryBuilder->andWhere('o.tel_fisso is not null');
                        break;
                    case "2": 
                        $queryBuilder->andWhere('o.cellulare is not null');
                        break;  
                    default:
                        break;
                }
            }
            
            /******************/
            //Query per prendere gli id che soddisfano i parametri di ricerca compresa la limit 

                $queryc = clone $queryBuilder;
                $queryc->select('o.id');
                
                if ($extraction_type == 'nol'){
                    $queryc->setMaxResults($max_lead); 
                }
                else{
                    if (!empty($num_lead))$queryc->setMaxResults($num_lead);
                    else $queryc->setMaxResults($max_lead); 
                }
                
                //Se sono le estraibili per vendita dobbiamo porre il vincolo per quelle odierne
                if ((!empty($request->get('filter')['gia_estratte'])) &&
                    ($request->get('filter')['gia_estratte']['value'] == '0')){
                        $queryc->andWhere('e.data_sblocco <= CURRENT_TIMESTAMP() OR e.data_sblocco IS NULL');
                }
                
                $result = $queryc->getQuery()->getArrayResult();

                $limit_val = array_map(function($val) {
                    return $val['id'];
                }, $result);
                
                //$limit_val = array_rand($limit_val, $lead_limit);
                
                //Se la pagina è la 0 vuol dire che abbiamo generato la lista random
                if($request->get('filter')['_page'] == 0){
                    //if($lead_limit>count($limit_val))$lead_limit=count($limit_val);  //Per evitare problemi con l'istruzione seguente
                    //var_dump(count($limit_val),  $lead_limit);
                    if ($extraction_type == 'nol') { $limit_val = array_rand(array_flip($limit_val), $num_lead ); }
                    else $limit_val = array_slice($limit_val, 0, $num_lead);
                    $session->set('arrIds', $limit_val);

                }
                else {
                    $limit_val = $session->get('arrIds');
                }
    
                

                $queryBuilder->andWhere($queryBuilder->expr()->In('o.id', $limit_val));
                
            /******************/

            //$formView = $datagrid->getForm()->createView();
                            
            if (!empty($request->get('limit'))) $num_lead_label = $request->get('limit');
            else $num_lead_label = 'tutte le';
            
            if (!empty($request->get('filter')['type']['value'])){
                switch($request->get('filter')['type']['value']){
                    case "nol": 
                        $tipologia_estrazione_label = "noleggio";
                        break;
                    case "ven":
                        $tipologia_estrazione_label = "vendita";
                        break;
                    default: 
                        $tipologia_estrazione_label = "vendita e noleggio";
                        break;  
                }
            }
            else $tipologia_estrazione_label = "vendita e noleggio";
            
            $filter_data_label = array();
            foreach($request->get('filter') as $key => $val){
                
                if(((strpos($key,'_')== false) || strpos($key,'_') != 0) &&
                   (!empty($val['value'])) &&
                   ($key != 'type') &&
                   ($key != 'gia_estratte')&&
                   (($key != 'data_range') || (($key == 'data_range') && !(empty($val['value']['start']) && empty($val['value']['end'])))))
                {
                    
                    $value = $val['value'];
                    if($key == 'cliente') $value = $this->admin->cliente_list->getClientefromId($value);                   
                    if($key == 'campagna'){
                        if ($value == 0) continue;
                        else if(isset($value['brand'])) {
                            foreach ($value['brand'] as $arrId => $arrVal){
                                $value['brand'][$arrId] = $this->getBrandNamefromId($arrVal);                    
                            }
                        }
                    }
                                        
                    $filter_data_label[$key] = $value;
                    
                }
                
            }
            
            if(!empty($request->get('filter')['gia_estratte'])){
                switch($request->get('filter')['gia_estratte']['value']){
                    case "0": 
                        $tipologia_lead_label = "estraibili";
                        break;
                    case "1":
                        $tipologia_lead_label = "estratte";
                        break;
                    default: 
                        $tipologia_lead_label = "estraibili ed estratte";
                        break;  
                }
            }
            else $tipologia_lead_label = "estraibili ed estratte";
            
            //Invia la mail agli amministratori se l'utente non lo è
            if (!in_array('ROLE_ADMIN',$user->getRoles()) &&
                !in_array('ROLE_SUPER_ADMIN',$user->getRoles())){
                $message = \Swift_Message::newInstance()
                      ->setSubject('Notifica visualizzazione lista')
                      ->setFrom('send@example.com')
                      ->setTo($admin_mails)
                      ->setBody(
                          $this->renderView(
                              // app/Resources/views/Emails/list_displayed.html.twig
                              'Emails/list_displayed.html.twig',
                              array('username' =>  $username_user,
                                    'email' => $email_user,
                                    'tipologia_estrazione_label' => $tipologia_estrazione_label,
                                    'tipologia_lead_label' => $tipologia_lead_label,
                                    'filter_data_label' => $filter_data_label)
                          ),
                          'text/html'
                      );
                $this->get('mailer')->send($message);
            }
            
            $session->set('tipologia_estrazione_label', $tipologia_estrazione_label);
            $session->set('tipologia_lead_label', $tipologia_lead_label);
            $session->set('filter_data_label', $filter_data_label);

            return $this->render('list__action_exportcust.html.twig', array(
                'action' => 'list',
                'action_label' => $actionLabel,
                //'batch_translation_domain' => $batchTranslationDomain,
                'datagrid' => $datagrid,
                //'form' => $formView->createView(),
//                'data' => $data,
                'csrf_token' => $this->getCsrfToken('sonata.batch'),
                'num_lead_label' => $num_lead_label,
                'lead_limit' =>$lead_limit,
                'limit' => $num_lead,
                'phone_type' => $ext_type,
                'tipologia_estrazione_label' => $tipologia_estrazione_label,
                'tipologia_lead_label' => $tipologia_lead_label,
                'filter_data_label' => $filter_data_label,
                'referer' => $session->get('referer'),
                'checkb' => $this->nested_checkboxes,
                'campi_default' => $this->campi_default
            ), null);
        }
    }
    
    public function extractDoAction(Request $request){
        
        $date = new \DateTime();
        $date->modify('+90 days');
        $strDate = $date->format('d.m.Y');
        
        $formView = $this->createFormBuilder()
                        ->add('data_di_sblocco','sonata_type_date_picker',
                                array('dp_default_date' => $strDate,
                                      'format' => 'dd.MM.yyyy'))
                        ->add('cliente', ChoiceType::class, 
                           [ 
                            'multiple' => false, 
                            'required' => true,
                            'empty_data'  => null,
                            //'placeholder' => '-- Scegli un cliente --',
                            'choices' => $this->admin->cliente_list->getList(),
                            'attr' => [ 'style' => 'width: 400px;' ]
                          ])
        ->getForm();
             
        
        return $this->render('modal_leads_extract.html.twig', array(
            'action' => 'extractdo',
//            'action_label' => $actionLabel,
//            'batch_translation_domain' => $batchTranslationDomain,
//            'datagrid' => $datagrid,
            'form' => $formView->createView(),
//                'data' => $data,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
//            'num_lead_label' => $num_lead_label,
//            'lead_limit' =>$lead_limit,
//            'tipologia_estrazione_label' => $tipologia_estrazione_label,
//            'tipologia_lead_label' => $tipologia_lead_label,
//            'filter_data_label' => $filter_data_label

        ), null);       
        
    }
    
    public function exportDoAction(Request $request)
    {
            
        $user = $this->get('security.token_storage')->getToken()->getUser();
        //$this->denyAccessUnlessGranted('ROLE_ESTRAZIONE_LEAD', null, 'Attenzione! Non hai i permessi per effettuare l\'estrazione!');  
        if (!$this->isGranted('ROLE_ESTRAZIONE_LEAD', null)) {
            return $this->render('access_denied.html.twig');            
        } 
        
        $username_user = $user->getUsername();
        $email_user = $user->getEmail();
        $admin_mails = $this->getAdminMails();
        
        $session = $request->getSession();
        
        $tipologia_estrazione_label = $session->get('tipologia_estrazione_label');
        $tipologia_lead_label = $session->get('tipologia_lead_label');
        $filter_data_label = $session->get('filter_data_label');
        
        if (!in_array('ROLE_ADMIN',$user->getRoles()) &&
            !in_array('ROLE_SUPER_ADMIN',$user->getRoles())){
            $message = \Swift_Message::newInstance()
                  ->setSubject('Notifica Estrazione')
                  ->setFrom('send@example.com')
                  ->setTo($admin_mails)
                  ->setBody( 
                      $this->renderView(
                          // app/Resources/views/Emails/list_extracted.html.twig
                          'Emails/list_extracted.html.twig',
                           array('username' =>  $username_user,
                                 'email' => $email_user,
                                 'tipologia_estrazione_label' => $tipologia_estrazione_label,
                                 'tipologia_lead_label' => $tipologia_lead_label,
                                 'filter_data_label' => $filter_data_label,
                                 'num_lead_label' => count($session->get('arrIds'))
                               )
                      ),
                      'text/html'
                  );
            $this->get('mailer')->send($message);
        }
        
        $this->admin->checkAccess('export');

        $format = $request->get('format');
        //$form = $request->get('form');
        //$num_lead = $form['Numero_lead_da_estrarre']; 
        $campi_estrazione = $request->get('campi_export');
        $campi_estrazione[] = 'id';
        $limit = $request->get('num_lead');
        
        if(!empty($request->get('filter')['type']['value']))
            $tipoEstrazione = $request->get('filter')['type']['value'];
        else 
            $tipoEstrazione = '';
                
        $is_to_store = $request->get('_store_into_db');
        if(!empty($is_to_store)){
            $cliente = $request->get('_cliente');
            $unlock_date = $request->get('_data_di_sblocco');
        }
        else {
            $cliente = '';
            $unlock_date = '';
        }

        $allowedExportFormats = (array) $this->admin->getExportFormats();

        if (!in_array($format, $allowedExportFormats)) {
            throw new \RuntimeException(
                sprintf(
                    'Export in format `%s` is not allowed for class: `%s`. Allowed formats are: `%s`',
                    $format,
                    $this->admin->getClass(),
                    implode(', ', $allowedExportFormats)
                )
            );
        }
        
        $filename = sprintf(
            'export_%s_%s.%s',
            strtolower(substr($this->admin->getClass(), strripos($this->admin->getClass(), '\\') + 1)),
            date('Y_m_d_H_i_s', strtotime('now')),
            $format
        );
        
        //Debug::dump($this->admin->getDataSourceIterator());

        $ret = $this->getResponse(
            $format,
            $filename,
            $this->getDataSourceIterator($campi_estrazione, $limit, $session),
            $is_to_store,
            $cliente,
            $unlock_date,
            $tipoEstrazione);
        
        //Debug::dump($ret);
       
        return $ret;
        
        
    }

    public function getDataSourceIterator($campi_estrazione, $limit, $session)
    {
 
       $date_now = new \DateTime();
       
       $datagrid = $this->admin->getDatagrid();
       $datagrid->buildPager();
       $queryBuilder = $datagrid->getQuery();

        $queryBuilder->select('DISTINCT '.$queryBuilder->getRootAlias());
        $queryBuilder->setFirstResult(null);
        $queryBuilder->setMaxResults($limit);
        
        $limit_val = $session->get('arrIds');
        if ($session->get('is_noleggio') == 1){
           $queryBuilder->andWhere($queryBuilder->expr()->In('o.id', $limit_val));
           $queryBuilder->resetDQLPart('orderBy');
        }
        else $queryBuilder->addOrderBy($queryBuilder->getSortBy(), $queryBuilder->getSortOrder());
        
        //var_dump($queryBuilder->getDql());
        
        if ($queryBuilder instanceof ProxyQueryInterface) {
            //$queryBuilder->addOrderBy($queryBuilder->getSortBy(), $queryBuilder->getSortOrder());
            $queryBuilder->andWhere($queryBuilder->expr()->In('o.id', $limit_val));
           // if ($session->get('is_noleggio') == 0) $queryBuilder->andWhere('e.data_sblocco <= CURRENT_TIMESTAMP() OR e.data_sblocco IS NULL');
            $queryBuilder = $queryBuilder->getQuery();
            
        }
        
       return new DoctrineORMQuerySourceIterator($queryBuilder, $campi_estrazione);
    }
    
    public function getResponse($format, $filename, SourceIteratorInterface $source, $is_to_store, $cliente, $unlock_date, $tipoEstrazione)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $id_user = $user->getId();
        
        $ip_address = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
        
        switch ($format) {
            case 'xls':
                $writer = new XlsWriter('php://output');
                $contentType = 'application/vnd.ms-excel';
                break;
            case 'xml':
                $writer = new XmlWriter('php://output');
                $contentType = 'text/xml';
                break;
            case 'json':
                $writer = new JsonWriter('php://output');
                $contentType = 'application/json';
                break;
            case 'csv':
                $writer = new CsvWriter('php://output', ',', '"', '', true, true);
                $contentType = 'text/csv';
                break;
            default:
                throw new \RuntimeException('Invalid format');
        }

        $callback = function () use ($source, $writer, &$exportedId, $is_to_store, $cliente, $unlock_date, $tipoEstrazione, $ip_address, $id_user) {
            $handler = Handler::create($source, $writer);
            $exportedId = $handler->export();
            
            if(!empty($is_to_store)){

            switch ($tipoEstrazione) {
                
                case 'nol': 
                    $tipoEstrazione = 'noleggio';
                    break;
                case 'ven':
                    $tipoEstrazione = 'vendita';
                    break;
                case 'prn':
                    $tipoEstrazione = 'prenotazione';
                    break;
                
                default : 
                    $tipoEstrazione = '';
                    break;
            }
                //Facciamo la insert nel DB

                $em = $this->getDoctrine()->getManager();

                $batchSize = 20; //per operazioni di batch con doctrine
                $i = 1;
                foreach($exportedId as $key => $val){

                        $lead = $em->find('AppBundle:Lead_uni', $val);
                        $cliente = $em->find('AppBundle:Cliente', $cliente);

                        $date_now = new \DateTime("now");
                        //$unlock_date += " 00:00:00";
                        $date_unlock = \DateTime::createFromFormat('d.m.Y', $unlock_date);
                        $date_unlock->setTime(0, 0);
                        
                        $entry = $em->find('AppBundle:Extraction', $lead);
                        
                        //Inseriamo un nuovo record nella tabella di estrazione
                        if (empty($entry)){
                            
                            $extraction = new Extraction();
                            $extraction->setLead($lead);
                            $extraction->setCliente($cliente);
                            $extraction->setDataSblocco($date_unlock);
                            $extraction->setDataEstrazione($date_now);
                            $extraction->setTipoEstrazione($tipoEstrazione);

                            $em->persist($extraction);
                            //$em->flush();
                            
                        }//Aggiorniamo un record esistente
                        else {
                            
                            $entry->setCliente($cliente);
                            $entry->setDataSblocco($date_unlock);
                            $entry->setDataEstrazione($date_now);
                            
                            //$em->flush();
                        }
                        
                        //Inseriamo un nuovo record nella history
                        
                        $extraction_hist = new Extraction_history();
                        $extraction_hist->setLead($lead);
                        $extraction_hist->setCliente($cliente);
                        $extraction_hist->setDataSblocco($date_unlock);
                        $extraction_hist->setDataEstrazione($date_now);
                        $extraction_hist->setTipoEstrazione($tipoEstrazione);
                        $extraction_hist->setIndirizzoIp($ip_address);
                        $extraction_hist->setBackendUserId($id_user);

                        $em->persist($extraction_hist);
                        //$em->flush();
                        
                    if (($i % $batchSize) === 0) {
                        $em->flush();
                        $em->clear(); // Detaches all objects from Doctrine!
                    }    
                    
                    $i++;
       
                }
                
                $em->flush(); //Persist objects that did not make up an entire batch
                $em->clear();
                
            }
            
        };

        return new StreamedResponse($callback, 200, array(
            'Content-Type' => $contentType,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ));
    }
    
        
    public function getDatesAction(Request $request){
        
        //$query = $this->admin->getDatagrid()->getQuery();
        $datagrid = $this->admin->getDatagrid();
        $datagrid->buildPager();
        $queryBuilder = $datagrid->getQuery();
        
        // get var_dump($query->getDql());

        //$queryc = clone $query;
        $queryBuilder->select('o.data');
        $queryBuilder->andWhere('e.data_sblocco is null OR e.data_sblocco <= CURRENT_TIMESTAMP()');
        //$queryBuilder->addSelect('max(o.data)');
        if(!empty($request->get('limit'))) $queryBuilder->setMaxResults($request->get('limit'));
        else $queryBuilder->setMaxResults($request->get('max_lead'));
        $queryBuilder->add('orderBy', 'o.data DESC');
        

        $dates = $queryBuilder->getQuery()->getArrayResult();
        
        if(count($dates)==0) $date['res'] = 'ko';
        else {
            $date['res'] = 'ok';
            
            $date['max'] = $dates[0];
            $date['min'] = $dates[count($dates)-1];
        }
        
        $response = new Response();
        $response->setContent(json_encode($date));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
            
    }   
    
    public function getSettoriAction(Request $request){

        $qb= $this->getDoctrine()->getManager()->createQueryBuilder();
        //$repo = $em->getRepository('AppBundle:Campagna');
        //$items = $repo->findAll();
        
        $ret = array();
        $tmp = array();
//        $i = 0;
//        foreach($items as $item)
//        {
//            $label = $item->getSettore();
//            
//            if (!in_array($label, $tmp)){
//                $ret[$i]['label'] = $label;
//                $ret[$i]['value'] = $label;
//                $i++;
//                $tmp[] = $label;
//            }
//            
//        }
        
        $res =  $qb->select('DISTINCT c.settore')
                    ->from('AppBundle:Campagna', 'c')
                    ->where('c.settore is not null AND c.settore not like \'\'')
                    ->orderBy('c.settore', 'ASC')
                    ->getQuery()
                    ->getArrayResult();
        
        //var_dump($ret);  
        $res = array_column($res, 'settore');
        
        $i = 0;
        foreach($res as $k => $v)  {
            $label = $v;
            
            if (!in_array($label, $tmp)){
                $ret[$i]['label'] = $label;
                $ret[$i]['value'] = $label;
                $i++;
                $tmp[] = $label;
            }
            
        }
        

        $response = new Response();
        $response->setContent(json_encode($ret));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    }   
    
    public function getTipoCampagnaAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Campagna');
        if ($request->get('settore') == '0') {
            $items = $repo->findAll();
        }
        else $items = $repo->findBySettore($request->get('settore'));
        //var_dump($request);
        
        $ret = array();
        $tmp = array();
        $i = 0;
        foreach($items as $item)
        {
            $label = $item->getTipoCampagna();
            
            if (!in_array($label, $tmp)){
                $ret[$i]['label'] = $label;
                $ret[$i]['value'] = $label;
                $i++;
                $tmp[] = $label;
            }
            
        }

        $response = new Response();
        $response->setContent(json_encode($ret));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    }
    
    public function getBrandAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Campagna');
        if ($request->get('settore') == '0'){
            if (in_array(0, $request->get('tipocampagna')))
                $items = $repo->findAll();
            else 
                $items = $repo->findBy(array('tipo_campagna' => $request->get('tipocampagna')));  
        } else {
             $items = $repo->findBy(
            array('settore'       => $request->get('settore'),
                  'tipo_campagna' => $request->get('tipocampagna')));
        }
        //var_dump($request);
        
        $ret = array();
        $tmp = array();
        $i = 0;
        foreach($items as $item)
        {
            $label = $item->getBrandName();
            $value = $item->getBrandId();
            
            if (!in_array($label, $tmp)){
                $ret[$i]['label'] = $label;
                $ret[$i]['value'] = $value;
                $i++;
                $tmp[] = $label;
            }
            
        }

        $response = new Response();
        $response->setContent(json_encode($ret));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    } 
    
    public function getB2bb2cAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Campagna');
        $items = $repo->findBy(
            array('settore'       => $request->get('settore'),
                  'tipo_campagna' => $request->get('tipocampagna'),
                  'brand'         => $request->get('brand')));
        //var_dump($request);
        
        $ret = array();
        $tmp = array();
        $i = 0;
        foreach($items as $item)
        {
            $label = $item->getTargetCampagna();
            
            if (!in_array($label, $tmp)){
                $ret[$i]['label'] = $label;
                $ret[$i]['value'] = $label;
                $i++;
                $tmp[] = $label;
            }
            
        }

        $response = new Response();
        $response->setContent(json_encode($ret));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    } 
    
    public function getNomeOffertaAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Campagna');
        $items = $repo->findBy(
            array('settore'       => $request->get('settore'),
                  'tipo_campagna' => $request->get('tipocampagna'),
                  'brand'         => $request->get('brand'),
                  'target_campagna' => $request->get('b2bb2c')));
        //var_dump($request);
        
        $ret = array();
        $tmp = array();
        $i = 0;
        foreach($items as $item)
        {
            $label = $item->getNomeOfferta();
            
            if (!in_array($label, $tmp)){
                $ret[$i]['label'] = $label;
                $ret[$i]['value'] = $label;
                $i++;
                $tmp[] = $label;
            }
            
        }

        $response = new Response();
        $response->setContent(json_encode($ret));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
            
    }
    
    public function addClienteAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Cliente');
        
        $date_now = new \DateTime("now");
        
        $entry = $repo->findByName($request->get('cliente'));

        //Inseriamo un nuovo record nella tabella di estrazione
        if (empty($entry)){

            $cliente = new Cliente();
            
            //Generiamo un codice cliente univoco
            $code_cliente = $cliente->genClienteCode($request->get('cliente'));
            
            
            $cliente->setName($request->get('cliente'));
            $cliente->setCode($code_cliente);
            $cliente->setCreationDate($date_now);

            $em->persist($cliente);
            $em->flush();
            
            $return_val['id'] = $cliente->getId();

        }//Aggiorniamo un record esistente
        else {

            $entry[0]->setCreationDate($date_now);

            $em->flush();
            
            $return_val['id'] = $entry[0]->getId();
            
        }
        
        $response = new Response();
        $response->setContent(json_encode($return_val));

        $response->headers->set('Content-Type', 'application/json');

        return  $response;
   
    }
    
    public function warningleadsAction(){
        
        
             return $this->render('modal_leads_warning.html.twig', array(
 
                'csrf_token' => $this->getCsrfToken('sonata.batch'),

                
            ), null);        
        
    }  
    
    public function getAdminMails(){
        
        $admins = $this->findByRole('ROLE_ADMIN');  
        $admins = array_map(function($val){return $val['id'];}, $admins);
        
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:User');
        
        $admins = $repo->findById($admins);
           
        foreach($admins as $admin){
            //var_dump($admin);
            $admin_mails[] = $admin->getEmail();
            
        }
        
        return $admin_mails;
        
    }
    
    public function findByRole($role)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('u.id')
            ->from('AppBundle:User', 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getResult();
    }
    
    public function accessDeniedAction(Request $request){
        
        return $this->render('base.html.twig', array(
            'action' => 'access_denied',
//            'action_label' => $actionLabel,
//            'batch_translation_domain' => $batchTranslationDomain,
//            'datagrid' => $datagrid,
//            'form' => $formView->createView(),
//                'data' => $data,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
//            'num_lead_label' => $num_lead_label,
//            'lead_limit' =>$lead_limit,
//            'tipologia_estrazione_label' => $tipologia_estrazione_label,
//            'tipologia_lead_label' => $tipologia_lead_label,
//            'filter_data_label' => $filter_data_label

        ), null);       
        
    }
    
    public function listAction()
    {
        $request = $this->getRequest();

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if ($preResponse !== null) {
            return $preResponse;
        }

        if ($listMode = $request->get('_list_mode')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        //$this->setFormTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action' => 'list',
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ), null);
    }
    
    public function getBrandNamefromId($brand_id){
        
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Brand');
        $item = $repo->findById($brand_id);
        
        return $item[0]->getName();
        
        
    }
       
}