<?php
namespace AppBundle\Controller;


class landingScreenshot{
	public $img_link = '';
	public $img = '';
	public $siteURL = '';
	
	public function __construct($siteURL){
		$this->siteURL = $siteURL;
		return $this;
	}
	
	public function scatta(){
		if(!empty($this->siteURL)){
			$this->sanitizeUrl();
			$siteURL = $this->siteURL;
			//print_r($siteURL);
			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);  

			$googlePagespeedData = file_get_contents("https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$siteURL&screenshot=true", false, stream_context_create($arrContextOptions));
			$googlePagespeedData = json_decode($googlePagespeedData, true);
			$screenshot = $googlePagespeedData['screenshot']['data'];
			$screenshot = str_replace(array('_','-'),array('/','+'),$screenshot); 
			$img = "<img src=\"data:image/jpeg;base64,".$screenshot."\" />";
			$this->img = $img;
			return $this;
		}
	}
	
	public function sanitizeUrl(){
		$url = $this->siteURL;
		$url = rtrim($url,"/");
		if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
			$url = "http://" . $url;
		}
		$this->siteURL = $url;
		return $this;
	}
	
	public function getImg(){
		return $this->img;
	}
}
?>