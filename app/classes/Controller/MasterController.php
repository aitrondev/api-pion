<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	class MasterController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getProv(Request $req, Response $res){
				$m = DB::select("select * from h_provinsi");
				return Res::cb($res,true,"success",['provinsi'=>$m]);
		}

		public static function getSlider(Request $req, Response $res){
				$m = DB::select("select * from h_slider");
				return Res::cb($res,true,"success",['slider'=>$m]);
		}

    public static function getKota(Request $req, Response $res){
				$m = DB::select("select k.*,c.id as id from eo_city c inner join eo_kota k on c.kota_id = k.city_id");
				return Res::cb($res,true,"success",['kota'=>$m]);
		}

	}
