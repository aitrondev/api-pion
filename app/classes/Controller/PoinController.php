<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Poin;

	class PoinController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getUserPoin(Request $req, Response $res){
			$id = $req->getAttribute("user_id");
			$m = Poin::where("user_id",$id)->first();
			if( isset($m->user_id) ){
				$poin = $m->poin;
			}else $poin = 0;

			return Res::cb($res,true,'Berhasil',["poin" => $poin]);
		}


	}
