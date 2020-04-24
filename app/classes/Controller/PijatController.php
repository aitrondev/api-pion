<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	
	class PijatController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public function save(Request $req, Response $res){
			$p = $req->getParsedBody();
			$m = new OrderPijatHistory;
			$m->user_id = $p["user_id"];
			$m->order_lat = $p["order_lat"];
			$m->order_lng = $p["order_lng"];
			$m->order_ket_alamat_lain = $p["order_ket_alamat_lain"];
			$m->order_method = $p["order_method"];
			if( $m->save() ){
				return Res::cb($res,true,'Berhasil');
			}

		}
	}


?>