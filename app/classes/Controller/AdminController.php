<?php

    namespace Controller;
    
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    use Illuminate\Database\Capsule\Manager as DB;
    
    use Models\Chat;
    use Models\User;
    use Models\Admin;
    
	use Tools\Res;
	use Tools\Encrypt;
	use Tools\Helper;
	use Tools\PushNotif;

    class AdminController{
        protected $ci;
        public function __construct(ContainerInterface $ci) {
    		$this->ci = $ci;
    	}
		  
		public static function save(Request $req, Response $res){
			$p = $req->getParsedBody();
			$m = Admin::where("p_user_id",2)->first();
			if( !isset($m->id) ) $m = new Admin;
			
			$m->p_user_id = 2;
			$m->firebase_token = $p["token"];
			$m->save();
			Res::cb($res,true,"",["data",$m]);
			//PushNotif::pushBroadcast("update token admin");
		}
	}
?>