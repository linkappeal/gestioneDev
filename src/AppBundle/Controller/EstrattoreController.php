<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pixels;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EstrattoreController extends Controller
{
     /**
     * @Route("/insert", name="RunPixel") 
     */
	  public function insertAction(Request $request)
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
		
		/* attivo il pixel */
		$pixel->setAttivo('1');
		
		// se il pixel esiste, aggiorno
		$_pixel = $this->checkExists($id_campagna,$media);
		if($_pixel){
			$_pixel->setCplNumber($media);
			$_pixel->setPixel($pixelcode);
			$_pixel->setDescrizione($descrizione);
			$_pixel->setIdCampagna($id_campagna);
			$pixel = $_pixel;
		}
		
		$em = $this->getDoctrine()->getManager();
		$em->persist($pixel);
		$em->flush();
	
		return $this->render('pixel.html.twig', [
            'dominio' => $request->get('dominio'),
            'id_campagna' => $id_campagna,
            'pixelcode' => $codicePixel,
            'falsemedia' => $falsemedia,
            'media' => $pixel->getCplNumber(),
            'descrizione' => $pixel->getDescrizione(),
			'inserito' => true,
			//realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
	}
	
	
	private function checkExists($idcampagna,$media){
		$pixels = $this->getDoctrine()->getRepository('AppBundle:Pixels', 'pixel_man');
		$_pixel = $pixels->findOneBy(array('idCampagna'=> $idcampagna,'cplNumber' => $media,));
		if($_pixel){ 
			return $_pixel; 	
		}else{
			return false;
		}
	}
	
	/**
	*
	* @Route("/check", name="checkPixels")
	*/
	public function checkAction(Request $request)
	{
		$pixel = new Pixels();
		/* genero l'id della campagna */
		$url = $request->get('dominio');
		$pixel->setUrl($url)->generateIdCampagna();
		$id_campagna = $pixel->getIdCampagna();

		/*salvo il cpl nell'oggetto Pixel */
		$media = $request->get('cplnumber');

		$_pixel = $this->checkExists($id_campagna,$media);
		
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
	
	 /**
     *
     * @Route("/list", name="listaPixels")
     */
	 
	public function listAction()
	{
		$pixels = $this->getDoctrine()
			->getRepository('AppBundle:Pixels', 'pixel_man')
			->findAll();

		if (!$pixels) {
			throw $this->createNotFoundException(
				'Nessun Pixel trovato '
			);
		}

		return $this->render('list.html.twig', [
            'pixels' => $pixels,
		]);
	}
	
	 /**
	 * Rendering della pagina di modifica
     *
     * @Route("/edit/{pixelid}", name="EditPixel")
     */
	 
	public function editAction(Request $request, $pixelid)
	{
		$em = $this->getDoctrine()->getManager();
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
			
			/* attivo il pixel */
			$attivo = $request->get('attivo');
			if(isset($attivo)){$attivo=1;}else{$attivo = 0;}
			$pixel->setAttivo($attivo);
			$em->flush();
			$aggiornato=true;
		}
		

		return $this->render('edit.html.twig', [
            'pixel' => $pixel,
			'aggiornato' => $aggiornato
		]);
	}
	
	/**
	 * Update del pixel
     *
     * @Route("/delete/", name="DeletePixel")
     */
	 
	public function deleteAction(Request $request)
	{
		$pixelid = $request->get('pixelid');
		$em = $this->getDoctrine()->getManager();
		$pixel = $em->getRepository('AppBundle:Pixels', 'pixel_man')->find($pixelid);

		if (!$pixel) {
			throw $this->createNotFoundException(
				'Nessun Pixel trovato con id: '.$pixelid
			);
		}
		$em->remove($pixel);
		$em->flush();
		
		$response = new Response();
		$response->setContent(json_encode(array(
			'eliminato' 	=> true,
		)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
}



