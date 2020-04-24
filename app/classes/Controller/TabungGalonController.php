<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Tools\Helper;

	use Models\OrderTabungGalonHistory;
	use Models\OrderTabungGalonItemHistory;
	use Models\Orders;
	use Models\Toko;
	use Models\Saldo;
	use Models\TopupHistoryToko;
	use Models\TopupToko;



	class TabungGalonController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getGasByToko(Request $request, Response $response){
      $tokoId = $request->getAttribute("toko_id");
			$m = DB::Select("select g.nama,g.icon,g.id,g.harga,'Tabung Gas' as tipe,g.berat
                      from eo_toko_vs_gas v
                      inner join eo_produk_gas g on g.id = v.gas_id
                      where v.toko_id = " . $tokoId);
			return Res::cb($response,true,'Berhasil',["results" => $m]);
		}

    public static function getGalonByToko(Request $request, Response $response){
      $tokoId = $request->getAttribute("toko_id");
			$m = DB::Select("select g.*,'Air Galon' as tipe,g.berat
                      from eo_toko_vs_airgalon v
                      inner join eo_produk_airgalon g on g.id = v.airgalon_id
                      where v.toko_id = " . $tokoId);
			return Res::cb($response,true,'Berhasil',["results" => $m]);
		}

    public static function getListOutlet(Request $req,Response $res){
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
                  SUBSTRING_INDEX(st.waktu_mulai,':',2) waktu_mulai,SUBSTRING_INDEX(st.waktu_akhir,':',2) waktu_akhir,
                  a.jenis_jasa
                  FROM eo_toko a

              inner join eo_setting_day_toko sd on sd.toko_id = a.id
              inner join eo_setting_time_toko st on st.toko_id = a.id and setting_day_id = ".date('N')."
              where  a.services_id = 4
                    and (a.jenis_jasa = 'Tabung Gas & Air Galon' or a.jenis_jasa = 'Tabung Gas' or a.jenis_jasa = 'Air Galon')
										and a.visible='yes'
              order by jarak
              ");

      foreach( $m as $t ){
        //get satuan
        $t->satuans[] = [ "nama"=>$t->jenis_jasa,"id"=>"1","harga"=> "0" ];
      }


      return Res::cb( $res,true,"Berhasil",["outlet" =>$m] );
    }

		public static function getHistory(Request $req,Response $res){
        $id = $req->getAttribute("id");

        $m = DB::select("select h.*,t.nama as outlet from eo_order_tabunggalon_history h
												inner join eo_toko t on t.id = h.outlet_id
												where h.id = " . $id)[0];
        //get items
        $i = DB::select("select * from eo_order_tabunggalon_items_history where order_tabunggalon_history_id = " . $id);
        $m->items = $i;

        return Res::cb( $res,true,"Berhasil",["history" => $m]);

    }

		public static function historyBisnisById(Request $req,Response $res){
			$id = $req->getAttribute("id");
			$tokoId = $req->getAttribute("toko_id");
			$toko  = Toko::where("id",$tokoId)->first();

			try{
					$barang = DB::Select("select
											bh.item_nama as nama,
											bh.item_id as barang_id,
											bh.qty,
											bh.harga_total as total,
											bh.berat_total as berat,
											'is' as isi,
											'tipe isi' as tipe_isi,
											'' as note,
											'no' is_deleted,
											bh.item_nama as kd_print,

											t.nama as toko_nama,
											t.alamat_jalan as toko_alamat,
											t.alamat_jalan as toko_alamat_jalan,
											t.alamat as toko_alamat_lengkap,
											t.toko_alamat_gmap as toko_alamat_gmap,
											t.latitude as toko_lat,
											t.longitude as toko_lng,
											'' as kategori_nama,
											(select icon from eo_produk_gas fb where fb.id = bh.item_id order by id desc limit 1) as photo

											from eo_order_tabunggalon_items_history bh
											inner join eo_toko t on t.id = $tokoId


											where bh.order_tabunggalon_history_id = $id ");

						$order = DB::select("
							select '".$toko->nama."' as toko_nama,'".$toko->alamat."' as toko_alamat,
							'".$toko->toko_alamat_gmap."' as toko_alamat_gmap, '".$toko->alamat."' as toko_alamat_lengkap,
							'".$toko->alamat_jalan."' as toko_alamat_jalan,
							".$toko->latitude." as toko_lat, ".$toko->longitude." as toko_lng,

							u.no_telp as no_telp_user,u.alamat as alamat_user,
							ekspedisi_nama as ekspedisi,
							sh.*,ongkir as price_antar,
							sh.total_price as price,
							o.order_type_id as type_order,u.no_telp,u.nama as nama_user,
							order_method as payment_method

							from eo_order_tabunggalon_history sh
							inner join eo_orders o on o.order_id = sh.id and o.order_type_id in(14)
							inner join eo_user u on u.id = sh.user_id
							where sh.id = $id
						");

				if( count($barang) > 0 && count($order) > 0  ){
					$history["order"] = $order[0];
					$history["barang"] = $barang;
					$jamAntar = [];
					$jam = DB::Select("select * from eo_setting_time_toko where toko_id = " . $tokoId . " and setting_day_id = " . date("N"));
					foreach ($jam as $v) {
						$jamAntar[] = [
								"id" => $v->id,"waktu" => $v->waktu_mulai . " s/d " . $v->waktu_akhir,
								"day" => $v->setting_day_id,
								"date_mulai" => date('Ymd') . " ". $v->waktu_mulai,
								"date_akhir" => date('Ymd') . " ". $v->waktu_akhir,
								"date_sekarang" => date('Ymd H:i:s'),
							];
					}
					$history["jam_antar"] = $jamAntar;
					$history['master_deskripsi_pengiriman'] = DB::select("select  * from eo_setting_penjual where toko_id = " .  $tokoId )[0]->master_deskripsi_pengiriman;
				}else{
					$history["order"] = [];
						$history["barang"] = [];
				}





				return Res::cb($res,true,"Berhasil",['history' => $history]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}


		public static function order(Request $req,Response $res){
			DB::beginTransaction();
			$p = $req->getParsedBody();
			//$set = LaundrySetting::first();
			//var_dump($p);die();
			$m = new OrderTabungGalonHistory;
			$m->user_id = $p['user_id'];
			$m->order_lat = $p['order_lat'];
			$m->order_lng = $p['order_lng'];
			$m->order_ket_lain = $p['ket_lain'];
			$m->order_method = $p['order_method'];
			$m->order_alamat = $p['address'];
			$m->total_price = $p['biaya_total'];
			$m->ongkir = $p['ongkir'];
			$m->voucher_kode = $p['voucher_kode'];
			if( !is_null($p['voucher_kode']) )
				$m->is_using_voucher = "no";
			$m->voucher_nominal = $p['voucher_nominal'];
			$m->voucher_tipe = $p['voucher_tipe'];
			$m->biaya_tabung = $p['biaya_tabung'];
			$m->biaya_galon = $p['biaya_galon'];
			$m->total_price_after_voucher = $p['total_price_after_voucher'];
			$m->km = $p['km'];
			$m->outlet_id = $p['outlet_id'];
			$m->vendor_id = 0;
			$m->ekspedisi_id = $p['ekspedisi_id'];
			$m->ekspedisi_nama = $p['ekspedisi_nama'];
			$m->total_berat = $p['total_berat'];
      $mToko = Toko::where("id",$p['outlet_id'])->first();
			if( $p['ekspedisi_nama'] == "Jasa " . $mToko['nama'] ){
				$isAntarSendiri=true;
				$m->is_antar_sendiri="yes";
			}
      $totalQty=0;
			if($m->save()){
					//save item
					$items = json_decode($p['items'],true);
					foreach ($items as $v) {
							$item = new OrderTabungGalonItemHistory;
							$item->order_tabunggalon_history_id = $m->id;
							$item->item_id = $v['id'];
							$item->item_nama = $v['satuanNama'];
							$item->harga_total = $v['hargaTotal'];
							$item->tipe = $v['typeGasGalon'];
							$item->qty = $v['qty'];
              $totalQty+=$v['qty'];
							$item->save();
					}


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

					//jika antar sendiri
					if($isAntarSendiri){
						$besarBagiHasil = $mToko['nominal_bagi_hasil_ongkir'];
						$ambil = ($p['ongkir'] * $besarBagiHasil)/100;
						$historySaldo = new TopupHistoryToko;
						$historySaldo->nominal = $ambil;
						$historySaldo->toko_id = $mToko['id'];
						$historySaldo->status = "Success";
						$historySaldo->type = "kurang";
						$historySaldo->deskripsi = "Pengurangan transaksi (bagi hasil) ongkir, no. transaksi #" . $m->id;
						$historySaldo->save();

						//update toko
						$topupToko = TopupToko::where("toko_id",$mToko->id)->first();
						if( !isset($topupToko["toko_id"]) ){
							$topupToko = new TopupToko;
							$topupToko->toko_id = $mToko->id;
						}
						$topupToko->nominal -= $ambil;
						$topupToko->save();
					}

          //bagi hasil
          $besarBagiHasil = $mToko['nominal_bagi_hasil'];
          $tipeBagihasil  = $mToko['tipe_bagi_hasil'];
          $tipeBagihasilAmount = $mToko['tipe_bagi_hasil_amount'];
          if( $tipeBagihasil=="Amount" ){
            if( $tipeBagihasilAmount == "Peritem" ){
                $ambil = $totalQty * $besarBagiHasil;

                $historySaldo = new TopupHistoryToko;
                $historySaldo->nominal = $ambil;
                $historySaldo->toko_id = $mToko['id'];
                $historySaldo->status = "Success";
                $historySaldo->type = "kurang";
                $historySaldo->deskripsi = "Pengurangan transaksi (bagi hasil), no. transaksi #" . $m->id;
                $historySaldo->save();

                //update toko
                $topupToko = TopupToko::where("toko_id",$mToko->id)->first();
                if( !isset($topupToko["toko_id"]) ){
                  $topupToko = new TopupToko;
                  $topupToko->toko_id = $mToko->id;
                }
                $topupToko->nominal -= $ambil;
                $topupToko->save();

            }
          }

					$o = new Orders;
					$o->order_id = $m->id;
					$o->order_type_id = 14;
					$o->user_id = $p['user_id'];
					$o->save();
					DB::commit();
					return Res::cb( $res,true,"Berhasil",["tabung_galon" => $m]);

			}else{
					return Res::cb( $res,false,"Gagal");
			}

		}

}
