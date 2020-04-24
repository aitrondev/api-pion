<?php

	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\LaundryTipeCuci;
	use Models\LaundrySatuan;
	use Models\OrderLaundryHistory;
	use Models\LaundryItemsHistory;
	use Models\Toko;
	use Models\LaundrySetting;
	use Models\Orders;
	use Models\Settings;
	use Models\Topup;
	use Models\TopupHistory;
	use Models\LaundryPoin;
	use Models\UserPoin;

	use Tools\Res;
	use Tools\PushNotif;
	use Tools\Helper;

	class LaundryController
	{
		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getListOutlet2(Request $req,Response $res){
			$p = $req->getParsedBody();
			$lat 	= $p['lat'];
			$lng 	= $p['lng'];

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
										and a.jenis_jasa = 'Laundry'
							order by jarak
							");

			foreach( $m as $t ){
				//get satuan
				$t->satuans = DB::select("select s.nama,ls.harga,s.id from eo_toko_vs_laundry_satuan ls
												inner join eo_laundry_satuan s on s.id = ls.satuan_id and ls.toko_id = " . $t->id . " limit 2");

				$satuanExist = false;
				if( count($t->satuans) > 0 ){
					$satuanExist=true;
				}
				$t->satuans[] = [ "nama"=>"Kiloan","id"=>"1","harga"=> "0" ];
				if( $satuanExist ){
					$t->satuans[] = [ "nama"=>"...","id"=>"1","harga"=> "0" ];
				}


			}


			return Res::cb( $res,true,"Berhasil",["outlet" =>$m] );
		}


		public static function cekPoin(Request $req, Response $res){
        $userId = $req->getAttribute("user_id");

        $LaundryPoin = DB::select("select * from eo_laundry_poin p")[0];
        $userPoin 	= UserPoin::where("user_id",$userId)->first();
				$t = Topup::where("user_id",$userId)->first();
				if( is_null($t) || $t["nominal"] == "" ) $t['nominal'] = "0";
        $poin = 0;
        if( isset($userPoin["user_id"]) && !is_null($userPoin) ) $poin = $userPoin["poin"];
        return Res::cb($res,true,'Berhasil',["min_poin"=>(int) $LaundryPoin->poin,"poin"=>$poin,"saldo"=>$t['nominal']]);
    }

		public static function changeStatusFinish(Request $req,Response $res){
				$p = $req->getParsedBody();
				$m = OrderLaundryHistory::where("id",$p['order_id'])->first();
				$m->status = $p['status'];
				$toko = Toko::where("id",$m->outlet_id)->first();

				$items = DB::select("SELECT t.*,tk.nama as outlet
					  FROM `eo_order_laundry_items_history` `t`
					  inner join eo_order_laundry_history  h on h.id = t.order_laundry_history_id
					 	inner join eo_toko tk on tk.services_id = 4 and tk.id = h.outlet_id
						 where t.order_laundry_history_id = " . $p['order_id']);

				$deskripsi = "Ambil Laundry \n" . $items[0]->outlet . "\n\n";
				foreach ($items as $v) {
						$deskripsi .= "Cuci : " . $v->tipe_cuci_nama . "\nPakaian : " . $v->pakaian_nama . " " . $v->tipe_kiloan_satuan . " " . $v->jum_kiloan . "kg\nUnit : " . $v->jum_unit . "\n\n";
				}
				$data = [
					"deskripsi" => $deskripsi,
					"to_lat" => $toko->latitude,
					"to_lng" => $toko->longitude,
					"telp_penerima" => $toko->no_telp,
					"to_alamat" => $toko->toko_alamat_gmap,
					"to_ket_lain" => $items[0]->outlet
				];

				if( $m->save() ){
					return Res::cb( $res,true,"Berhasil",$data);
				}
		}

		public static function getTipeCuciBySatuanId(Request $req,Response $res){
			$id = $req->getAttribute("satuan_id");
			$m = DB::select("select t.id,t.nama,t.icon from eo_pakaian_vs_tipe_cuci p
										inner join eo_tipe_cuci t on t.id = p.tipe_cuci_id
										where p.tipe_pakaian_id = " . $id);

				return Res::cb( $res,true,"Berhasil",["tipe_cuci" => $m] );
		}

		public static function getListOutlet(Request $req,Response $res){
			$p = $req->getParsedBody();
			$lat = $p['lat'];
			$lng = $p['lng'];
			$namaHari  = Helper::getNameDayInd(date('N'));
			$inMiles = 3959;
			$inKm = 6371;
			$m = DB::Select("SELECT h_".strtolower($namaHari)." status_buka, a.services_id,TRUNCATE(
					acos(sin(a.latitude * 0.0175) * sin(".$lat." * 0.0175)
							 + cos(a.Latitude * 0.0175) * cos(".$lat." * 0.0175) *
								 cos((".$lng." * 0.0175) - (a.longitude * 0.0175))
							) * $inKm ,1) as jarak, nama, alamat,a.latitude,a.longitude,a.id
							FROM eo_toko a
							inner join eo_setting_day_toko sd on sd.toko_id = a.id

							where  a.services_id = 4 order by jarak
							and a.jenis_jasa = 'Laundry'
							");
			$i = 0;
			foreach( $m as $v ){
				if($v->services_id == "4"){
					$m[$i]->jarak += 0.5;
					$m[$i]->layanan = DB::select("select l.satuan_id,ls.nama from eo_toko_vs_laundry_satuan l
					 															inner join eo_laundry_satuan ls on ls.id = l.satuan_id
																				and l.toko_id = " . $v->id);
				  $jam = DB::select("select waktu_mulai jam_buka,waktu_akhir jam_tutup from eo_setting_time_toko t
																				where t.setting_day_id = " . date('N') ." and t.toko_id = " . $v->id);

					$m[$i]->jam_buka = substr($jam[0]->jam_buka,0,-3);
					$m[$i]->jam_tutup = substr($jam[0]->jam_tutup,0,-3);
				}
				$i++;
			}

					return Res::cb( $res,true,"Berhasil",["outlet" => $m] );
		}

		public static function getSatuan(Request $req,Response $res){
			$id = $req->getAttribute("toko_id");
			$m  = DB::select("select v.harga,s.nama,s.id from eo_toko_vs_laundry_satuan  v
												inner join eo_laundry_satuan s on s.id = v.satuan_id and v.toko_id = ". $id);
			return Res::cb( $res,true,"Berhasil",["satuan" => $m] );
		}

		public static function getTipeCuci(Request $req,Response $res){
			$m  = LaundryTipeCuci::get();
			return Res::cb( $res,true,"Berhasil",["tipe_cuci" => $m] );
		}

		public static function getLayanan(Request $req,Response $res){
			$m  = DB::select("select * from eo_tipe_laundry");
			return Res::cb( $res,true,"Berhasil",["layanan" => $m] );
		}

		public static function getPosition(Request $req,Response $res){
			$m  = DB::select("select * from eo_laundry_setting ");
			return Res::cb( $res,true,"Berhasil",["lat" => $m[0]->lat,"lng" => $m[0]->lng ]);
		}

		public static function cekHargaSatuan(Request $req,Response $res){
			$p = $req->getParsedBody();
			$umur = $p['umur'];
			$tipeCuciId = $p['cuci_id'];
			$pakaianId = $p['pakaian_id'];
			$durasiId = $p['durasi_id'];

			//var_dump($p);die();

			$fh = DB::select("select * from eo_laundry_harga_umur_vs_durasi_vs_prosentase where tipe_cuci_id = ".$tipeCuciId." and umur='".$umur."' and durasi_id=".$durasiId)[0];
			$hargaDasar = LaundryTipePakaian::where("id",$pakaianId)->first();
			$hargaFinal   = ($hargaDasar["harga"] * $fh->prosentase)/100;
			return Res::cb( $res,true,"Berhasil",["harga" =>  $hargaFinal] );

		}

		public static function getHargaKiloan(Request $req,Response $res){
			$id  = $req->getAttribute("toko_id");
			$m = DB::select("select tipe_cuci_id,kategori_id,harga
										from eo_toko_vs_laundry_harga_kiloan
										where toko_id = " . $id);

			return Res::cb( $res,true,"Berhasil",["harga" => $m] );
		}

		public static function getTimesAndDuration(Request $req,Response $res){
			$m  = DB::select("select * from eo_laundry_time_pick");
			$d  = DB::select("select * from eo_laundry_setting");
			return Res::cb( $res,true,"Berhasil",["times" =>  $m,"duration" => $d[0] ] );
		}

		public static function historyById(Request $req,Response $res){
			$id = $req->getAttribute("id");
			$m = DB::Select("select tk.nama as outlet,t.*,u.nama as nama_user,v.nama as nama_vendor,v.photo as foto_vendor,v.photo as foto_driver,
			v.nama as nama_driver
											from eo_order_laundry_history t
											left join eo_vendor v on v.id = t.vendor_id
											inner join eo_user u on u.id = t.user_id
                      inner join eo_toko tk on tk.id = t.outlet_id
											where t.id = " . $id );
			//$set = DB::select("select * from eo_laundry_setting");
			//$m[0]->is_use_pick_time = $set[0]->is_use_pick_time;
			$i = DB::Select("select * from eo_order_laundry_items_history where order_laundry_history_id = " . $id);
			$m[0]->items = $i;
			return Res::cb( $res,true,"Berhasil",['history' => $m[0]] );
		}

		public static function order(Request $req,Response $res){
			DB::beginTransaction();
			$p = $req->getParsedBody();
			//$set = LaundrySetting::first();
			//var_dump($p);die();
			$m = new OrderLaundryHistory;
			$m->user_id = $p['user_id'];
			$m->order_lat = $p['order_lat'];
			$m->order_lng = $p['order_lng'];
			$m->order_ket_lain = $p['ket_lain'];
			$m->order_method = $p['order_method'];
			$m->order_alamat = $p['address'];
			$m->time_pick_from = $p['time_pick_from'];
			$m->time_pick_to = $p['time_pick_to'];
			$m->total_price = $p['biaya_total'];
			$m->ongkir = $p['ongkir'];
			$m->voucher_kode = $p['voucher_kode'];
			if( !is_null($p['voucher_kode']) )
				$m->is_using_voucher = "no";
			$m->voucher_nominal = $p['voucher_nominal'];
			$m->voucher_tipe = $p['voucher_tipe'];
			$m->biaya_satuan = $p['biaya_satuan'];
			$m->biaya_kiloan = $p['biaya_kiloan'];
			$m->total_price_after_voucher = $p['total_price_after_voucher'];
			$m->km = $p['km'];
			$m->outlet_id = $p['outlet_id'];
			$m->vendor_id = 0;
			$jumlahSatuan = 0;
			$jumlahKiloan = 0;
			if($m->save()){
					//save item
					$items = json_decode($p['items'],true);
					foreach ($items as $v) {
							$item = new LaundryItemsHistory;
							$item->order_laundry_history_id = $m->id;
							$item->kg = isset($v['kg']) ? $v['kg'] : "0";
							$item->satuan_nama = isset($v['satuanNama']) ? $v['satuanNama'] : "" ;
							$item->satuan_id = isset($v['satuanId']) ? $v['satuanId'] : "";
							$item->tipe_cuci_id = isset($v['tipeCuciId']) ? $v['tipeCuciId'] : "";
							$item->kategori_cuci_id = isset($v['kategoriCuciId']) ? $v['kategoriCuciId'] : "" ;
							$item->tipe_kiloan_satuan = isset($v['typeSatuanKiloan']) ? $v['typeSatuanKiloan'] : "";
							$item->harga = $v['hargaTotal'];
							$item->save();
							if( $v['typeSatuanKiloan'] == "Satuan"){
							    $jumlahSatuan++;
							}else if($v['typeSatuanKiloan'] == "Kiloan"){
							    $jumlahKiloan += $v['kg'];
							}
					}

					//cari driver denga radius yang ada di setting
					/*$radius = (int) Settings::first()["radius_driver"];
					$inKm = 6371;
					$sqlRadius = DB::Select("SELECT * FROM eo_vendor a
								  WHERE (
							          acos(sin(a.last_latitude * 0.0175) * sin(".$p['lat']." * 0.0175)
							               + cos(a.last_Latitude * 0.0175) * cos(".$p['lat']." * 0.0175) *
							                 cos((".$p['lng']." * 0.0175) - (a.last_longitude * 0.0175))
							              ) * $inKm
							      )  <= $radius and (a.is_have_order = '0' or a.is_have_order = 'no')
							      and a.is_login = '1'
							      and (is_active = '1' or is_active = 'yes')
							      and a.type_vendor_id = 3");

					$tokens = [];
					foreach ($sqlRadius as $v) {
						$tokens[] = $v->firebase_token;
					}

					if( count($tokens) <= 0 ){
						DB::rollBack();
						return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);
					}*/

					//kurangin deposit jika pembayaran saldo
					if($m->order_method == "Saldo"){
						$mSaldo = Topup::where("user_id",$m->user_id)->first();
						$mSaldo->nominal = (float) $mSaldo->nominal -  (  $m->total_price +  $m->ongkir );
						if( $mSaldo->save() ){
							//catat di history
							$mSaldoHistory = new TopupHistory;
							$mSaldoHistory->nominal = (  $m->total_price +  $m->ongkir );
							$mSaldoHistory->type = "kurang";
							$mSaldoHistory->status = "Success";
							$mSaldoHistory->user_id = $m->user_id;
							$mSaldoHistory->save();
						}
					}


					/*$outlet = DB::select("select * from eo_toko t where t.id = " . $p['outlet_id'] );
					$groupName = $m->id.$p['type_vendor']."order";
					$notifKey = PushNotif::requestDeviceGroup($groupName,$tokens);

					if( isset($notifKey["notification_key"]) ){


					$pushData = [
						"action"		=> "new_order",
						"type_order"	=> "",
						"intent"		=> "move",
						"type_vendor"	=> "Laundry",
						"id"			=> $m->id,
						"order_alamat"	=> $p['address'],
						"order_ket_lain"=> $p['ket_lain'],
						"order_lat"	    => $p['lat'],
						"order_lng"		=> $p['lng'],
						"outlet_lat"	=> $outlet[0]->latitude,
						"outlet_lng"	=> $outlet[0]->longitude,
						"outlet"		=> $outlet[0]->nama,
						"outlet_address"=> $outlet[0]->alamat,
						"ongkir"		=> $p['ongkir'],
						"km"			=> $p['km'],
						"satuan"		=> $jumlahSatuan,
						"kiloan"		=> $jumlahKiloan . " Kg"

					];

					$push = PushNotif::pushTo("Pesanan Baru Layanan Laundry","Pesanan Baru telah di terima, klik untuk detail",$notifKey["notification_key"],$pushData);
					if( count($push['success']) > 0 ){*/
					    $o = new Orders;
    					$o->order_id = $m->id;
    					$o->order_type_id = 7;
    					$o->user_id = $p['user_id'];
    					$o->save();
							DB::commit();
    					return Res::cb( $res,true,"Berhasil",["laundry" => $m]);
					/*}else{
					     return Res::cb($res,false,"Kesalahan Server",['message'=>$push]);
					}

					}else{
					    //return Res::cb($res,false,"Kesalahan Server");
							DB::rollBack();
							return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);

					}*/
			}else{
					return Res::cb( $res,false,"Gagal");
			}

		}
	}
?>
