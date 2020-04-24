<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\FBAuthResponse;


	class FBController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function insertAuth(Request $req, Response $res){
		    $p = $req->getParsedBody();
		    $m = new FBAuthResponse;
		    $m->user_id = $p['user_id'];
		    $m->auth = $p['auth'];
		    $m->fb_user_id = json_decode($p['auth'],true)['authResponse']['userID'];
		    if($m->save()){
		        return Res::cb($res,true,"Berhasil");
		    }

		}

		public static function postInstaPhoto(Request $req, Response $res){
			$p = $req->getParsedBody();

			$token =  DB::select("select * from b_fb_auth_response where fb_user_id = " . $p['fbId'] . " order by id desc limit 1");
			$token = json_decode($token[0]->auth);
			$headers = [
				"Authorization: Bearer " . $token->authResponse->accessToken
			];

			$r = self::cURL($headers,"https://graph.facebook.com/v3.1/".$p['id']."/media?image_url=".$p['url']."&caption=" . $p['caption']);

			$containerID = $r['data'][0]['id'];
			echo "https://graph.facebook.com/v3.1/".$p['id']."/media_publish?creation_id=" . $containerID;die();
			$r = self::cURL($headers,"https://graph.facebook.com/v3.1/".$p['ig_id']."/media_publish?creation_id=" . $containerID);
			//var_dump($r);
			die();


			if($m->save()){
					return Res::cb($res,true,"Berhasil");
			}
		}

		public static function updateAuth(Request $req, Response $res){
		    $p = $req->getParsedBody();
		    $m = FBAuthResponse::where("fb_user_id",$p['fb_user_id'])->first();
		    $m->auth = $p['auth'];
		    if($m->save()){
		        return Res::cb($res,true,"Berhasil");
		    }
		}

		public static function getFBAccounts(Request $req, Response $res){
			$userId = $req->getAttribute("user_id");
			$ac = DB::select("select * from b_fb_auth_response where user_id = " . $userId . " order by id desc limit 1");
			$ac = json_decode($ac[0]->auth);
			$headers = [
				"Authorization: Bearer " . $ac->authResponse->accessToken
			];
            //17841406220614623?fields=username,ig_id,profile_picture_url,name
			$r = self::cURL($headers,"https://graph.facebook.com/v3.1/me/?fields=id,first_name,last_name,name,picture.width(150).height(150)");

			return Res::cb($res,true,"Berhasil",[$r]);
		}

		public static function getInstaAccounts(Request $req, Response $res){
			$userId = $req->getAttribute("user_id");
			$fbId = $req->getAttribute("fb_id");
			$ac = DB::select("select * from b_fb_auth_response where user_id = " . $userId . " order by id desc limit 1");
			$ac = json_decode($ac[0]->auth);
			$headers = [
				"Authorization: Bearer " . $ac->authResponse->accessToken
			];
            //17841406220614623?fields=username,ig_id,profile_picture_url,name
			$r = self::cURL($headers,"https://graph.facebook.com/v3.1/".$fbId."/accounts?fields=instagram_business_account");
			$instaBisnisId = [];
			foreach($r['data'] as $v){
			    if( isset($v["instagram_business_account"]) ){
			        $instaBisnisId[] = $v["instagram_business_account"]["id"];
			    }
			}

			$insta = [];
			foreach($instaBisnisId as $b){
			    $insta[] = self::cURL($headers,"https://graph.facebook.com/v3.1/".$b."?fields=username,ig_id,profile_picture_url,name");
			}

			return Res::cb($res,true,"Berhasil",$insta);
		}

		public static function cURL($headers,$url,$fields = "",$method = "GET") {
			try{
    			$ch = curl_init();
    			curl_setopt($ch, CURLOPT_URL, $url);
    			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    			if( $method == "GET" ){
    					curl_setopt($ch, CURLOPT_POST, false);
    			}else{
    					curl_setopt($ch, CURLOPT_POST, true);
    					curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
    			}

    			$result = curl_exec($ch);
    			curl_close($ch);

    			//var_dump($result);die();
			}catch(Exception $err){
			    var_dump($err);
			}


			return json_decode($result, true);
		}




	}
