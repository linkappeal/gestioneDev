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

use AppBundle\CustomFunc\Handler as Handler;
use AppBundle\Entity\Extraction as Extraction;
use AppBundle\Entity\Extraction_history as Extraction_history;
use AppBundle\Entity\Campagna as Campagna;
use AppBundle\Entity\Cliente as Cliente;
use AppBundle\Entity\Pixels as Pixels;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
//use AppBundle\CustomFunc\Pager as Pager;

class CRUDPixelManagerController extends Controller
{
    
    
    public function pixelmanagerAction(){
        
            $pixels = $this->getDoctrine()
            ->getRepository('AppBundle:Pixels', 'pixel_man')
            ->findAll();

            return $this->render('listpixel.html.twig', array(
                'action' => 'pixelmanager',
                'csrf_token' => $this->getCsrfToken('sonata.batch'),
                'pixels' => $pixels,

            ), null); 
    }
        

    
    public function addpixelAction(Request $request)
    {
		// inizializzo le variabili della prima pagina
        return $this->render('newpixel.html.twig', [
			'inserito' => false,
        ]);
    }
    
    public function deletepixelAction(Request $request)
	{
		$pixelid = $request->get('pixelid');
		$em = $this->getDoctrine()->getManager('pixel_man');
		$pixel = $em->getRepository('AppBundle:Pixels', 'pixel_man')->find($pixelid);

		if ($pixel) {
			$em->remove($pixel);
			$em->flush();
			$response = new Response();
			$response->setContent(json_encode(array(
				'eliminato' 	=> true,
			)));
		}
		
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
        
    public function insertpixelAction(Request $request)
    {
		$pixel = new Pixels();

		/* genero l'id della campagna */
		$url = $request->get('dominio');
		$pixel->setUrl($url)->generateIdCampagna();
		$id_campagna = $pixel->getIdCampagna();

		/*salvo il cpl nell'oggetto Pixel */
		$media = $request->get('cplnumber');
		$pixel->setCplNumber($media);

		/* ripulisco il codice del pixel */
		$pixelcode = $request->get('pixel');
		$pixel->setPixel($pixelcode);
		$codicePixel = $pixel->getPixel();
		
		/* setto la descizione */
		$descrizione = $request->get('descrizione');
		$pixel->setDescrizione($descrizione);
		
		/* recupero il falsemedia */
		$falsemedia = $pixel->parseFalseMedia();
		
		/* recupero il payout */
		$payout_code = $request->get('payout_code');
		if(empty($payout_code)){$payout_code=null;}
		$pixel->setPayoutCode($payout_code);
		
		/* attivo il pixel */
		$pixel->setAttivo('1');
		
		/* data creazione pixel*/
		$datenow = new \DateTime("now");
		$pixel->setDataCreazione($datenow);
		
		// se il pixel esiste, aggiorno
		$_pixel = $this->checkExists($id_campagna,$media,$payout_code);
		if($_pixel){
			$_pixel->setCplNumber($media);
			$_pixel->setPixel($pixelcode);
			$_pixel->setDescrizione($descrizione);
			$_pixel->setPayoutCode($payout_code);
			$_pixel->setIdCampagna($id_campagna);
			$pixel = $_pixel;
		}
		
		$em = $this->getDoctrine()->getManager('pixel_man');
		$em->persist($pixel);
		$em->flush();
	
		return $this->render('newpixel.html.twig', [
            'dominio' => $request->get('dominio'),
            'id_campagna' => $id_campagna,
            'pixelcode' => $codicePixel,
            'falsemedia' => $falsemedia,
            'media' => $pixel->getCplNumber(),
            'payoutcode' => $pixel->getPayoutCode(),
            'descrizione' => $pixel->getDescrizione(),
			'inserito' => true,
			//realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
	}
	
	
	private function checkExists($idcampagna,$media,$payout_code){
		$pixels = $this->getDoctrine()->getRepository('AppBundle:Pixels', 'pixel_man');
		$_pixel = $pixels->findOneBy(array('idCampagna'=> $idcampagna,'cplNumber' => $media,'payout_code' => $payout_code,));
		if($_pixel){ 
			return $_pixel; 	
		}else{
			return false;
		}
	}
	

	public function checkpixelAction(Request $request)
	{
		$pixel = new Pixels();
		/* genero l'id della campagna */
		$url = $request->get('dominio');
		$pixel->setUrl($url)->generateIdCampagna();
		$id_campagna = $pixel->getIdCampagna();
		/*salvo il cpl nell'oggetto Pixel */
		$media = $request->get('cplnumber');
	
		$payout_code = $request->get('payout_code');
		if(empty($payout_code)){$payout_code=null;}
		$_pixel = $this->checkExists($id_campagna,$media,$payout_code);
		
		// trovato già un pixel 
		$presente = false;
		if($_pixel){ $presente = true; 	}
		$response = new Response();
		$response->setContent(json_encode(array(
			'presente' 	=> $presente,
		)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	 
	public function editpixelAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager('pixel_man');
		$pixelid = $request->get('pixelid');
		$pixel = $this->getDoctrine()
				->getRepository('AppBundle:Pixels', 'pixel_man')
				->find($pixelid);

		if (!$pixel) {
			throw $this->createNotFoundException(
				'Nessun Pixel trovato con id '.$pixelid
			);
		}
		$aggiornato=false;
		$action = $request->get('azione');
		if(isset($action) && $action=='update'){
		
			$idcampagna = $request->get('idcampagna');
			$pixel->setIdCampagna($idcampagna);
			
			$media = $request->get('cplnumber');
			$pixel->setCplNumber($media);
			
			/* ripulisco il codice del pixel */
			$pixelcode = $request->get('pixel');
			$pixel->setPixel($pixelcode);
			
			/* setto la descizione */
			$descrizione = $request->get('descrizione');
			$pixel->setDescrizione($descrizione);
			
			/* recupero il falsemedia */
			$codebase = $request->get('codebase');
			$pixel->setCodebase($codebase);
			
			/* recupero il payout */
			$payout_code = $request->get('payout_code');
			if(empty($payout_code)){$payout_code=null;}
			$pixel->setPayoutCode($payout_code);
		
			/* attivo il pixel */
			$attivo = $request->get('attivo');
			if(isset($attivo)){$attivo=1;}else{$attivo = 0;}
			$pixel->setAttivo($attivo);
			$em->flush();
			$aggiornato=true;
		}
		

		return $this->render('editpixel.html.twig', [
            'pixel' => $pixel,
			'aggiornato' => $aggiornato
		]);
	}        
 
       
}