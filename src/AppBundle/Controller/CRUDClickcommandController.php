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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CRUDClickcommandController extends Controller
{
    
    
	 public function listaAction($message = null){
		$clickcommands = $this->getAllClickCommands();

		return $this->render('listclickcommand.html.twig', array(
           'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
           'clickcommands' 	=> $clickcommands,
           'message' 		=> $message,
        ), null); 
    }
	 
	 private function generateCopiaBtn($copiaTxt){
		$html = '<div class="overbox"><textarea class="txt_copy" style="width:1px; height: 1px; display:none;">' . (trim($copiaTxt)) .'</textarea><button class="click-copy btnover btn btn-default">Copia</button></div>';
		return $html;
	 }
	private function generatePixel($id_campagna,$parametri = '',$step='2'){
		$pixel = '';
		
		if(!empty($parametri)){
			$parametri = '&'.$parametri;
		}
		if(!empty($id_campagna)){
			$pixel = '<iframe src="https://tracking.linkappeal.it/trace/pixel.php?id_campagna='.strtoupper($id_campagna).
					'&id_step='.$step.'&code={TIMESTAMP}'.$parametri.'" style="border:0;" width="1" height="1"></iframe>';
				
		}
		return $pixel;
	}
	
	private function getAllClickCommands(){
		$sql = "SELECT * FROM redirect";
		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute();
		$_clickcommands =  $stmt->fetchAll();
		$clickcommands = array();
		
		foreach($_clickcommands as $clickcommand){
			$parametri  = '';
			$clickcommand['strpars'] 	= '';
			if(!empty($clickcommand['parametri'])){
				$_parametri = unserialize($clickcommand['parametri']);
				$parametri = json_encode($_parametri);
				foreach($_parametri as $key => $val){
				if(!empty($val)){
					$clickcommand['strpars'] .= $key .'='.$val . '&';
				}
				}
				$clickcommand['strpars'] = substr($clickcommand['strpars'],0,-1);
				$clickcommand['parametri'] = $parametri;
			}

			$clickcommand['attivo'] = '<span class="color-deactive">DISATTIVO</span>';
			if($clickcommand['stato']==1){
				$clickcommand['attivo'] = '<span class="color-active">ATTIVO</span>';
			}
			/** generazione del click command */
			
			if(empty(trim($clickcommand['rewrite_campagna']))){
				$clickcommand['rewrite_campagna'] = 'NESSUN REWRITE';
			}

			$clickcommand['cp_redirect'] = '';
			if(!isset($clickcommand['url_redirect']) || empty(trim($clickcommand['url_redirect']))){
				$clickcommand['url_redirect'] = 'DISATTIVO';
			}else{
				$clickcommand['url_redirect'] = trim($clickcommand['url_redirect']);
				$clickcommand['cp_redirect'] = $this->generateCopiaBtn($clickcommand['url_redirect']);
			}
			
			// genero il click command e pixel
			$clickcommand['click_command'] = $this->generateClickCommand($clickcommand['id_campagna'],$clickcommand['id_prodotto'], $clickcommand['rewrite_campagna'],$clickcommand['strpars']);
			$clickcommand['pixel'] = $this->generatePixel($clickcommand['id_campagna'], $clickcommand['strpars']);
			
			// selettori per la copia dei contenuti
			$clickcommand['cp_clickc'] = $this->generateCopiaBtn($clickcommand['click_command']);
			$clickcommand['px_clickc'] = $this->generateCopiaBtn($clickcommand['pixel']);
			$clickcommands[] = $clickcommand;
		}
		return $clickcommands;
	}
	
	private function generateClickCommand($id_campagna, $id_prodotto, $rewrite, $parametri){
		
		$id_prodotto_cmd = !empty($id_prodotto) ? "&id_prodotto=" . $id_prodotto : '';
		$clickcommand = 'https://tracking.linkappeal.it/trace/redirect.php?id_campagna='. $id_campagna . $id_prodotto_cmd;
		$query_concat = '&';
		if(!empty(trim($rewrite)) && $rewrite!='NESSUN REWRITE'){
			$clickcommand = 'https://tracking.linkappeal.it/r/'. $rewrite;
			$query_concat = '?';
		}
		// append dei parametri
		$clickcommand .= (!empty(trim($parametri))) ? $query_concat . $parametri : '';

		return trim($clickcommand);
	 }

   public function deleteclickcommandAction(Request $request){
		$em = $this->getDoctrine()->getManager('pixel_man');
		$idr = $request->get('id');
		$sql = "DELETE FROM redirect WHERE idr = ?";
		$stmt = $em->getConnection()->prepare($sql);
		$stmt->execute(array($idr));
		
		$response = new Response();
		$response->setContent(json_encode(array(
			'eliminato' 	=> true,
		)));

		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
        
    public function insertclickcommandAction(Request $request)
    {
		$id 				= $request->get('id');
		$id_campagna 		= $request->get('id_campagna');
		$id_prodotto		= $request->get('id_prodotto');
		$descrizione 		= $request->get('descrizione');
		$accorda_redirect	= $request->get('accorda_redirect'); $accorda_redirect	=	isset($accorda_redirect) 	? 1 : 0;
		$hide_redirect 		= $request->get('hide_redirect'); 	 $hide_redirect 	=	isset($hide_redirect) 		? 1 : 0;
		$attivo 			= $request->get('attivo');			 $attivo 			=	isset($attivo) 				? 1 : 0;
		$indiretta 			= $request->get('indiretta'); 		 $indiretta 		=	isset($indiretta) 			? 1 : 0;
		$url_redirect 		= $request->get('url_redirect');
		$media 				= $request->get('media');
		$parametri			= $request->get('parametri');
		$vars 				= array(
									'ind' 	=> $indiretta ,
									'media' => $media ,
									'q' 	=> $accorda_redirect ,
									);
		if(!empty($parametri)){
			$parametri = explode('&',$parametri);
			
			foreach($parametri as $parametro){
				list($par,$val) = explode('=',$parametro);
				$vars[$par] = $val;
			}
		}
		$parSerial = serialize($vars);
		
		/*---------------------------- funzioni sui dati --------------------- */
		$rewrite_campagna = '';
		if($hide_redirect){
			$rewrite_campagna = md5($id_campagna);
		}
		if(empty($id_prodotto)) { $id_prodotto = null; }
		$valori_insert = array($descrizione, $rewrite_campagna,$id_campagna,$id_prodotto,$url_redirect,$parSerial,$attivo);
		$action = $request->get('action');
		
		if($action=='genera'){
			$sql_insert = "INSERT INTO redirect (descrizione, rewrite_campagna, id_campagna, id_prodotto, url_redirect,parametri,stato) 
							VALUES(?,?,?,?,?,?,?);";
			$message = "Click Command per la campagna id: ".$id_campagna." generato correttamente!";
		}else{
			$sql_insert = "UPDATE redirect SET descrizione=?, rewrite_campagna=?, id_campagna=?, id_prodotto=?, url_redirect=?,parametri=?,stato=? 
							WHERE idr=?;";
			$valori_insert[] = $id;  // aggiungo l'id per l'update
			$message = "Campagna id: ".$id_campagna." modificata correttamente!";
		}

		$em = $this->getDoctrine()->getManager('pixel_man');
		$stmt = $em->getConnection()->prepare($sql_insert);
		$stmt->execute($valori_insert);
		
		$clickcommands = $this->getAllClickCommands();
		
		$redirect = $this->admin->generateUrl('lista',array('messaggio' => 'Click Command inserito correttamente'));
		
		$response = new RedirectResponse($redirect);
		
		return $response;
		//return $this->render('listclickcommand.html.twig', array(
        //   'csrf_token' 	=> $this->getCsrfToken('sonata.batch'),
        //   'clickcommands' 	=> $clickcommands,
        //   'message' 		=> $message,
        //), null);
	
		//return $this->listaAction('Click Command generato correttamente!');
		//return new RedirectResponse($this->container->get('router')->generate('lista', array('id' => $object->getId())));
		//return new RedirectResponse($this->container->get('router')->generate('lista'));
	}

}