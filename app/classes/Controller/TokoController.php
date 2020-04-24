<?php

	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Tools\Res;
	use Tools\Helper;
	use Models\Settings;
	use Models\Favorite;
	use Models\User;
	use Models\Toko;



	class TokoController
	{

		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		public static function getDetail(Request $req,Response $res){
      $id = $req->getAttribute("toko_id");
			$m = Toko::where("id",$id)->first();
      return Res::cb($res,true,"Berhasil",['toko' => $m]);
    }
		public static function getSettingTime(Request $req,Response $res){
      $id = $req->getAttribute("id");
      $nameDay = Helper::getNameDayInd(date("N"));
      $m = DB::select("select * from eo_setting_day_toko where toko_id = " . $id . " and h_".strtolower($nameDay)." = 'Buka' " )[0];
      $t = DB::select("select * from eo_laundry_time_pick where day_id = " .  date('N')  . " and toko_id = ". $id);
      return Res::cb($res,true,"Berhasil",['times' => $t]);
    }
		public static function getTokoTabungGalonTedekat(Request $req,Response $res){
				$p = $req->getParsedBody();
				$lat = $p['lat'];
				$lng = $p['lng'];

				$namaHari  = Helper::getNameDayInd(date('N'));
				$inMiles = 3959;
				$inKm = 6371;
				$m = DB::Select("SELECT
								h_".strtolower($namaHari)." status_buka, a.services_id,TRUNCATE(
								acos(sin(a.latitude * 0.0175) * sin(".$lat." * 0.0175)
										 + cos(a.Latitude * 0.0175) * cos(".$lat." * 0.0175) *
											 cos((".$lng." * 0.0175) - (a.longitude * 0.0175))
										) * $inKm ,1) as jarak, nama, alamat,a.latitude,a.longitude,
										a.id,a.photo,a.latitude as last_latitude,a.longitude as last_longitude,
										a.is_kiloan_satuan,
										SUBSTRING_INDEX(st.waktu_mulai,':',2) waktu_mulai,SUBSTRING_INDEX(st.waktu_akhir,':',2) waktu_akhir
										FROM eo_toko a

								inner join eo_setting_day_toko sd on sd.toko_id = a.id
								inner join eo_setting_time_toko st on st.toko_id = a.id and setting_day_id = ".date('N')."
								where  a.services_id = 4
											and a.jenis_jasa = '".$tipe."'
								order by jarak
								");

				$tokos = [];
				foreach( $m as $t ){
					//hany aambil yg status buka
					if( $t->status_buka == "yes" ){
							$tokos[] = $t;
					}
				}


				return Res::cb( $res,true,"Berhasil",["outlet" =>$tokos] );
		}
		public function searchToko(Request $req,Response $res){ //di gunakan untuk pencarian resto maupun toko
			$key = 	$req->getAttribute("key");
			$tipe = 	$req->getAttribute("type"); // resto / toko
			$userId = $req->getAttribute("user_id"); //user

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes' )";
			}
			$whTipe = "";
			if( $tipe == "resto" ){
				$whTipe  = "and t.services_id = 3";
			}else $whTipe  = "and t.services_id = 1";



			$toko = DB::Select("select * from eo_toko t
			where t.visible='yes' t.nama like '%".urldecode($key)."%'  $whTipe $whKota ");
			return Res::cb($res,true,"Berhasil",['toko' => $toko]);
		}
		public static function getTokoByMainkat(Request $req,Response $res){ //ambil toko dengan kategori yang ikut di main kat
			$nameDay = Helper::getNameDayInd(date("N"));
      $id = $req->getAttribute("main_kategori_id");

			$m = DB::select("select t.*, t.alamat_jalan as alamat from eo_toko t
												inner join eo_barang b on b.toko_id = t.id
												inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id
												where t.main_kategori_id = $id
												and t.visible='yes'
												group by t.id");
      $tokos = [];$tokoIds=[];
      if( isset($_GET['filter']) ){
        $filter = $_GET['filter'];
        if( $filter == "Hanya yang buka" ){
          foreach(  $m as $t ){
            $day = DB::select("select * from eo_setting_day_toko where toko_id = " . $t->id ." and h_".strtolower($nameDay)." = 'Buka' ");
            if( count($day) > 0 ){ //jika buka
              $time = DB::select("select * from eo_setting_time_toko where setting_day_id = " . $day[0]->id);
              if( count($time) > 0 ){
                 //cek jika pada jam buka
                $start_ts = strtotime($time[0]->waktu_akhir);
                $end_ts = strtotime($time[0]->waktu_mulai);
                $user_ts = strtotime(date("Y-m-d H:i:s"));

                //cek jika berada dalam range
                if(($user_ts >= $start_ts) && ($user_ts <= $end_ts)){
                    $tokos[] = $t;
										$tokoIds[] = $t->id;
                }
              }else continue;
            }else continue;
          }
        }
      }

			//$ids = array_join(",",$tokoIds);


			//sorting
			$whSort="";
			if( isset($_GET['sort']) ){
					if($_GET['sort'] == "Terbaru"  ){
						$whSort=" order by created_at";
					}else if($_GET['sort'] == "A to Z"){

					}else if($_GET['sort'] == "Z to A"){

					}
			}



			return Res::cb($res,true,"Berhasil",['toko' => $m]);

		}
		public static function getTokoByMainkatNew(Request $req,Response $res){ //ambil toko dengan kategori yang ikut di main kat
			$nameDay = Helper::getNameDayInd(date("N"));
      $id = $req->getAttribute("main_kategori_id");

			$m = DB::select("select t.*,t.alamat_jalan as alamat from eo_toko t
												inner join eo_barang b on b.toko_id = t.id
												inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id
												where t.main_kategori_id = $id
												and t.visible='yes'
												group by t.id");
      $tokos = [];$tokoIds=[];
			if( count($m) > 0 ){
		    if( isset($_GET['filter']) ){
		      $filter = $_GET['filter'];
		      if( $filter == "Buka Only" ){
		        foreach(  $m as $t ){
		          $day = DB::select("select * from eo_setting_day_toko where toko_id = " . $t->id ." and h_".strtolower($nameDay)." = 'Buka' ");
		          if( count($day) > 0 ){ //jika buka
		            $time = DB::select("select * from eo_setting_time_toko where setting_day_id = " . date("N") . " and toko_id = " . $t->id);
		            if( count($time) > 0 ){
		               //cek jika pada jam buka
		              $start_ts = strtotime($time[0]->waktu_mulai);
		              $end_ts = strtotime($time[0]->waktu_akhir);
		              $user_ts = strtotime(date("Y-m-d H:i:s"));

		              //cek jika berada dalam range
		              if(($user_ts >= $start_ts) && ($user_ts <= $end_ts)){
		                  $tokos[] = $t;
											$tokoIds[] = $t->id;
		              }
		            }else continue;
		          }else continue;
		        }
		      }else $tokos = $m;
		    }else{
		      $tokos = $m;
		    }

				//kumpulkan ids
				if( count( $tokoIds ) <= 0 ){
					foreach($tokos as $t ){
						$tokoIds[] = $t->id;
					}
				}

				if( isset( $_GET['sort'] ) ){
						$whSort="";
						if( $_GET['sort'] == "Terbaru" ){
							$whSort=" order by t.created_at desc ";
						}else if( $_GET['sort'] == "A to Z" ){
							$whSort=" order by  t.nama asc ";
						}else if( $_GET['sort'] == "Z to A" ){
							$whSort=" order by t.nama desc ";
						}
						$ids = implode(",",$tokoIds);
						$tokos = DB::select("select t.*,t.alamat_jalan as alamat from eo_toko t
															inner join eo_barang b on b.toko_id = t.id
															inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id
															where t.main_kategori_id = $id
															and t.id in (".$ids.")
															and t.visible='yes'
															group by t.id " . 	$whSort);
				}
			}else{
				$tokos = $m;
			}



			return Res::cb($res,true,"Berhasil",['toko' => $tokos]);

		}
		public static function getAll(Request $req,Response $res){
			$katId = $req->getAttribute("kat_id"); //katgori
			$userId = $req->getAttribute("user_id"); //user

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes' )";
			}


		    try{
		    	$toko = DB::select("select t.* from eo_barang b
								inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
								where b.kategori_makanan_id = $katId and b.visible = 'yes'
								$whKota
								group by t.id");
				return Res::cb($res,true,"Berhasil",['toko' => $toko]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}
		public static function getById(Request $req,Response $res){
			$id = $req->getAttribute("id"); //katgori
			$uId = $req->getAttribute("user_id"); //user

			$m = DB::select("select t.*, case when f.id is null then 'no' else 'yes' end as is_favorite from eo_toko t
											left join eo_favorite f on f.from_id = $id and f.tipe = 'Toko' and f.user_id = $uId
												where t.id = " . $id);
			$etalase = [];
			$etalase[]	= [ 'id'=>0,"nama"=>"All","toko_id"=> $m[0]->id ];
			$etalase = array_merge($etalase,DB::select('select * from eo_etalase where toko_id = ' . $id . " and visible='yes'"));
			$m[0]->etalase 		= $etalase;

			return Res::cb($res,true,"Berhasil",['toko' => $m[0] ]);
		}
		public static function getAllByPasar(Request $req,Response $res){
			$pasarId = $req->getAttribute("pasar_id"); //pasar
			$userId = $req->getAttribute("user_id"); //user

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes' )";
			}


		    try{
		    	$toko = DB::select("select t.* from eo_toko t where  t.visible = 'yes'
								and t.pasar_id = $pasarId
								$whKota
								group by t.id");
				return Res::cb($res,true,"Berhasil",['toko' => $toko]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}


	}
