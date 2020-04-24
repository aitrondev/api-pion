<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Models\Toko;



	class EkspedisiController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function get(Request $req, Response $res){
			$tokoId = $req->getAttribute("toko_id");
			$e = DB::Select("select e.* from eo_ekspedisi e
											inner join eo_toko_vs_ekspedisi t on t.toko_id  = $tokoId and e.id = t.ekspedisi_id  and t.visible='yes'
											where e.visible = 'yes' ");

			//cek jasa antar sendiri
			$t = Toko::where("id",$tokoId)->first();
			$j = DB::select("select *,'Jasa ".$t->nama."' as nama  from eo_ekspedisi_antar_sendiri e where e.toko_id = " . $tokoId . " and is_active='yes' ");
			if( count($j) > 0 ){
				$j[0]->icon = $t->photo;
				$e = array_merge($e,$j);
			}
			return Res::cb($res,true,'Berhasil',["ekspedisi" => $e]);
		}

		public static function tabungGalon(Request $req, Response $res){
			$e = DB::Select("select * from eo_ekspedisi where id=6 ");
			return Res::cb($res,true,'Berhasil',["ekspedisi" => $e]);
		}

	}
