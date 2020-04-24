<?php
	
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;
	
	use Models\KategoriBarang;

	use Tools\Res;



	class KategoriTokoController
	{
		
		protected $ci;
	
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		public static function getAll(Request $req,Response $res){
			$id = $req->getAttribute('id');
		    try{
		    	$kategoriToko = DB::select("select * from eo_services");
				return Res::cb($res,true,"Berhasil",['kategori_toko' => $kategoriToko]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
		}
		
	}