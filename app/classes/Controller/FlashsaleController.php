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


	class FlashsaleController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function get5(Request $request, Response $response){
			$kotaId = $request->getAttribute("kota_id"); //default kota / kota yang di pakai acuan untk lihat produk
			$city = DB::select("select * from eo_city where id = " . $kotaId);
			if( count($city) > 0 ){
				$cityId = $city[0]->kota_id;
			}else $cityId = "0";
			$f = DB::Select("select *,date_format(concat(tglTo,' 23:59:59'),'%Y-%m-%d %H:%i:%s') tglTo from eo_flashsale
											where is_terbitkan = 'yes'
											and (kota_id = ".$cityId." or is_global = 'yes')
											and  now() <= date_format(concat(tglTo,' 23:59:59'),'%Y-%m-%d %H:%i:%s') ");


      foreach( $f as $v ){
          $v->produk = DB::Select("select  t.nama as nama_toko,b.*,fp.*,
										(select  path as photo from eo_foto_barang fb where fb.barang_id = fp.barang_id limit 1) as photo
										from eo_flashsale_produk fp
										inner join eo_barang b on b.id = fp.barang_id
										inner join eo_toko t on t.id = b.toko_id and t.visible='yes'
										where fp.flashsale_id = " . $v->id  . " and fp.status = 'Approved'

										order by urutan asc limit 5");

      }

			$flashsales = [];
			foreach ($f as $v) {
				if( count($v->produk) > 0 ){
					$flashsales[] = $v;
				}
			}

			return Res::cb($response,true,'Berhasil',["flashsale" => $flashsales]);
		}

		public static function getByFlashsaleId(Request $request, Response $response){
			$id = $request->getAttribute("id");
			$f = DB::Select("select *,date_format(concat(tglTo,' 23:59:59'),'%Y-%m-%d %H:%i:%s') tglTo from eo_flashsale where is_terbitkan = 'yes' and id = " . $id . "
																and now() <= date_format(concat(tglTo,' 23:59:59'),'%Y-%m-%d %H:%i:%s') " );
			if( count($f) > 0 ){
				$f = $f[0];
	      $f->produk = DB::Select("select  t.nama as nama_toko,b.*,fp.*,
									(select  path as photo from eo_foto_barang fb where fb.barang_id = fp.barang_id limit 1) as photo from eo_flashsale_produk fp
									inner join eo_barang b on b.id = fp.barang_id
									inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
									where fp.flashsale_id = " . $f->id  . " and fp.status = 'Approved'
									order by urutan asc ");
				return Res::cb($response,true,'Berhasil',["flashsale" => $f]);
			}else{
						return Res::cb($response,true,'Berhasil');
			}



		}


	}
