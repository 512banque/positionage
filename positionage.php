<?php

class Serpscrap{

	public $kw;
	public $hl = 'fr';
	public $gl = 'fr';
	public $ext = 'fr';
	public $proxy = '';
	public $proxyauth = '';
	public $urltocheck = '';
	public $position = '';
	public $googleip = '';
	public $results;
	public $nbresults;
	public $httpcode;
	public $queryurl;
	public $end;

	function __construct($kw = '', $urltocheck = '', $proxy='') {
		$this->kw = $kw;
		$this->urltocheck = $urltocheck;
		if(!empty($proxy)) {
			$p = explode(':', $proxy);
			$this->proxy = $p[0].':'.$p[1];
			$this->proxyauth = $p[2].':'.$p[3];
		}
		$this->set100(str_replace(':','.',$this->proxy));
		$this->check();
		$this->getpos();
	}

	function check() {
		$url = 'https://www.google.'.$this->ext.'/search?hl='.$this->hl.'&gl='.$this->gl.'&q='.urlencode($this->kw).'&num=100&pws=0&filter=0&tbs=li:1&start=';
		
		$this->googleip = gethostbyname('www.google.'.$this->ext);

		$start = 0;
		$result = array();
		
		//cookie 100 results appelé précédemment
		$cookies_file = 'cookies'.str_replace(':','.',$this->proxy).".txt";
		
		while($start<=0) { //mettre 1000 ici pour tout scraper
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url.$start);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			// Activation de l'utilisation d'un serveur proxy
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
			
			// Définition de l'adresse du proxy
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
			
			$header = array();
			$header[] = "Accept-Charset: " . 'ISO-8859-1,utf-8;q=0.7,*;q=0.3';
			$header[] = "Connection: " . 'Keep-Alive';
			$header[] = "Accept: " . 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
			$header[] = "Accept-Language: " . 'fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4';
// 			$header[] = "Accept-Encoding: " . 'gzip,deflate,sdch';
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31');
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies_file);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies_file);
			$res = curl_exec($ch);
			
			$curlinfo = curl_getinfo($ch);
			if($curlinfo["http_code"]!="200")
				$this->end = $curlinfo["http_code"];
			
			curl_close($ch);

			$html = new DOMDocument();
			@$html->loadHTML($res);
			$xpath = new DOMXPath( $html );

			// nb results ******************************
			$nb = $xpath->evaluate("//div[@id='resultStats']");
			$nb = preg_replace('/\(.+\)/s', '', $nb->item(0)->nodeValue); // on supprime le temps de chargement
			$nb = preg_replace('/[^0-9]/s', '', $nb); // on garde que les chiffres
			//$nb = 0;

			// results
			$num = $xpath->evaluate("count(//h3[@class='r']/a/@href)");
			if($num==0) break;
		

			$nodelist = $xpath->query("//h3[@class='r']/a/@href");
			foreach ($nodelist as $n) {
				$result[]=$n->value;
			}
			$start = $start + 100;
			//sleep(rand(1,4)); 
		}
	$result = array_unique($result);
	$this->results = $result;
	$this->httpcode = $curlinfo["http_code"];
	$this->nbresults = $nb;
	$this->queryurl = $url;
	return array('httpcode'=>$this->httpcode,'nbresults'=>$this->nbresults,'results'=>$this->results);
	}
	
	function set100($cookiename = 'cookie') {

		$url = 'https://www.google.fr/preferences?hl=fr';
		$useragent = "Mozilla/5.0";
		$referer = $url;
		
		
		//Initialise une session CURL
		$ch = curl_init($url);
		//cookies
		$cookies_file = WWW_ROOT.'cookies'.DS.$cookiename.'.txt';
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies_file);
		//fin cookies
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Activation de l'utilisation d'un serveur proxy
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
			
			// Définition de l'adresse du proxy
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$html = new DOMDocument();
		@$html->loadHTML($result);
		$xpath = new DOMXPath( $html );
		
		$nodelist = $xpath->query('//*[@id="ssform"]//input[@name="sig"]/@value');
		@$result = $nodelist->item(0)->nodeValue;

		$referer = $url;

		$url = 'https://www.google.fr/setprefs';
		
		$postfields=array();
		$postfields['sig']=$result;
		$postfields['submit2']="Enregistrer les préférences";
		$postfields['hl']="fr";
		$postfields['lr']="lang_fr";
		$postfields['uulo']="0";
		$postfields['muul']="4_20";
		$postfields['luul']="paris, france";
		$postfields['safeui']="off";
		$postfields['suggon']="2";
		$postfields['num']="100";
		$postfields['newwindow']="0";
		$postfields['q']="";
		$postfields['prev']="";
		
		
		
		//Initialise une session CURL
		$ch = curl_init($url.'?'.http_build_query($postfields));
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies_file);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Activation de l'utilisation d'un serveur proxy
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
			
			// Définition de l'adresse du proxy
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
		$result = curl_exec($ch);
		curl_close($ch);
		
		
	}

	function getpos() {
		$this->position = '>100';
		foreach($this->results as $key=>$value) {
			if(strpos($value, $_GET['url'])) {
				$this->position = $key+1; break(1);
			}
		}
		return (empty($this->position)) ? '>100' : $this->position;
	}

}


// fin class, début travail concret
if(!empty($_GET['deletecookies'])) {
	// unset cookies
	if (isset($_SERVER['HTTP_COOKIE'])) {
	    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	    foreach($cookies as $cookie) {
	        $parts = explode('=', $cookie);
	        $name = trim($parts[0]);
	        setcookie($name, '', time()-1000);
	        setcookie($name, '', time()-1000, '/');
	    }
	}
	header("Location: http://".$_SERVER['HTTP_HOST'] .  $_SERVER['SCRIPT_NAME']);
}

if(!empty($_GET['keyword'])&&!empty($_GET['url'])) {
	$sp = new Serpscrap($_GET['keyword'], $_GET['url']);
	// cookies
	$tenyears = time()+3600*24*365*10;
	setcookie('searches['.md5(serialize($_GET)).']', serialize($_GET), $tenyears);
	setcookie('time['.time().']', md5(serialize($_GET)), $tenyears);

	//render
	echo $sp->position;
}

?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>Positionage</title>
</head>
<body>
	<form action="" method="get">
		Mot-clé : <input type="text" name="keyword"><br />
		Url : <input type="text" name="url"><br />
		Debug ? <input type="checkbox" name="debug" value="true"><br />
		<button type="submit">Lancer la recherche</button>
	</form>
	<?php if(!empty($_GET['debug'])) {
			echo '<pre>';
			print_r($sp);
			echo '</pre>';
		} ?>
	<ul>
	<?php
// ordonner les recherches par desc
	$time = array_reverse($_COOKIE['time']);
	$time = array_unique($time);


	if(!empty($_GET['keyword'])&&!empty($_GET['url'])) echo '<li><strong><a href="?keyword='.$_GET['keyword'].'&url='.$_GET['url'].'">'.$_GET['keyword'].' => '.$_GET['url'].'</a></strong></li>';
if(isset($_SERVER['HTTP_COOKIE'])):
foreach($time as $k) {
	$render = unserialize($_COOKIE['searches'][$k]);
	echo '<li><a href="?keyword='.$render['keyword'].'&url='.$render['url'].'">'.$render['keyword'].' => '.$render['url'].'</a></li>';
}
endif;
?>
</ul>
	<a href="?deletecookies=1">Supprimer les cookies</a>
</body>
</html>
