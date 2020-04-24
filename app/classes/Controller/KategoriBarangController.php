<?php

	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\KategoriBarang;
	use Models\Toko;

	use Tools\Res;



	class KategoriBarangController
	{

		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}


		public static function getAllByToko(Request $req,Response $res){
			//ambil kategori yang di ikuti oleh main kategori
			$tokoId = $req->getAttribute('toko_id');
			$toko = Toko::where("id",$tokoId)->first();
			/*$m = DB::Select("select kb.* from eo_barang b
							inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id
							where b.toko_id = $tokoId group by kb.id");*/
			$m = DB::select("select * from eo_kategori_barang where main_kategori_id = " . $toko->main_kategori_id);

			return Res::cb($res,true,"Berhasil",['kategori_barang' => $m]);

		}

		public static function getKatByMainKat(Request $req,Response $res){
		  $id = $req->getAttribute('main_kat_id');
		  $m = DB::Select("select * from eo_kategori_barang kb where kb.main_kategori_id = " . $id . " and visible = 'yes' order by urutan ");

		  return Res::cb($res,true,"Berhasil",['kategori_barang' => $m]);

		}

		public static function getAll(Request $req,Response $res){
		    $currentPage = $req->getAttribute("current_page");
		    try{
		    	//get total barang
		    	$total = DB::Select("select count(*) as jum from eo_kategori_barang
		    						where visible = 'yes' and  id != 29 ");
				$total = $total[0]->jum;

				$limit = 12;
				$totalPages = ceil($total / $limit);

				if($currentPage > $totalPages) $currentPage = $totalPages;
				if( $currentPage < 0 ) $currentPage = 1;

				$offset = ($currentPage - 1) * $limit;



		    	$kategoriBarang = DB::select(" select b.*
		    		from eo_kategori_barang b
		    		where  b.visible = 'yes' and id != 29
		    		 ");
					 //limit $offset,$limit
				return Res::cb($res,true,"Berhasil",['kategori_barang' => $kategoriBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllByPasar(Request $req,Response $res){
		    $pasarId = $req->getAttribute("pasar_id");
		    try{
		    	//get total barang
		    	/*$total = DB::Select("select count(*) as jum from eo_kategori_barang
		    						where visible = 'yes' and  id != 29 ");
				$total = $total[0]->jum;

				$limit = 12;
				$totalPages = ceil($total / $limit);

				if($currentPage > $totalPages) $currentPage = $totalPages;
				if( $currentPage < 0 ) $currentPage = 1;

				$offset = ($currentPage - 1) * $limit;*/



	    		$kategoriBarang = DB::select(" select b.*
		    		from eo_kategori_barang b
		    		where  b.visible = 'yes' and b.pasar_id = $pasarId
		    		 ");
					 //limit $offset,$limit
					return Res::cb($res,true,"Berhasil",['kategori_barang' => $kategoriBarang]);
				}catch(Exception $e){
					return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
				}
		}

		public static function getAllByTypeShop(Request $req,Response $res){
		    $currentPage = $req->getAttribute("current_page");
		    $typeShop = $req->getAttribute("type_shop");
		    try{
		    	//get total barang
		    	$total = DB::Select("select count(*) as jum from eo_kategori_barang kb
		    						where kb.visible = 'yes' and  kb.id != 29 and kb.type_shop = '".$typeShop."' ");
				$total = $total[0]->jum;

				$limit = 12;
				$totalPages = ceil($total / $limit);

				if( $totalPages == 0 ){
					return Res::cb($res,true,"Berhasil",['kategori_barang' => [] ]);
				}

				if($currentPage > $totalPages) $currentPage = $totalPages;
				if( $currentPage < 0 ) $currentPage = 1;

				$offset = ($currentPage - 1) * $limit;


				$whKota = "";
				if( isset($_GET['kota_id']) ){
					$whKota = "and b.city_id = " . $_GET['kota_id'];
				}

		    	$kategoriBarang = DB::select(" select b.*
		    		from eo_kategori_barang b
		    		where  b.visible = 'yes' and id != 29 and type_shop = '".$typeShop."'
		    		$whKota
		    		");
				return Res::cb($res,true,"Berhasil",['kategori_barang' => $kategoriBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllProdukKhusus(Request $req,Response $res){
		    $currentPage = $req->getAttribute("current_page");
		    try{
		    	//get total barang
		    	$total = DB::Select("select count(*) as jum from eo_kategori_barang where visible = 'yes' and is_produk_khusus = 'yes'  ");
				$total = $total[0]->jum;

				$limit = 12;
				$totalPages = ceil($total / $limit);

				if($currentPage > $totalPages) $currentPage = $totalPages;
				if( $currentPage < 0 ) $currentPage = 1;

				$offset = ($currentPage - 1) * $limit;
				if( $offset < 0 ) $offset = 0;



		    	$kategoriBarang = DB::select(" select b.*
		    		from eo_kategori_barang b   where  b.visible = 'yes' and  b.is_produk_khusus = 'yes'
		    		limit $offset,$limit ");
				return Res::cb($res,true,"Berhasil",['kategori_barang' => $kategoriBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllMakanan(Request $req,Response $res){
		    try{
		    	$kategoriBarang = DB::select(" select b.*
		    		from eo_kategori_makanan b
		    		where  b.visible = 'yes'");
				return Res::cb($res,true,"Berhasil",['kategori_barang' => $kategoriBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}




	}
