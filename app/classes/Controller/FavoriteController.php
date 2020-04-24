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
	use Models\Favorite;
	use Models\SettingTimeToko;

	use Tools\Helper;


	class FavoriteController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getToko(Request $req, Response $res){
      $uid = $req->getAttribute("user_id");
			$favorite = DB::Select("select t.* from eo_favorite f
                          inner join eo_toko t on t.id = f.from_id
                          where f.tipe = 'toko' and user_id = $uid
                        ");
			return Res::cb($res,true,'Berhasil',["favorites" => $favorite]);
		}

    public static function getBarang(Request $req, Response $res){
      $nameDay = Helper::getNameDayInd(date("N"));
			$uId = $req->getAttribute('user_id'); //user id
			$p = $req->getParsedBody();

		  try{
					$barang = DB::select("
								select
								b.created_at,
								t.id as toko_id,t.latitude,t.longitude,
								b.nama,
								b.id,
								b.toko_id,
								b.harga,
								b.stock,
								b.tipe_stock,
								b.kondisi,
								b.discount,
								b.berat,
								b.viewer,
								b.isi,b.tipe_isi,
								b.kategori_barang_id,
								t.nama as nama_toko,
								p.id as setting_id,
								p.type_biaya_antar,
								p.type_jasa_pengiriman,
								p.price_flat,
								t.alamat,
								t.city_id,
								t.province_id,
								t.toko_alamat_gmap,
								sd.h_".strtolower($nameDay)." as status_toko,
								p.deskripsi_toko_tutup,
								(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
								case when f.id is null then 'no' else 'yes' end as is_favorite
								from eo_barang b

								inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
								inner join eo_setting_penjual p on p.toko_id = t.id
								inner join eo_setting_day_toko sd on sd.toko_id = t.id

								inner join eo_favorite f on f.from_id = b.id and f.tipe = 'Barang' and f.user_id = $uId
								where  b.visible = 'yes'

								order by b.id desc
						");


				$newBarang  = [];
				foreach($barang as $b){
					$newTimes = [];
					$times = SettingTimeToko::where("toko_id",$b->toko_id)->where("setting_day_id",date('N'))->get();
					foreach ($times as $t) {
						$time = [
									"date_mulai" => date('Ymd') . " ". $t->waktu_mulai,
									"date_akhir" => date('Ymd') . " ". $t->waktu_akhir,
									"date_sekarang" => date('Ymd H:i:s'),
									"waktu_mulai" => $t->waktu_mulai,
									"waktu_akhir" => $t->waktu_akhir,

								];
						$newTimes[] = $time;
					}
					$b->times = $newTimes;
					$hariBuka = [];
						$hari = DB::Select("select
						h_senin,
						h_selasa,
						h_rabu,
						h_kamis,
						h_jumat,
						h_sabtu,
						h_ahad
					 from eo_setting_day_toko where toko_id = " . $b->toko_id );

						$hari = (array) $hari[0];

						foreach ($hari as $k => $v) {
							//if( $hari[$k]  == "Buka" )
								$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
						}
						$b->days = $hariBuka;

					$newBarang[] = $b;
				}

				return Res::cb($res,true,"Berhasil",['favorites' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

    public static function setFavorite(Request $req,Response $res){ //ambil toko dengan kategori yang ikut di main kat
      $p = $req->getParsedBody();

      $m = Favorite::where("user_id",$p['user_id'])
			->where("from_id",$p['from_id'])
			->where("tipe",$p['tipe'])->first();

      if( !is_null($m) ){
        $m->delete();
      }else{
        $m = new Favorite;
        $m->user_id = $p['user_id'];
        $m->from_id = $p['from_id'];
        $m->tipe = $p['tipe'];
        $m->save();
      }

      return Res::cb($res,true,"Berhasil");

    }



	}
