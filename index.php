<?php

define(APPSTORE_HOSTNAME, 'itunes.apple.com');


$url = empty($_GET['url']) ? $argv[1] : $_GET['url'];


$url = empty($_GET['url']) ? $argv[1] : $_GET['url'];
$js = "javascript:var url = encodeURI(document.URL);location.href = 'http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']."?url=' + url";


//
$ua = 'iTunes/9.0.2 (Macintosh; Intel Mac OS X 10.5.8) AppleWebKit/531.21.8';
$header = array("X-Apple-Store-Front: 143441-1");


if($ch = curl_init()) {
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	
	$res = curl_exec($ch);
	curl_close($ch);
}

$links = extractLink($res);


if(!is_null($links)) {
	$links = array_unique($links);
	foreach($links as $link) {
		if(iOSApp::isApp($link)) {
	
			$iosapp = new iOSApp();
			$iosapp->initByUrl($link);
			
			echo '<h1>'.$iosapp->appname.'</h1>';
			echo '<img src="'.$iosapp->artwork.'"/>';
			echo '<img src='."\"http://chart.apis.google.com/chart?cht=qr&chs=175x175&chl=".urlencode($link)."\"".'/>';
			echo '<br />';
		}
	}
}
else {
	echo 'nothing apps!!';
}





class iOSApp {
	public $id;
	public $artwork;
	public $appname;
	public $itunesHttp;
	public $itunesItms;
	private $url;


	public static function isAppStoreUrl($url) {
		if(preg_match('#'.APPSTORE_HOSTNAME.'#', $url)) {
			return true;
		}
		return false;
	}
	
	
	public static function isApp($url) {
		return preg_match('#'.APPSTORE_HOSTNAME.'.+app.+id\d+.+#', $url);
	}


	public static function isArtist($url) {
		return preg_match('#'.APPSTORE_HOSTNAME.'.+artist.+#', $url);
	}


	
	public function initById($id, $lang='jp') {
		$_ = 'itunes.apple.com/'.$lang.'/app/id'.$id;;
		$this->itunesHttp = 'http://'.$_;
		$this->itunesItms = 'itms://'.$_;
		$this->id = $id;
		$this->_init();
	}
	

	public function initByUrl($url) {
		$this->url = $url;
	
		if(!preg_match('#^http://itunes.apple.com#', $url)) {
			if(($url = $this->_squeeze($url)) === FALSE) {
				die("incorrect URL : {$url}");
			}
		}
		
		if(preg_match('#/id(\d+)#', $url, $matches)) {
			$this->id = $matches[1];
		}

		if(preg_match('#itunes.apple.com/(.+?)/#', $url, $matches)) {
			$this->lang = $matches[1];
		}

		$this->itunesHttp = 'http://itunes.apple.com/'.$this->lang.'/app/id'.$this->id;
		$this->itunesItms = str_replace('http://', 'itms://', $url);

		$this->_init();
	}


	private function _squeeze($link) {
		if(preg_match('#'.APPSTORE_HOSTNAME.'#', $link)) {
			// パラメタ指定で含まれる場合はURLだけを抽出
			if(preg_match('#.*=(.+?'.APPSTORE_HOSTNAME.'.+)#', $link, $matches)) {

				$url = urldecode($matches[1]);
				while(strstr($url, '%')) {
					$url = urldecode($url);
				}
				
				return $url;
			}
		}
		return false;
	}





	
	private function _getApplicationHtml() {
		$ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:5.0.1) Gecko/20100101 Firefox/5.0.1';
		if($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $this->itunesHttp);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
			
			$res = curl_exec($ch);
			curl_close($ch);
		}
		
		return $res;
	}
	
	
	private function _init() {
		$res = $this->_getApplicationHtml();

		// artwork
		if(preg_match_all('#<img[^>]+?class="artwork"[^>]+?src="(.+?)"[^>]+?>#', $res, $matches)) {
			foreach($matches[1] as $image) {
				if(preg_match('#175x175#', $image)) {
					$this->artwork = $image;
				}
			}
		}
		
		// application title
		if(preg_match('#<h1>(.+?)</h1>#', $res, $matches)) {
			$this->appname = $matches[1];
		}
		
		// devleoper
		if(preg_match('#<h2>(.+?)</h2>#', $res, $matches)) {
			$this->developer = $matches[1];
		}
	}
}










class AppStore {
	public $id;
	public $artwork;
	public $appname;
	public $itunesHttp;
	public $itunesItms;

	function __construct($id, $lang = 'jp') {
		$_ = 'itunes.apple.com/'.$lang.'/app/id'.$id;;
		$this->itunesHttp = 'http://'.$_;
		$this->itunesItms = 'itms://'.$_;

		$this->id = $id;
		
		$this->_init();
	}
	
	private function _getApplicationHtml() {
		$ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:5.0.1) Gecko/20100101 Firefox/5.0.1';
		if($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $this->itunesHttp);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
//			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
			
			$res = curl_exec($ch);
			curl_close($ch);
			
		}
		
		return $res;
	}
	
	
	private function _init() {
		$res = $this->_getApplicationHtml();

		// artwork
		if(preg_match_all('#<img[^>]+?class="artwork"[^>]+?src="(.+?)"[^>]+?>#', $res, $matches)) {
			foreach($matches[1] as $image) {
				if(preg_match('#175x175#', $image)) {
					$this->artwork = $image;
				}
			}
		}
		
		// application title
		if(preg_match('#<h1>(.+?)</h1>#', $res, $matches)) {
			$this->appname = $matches[1];
		}
		
		// devleoper
		if(preg_match('#<h2>(.+?)</h2>#', $res, $matches)) {
			$this->developer = $matches[1];
		}
	}
}









function squeeze($links = array()) {
	if(count($links) === 0){
		return null;
	}

	$_ = array();
	foreach($links as $link) {
		if(preg_match('#'.APPSTORE_HOSTNAME.'#', $link)) {
			// パラメタ指定で含まれる場合はURLだけを抽出
			if(preg_match('#.*=(.+?'.APPSTORE_HOSTNAME.'.+)#', $link, $matches)) {
				$url = urldecode($matches[1]);
				while(strstr($url, '%')) {
					$url = urldecode($url);
				}
				$_[] = $url;
			}
			else {
				$_[] = $link;
			}
		}
	}
	return $_;
}




// リンクを抽出
function extractLink($str) {
	if(preg_match_all('#<a[^>]+?href="(.+?)"[^>]+?>#', $str, $matches)) {
		return $matches[1];
	}
	return null;
}



echo '<br /><a href="'.$js.'">AppQR</a><br />';



?>