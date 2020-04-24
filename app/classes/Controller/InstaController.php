<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Symfony\Component\DomCrawler\Crawler;

	use Tools\Res;
	use Tools\MootaAPI;
	use Tools\Helper;
	use Tools\MootaScrap;
	use Tools\MootaURL;
	use Tools\InstagramUpload as IG;

	use Models\AutobankBank;
	use Models\FBAuthResponse;
	use Models\InstaAuthResponse;
	use Models\Scheduler;
	use Models\MootaGmail;

	class InstaController{
    	protected $ci;
        public static $otherFind=0;
        public static $cookies = [];
        public static $headers = [];
    	public function __construct(ContainerInterface $ci) {
    		$this->ci = $ci;
    	}

			public static function checkPayment(Request $req, Response $res){
				$p = $req->getParsedBody();
				$m = DB::select("select * from b_transaksi where user_id = " . $p['uid'] . " and status='Success' order by id desc limit 1");
				if( count($m) > 0 ){
					$durasiBulan = 0;
					if( $m[0]->paket_id == "1" ) $durasiBulan = 1;
					else if( $m[0]->paket_id == "2" ) $durasiBulan = 3;
					else $durasiBulan = 6;

					$fromDate = strtotime(explode(" ",$m[0]->created_at)[0] );
					$expireDate = date("Y-m-d", strtotime("+".$durasiBulan." month", $fromDate));
					 if( date('Y-m-d') >  $expireDate ){
							return Res::cb($res,false,"notvalid");
					 }else{
						 return Res::cb($res,true,"valid");
					 }
				}
			}


			public static function convertPNGtoJPG(Request $req, Response $res){

				$p = $req->getParsedBody();
				$filePath = $p["url_origin"];
				#upload photo to server first
				$newName = md5(date('YmdHis') . $p['user_id']);
				$path  = "/home/pivooid/apiv1.pivoo.id/assets/";

				$image = imagecreatefrompng($filePath);
				$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
				imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
				imagealphablending($bg, TRUE);
				imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
				imagedestroy($image);
				$quality = 80; // 0 = worst / smaller file, 100 = better / bigger file
				imagejpeg($bg, $path . $newName . ".jpg" , $quality);
				imagedestroy($bg);

				return Res::cb($res,true,"Berhasil",["url"=>"http://botolbagus.com/assets/" . $newName . ".jpg"]);
			}

			public static function setActive(Request $req, Response $res){
					$status = $req->getAttribute("status");
					$igId = $req->getAttribute("ig_id");
					$m = InstaAuthResponse::where("ig_id",$igId)->first();
					$m->is_active = $status;
					if($m->save()){
						return Res::cb($res,true,"Berhasil");
					}
			}

			public static function createScheduler(Request $req, Response $res){
					$p = $req->getParsedBody();
					$m = new Scheduler;
					$m->user_id = $p['userId'];
					$m->ig_id = $p['igId'];
					$m->schedule_date_at = $p['scheduleDateAt'];
					$m->schedule_time_at = $p['scheduleTimeAt'];
					$m->url = $p['url'];
					$m->caption = $p['caption'];
					if( $m->save() ){
						//get account info
						$a = InstaAuthResponse::where("ig_id",$p['igId'])->first();
						$m->profile_url = $a->profile_url;
						$m->name = $a->name;
						return Res::cb($res,true,"Berhasil",$m);
					}
			}

			public static function uploadPhotoServer($url,$userId){
				$newName = md5(date('YmdHis') . $userId);

				if($p['type'] == 'new_post'){
						$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
						file_put_contents($file,base64_decode(explode(",",$p['data'])[1] ));
				}
				else {
					$rawPhoto = file_get_contents($file);
				 	$base64 = 'data:image/jpeg;base64,' . base64_encode($rawPhoto);
				 	$file = "/home/pivooid/apiv1.pivoo.id/assets" . $newName . '.jpg';
				}
				return "http://botolbagus.com/assets/" . $newName . '.jpg';
			}

			public static function updateScheduler(Request $req, Response $res){
					$p = $req->getParsedBody();
					$m = Scheduler::where("id",$p['id'])->first();
					$m->user_id = $p['userId'];
					$m->ig_id = $p['igId'];
					$m->schedule_date_at = $p['scheduleDateAt'];
					$m->schedule_time_at = $p['scheduleTimeAt'];
					$m->url = $p['url'];
					$m->caption = $p['caption'];
					if( $m->save() ){
						//get account info
						$a = InstaAuthResponse::where("ig_id",$p['igId'])->first();
						$m->profile_url = $a->profile_url;
						$m->name = $a->name;
						return Res::cb($res,true,"Berhasil",$m);
					}
			}

			public static function getScheduler(Request $req, Response $res){
					$id = $req->getAttribute("id");
					$m = Scheduler::where("id",$id)->first();
					return Res::cb($res,true,"Berhasil",$m);
			}

			public static function syncAccount(Request $req, Response $res){
					$userId = $req->getAttribute("user_id");
					$m = DB::select("select * from b_insta_auth_response where user_id = " . $userId);
					return Res::cb($res,true,"Berhasil",$m);
			}


    	public static function uploadPhoto(Request $req, Response $res){
    	    $p = $req->getParsedBody();
					//var_dump($p);die();
					$cookies = [];
					foreach( $p["cc"] as $v  ){
						$cookies[ $v['name'] ] = $v;
					}

					//upload to server first
					$newName = md5(date('YmdHis') . $p['user_id']);
					$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
					file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
					#upload photo to server first

					if($p['type'] == 'new_post'){
							$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
							file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
							$file = $newName . '.jpg';
					}
					else {
						if ($p['type'] == "repost_canva") {
							$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
							file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
							$file = $newName . '.jpg';
						}else{
							$file = $newName . '.jpg';
						}

					}
					# end of upload photo to server
    	    $uploadId = abs( crc32( uniqid() ) );
    	    //$cookies = self::getCookies($p['igId']);
    	    $headers  = self::setHeader($cookies);
    	    $send=self::uploadAPIPhoto($headers,$cookies["csrftoken"]["value"],$file,$uploadId,$p['caption'],$p['usertags'],$p['location']);
    	    if( isset($send["media"]) ) return Res::cb($res,true,"Berhasil",$send);
    	    else return Res::cb($res,false,"Berhasil",$send);
    	}

			public static function uploadStory(Request $req, Response $res){
    	    $p = $req->getParsedBody();
					//var_dump($p);die();
					$cookies = [];
					foreach( $p["cc"] as $v  ){
						$cookies[ $v['name'] ] = $v;
					}

					//upload to server first
					$newName = md5(date('YmdHis') . $p['user_id']);
					$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
					file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
					#upload photo to server first

					if($p['type'] == 'post'){
							$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
							file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
							$file = $newName . '.jpg';
					}
					else {
						if ($p['type'] == "repost_canva") {
							$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $newName . '.jpg';
							file_put_contents($file,base64_decode(explode(",",$p['photo'])[1] ));
							$file = $newName . '.jpg';
						}else{
							$file = $newName . '.jpg';
						}

					}
					# end of upload photo to server
    	    $uploadId = abs( crc32( uniqid() ) );
    	    //$cookies = self::getCookies($p['igId']);
    	    $headers  = self::setHeader($cookies);
    	    $send=self::uploadAPIStory($headers,$cookies["csrftoken"]["value"],$file,$uploadId,$cookies["ds_user_id"]["value"]);
    	    if( isset($send["media"]) ) return Res::cb($res,true,"Berhasil",$send);
    	    else return Res::cb($res,false,"Berhasil",$send);
    	}


    	public static function uploadConf(){
    		$ig = new IG();
    		$ig->Login("deliverytuban", "");
    		$ig->UploadPhoto("","Test Upload Video From PHP");
    	}

    	public static function getCredential(Request $req, Response $res){
    	    $post = $req->getParsedBody();
    	    $m = InstaAuthResponse::where("ig_id",$post['ig_id'])->first();
    			$m = json_decode($m,true);
    	    return Res::cb($res,true,"Berhasil",$m);
    	}

    	public static function getCookies($igId){
    	    $m = InstaAuthResponse::where("ig_id",$igId)->first();
    			$m = json_decode($m->auth,true);
    	    return $m;
    	}

        public static function setHeader($j){
            $h   = [];
            $h = ["Cookie:mcd=3; csrftoken=".$j['csrftoken']['value']."; shbid=".$j['shbid']['value']."; ds_user_id=".$j['ds_user_id']['value']."; sessionid=" . $j['sessionid']['value'],
                    "x-csrftoken:" . $j['csrftoken']['value'],
                    "User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25
                "];

            return $h;
        }

			static function getSharedData($content){
					$split = str_split($content);
					$find = "window._sharedData = ";
					$l = count( str_split($find) );
					$s = "";
					for($i=0;$i<$l;$i++){
							$s .= $split[$i];
					}
					if($s == $find){
						return true;
					}else return false;
			}

      public static function login(Request $req, Response $res){
            $post = $req->getParsedBody();

            $scrap = new MootaScrap();
            $scrap->init();

					  $url = "https://www.instagram.com/accounts/login/";
            $r = $scrap->req($url);
            $getCookie = $r->client->getResponse()->getHeaders();

            $csrftoken =  self::extractCookiesParamsOnly(str_split(json_encode($getCookie["Set-Cookie"])),"csrftoken");
            $mid =  self::extractCookiesParamsOnly(str_split(json_encode($getCookie["Set-Cookie"])),"mid");
            $rur =  self::extractCookiesParamsOnly(str_split(json_encode($getCookie["Set-Cookie"])),"rur");
            $mcd =  self::extractCookiesParamsOnly(str_split(json_encode($getCookie["Set-Cookie"])),"mcd");

            $tempc =  "mid=".$mid.";"."csrftoken=".$csrftoken.";"."mcd=".$mcd.";"."rur=".$rur.";";
            //mid=W37IVgAEAAGvzCPn_66cssTQmvcZ;csrftoken=MrLn61EGGUFbFBnYkpvrUn6RPho5dN0f;mcd=3;rur=ATN;
            //echo "<pre>"; var_dump($getCookie['Set-Cookie']);
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: */*',
                'X-Requested-With: XMLHttpRequest',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.84 Safari/537.36',
                'Cookie: ' . $tempc,
                'x-csrftoken:' . $csrftoken
            ];

            $p =  http_build_query($post);
            $rr = self::cURL($headers,"https://www.instagram.com/accounts/login/ajax/",$p);
						$igId = $rr['response']['userId'];
            if(isset($rr['response']['authenticated']) && $rr['response']['authenticated'] == true ){
							$url = "https://www.instagram.com/" . $post['username'] . "/";
							$r = $scrap->req($url);

							$sharedDataText = "";
							$scripts = $r->crawler->filter('script');
							foreach($scripts as $i => $content){
								$n = new Crawler($content);
								if(self::getSharedData($n->text())){
									$sharedDataText = $n->text();
									break;
								}
							}

							$text = str_replace("window._sharedData = ","", $sharedDataText);
							$p = json_decode(rtrim($text,";"),true);
							$profilePicUrl = $p["entry_data"]["ProfilePage"][0]["graphql"]['user']["profile_pic_url_hd"];
							$fullName = $p["entry_data"]["ProfilePage"][0]["graphql"]['user']["full_name"];

							//insert into database
							$m = InstaAuthResponse::where("ig_id",$igId)->where("user_id",$post['user_id'])->first();
							if( !isset($m['auth']) ){
									$m = new InstaAuthResponse;
							}
							$m->auth = json_encode($rr);
							$m->user_id = $post['user_id'];
							$m->ig_id = $igId;
							$m->profile_url = $profilePicUrl;
							$m->username = $post['username'];
							$m->name = $fullName;
							$rr["response"]["name"]  = $fullName;
							$rr["response"]["ig_id"]  = $igId;
							$rr["response"]["username"]  = $post['username'];
							$rr["response"]["profile_url"]  = $profilePicUrl;
							$rr["response"]["user_id"]  = $post['user_id'];
							if($m->save()){
								 return Res::cb($res,true,"Berhasil",$rr);
							}
            }

            return Res::cb($res,false,"Username atau password salah");

        }

    	public static function cURL($headers,$url,$fields,$isPost = true) {
    			$ch = curl_init();
    			curl_setopt($ch, CURLOPT_URL, $url);
    			curl_setopt($ch, CURLOPT_POST, $isPost);
    			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					if($isPost)
    				curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
          curl_setopt($ch, CURLOPT_HEADER, 1);
    			$result = curl_exec($ch);
					if(!$isPost){
						echo $url;
						echo $result;die();
					}
                  $c = str_split($result);
                  $index = 0;
                  foreach( $c as $s ){
                      if($s == "{"){break;}
                      $index++;
                  }
                  $r = "";
                  for($i=$index;$i<count($c)-1;$i++){
                    $r .= $c[$i];
                  }

                  $json_result = json_decode($r."}",true);
                  //var_dump($json_result);die();
                  if( isset($json_result['errors']) ){
                    echo json_encode(["status"=>false,"message"=>$json_result["errors"]['error'][0]]);
                    die();
                  }

                  //echo "token : " . count(str_split($result)) . " " . self::extractCookies( str_split($result),"csrftoken");
                  $cookie = [];
                  self::extractCookies( str_split($result),["csrftoken","Domain","expires","Max-Age","Path"]);
                  $cookie["csrftoken"] = self::$cookies;


                  self::$otherFind = 0;self::$cookies = [];
                  self::extractCookies( str_split($result),["shbid","Domain","expires","Max-Age","Path"]);
                  $cookie["shbid"] = self::$cookies;

                  self::$otherFind = 0;self::$cookies = [];
                  self::extractCookies( str_split($result),["shbts","Domain","expires","Max-Age","Path"]);
                  $cookie["shbts"] = self::$cookies;

                  self::$otherFind = 0;self::$cookies = [];
                  self::extractCookies( str_split($result),["ds_user_id","Domain","expires","Max-Age","Path"]);
                  $cookie["ds_user_id"] = self::$cookies;

                  self::$otherFind = 0;self::$cookies = [];
                  self::extractCookies( str_split($result),["sessionid","Domain","expires","Max-Age","Path"]);
                  $cookie["sessionid"] = self::$cookies;


                  //$http=  curl_getinfo($ch, CURLINFO_HTTP_CODE);
                  $cookie = ["response" => $json_result,"cookies" => $cookie];
            			curl_close($ch);
                  return $cookie;
    		}

        static function extractCookiesParamsOnly($arr,$findd,$index = 0){
            $stop = false;
            $find = str_split($findd);
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
                                $char = self::extractContentParamsOnly($i,$arr,$findd);
                            }
                            $count++;
                        }
                    }
                }
            }
            return $char;
        }

        static function extractContentParamsOnly($index,$arr,$findd){
    					$char = "";$find = false;
    					for($i=$index;$i<count($arr);$i++){
    							if( $arr[$i] == '=' ){
    									while(true){
    											$i++;
    											if( $arr[$i] == ';'){$find=true;break;}
    											$char .= $arr[$i];
    									}
    							}

    							if($find){
                    break;
                  }
    					}
    					return $char;
    			}

    	static function extractContent($index,$arr,$findd,$arrFind){
    					$char = "";$find = false;
    					for($i=$index;$i<count($arr);$i++){
    							if( $arr[$i] == '=' ){
    									while(true){
    											$i++;
    											if( $arr[$i] == ';'){$find=true;break;}
    											$char .= $arr[$i];
    									}
    							}

    							if($find){
                    if( self::$otherFind > 3 ){
                      break;
                      $ret = self::$cookies;
                      return $ret;

                    }
                    self::$cookies[str_replace("-","",$findd)] = $char;
                    self::$otherFind++;
                    self::extractCookies($arr,$arrFind,$i);

                    break;
                  }
    					}

    					//return $char;
    			}

    	static function extractCookies($arr,$arrFind,$index = 0){
            $findd = $arrFind[self::$otherFind];

  					$stop = false;
  					$find = str_split($findd);
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
                                return self::extractContent($i,$arr,$findd,$arrFind);
  													}
  													$count++;
  											}
  									}
  							}
  					}
  					return $char;
  			}

				static function uploadAPIStory($headers,$csrftoken,$file,$uploadId,$igId) {
						$file = "/home/pivooid/apiv1.pivoo.id/assets/" . $file;
						$postData =[];
						$postData['upload_id'] = $uploadId;
						//var_dump($file);die();
						$postData['photo'] = curl_file_create($file);
						$postData['media_type'] =	"1";


						$url = "https://www.instagram.com/create/upload/photo/";
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
						//curl_setopt ($ch, CURLOPT_SAFE_UPLOAD, false);
						curl_setopt($ch, CURLINFO_HEADER_OUT, false);
						curl_setopt($ch, CURLINFO_HTTP_CODE, false);
						curl_setopt($ch, CURLOPT_VERBOSE, false);

						$result = curl_exec($ch); // "{"upload_id": "77266272", "xsharing_nonces": {}, "status": "ok"}"
						$err = curl_error($ch);
						curl_close($ch);
						if ($err) {
							echo "cURL Error #:" . $err;
						}

						$r = json_decode($result,true);
						if( isset($r["upload_id"]) ){
								return self::uploadStoryConf($headers,$uploadId,$csrftoken,$igId);
						}else{
								return json_encode(["status"=>false]);
						}

					}

				static function uploadStoryConf($headers,$uploadId,$csrftoken,$igId) {
						$support = [
					        [
					            'name'    => 'SUPPORTED_SDK_VERSIONS',
					            'value'   => '13.0,14.0,15.0,16.0,17.0,18.0,19.0,20.0,21.0,22.0,23.0,24.0,25.0,26.0,27.0,28.0,29.0,30.0,31.0,32.0,33.0,34.0,35.0,36.0,37.0,38.0,39.0,40.0,41.0,42.0,43.0,44.0,45.0,46.0,47.0,48.0,49.0,50.0',
					        ],
					        [
					            'name'  => 'FACE_TRACKER_VERSION',
					            'value' => '12',
					        ],
					        [
					            'name'  => 'segmentation',
					            'value' => 'segmentation_enabled',
					        ],
					        [
					            'name'  => 'WORLD_TRACKER',
					            'value' => 'WORLD_TRACKER_ENABLED',
					        ],
					    ];
							$postData = [];
							$postData['upload_id']   = $uploadId;
							$postData['caption']     = "";
							$postData['supported_capabilities_new'] =  json_encode($support);
            	$postData['_csrftoken'] =  $csrftoken;
            	$postData['_uid'] 			=  $igId;


							$url = "https://www.instagram.com/create/configure_to_story/";
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url);
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
							//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
							curl_setopt($ch, CURLOPT_HEADER, false);
							curl_setopt($ch, CURLINFO_HEADER_OUT, false);
							$result = curl_exec($ch);
							$err = curl_error($ch);
									curl_close($ch);
									if ($err) {
										echo "cURL Error #:" . $err;
									}

							return json_decode($result,true);
					}

        static function uploadAPIPhoto($headers,$csrftoken,$file,$uploadId,$caption,$usertags,$location=null) {
	        $file = "/home/pivooid/apiv1.pivoo.id/assets/" . $file;
    	    $postData =[];
          $postData['_uuid'] = $uploadId;
          $postData['_csrftoken'] = $csrftoken;
          $postData['upload_id'] = $uploadId;
          $postData['image_compression'] = '{"lib_name":"jt","lib_version":"1.3.0","quality":"100"}';
          $postData['photo'] = curl_file_create($file);
          $postData['location'] = $location;


	    		$url = "https://www.instagram.com/create/upload/photo/";
	    		$ch = curl_init();
	    		curl_setopt($ch, CURLOPT_URL, $url);
	    		curl_setopt($ch, CURLOPT_POST, true);
	    		curl_setopt($ch, CURLOPT_HEADER, false);
	    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    		curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
	    		//curl_setopt ($ch, CURLOPT_SAFE_UPLOAD, false);
	    		curl_setopt($ch, CURLINFO_HEADER_OUT, false);
	    		curl_setopt($ch, CURLINFO_HTTP_CODE, false);
	    		curl_setopt($ch, CURLOPT_VERBOSE, false);

	    		$result = curl_exec($ch); // "{"upload_id": "77266272", "xsharing_nonces": {}, "status": "ok"}"
	    		$err = curl_error($ch);
	            curl_close($ch);
	            if ($err) {
	              echo "cURL Error #:" . $err;
	            }



	    		$r = json_decode($result,true);
	    		if( isset($r["upload_id"]) ){
	    		    return self::uploadPhotoConf($headers,$uploadId,$caption,$usertags,$location);
	    		}else{
	    		    return json_encode(["status"=>false]);
	    		}

        }

        static function uploadPhotoConf($headers,$uploadId,$caption,$usertags,$location) {
    				$postData = [];
            $postData['upload_id']   = $uploadId;
            $postData['caption']     = $caption;
            $postData['usertags']    = $usertags;
            if( !empty($location) && !is_null($location) ){
                $postData["location"] = json_encode([
                    "lat" => $location["lat"],
                    "lng" => $location["lng"],
                    "facebook_places_id" => $location["facebook_places_id"]]);

                $postData["geotag_enabled"] = true;
            }


		    		$url = "https://www.instagram.com/create/configure/";
		    		$ch = curl_init();
		    		curl_setopt($ch, CURLOPT_URL, $url);
		    		curl_setopt($ch, CURLOPT_POST, true);
		    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		    		curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
		    		//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
		    		curl_setopt($ch, CURLOPT_HEADER, false);
		    		curl_setopt($ch, CURLINFO_HEADER_OUT, false);
		    		$result = curl_exec($ch);

		    		$err = curl_error($ch);
		            curl_close($ch);
		            if ($err) {
		              echo "cURL Error #:" . $err;
		            }

		    		return json_decode($result,true);
        }

        static function getImageRawData($image_url) {
            $opts                                   = [];
            $http_headers                           = [];
            $http_headers[]                         = 'Expect:';

            $opts[CURLOPT_URL]                      = $image_url;
            $opts[CURLOPT_HTTPHEADER]               = $http_headers;
            $opts[CURLOPT_CONNECTTIMEOUT]           = 10;
            $opts[CURLOPT_TIMEOUT]                  = 60;
            $opts[CURLOPT_HEADER]                   = FALSE;
            $opts[CURLOPT_BINARYTRANSFER]           = TRUE;
            $opts[CURLOPT_VERBOSE]                  = FALSE;
            $opts[CURLOPT_SSL_VERIFYPEER]           = FALSE;
            $opts[CURLOPT_SSL_VERIFYHOST]           = 2;
            $opts[CURLOPT_RETURNTRANSFER]           = TRUE;
            $opts[CURLOPT_FOLLOWLOCATION]           = TRUE;
            $opts[CURLOPT_MAXREDIRS]                = 2;
            $opts[CURLOPT_IPRESOLVE]                = CURL_IPRESOLVE_V4;

            # Initialize PHP/CURL handle
            $ch = curl_init();
            curl_setopt_array($ch, $opts);
            $content = curl_exec($ch);

            curl_close($ch);
            return $content;
        }
	}
