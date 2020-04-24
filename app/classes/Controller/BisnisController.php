<?php
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\OrderServiceHistory;
	use Models\OrderServiceBarangHistory;
	use Models\OrderTabungGalonHistory;
	use Models\OrderTabungGalonItemHistory;
	use Models\Barang;
	use Models\SettingDayToko;
	use Models\SettingTimeToko;
	use Models\SettingPenjual;
	use Models\Toko;
	use Models\TokoLogin;
	use Models\User;
	use Models\Orders;
	use Models\Tagihan;
	use Models\Etalase;
	use Models\TagihanHistory;
	use Models\TopupHistoryToko;
	use Models\TopupToko;
	use Models\Topup;
	use Models\TopupHistory;
	use Models\SettingOngkirToko;
	use Models\TokoVsEkspedisi;
	use Models\Voucher;
	use Models\VoucherHistory;
	use Models\Ekspedisi;
	use Models\TopupEkspedisi;
	use Models\TopupHistoryEkspedisi;
	use Models\Poin;
	use Models\PoinHistory;
	use Models\PUser;
	use Models\Flashsale;
	use Models\FlashsaleProduk;

	use Tools\Res;
	use Tools\Helper;
	use Tools\PushNotif;

	header("Access-Control-Allow-Origin:*");
	class BisnisController{

		protected $ci;
		public $setting;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
			self::$setting = Settings::first();
		}

		public static function getGasGalon(Request $req,Response $res){
      $tokoId = $req->getAttribute("toko_id");
      $jenisJasa = $req->getAttribute("jenis_jasa");

			if( $jenisJasa==1 ) $jenisJasa = "Tabung Gas";
			else if( $jenisJasa==1 ) $jenisJasa = "Air Galon";
			else $jenisJasa = "Tabung Gas & Air Galon";

			$gas = [];$galon=[];

			if( strpos($jenisJasa,"Gas") !== false ){
					$gas = DB::select(" select g.* from eo_toko t
							inner join eo_toko_vs_gas v on v.toko_id = t.id
							inner join eo_produk_gas g on g.id = v.gas_id");
			}

			if( strpos($jenisJasa,"Galon") !== false ){
					$galon = DB::select(" select g.* from eo_toko t
							inner join eo_toko_vs_airgalon v on v.toko_id = t.id
							inner join eo_produk_airgalon g on g.id = v.airgalon_id");
			}

			$h = array_merge($gas,$galon);
      return Res::cb($res,true,"Berhasil",$h);

		}

    public static function getKategoriEtalase(Request $req,Response $res){
      $tokoId = $req->getAttribute("toko_id");
      $tk = Toko::where("id",$tokoId)->first();
      $data["kats"] = DB::select("select * from eo_kategori_barang where main_kategori_id = " . $tk->main_kategori_id . " and visible='yes' ");
      $data['etalases'] = DB::select("select * from eo_etalase where toko_id = " . $tokoId .  " and visible='yes' ");

      return Res::cb($res,true,"Berhasil",$data);
    }

    public static function getBarangForFlashsale(Request $req,Response $res){
      $p = $req->getParsedBody();
      $katId = $p["kat_id"];
      $etalaseId = $p["etalase_id"];
      $tokoId = $p["toko_id"];
      $keys = $p["keys"];

      $whEtalase = "";
      if( $etalaseId != "0" && !is_null($etalaseId) ) $whEtalase = " and etalase_id = " . $etalaseId;

      $m = DB::select("select b.*,fb.path as photo,kb.nama as kategori_nama
      from eo_barang b
      left join eo_foto_barang fb on fb.barang_id = b.id
      left join eo_kategori_barang kb on kb.id = b.kategori_barang_id
      where b.nama like '%".$keys."%' and b.toko_id = " . $tokoId . " and b.kategori_barang_id = " . $katId . $whEtalase . " group by b.id");
      return Res::cb($res,true,"Berhasil",["barang"=>$m]);
    }

    public static function getBarangAfterInputFlashsale(Request $req,Response $res){
      $p = $req->getParsedBody();
      $flashsaleId = $p["flashsale_id"];
      $tokoId = $p["toko_id"];

      $m = DB::select("select b.*,fb.path as photo,kb.nama as kategori_nama
      from eo_barang b
      left join eo_foto_barang fb on fb.barang_id = b.id
      left join eo_kategori_barang kb on kb.id = b.kategori_barang_id
      inner join eo_flashsale_produk fp on fp.barang_id = b.id and fp.flashsale_id =  $flashsaleId
      where b.toko_id = " . $tokoId . " group by b.id");

      return Res::cb($res,true,"Berhasil",["barang"=>$m]);
    }

    public static function getFlashsale(Request $req,Response $res){
      $id = $req->getAttribute("toko_id");
      $tk = Toko::where("id",$id)->first();
      if( $tk->is_global == "yes" ){ //ambil semua falshsale, tapi yan gaktif dan di terbitkan saja
        $m = DB::select("select * from eo_flashsale where  (is_global = 'yes' or kota_id = ".$tk->city_id." ) ");
      }else{ //jika tidak global ambil hanya kotanya saja
        $m = DB::select("select f.* ,k.city_name as kota_nama
              from eo_flashsale f
              left join eo_kota k on k.city_id = f.kota_id
              where  f.kota_id = ".$tk->city_id);
      }

      return Res::cb($res,true,"Berhasil",["flashsale"=>$m]);
    }

    public static function inputProdukFlashsale(Request $req,Response $res){
      $p = $req->getParsedBody();
      $tokoId = $p['toko_id'];
      $mToko = Toko::where("id",$tokoId)->first();
      $topupToko = TopupToko::where("toko_id",$tokoId)->first();

      //cek dulu apakah barang sudah ada
      $cek = FlashsaleProduk::where("barang_id",$p['barang_id'])->where("flashsale_id",$p['flashsale_id'])->first();
      $flashsale = Flashsale::where("id",$p['flashsale_id'])->first();
      $budget = $flashsale->budget;

      //cek apakah budget cukup
      if( isset($topupToko->id) ){
        if( $topupToko->nominal <  $budget )
          return Res::cb($res,false,"Maaf, Deposit Anda tidak mencukupi");
      }else{
        return Res::cb($res,false,"Maaf, Deposit Anda tidak mencukupi");
      }


      if( isset($cek["id"]) ){
        return Res::cb($res,false,"Maaf, Barang sudah di tambahkan di Flashasle ini");
      }

      $m = new FlashsaleProduk;
      $m->barang_id = $p['barang_id'];
      $m->status = "Pending";
      $m->flashsale_id = $p['flashsale_id'];
      $m->urutan = 0;
      if($m->save()){

        $historySaldo = new TopupHistoryToko;
        $historySaldo->nominal = $budget;
        $historySaldo->toko_id = $tokoId;
        $historySaldo->status = "Success";
        $historySaldo->type = "kurang";
        $historySaldo->deskripsi = "Dana Flashsale";
        $historySaldo->save();

        //update toko
        $topupToko->nominal -= $budget;
        $topupToko->save();
        return Res::cb($res,true,"Berhasil",["flashsale"=>$m]);
      }else
        return Res::cb($res,false,"Gagal Input");
    }

    public static function deleteProdukFlashsale(Request $req,Response $res){
      $p = $req->getParsedBody();
      $tokoId = $p['toko_id'];
      //cek dulu apakah barang sudah ada
      $cek = FlashsaleProduk::where("barang_id",$p['barang_id'])->where("flashsale_id",$p['flashsale_id'])->delete();
      $flashsale = Flashsale::where("id",$p['flashsale_id'])->first();
      $budget = $flashsale->budget;

      $mToko = Toko::where("id",$tokoId)->first();
      $historySaldo = new TopupHistoryToko;
      $historySaldo->nominal = $budget;
      $historySaldo->toko_id = $tokoId;
      $historySaldo->status = "Success";
      $historySaldo->type = "tambah";
      $historySaldo->deskripsi = "Pengembalian Dana Flashsale";
      $historySaldo->save();

      //update toko
      $topupToko = TopupToko::where("toko_id",$tokoId)->first();
      $topupToko->nominal += $budget;
      $topupToko->save();

      return Res::cb($res,true,"Berhasil",["flashsale"=>$m]);

    }

    public static function getFlashsaleById(Request $req,Response $res){
      $id = $req->getAttribute("id");
      $m = DB::select("select f.* ,k.city_name as kota_nama,m.nama as kategori_nama
            from eo_flashsale f
            left join eo_kota k on k.city_id = f.kota_id
            inner join eo_main_kategori m on m.id = f.main_kategori_id
            where  f.id = " . $id)[0];


      return Res::cb($res,true,"Berhasil",["flashsale"=>$m]);
    }

    public static function ubahPassword(Request $req,Response $res){
      $post = $req->getParsedBody();

      if (isset($post['password_baru']) && isset($post['toko_id']) ){

        $password 	= $post['password_baru'];
        $tokoId 	= $post['toko_id'];
        $toko = Toko::where('id',$tokoId)->first();
        $tokoLogin = TokoLogin::where("toko_id",$tokoId)->first();
        $pUser = PUser::where("id",$tokoLogin['p_user_id'])->first();

        if( isset($pUser['id']) ){
            //check password lama apakah sesuai
            if( !password_verify($post['password_lama'],$pUser['password']) ){
             return Res::cb($res,false,'Password lama tidak sesuai !');
            }

            $pUser->password = password_hash($post['password_baru'],PASSWORD_DEFAULT);
            if($pUser->save()){
              return Res::cb($res,true,"Berhasil",["toko" => $toko]);
            }else{
              return Res::cb($res,false,"Terdapat kesalahan, mohon dicoba lagi",[]);
            }
        }else{
            return Res::cb($res,false,"Toko tidak di ketahui",[]);
        }

      }
    }

		public static function changeStatus(Request $req,Response $res){
			$p = $req->getParsedBody();
			$id = $req->getAttribute('id');
			$status = $req->getAttribute('status');

			if( isset($p['service_id']) && $p['service_id'] == "4" ){
				$orderTypeId = '14';
				$m = OrderTabungGalonHistory::where("id",$id)->first();
				$b = OrderTabungGalonItemHistory::where("order_tabunggalon_history_id",$id)->first();
				$mToko = Toko::where("id",$m->outlet_id)->first();
			}else{
				$m = OrderServiceHistory::where("id",$id)->first();
				$b = OrderServiceBarangHistory::where("order_service_history_id",$m['id'])->first();
				$mToko = Toko::where("id",$b['toko_id'])->first();
				if($mToko['services_id'] == "1") $orderTypeId = "6";
				else $orderTypeId = "4";
			}



			if( $status == "Success" ){
          $message = "Pesanan Anda telah berhasil di kirim";
          $isChange = true;
          $m->status = "Complete";
          $m->status_deskripsi = "By Toko";
      }else  if( $status == "Progress" ){
          $message = "Pesanan Sedang di siapkan / proses kirim";
          $isChange = true;
          $m->status = "Progress";
      }else{
          $message = "Pesanan Anda gagal di kirim";
          $isChange = true;
          $m->status = "Cancel";
          $m->alasan = $p["alasan"];
          $m->status_deskripsi = "By Toko";
      }

      $dataPush = [
				"action" => "update_status_order_barang",
				"history_id" => $id,
				"intent" => "move"
			];

      $user = User::where("id",$m['user_id'])->first();
    	$push = PushNotif::pushTo($message,"Klik untuk detail",$user['firebase_token'],$dataPush);

    		    if( $m->save() ){
								//jika pesanan batal, maka kembalikan sharing profit
								if( $m->status == "Cancel" ){
										$besarBagiHasil = $mToko['nominal_bagi_hasil'];
										$tipeBagihasil = $mToko['tipe_bagi_hasil'];
										$tipeBagihasilAmount = $mToko['tipe_bagi_hasil_amount'];

										if( $tipeBagihasil=="Prosentase" ){
											$ambil = ($m->price * $besarBagiHasil)/100;
											$historySaldo = new TopupHistoryToko;
											$historySaldo->nominal = $ambil;
											$historySaldo->toko_id = $mToko['id'];
											$historySaldo->status = "Success";
											$historySaldo->type = "tambah";
											$historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) karena transaksi di batalkan oleh toko, no. transaksi #" . $m->id;
											$historySaldo->save();

											//update toko
											$topupToko = TopupToko::where("toko_id",$mToko['id'])->first();
											$topupToko->nominal += $ambil;
											$topupToko->save();

									  }

										//kembalikan slado ke pemilik Saldo
										//catat di history oemakain saldi
										if( $m->order_method == "Saldo" ){

											$topupUserH = new TopupHistory;
											$topupUserH->type = "tambah";
											$topupUserH->nominal = (float) $m->price + (float) $m->price_antar;
											$topupUserH->status = "Success";
											$topupUserH->alasan = "Pembatalan pembelian oleh user, no transaksi #".$m->id;
											$topupUserH->user_id = $m->user_id;
											if($topupUserH->save() ){
												$topupUser = Topup::where("user_id",$m->user_id)->first();
												$topupUser->nominal = (float) $topupUser->nominal + $topupUserH->nominal;
												$topupUser->save();
											}

										}



										//jika pakai voucher, maka ubah voucher mencaji cancel, dan beri deksripsi
										if( $m->voucher_kode != "" && !is_null($m->voucher_kode) ){
											$vh = VoucherHistory::where("voucher_kode",$m->voucher_kode)
											->where("user_id",$m->user_id)
											->where("order_service_history_id",$m->id)
											->where("toko_id",$mToko['id'])->where("status","Success")->first();
											$vh->status = "Cancel";
											$vh->deskripsi = "Transaksi di batalkan";
											$vh->save();
										}


                  //kembalikan stok jika real
                  $bh = OrderServiceBarangHistory::where("order_service_history_id",$m['id'])->get();
                  foreach($bh as $v){
                    $cb = Barang::where("id",$v['barang_id'])->first();
                    if( $cb["tipe_stock"] == "Real" ){
                      $cb->stock += $v['qty'];
                      $cb->save();
                    }
                  }

                  //kembalikan ongkir
                  if( $m->is_antar_sendiri=='yes' ){
                    $besarBagiHasil = $mToko->nominal_bagi_hasil_ongkir;
                    $ambil = ($m->price_antar * $besarBagiHasil)/100;
                    $historySaldo = new TopupHistoryToko;
                    $historySaldo->nominal = $ambil;
                    $historySaldo->toko_id = $mToko['id'];
                    $historySaldo->status = "Success";
                    $historySaldo->type = "tambah";
                    $historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) ongkir karena transaksi di batalkan oleh toko, no. transaksi #" . $m->id;
                    $historySaldo->save();

                    //update toko
                    $topupToko = TopupToko::where("toko_id",$mToko['id'])->first();
                    $topupToko->nominal += $ambil;
                    $topupToko->save();
                  }else{
                      /*$e = Ekspedisi::where("id",$m->ekspedisi_id)->first();
                      $besarBagiHasil = $e['nominal_bagi_hasil'];
                      $ambil = ($m->price_antar * $besarBagiHasil)/100;
                      $historySaldo = new TopupHistoryEkspedisi;
                      $historySaldo->nominal = $ambil;
                      $historySaldo->ekspedisi_id = $e->id;
                      $historySaldo->status = "Success";
                      $historySaldo->type = "tambah";
                      $historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) ongkir karena transaksi di batalkan oleh toko, no. transaksi #" . $m->id;
                      $historySaldo->save();

                      //update ekspedisi
                      $topupEkspedisi = TopupEkspedisi::where("ekspedisi_id",$e->id)->first();
                      if( !isset($topupEkspedisi["ekspedisi_id"]) ){
                        $topupEkspedisi = new TopupEkspedisi;
                        $topupEkspedisi->ekspedisi_id = $e->id;
                        $topupEkspedisi->nominal = 0;
                      }
                      $topupEkspedisi->nominal += $ambil;
                      $topupEkspedisi->save();*/
                  }

								}

    		        //update eo_ordes
    		        $o = Orders::where("order_id",$id)->where("order_type_id",$orderTypeId)->first();
    		        if( $m['status'] == "Success"  ||  $m['status'] == "Complete" ){
    		            $o['status'] = "Complete";

                   //cek poin
                    if( $m->poin > 0 ){
                      $poinHistory = new PoinHistory;
                      $poinHistory->user_id = $m->user_id;
                      $poinHistory->poin = $m->poin;
                      $poinHistory->ket = "dapat poin dari no. transaksi #" . $m->id;
                      $poinHistory->save();

                      $poin = Poin::where("user_id",$m->user_id)->first();
                      if( !isset($poin['user_id']) ){
                        $poin = new Poin;
                        $poin->user_id = $m['user_id'];
                      }
                      $poin->poin += $m['poin'];
                      if( $poin->save() ){
                          //kirim notifikasi ke user
                          $dataPushPoin = [
                            "action" => "get_poin",
                            "intent" => "move"
                          ];
                          $push = PushNotif::pushTo("Selamat","Anda dapat poin ",$user['firebase_token'],$dataPushPoin);
                      }

                    }
    		            //jika complete dan pakai saldo
    		            if($m['order_method'] == 'Saldo'){
                        //insert into tagihan
    		                $tagihan = Tagihan::where('toko_id',$b['toko_id'])->first();
    	                    if( !isset($tagihan['toko_id']) ){
    	                        $tagihan = new Tagihan;
    	                        $tagihan->nominal = 0;
    	                    }

    	                    $tagihan->toko_id = $b['toko_id'];
    	                    $tagihan->nominal += $m['price'];
    	                    if(!$tagihan->save()){
    	                        return Res::cb($res,false,"Gagal 4");
    	                    }


    	                  $tagihanH = new TagihanHistory;
    		                $tagihanH->nominal = $m['price'];
    		                $tagihanH->created_at = date('Y-m-d H:i:s');
    		                $tagihanH->updated_at = date('Y-m-d H:i:s');
    		                $tagihanH->tagihan_id = $tagihan->id;
    		                $tagihanH->order_id = $m->id;
    		                if(!$tagihanH->save()){
    	                       	return Res::cb($res,false,"Gagal 3");
    	                    }else{
    	                    	$o->save();
    	                    	return Res::cb($res,true,"Ubah status berhasil");
    	                    }

    		            }
    		        }else if( $m['status'] == "Progress" ){
    		            $o['status'] = "Progress";
    		        }else{
    		             $o['status'] = "Cancel";
    		        }

    		        if(!$o->save()){
    		            return Res::cb($res,false,"Gagal Mengubah Status, kode : 101");
    		        }else
    		             return Res::cb($res,true,"Berhasil");
                }else{
                    return Res::cb($res,false,"Gagal Mengubah Status, kode : 102");
                }

		}

		public static function getDashboard(Request $req,Response $res){
			$tokoId = $req->getAttribute("toko_id");

			$toko = Toko::where("id",$tokoId)->first();

			//total produk
			$tp = Barang::where("toko_id",$tokoId)->get();

			//total produk terjual
			$tpj = 0;
			$q0 = DB::Select("select qty from eo_order_service_barang_history h
							inner join eo_order_service_history sh on sh.id = h.order_service_history_id
							and (sh.status = 'Complete' or sh.status = 'Success')

							where h.toko_id = ".$tokoId."
			 ");
			foreach( $q0 as $v ){
				$tpj += (int) $v->qty;
			}

			//total produk terjual bulan
			$bln = date('Y-m');
			$tptb = 0;
			$q = DB::Select("select qty from eo_order_service_barang_history h
							inner join eo_order_service_history sh on sh.id = h.order_service_history_id
								and (sh.status = 'Complete' or sh.status = 'Success')

							where h.toko_id = ".$tokoId."
			 				and date_format(sh.created_at,'%Y-%m') = '".$bln."'

			 ");



			 foreach( $q as $v ){
				$tptb += (int) $v->qty;
			}

			 //total produk terjual hari
			$hari = date('Y-m-d');
			$tpth = 0;
			$q1 = DB::Select("select qty from eo_order_service_barang_history h
							inner join eo_order_service_history sh on sh.id = h.order_service_history_id
							and (sh.status = 'Complete' or sh.status = 'Success')

							where h.toko_id = ".$tokoId."
			 				and date_format(sh.created_at,'%Y-%m-%d') = '".$hari."'
			 ");



			 foreach( $q1 as $v ){
				$tpth += (int) $v->qty;
			}

			return Res::cb($res,true,"Berhasil",
				[
					"total_produk" => count($tp),
					"total_produk_terjual" => $tpj,
					"terjual_bulan" => $tptb,
					"terjual_hari" => $tpth
				]
			);

		}

		public static function getOrderByStatus(Request $req,Response $res) {
			$status = $req->getAttribute("status");
			$tokoId = $req->getAttribute("toko_id");

			$orders = DB::select("select h.*,u.nama as nama_user, date_format(h.created_at,'%H:%i:%s') as time,
			date_format(h.created_at,'%Y-%m-%d') as tgl, 'Belanja' as type
			from eo_order_service_history h
			inner join eo_order_service_barang_history b on b.toko_id = $tokoId
				and b.order_service_history_id = h.id
			inner join eo_user u on u.id = h.user_id
			where h.status = '".$status."' group by b.order_service_history_id order by b.created_at desc");

			return Res::cb($res,true,"Berhasil",["orders" => $orders]);

		}

		public static function getOrderGalonGasByStatus(Request $req,Response $res) {
			$status = $req->getAttribute("status");
			$tokoId = $req->getAttribute("toko_id");

			$orders = DB::select("select h.*,u.nama as nama_user, date_format(h.created_at,'%H:%i:%s') as time,
			date_format(h.created_at,'%Y-%m-%d') as tgl, 'Belanja' as type
			from eo_order_tabunggalon_history h
			inner join eo_order_tabunggalon_items_history b on b.order_tabunggalon_history_id = h.id
			inner join eo_user u on u.id = h.user_id
			where  h.outlet_id = $tokoId and  h.status = '".$status."' group by b.order_tabunggalon_history_id order by b.created_at desc");

			return Res::cb($res,true,"Berhasil",["orders" => $orders]);

		}

		public static function getEtalase(Request $req,Response $res){
			$id = $req->getAttribute("toko_id");
			$e = Etalase::where("toko_id",$id)->get();
			return Res::cb($res,true,"Berhasil",["etalase" => $e]);
		}

		public static function updateEtalase(Request $req,Response $res){
			$p = $req->getParsedBody();
			if(isset($p['id']))
				$e = Etalase::where("id",$p['id'])->first();
			else
				$e = new Etalase;

			$e->nama = $p['nama'];
			$e->visible = $p['visible'];
			$e->toko_id = $p['toko_id'];
			if( $e->save() ){
					return Res::cb($res,true,"Berhasil");
			}else{
				return Res::cb($res,false,"Berhasil");
			}

		}

		public static function getVoucher(Request $req,Response $res){
			$id = $req->getAttribute("toko_id");
      $m = DB::select("select v.*,
        v.max_penggunaan -
        (select count(1) from eo_voucher_history h
                  where h.toko_id = v.toko_id and v.kode = h.voucher_kode and h.toko_id = $id and h.status = 'Success' ) as sisa
        from eo_voucher v where v.toko_id =  " . $id);
			return Res::cb($res,true,"Berhasil",["voucher" => $m]);
		}

		public static function getHistoryVoucher(Request $req,Response $res){
			$id = $req->getAttribute("toko_id");
			$m = DB::select("select h.*,u.nama as nama_user
                      from eo_voucher_history h
                      inner join eo_user u on u.id = h.user_id where h.toko_id = " . $id);
			return Res::cb($res,true,"Berhasil",["history" => $m]);
		}

		public static function updateVoucher(Request $req,Response $res){
			$p = $req->getParsedBody();
			if($p['id'] != "0")
				$e = Voucher::where("id",$p['id'])->first();
			else{
        //cek jika ada okde yang sama
        $v = Voucher::where("kode",$p['kode'])->where("toko_id",$p['toko_id'])->first();
        if( !is_null($v) ) return Res::cb($res,false,"Kode tidak boleh sama");
        $e = new Voucher;
      }

			$e->kode = $p['kode'];
			$e->max_per_user = $p['max_per_user'];
			$e->max_penggunaan = $p['max_penggunaan'];
			$e->tipe = $p['tipe'];
			$e->nominal = $p['nominal'];
      $e->toko_id = $p['toko_id'];
			if( $e->save() ){
					return Res::cb($res,true,"Berhasil");
			}else{
				return Res::cb($res,false,"Berhasil");
			}

		}

		public static function getReport(Request $req,Response $res) {
			$tokoId = $req->getAttribute("toko_id");
			$post = $req->getParsedBody();
			$dateFrom = $post['date_from'];  //yyyy-mm-dd
			$dateTo = $post['date_to'];		//yyyy-mm-dd
			$toko = Toko::where("id",$tokoId)->first();

			$whStatus = "";
			if( isset($post['status']) ){
				if( $post['status']   == "Sukses") $status = "Complete";
				else $status  = "Cancel";

				$whStatus = " and h.status =  '".$status."'  " ;
			}

			if( $toko->services_id == "4" ){
				$orders = DB::select("select h.*,u.nama as nama_user,date_format(h.created_at,'%H:%i:%s') as time,
				date_format(h.created_at,'%Y-%m-%d') as tgl, 'Belanja' as type
				from eo_order_tabunggalon_history h
				inner join eo_order_tabunggalon_items_history b on b.order_tabunggalon_history_id = h.id
				inner join eo_user u on u.id = h.user_id
				where date_format(h.created_at,'%Y-%m-%d') between '".$dateFrom."' and '".$dateTo."'
				$whStatus
				group by b.order_tabunggalon_history_id");
			}else{
				$orders = DB::select("select h.*,u.nama as nama_user,date_format(h.created_at,'%H:%i:%s') as time,
				date_format(h.created_at,'%Y-%m-%d') as tgl, 'Belanja' as type
				from eo_order_service_history h
				inner join eo_order_service_barang_history b on b.toko_id = $tokoId
					and b.order_service_history_id = h.id and b.is_deleted = 'no'
					inner join eo_user u on u.id = h.user_id
				where date_format(h.created_at,'%Y-%m-%d') between '".$dateFrom."' and '".$dateTo."'
					$whStatus
				group by b.order_service_history_id");
			}




			return Res::cb($res,true,"Berhasil",["orders" => $orders]);

		}

		public static function getSetting(Request $req,Response $res) {
			$tokoId = $req->getAttribute("toko_id");
			$day = 	DB::select("select * from eo_setting_day_toko p
								where p.toko_id = $tokoId");

			$time = 	DB::select("select * from eo_setting_time_toko p
								where p.toko_id = $tokoId");

			$tk = Toko::where("id",$tokoId)->first();
			$ret = ["day" => $day[0],"time"=>$time];
			$ret['toko'] = $tk;
			$ret['setting_toko'] = SettingPenjual::where("toko_id",$tokoId)->first();
			$ret['ongkir'] = SettingOngkirToko::where("toko_id",$tokoId)->first();
			if( is_null($ret['ongkir']) ){
				//insert baru
				$o = new SettingOngkirToko;
				$o->tipe = "Jarak";
				$o->harga_km_selanjutnya = "1000";
				$o->km_pertama = "10";
				$o->harga_km_pertama = "5000";
				$o->harga_per_kg = "50";
				$o->icon = "";
				$o->toko_id = $tokoId;
				$o->is_active = "yes";
				$o->save();
				$ret['ongkir'] = SettingOngkirToko::where("toko_id",$tokoId)->first();
			}

			return Res::cb($res,true,"Berhasil",$ret);
		}

		public static function updateSettingEkspedisi(Request $req,Response $res){
		   $tokoId = $req->getAttribute("toko_id");
		   $eksId = $req->getAttribute("ekspedisi_id");
		   $visible = $req->getAttribute("visible");
		   $m = TokoVsEkspedisi::where("ekspedisi_id",$eksId)->where("toko_id",$tokoId)->first();
		   if( is_null($m) ){
		       $m = new TokoVsEkspedisi;
		       $m->toko_id = $tokoId;
		       $m->ekspedisi_id = $eksId;
		       $m->visible = $visible;
		   }else{
		       $m->visible = $visible;
		   }

		   if($m->save()){
		       return Res::cb($res,true,"Berhasil");
		   }else{
		       return Res::cb($res,false,"Kesalahan server, hub. Admin");
		   }

		}

		public static function getHistoryTopup(Request $req,Response $res){
		   $tokoId = $req->getAttribute("toko_id");
		   $m = DB::select("select * from eo_topup_history_toko where toko_id = " . $tokoId . " and created_at like '%".date('Y-m-d')."%' order by created_at desc");
		   return Res::cb($res,true,"Berhasil",["history"=>$m]);
		}

		public static function getEkspedisi(Request $req,Response $res){
		   $tokoId = $req->getAttribute("toko_id");
		   $m = DB::select("select e.*,
		   case when e.id is not null then t.visible else 'no' end as visible
		   from eo_ekspedisi e
		   left join eo_toko_vs_ekspedisi t on t.ekspedisi_id = e.id and  t.toko_id = " . $tokoId . " and e.visible='yes'
		   where e.id != 1");
		   return Res::cb($res,true,"Berhasil",["ekspedisi"=>$m]);
		}

		public static function updateSetting(Request $req,Response $res) {
			$tokoId = $req->getAttribute("toko_id");
			$p = $req->getParsedBody();
			//return Res::cb($res,false,json_encode($p),$p);
			//var_dump($p);die();

            $ongkir = SettingOngkirToko::where("toko_id",$tokoId)->first();
            $ongkir->tipe = $p['tipe'];
            $ongkir->is_active = $p['is_active'];
            $ongkir->km_pertama = $p['km_pertama'];
            $ongkir->harga_km_pertama = $p['harga_km_pertama'];
            $ongkir->harga_km_selanjutnya = $p['harga_km_selanjutnya'];
            $ongkir->harga_per_kg = $p['harga_per_kg'];
            $ongkir->save();


            $set = SettingPenjual::where("toko_id",$tokoId)->first();
            $set->master_deskripsi = $p['master_deksripsi'];
            $set->master_deskripsi_pengiriman = $p['master_deksripsi_pengiriman'];
            $set->save();

			$sp = SettingDayToko::where("toko_id",$tokoId)->first();
			$sp['h_senin'] 	= $p['senin'];
			$sp['h_selasa'] = $p['selasa'];
			$sp['h_rabu'] 	= $p['rabu'];
			$sp['h_kamis'] 	= $p['kamis'];
			$sp['h_jumat'] 	= $p['jumat'];
			$sp['h_sabtu'] 	= $p['sabtu'];
			$sp['h_ahad'] 	= $p['minggu'];
			$sp->save();


			//simpan time
			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","1")->first();
			$st->waktu_mulai = $p['senin_buka'];
			$st->waktu_akhir = $p['senin_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","2")->first();
			$st->waktu_mulai = $p['selasa_buka'];
			$st->waktu_akhir = $p['selasa_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","3")->first();
			$st->waktu_mulai = $p['rabu_buka'];
			$st->waktu_akhir = $p['rabu_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","4")->first();
			$st->waktu_mulai = $p['kamis_buka'];
			$st->waktu_akhir = $p['kamis_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","5")->first();
			$st->waktu_mulai = $p['jumat_buka'];
			$st->waktu_akhir = $p['jumat_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","6")->first();
			$st->waktu_mulai = $p['sabtu_buka'];
			$st->waktu_akhir = $p['sabtu_tutup'];
			$st->save();

			$st = SettingTimeToko::where("toko_id",$tokoId)->where("setting_day_id","7")->first();
			$st->waktu_mulai = $p['minggu_buka'];
			$st->waktu_akhir = $p['minggu_tutup'];
			$st->save();

			return Res::cb($res,true,"Berhasil");

		}

	}
?>
