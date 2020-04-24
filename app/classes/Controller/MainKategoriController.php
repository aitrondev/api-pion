<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Provinces;
	use Models\Regencies;
	use Models\Districts;
	use Models\Villages;


	class MainKategoriController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getMainKategori(Request $req, Response $res){
      //$id = $req->getAttribute("master_id");
			$p = DB::Select("select * from eo_main_kategori where visible='yes' order by urutan asc");
			return Res::cb($res,true,'Berhasil',["main_kategori" => $p]);
		}

		public static function getMainKategoriOnlyPenjualan(Request $req, Response $res){
      //$id = $req->getAttribute("master_id");
			$p = DB::Select("select * from eo_main_kategori
				where nama != 'Jasa' and master_kategori_id = 1
				and visible='yes' order by urutan asc");
			return Res::cb($res,true,'Berhasil',["main_kategori" => $p]);
		}

		public static function getMainKategoriOnlyJasa(Request $req, Response $res){
      //$id = $req->getAttribute("master_id");
			$p = DB::Select("select * from eo_main_kategori
				where nama = 'Jasa' or master_kategori_id = 2
				and visible='yes' order by urutan asc");
			return Res::cb($res,true,'Berhasil',["main_kategori" => $p]);
		}




	}
