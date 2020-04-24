<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Tools\MootaAPI;
	use Tools\Helper;
	use Tools\MootaScrap;
	use Tools\MootaURL;
	use Models\AutobankBank;


	use Models\MootaGmail;

	class MootaBankController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function detailBank(Request $req, Response $res){
				$bankKode = $req->getAttribute("bank_kode");
				$gmail = MootaGmail::where("is_active","yes")->first();
				new MootaAPI($res,$gmail->apikey_moota,"DETAIL_BANK",['bank_kode'=>$bankKode]);
		}

		public static function addBank(Request $req, Response $res){
				$post = $req->getParsedBody();

				$g = MootaGmail::where("email",$h->email)->first();
				$cookieJar = file_get_contents('/home/ordersol/admin.order-solution.com/assets/tmp/cookies.dat');
				$scrap = new MootaScrap();
				$scrap->initCookies($cookieJar);
				$scrap->init();

				$url = "https://app.moota.co/bank/create";
				$r = $scrap->req($url);
				if( !$r->isLogin ) return Res::cb($res,false,"Mohon maaf, saat ini server sedang sibuk, silahkan ulangi 2 menit lagi",[]);

				$cookies = self::getCookies(serialize($r->client->getCookieJar()));
				//echo "<pre>"; var_dump($r->client->getResponse()->getHeaders());
				$token =  $r->crawler->filter("meta[name='csrf-token']")->eq(0)->attr('content');
				# request lewat cURL
				#respon v
				/*
				{
					"error": false,
					"message": "",
					"data": "https://app.moota.co/bank/2EpWoOYdWJd/edit?tab=notification"
				}
				*/
				$headers = [
						'Content-Type: application/x-www-form-urlencoded',
						'X-CSRF-TOKEN: ' . $token,
						'Accept: application/json, text/plain, */*',
						'X-Requested-With: XMLHttpRequest',
						'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.84 Safari/537.36',
						'Cookie: ' . $cookies,
				];

				$p =  http_build_query($post);

				$reqAddBank = self::cURL($headers,"https://app.moota.co/bank",$p);
				//var_dump($reqAddBank);die();
				if( isset($reqAddBank["error"]) && !$reqAddBank["error"] ){
						$bankKode = self::getBankId($reqAddBank['data']);
						#save Bank
						$b = new AutobankBank;
						$b->user_id = $post["user_id"];
						$b->kode_bank = $bankKode;
						if( $b->save() ){
								return Res::cb($res,true,"Bank berhasil tersimpan",[]);
						}
				}

				return Res::cb($res,false,"Bank gagal tersimpan",[]);

		}

		public static function getBankId($res){
				//https://app.moota.co/bank/2EpWoOYdWJd/edit?tab=notification
				$s = explode("/",$res);
				return $s[4];
		}

		public static function editBank(Request $req, Response $res){
				$post = $req->getParsedBody();

				$g = MootaGmail::where("email",$h->email)->first();
				$cookieJar = file_get_contents('/home/ordersol/admin.order-solution.com/assets/tmp/cookies.dat');
				$scrap = new MootaScrap();
				$scrap->initCookies($cookieJar);
				$scrap->init();

				$url = "https://app.moota.co/bank/".$post['bank_kode']."/edit";
				$r = $scrap->req($url);
				if( !$r->isLogin ) return Res::cb($res,false,"Mohon maaf, saat ini server sedang sibuk, silahkan ulangi 2 menit lagi",[]);

				$cookies = self::getCookies(serialize($r->client->getCookieJar()));
				$token =  $r->crawler->filter("meta[name='csrf-token']")->eq(0)->attr('content');
				# request lewat cURL
				#respon v
				/*
				{
					"error": false,
					"message": "",
					"data": "https://app.moota.co/bank/2EpWoOYdWJd/edit?tab=notification"
				}
				*/
				$headers = [
						'Content-Type: application/x-www-form-urlencoded',
						'X-CSRF-TOKEN: ' . $token,
						'Accept: application/json, text/plain, */*',
						'X-Requested-With: XMLHttpRequest',
						'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.84 Safari/537.36',
						'Cookie: ' . $cookies,
				];
				$post['_token'] = $token;
				$post['_method'] = 'PUT';
				$p =  http_build_query($post);
				$reqEditBank = self::cURL($headers,"https://app.moota.co/bank/".$post['bank_kode'],$p);
				if( is_null($reqEditBank) ){
						return Res::cb($res,true,"Bank berhasil diedit",[]);
				}

				return Res::cb($res,false,"Bank gagal diedit",[]);

		}

		public static function delBank(Request $req, Response $res){
				$bankKode = $req->getAttribute("kode");

				$g = MootaGmail::where("email",$h->email)->first();
				$cookieJar = file_get_contents('/home/ordersol/admin.order-solution.com/assets/tmp/cookies.dat');
				$scrap = new MootaScrap();
				$scrap->initCookies($cookieJar);
				$scrap->init();

				$url = "https://app.moota.co/bank/".$bankKode."/edit";
				$r = $scrap->req($url);
				if( !$r->isLogin ) return Res::cb($res,false,"Mohon maaf, saat ini server sedang sibuk, silahkan ulangi 2 menit lagi",[]);

				$cookies = self::getCookies(serialize($r->client->getCookieJar()));
				$token =  $r->crawler->filter("meta[name='csrf-token']")->eq(0)->attr('content');
				# request lewat cURL
				#respon v
				/*
				{
					"error": false,
					"message": "",
					"data": "https://app.moota.co/bank/2EpWoOYdWJd/edit?tab=notification"
				}
				*/
				$headers = [
						'Content-Type: application/x-www-form-urlencoded',
						'X-CSRF-TOKEN: ' . $token,
						'Accept: application/json, text/plain, */*',
						'X-Requested-With: XMLHttpRequest',
						'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.84 Safari/537.36',
						'Cookie: ' . $cookies,
				];
				$post = [];
				$post['_token'] = $token;
				$post['_method'] = 'delete';
				$p =  http_build_query($post);
				$delBank = self::cURL($headers,"https://app.moota.co/bank/".$bankKode,$p);
				if( is_null($delBank) ){
						$a = AutobankBank::where("kode_bank",$bankKode);
						$a->delete();
						 
						return Res::cb($res,true,"Bank berhasil di hapus",[]);
				}

				return Res::cb($res,false,"Bank gagal dihapus",[]);

		}



		public static function cURL($headers,$url,$fields) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
			$result = curl_exec($ch);
			curl_close($ch);

			return json_decode($result, true);
		}

		static function getCookies($arr){
					//$arr = file_get_contents('/var/www/html/autobank/cookies.dat');
					$cfduid = self::extractCookies( str_split($arr),"__cfduid");
					$csrftoken = self::extractCookies( str_split($arr),"XSRF-TOKEN");
					$mostoken = self::extractCookies( str_split($arr),"mos_token");
					$mootaSession = self::extractCookies( str_split($arr),"moota_session");

					return "__cfduid=".$cfduid."; " . "XSRF-TOKEN=".$csrftoken."; " . "moota_session=".$mootaSession."; " . "mos_token=".$mostoken."; ";
			}

		static function extractContent($index,$arr){
					$char = "";$find = false;
					for($i=$index;$i<count($arr);$i++){
							if( $arr[$i] == '"' ){
									while(true){
											$i++;
											if( $arr[$i] == '"'){$find=true;break;}
											$char .= $arr[$i];
									}
							}
							if($find) break;
					}
					return $char;
			}

			static function extractCookies($arr,$findd,$index = 0){
					$stop = false;
					$find = str_split($findd);
					$countFind = 0;
					$i = $index;
					$char  = "";
					for($i;$i<count($arr);$i++){
							if($stop) break;
							if($arr[$i] == $find[0]){
									$count=1;
									foreach( $find as $f){
											if(  $arr[$i+$count] == $find[$count] ){
													if( $count == count($find) -1 ){
															$count = 1;
															$countFind++;
															if( $findd  == "__cfduid" &&  $countFind == 2 ){
																	return self::extractCookies($arr,'value',$i);
															}else if($findd  == 'value'){
																	$char = self::extractContent($i+6,$arr);
																	$stop = true;
																	break;
															}else{
																	return self::extractCookies($arr,'value',$i);
															}
													}
													$count++;
											}
									}
							}
					}
					return $char;
			}


	}
