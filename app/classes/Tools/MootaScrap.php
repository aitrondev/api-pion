<?php

namespace Tools;

use Illuminate\Database\Capsule\Manager as DB;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use \Symfony\Component\BrowserKit\CookieJar;
use \Symfony\Component\BrowserKit\Cookie;

	class MootaScrap{
		public $client;
		public $guzzleClient;
		public $crawler;
		public $cookies = null;
		public $headers = [];
		public $isLogin = true;

		function initCookies($cookies){
			$this->cookies = $cookies;
		}

		function init(){
			if( !is_null($this->cookies) ){
				$cookieJar = unserialize( $this->cookies );
				$this->client = new \Goutte\Client([],null,$cookieJar);
			}else $this->client = new \Goutte\Client();

			$this->guzzleClient = new \GuzzleHttp\Client(array(
		        'timeout' => 60,
		        'curl' => array( CURLOPT_SSL_VERIFYPEER => false)
		    ));
		}

		function setHeaders($h){
			$this->headers = $h;
		}

		function req($url,$method = 'GET'){
		    $this->client->setClient($this->guzzleClient);
				if( count($this->headers) > 0  ){
					$this->crawler = $this->client->request($method, $url,[],[],$this->headers);
				}else $this->crawler = $this->client->request($method, $url);

				#cek apa sudah login
				/*$this->crawler->filter('input')->each(function($n){
					if($n->attr('name') == "email" && $n->attr('type') == "email"){
						$f=DB::Select("	select id,is_login from  moota_forgotpass_history
														where date_format(created_at,'%Y-%m-%d') = '".date('Y-m-d')."' ");

						if( $f[0]->is_login == "yes"  )
								#update to waiting
								DB::statement("update moota_forgotpass_history set is_login = 'waiting' where id=" . $f[0]->id);
						$this->isLogin = false;
					}
				});*/

				return $this;
		}
	}
?>
