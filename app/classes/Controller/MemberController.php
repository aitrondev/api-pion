<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	class MemberController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getDPC(Request $req, Response $res){
			$m = DB::select("select m.*,k.city_name,p.province_name from h_member m 
			    inner join h_kota k on k.city_id = m.id
			    inner join h_provinsi p on p.province_id = m.provinsi_id
			     where tipe = 'dpc'
			    ");
			return Res::cb($res,true,"success",['dpc'=>$m]);
		}
		
		public static function getDPD(Request $req, Response $res){
			$m = DB::select("select m.*,k.city_name,p.province_name from h_member m 
			    inner join h_kota k on k.city_id = m.id
			    inner join h_provinsi p on p.province_id = m.provinsi_id
			     where tipe = 'dpd'
			    ");
			return Res::cb($res,true,"success",['dpd'=>$m]);
		}
		
		
	

	}
