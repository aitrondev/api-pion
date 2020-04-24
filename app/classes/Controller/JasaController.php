<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\OrderJasaHistory;
	use Models\OrderJasaItemsHistory;
	use Models\Jasa;
	use Models\Orders;

	use Tools\Res;
	use Tools\Helper;



	class JasaController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getNearestOutlet(Request $req,Response $res){
			$p = $req->getParsedBody();
			$lat = $p['lat'];
			$lng = $p['lng'];
			$tipe = $p['tipe'];
			if( $tipe == "tabunggas_airgalon" ){
				$tipe = "Tabung Gas & Air Galon";
			}else if( $tipe == "tabunggas" ){
				$tipe = "Tabung Gas";
			}else if( $tipe == "airgalon" ){
				$tipe = "Air Galon";
			}
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

							where  a.services_id = 4 and (a.jenis_jasa = 'Tabung Gas' or a.jenis_jasa = '".$tipe."' ) order by jarak
							");
			if( count($m) > 0 ) $m = $m[0];

			return Res::cb( $res,true,"Berhasil",["outlet" => $m] );
		}

		public function getAll(Request $req, Response $res){
			$m = Jasa::where("visible","yes")->get();
			return Res::cb($res,true,'Berhasil',["jasa"=>$m]);
		}

		public static function itemGas(Request $req, Response $res){
			$m = DB::select("select * from eo_produk_gas");
			return Res::cb($res,true,'Berhasil',["gas"=>$m]);
		}

		public static function itemGalon(Request $req, Response $res){
			$m = DB::select("select * from eo_produk_airgalon");
			return Res::cb($res,true,'Berhasil',["galon"=>$m]);
		}

		public static function historyById(Request $req,Response $res){
			$id = $req->getAttribute("id");
			$m = DB::Select("select t.ekspedisi_nama,tk.nama as outlet,t.*,u.nama as nama_user,v.nama as nama_vendor,v.photo as foto_vendor,v.photo as foto_driver,
			v.nama as nama_driver
											from eo_order_jasa_history t
											left join eo_vendor v on v.id = t.vendor_id
											inner join eo_user u on u.id = t.user_id
                      inner join eo_toko tk on tk.id = t.outlet_id
											where t.id = " . $id );

			$i = DB::Select("select * from eo_order_jasa_items_history where order_jasa_history_id = " . $id);
			$m[0]->items = $i;
			return Res::cb( $res,true,"Berhasil",['history' => $m[0]] );
		}

		public static function order(Request $req, Response $res){
			DB::beginTransaction();
			$p = $req->getParsedBody();
			$m = new OrderJasaHistory;
			$m->user_id = $p["user_id"];
			$m->outlet_id = $p["outlet_id"];
			$m->jasa_id = 10;
			$m->order_lat = $p["order_lat"];
			$m->order_lng = $p["order_lng"];
			$m->order_ket_lain = $p["order_ket_lain"];
			$m->order_method = $p["order_method"];
			$m->order_address = $p["order_address"];
			$m->order_address = $p["order_address"];
			$m->biaya_jasa = $p["biaya_jasa"];
			$m->ongkir = $p["ongkir"];
			$m->total = $p["total"];
			$m->km = $p["km"];
			$m->voucher_kode = $p["voucher_kode"];
			$m->voucher_nominal = $p["voucher_nominal"];
			$m->voucher_tipe = $p["voucher_tipe"];
			$m->total_price_after_voucher = $p["total_price_after_voucher"];
			$m->biaya_gas = $p["biaya_gas"];
			$m->biaya_galon = $p["biaya_galon"];
			$m->ekspedisi_id = $p["ekspedisi_id"];
			$m->ekspedisi_nama = $p["ekspedisi_nama"];
			if( $m->save() ){
				$items = json_decode( $p["items"] ,true);
				foreach( $items as $i ){
					$item = new OrderJasaItemsHistory;
					$item->total_berat = $i['berat'] ;
					$item->total_harga =  $i['harga'];
					$item->item_id =  $i['itemId'];
					$item->nama =  $i['nama'];
					$item->qty =  $i['qty'];
					$item->tipe =  $i['tipe'];
					$item->order_jasa_history_id =  $m->id;
					$item->save();
				}

				$o = new Orders;
				$o->order_type_id = 14;
				$o->order_id = $m->id;
				$o->status = "Pending";
				$o->user_id = $p['user_id'];
				$o->save();

				DB::commit();
				return Res::cb($res,true,'Berhasil',$m);
			}
		}
	}


?>
