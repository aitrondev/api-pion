<?php



	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\OrderServiceHistory;
	use Models\OrderServiceBarangHistory;
	use Models\Topup;
	use Models\TopupHistory;
	use Models\Orders;
	use Models\Barang;
	use Models\SettingPenjual;
	use Models\SettingTimeToko;
	use Models\SettingDayToko;
	use Models\Toko;
	use Models\Settings;
	use Models\InputUser as User;
	use  Models\FotoBarang;
	use  Models\Poin;
	use  Models\PoinSetting;
	use  Models\PoinHistory;

	use Tools\Res;
	use Tools\Helper;
	use Tools\PushNotif;


	class SearchController{

		protected $ci;
		public $setting;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
			self::$setting = Settings::first();
		}

    public static function search(Request $req,Response $res){
      $p = $req->getParsedBody();
			$keys    = $p["keys"];
			if( is_null($keys) || $keys=="" ) return Res::cb($res,false,"Kata kunci tidak boleh kosong");
			$nameDay = Helper::getNameDayInd(date("N"));
			$userId  = $p['user_id'];
			$tipe    = $p['tipe']; //Barang / Toko
			$mainkategoriId = $p['main_kategori_id']; //kategori id
			$whKat = "";
			if( $mainkategoriId!="" && !is_null($mainkategoriId) ){
				$whKat = "and t.main_kategori_id = " .  $mainkategoriId;
			}

			$whToko="";
			if( isset($p['toko_id'])  ){
				$whToko = "and t.id = " . $p['toko_id'];
			}

      if( $tipe=="Barang" ){
  			$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
										b.tipe_stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
		    						b.tipe_isi,
										b.type_berat,
		    						b.isi,
		    						b.viewer,
		    						b.kategori_barang_id,
		    						kb.nama as kategori_nama,
		    						t.nama as nama_toko,
		    						p.id as setting_id,
		    						p.type_biaya_antar,
		    						p.type_jasa_pengiriman,
		    						p.price_flat,
		    						t.alamat,
		    						t.city_id,
		    						t.province_id,
		    						t.toko_alamat_gmap,
										p.deskripsi_toko_tutup,
		    						sd.h_".strtolower($nameDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
                    case when f.id is null then 'no' else 'yes' end is_favorite

		    						from eo_barang b

		    						inner join eo_toko t on t.visible='yes' and t.id = b.toko_id $whKat $whToko
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id
                    left join eo_favorite f on f.from_id = b.id and f.user_id = $userId and f.tipe = 'Barang'

		    						where  b.visible = 'yes'
		    						and b.keyword like '%".$keys."%'

		    						");


                    $datas  = [];
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
            			    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $b->toko_id );
            			    	$hari = (array) $hari[0];
            			    	foreach ($hari as $k => $v) {
            			    		if( $hari[$k]  == "Buka" )
            			    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
            			    	}
            			    	$b->days = $hariBuka;

            					$datas[] = $b;
            				}

        }else if($tipe=="Toko"){
          $datas = DB::select("select t.*,case when f.id is null then 'no' else 'yes' end is_favorite
              from eo_toko t
              left join eo_favorite f on f.from_id = t.id and f.user_id = $userId and tipe = 'Toko'
              where t.visible='yes' and t.nama like '%".$keys."%' $whKat

          ");
        }



				return Res::cb($res,true,"Berhasil",['results' => $datas]);
		}



  }
