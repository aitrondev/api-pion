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
	use  Models\VoucherHistory;
	use  Models\Voucher;
	use  Models\TopupHistoryToko;
	use  Models\TopupToko;
	use  Models\TopupEkspedisi;
	use  Models\TopupHistoryEkspedisi;
	use  Models\Ekspedisi;

	use Tools\Res;
	use Tools\Helper;
	use Tools\PushNotif;


	class BarangController{

		protected $ci;
		public $setting;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
			self::$setting = Settings::first();
		}

		public static function getBestSeller(Request $req,Response $res){
			$p = $req->getParsedBody();
			$mainKatId = $req->getAttribute("main_kategori_id");
			$userId = $req->getAttribute("user_id");
			$nameDay = Helper::getNameDayInd(date("N"));

			$sort = $p['sort'];
			$orderBy = "";
			if( $sort == "A to Z" )
				$orderBy = " b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = " b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = " b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = " b.created_at desc";

			$tipe = $req->getAttribute("tipe");
			$limit = $tipe == "All" ? "" : "limit 5";
			/*$m = DB::select("select b.*,t.nama as nama_toko,
												(select path from eo_foto_barang where barang_id = b.id limit 1) as photo,
												case when f.id is null then 'no' else 'yes' end as is_favorite
												from v_bestseller vb
												inner join eo_barang b on b.id = vb.barang_id and b.visible='yes'
												left join eo_favorite f on f.tipe='Barang' and  f.from_id = b.id and f.user_id = $userId
												inner join eo_toko t on t.id = b.toko_id and t.main_kategori_id = $mainKatId order by $orderBy " . $limit );*/

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
						b.kondisi,
						b.discount,
						b.berat,
						b.viewer,
						b.kategori_barang_id,
						b.tipe_stock,
						b.tipe_isi,
						b.isi,
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
						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
						from eo_barang b

						inner join v_bestseller vb on vb.barang_id = b.id
						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
						inner join eo_setting_penjual p on p.toko_id = t.id
						inner join eo_setting_day_toko sd on sd.toko_id = t.id
						inner join eo_kategori_barang kb on kb.main_kategori_id = $mainKatId and kb.id = b.kategori_barang_id
						left join eo_favorite f on f.tipe='Barang' and f.from_id = b.id and f.user_id = $userId

						where  b.visible = 'yes' order by " . $orderBy  . " " . $limit
				);

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
								$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
						}
						$b->days = $hariBuka;

					$newBarang[] = $b;
				}

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}

			return Res::cb($res,false,"Berhasil",["barang"=>$m]);
		}

		public static function getSelectBarangProdukUtama(Request $req,Response $res){
			$p = $req->getParsedBody();
			$mainKatId = $req->getAttribute("main_kategori_id");
			$productId = $req->getAttribute("product_id");
			$userId = $req->getAttribute("user_id");

			$sort = $p['sort'];
			$orderBy = "";
			if( $sort == "A to Z" )
				$orderBy = " b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = " b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = " b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = " b.created_at desc";

			$tipe = $req->getAttribute("tipe");
			$limit = $tipe == "All" ? "" : "limit 5";
			$m = DB::select("select b.*,t.nama as nama_toko,
												(select path from eo_foto_barang where barang_id = b.id limit 1) as photo,
												case when f.id is null then 'no' else 'yes' end as is_favorite
												from eo_barang b
												inner join eo_produk_utama_items pui on pui.barang_id = b.id
												left join eo_favorite f on f.tipe='Barang' and  f.from_id = b.id and f.user_id = $userId
												inner join eo_toko t on t.id = b.toko_id
												where pui.produk_utama_id = $productId
												and b.visible='yes'
												order by $orderBy " . $limit );

			return Res::cb($res,false,"Berhasil",["barang"=>$m]);
		}


		public static function getProdukUtamaPromo($mainKategoriId,$limit="All",$userId,$productId,$index){
			$limit = $limit == "All" ? "" : "limit 5";
			$m = DB::select("select b.*,t.nama as nama_toko,$productId as product_id,$index as idx,
												(select path from eo_foto_barang where barang_id = b.id limit 1) as photo,
												case when f.id is null then 'no' else 'yes' end as is_favorite
												from eo_barang b
												inner join eo_toko t on t.id = b.toko_id and t.main_kategori_id = $mainKategoriId
												left join eo_favorite f on f.tipe='Barang' and  f.from_id = b.id and f.user_id = $userId
												where b.is_promo='Ya'
												and b.visible='yes'
												order by b.created_at desc " . $limit );

			return $m;
		}

		public static function getProdukUtamaSelect($mainKategoriId,$limit="All",$userId,$productId,$index){
			$limit = $limit == "All" ? "" : "limit 5";
			$m = DB::select("select b.*,t.nama as nama_toko,$productId as product_id,$index as idx,
												(select path from eo_foto_barang where barang_id = b.id limit 1) as photo,
												case when f.id is null then 'no' else 'yes' end as is_favorite
												from eo_barang b
												inner join eo_produk_utama_items pui on pui.produk_utama_id = $productId and  pui.barang_id = b.id
												inner join eo_toko t on t.id = b.toko_id
												left join eo_favorite f on f.tipe='Barang' and  f.from_id = b.id and f.user_id = $userId
												where b.visible='yes'
												order by b.created_at desc " . $limit );

			return $m;
		}

		public static function getProdukUtamaBestSeller($mainKategoriId,$limit='All',$userId,$productId,$index){
			$limit = $limit == "All" ? "" : "limit 5";
			$m = DB::select("select b.*,t.nama as nama_toko, $productId as product_id,$index as idx,
												(select path from eo_foto_barang where barang_id = b.id limit 1) as photo,
												case when f.id is null then 'no' else 'yes' end as is_favorite
												from v_bestseller vb
												inner join eo_barang b on b.id = vb.barang_id and b.visible = 'yes'
												left join eo_favorite f on f.tipe='Barang' and  f.from_id = b.id and f.user_id = $userId
												inner join eo_toko t on t.id = b.toko_id and t.main_kategori_id = $mainKategoriId order by b.created_at desc " . $limit );

			return $m;
		}

		public static function getProdukUtama(Request $req,Response $res){
			$userId = $req->getAttribute("user_id");
			$m = DB::select("select u.*,u.id as product_id,mk.nama as kategori from eo_produk_utama u
			left join eo_main_kategori mk on mk.id = u.main_kategori_id
			 where u.visible='yes' order by urutan asc");
			$products=[];$barangs=[];
			$idx=0;
			foreach( $m as $v ){
				if( $v->tipe == "Bestseller" )
					$v->barangs = self::getProdukUtamaBestSeller($v->main_kategori_id,"5",$userId,$v->product_id,$idx);
				else if( $v->tipe == "Promo" )
						$v->barangs = self::getProdukUtamaPromo($v->main_kategori_id,"5",$userId,$v->product_id,$idx);
				else if( $v->tipe == "Select" )
						$v->barangs = self::getProdukUtamaSelect($v->main_kategori_id,"5",$userId,$v->product_id,$idx);
				$products[]=$v;
				$idx++;
			}

			return Res::cb($res,false,"Berhasil",["products"=>$products]);
		}

		public static function editOrderBarang(Request $req,Response $res){
				$p = $req->getParsedBody();
				$barang = Barang::where("id",$p['barang_id'])->first();
				$hargaBarangBaru  = $barang->harga * $p['qty'];
				$hargaBarangSatuanBaru = $barang->harga;

				//ambil harga barang lama * qty
				$b = OrderServiceBarangHistory::where("order_service_history_id",$p['order_id'])->where("barang_id",$p['barang_id'])->first();
				$hargaBarangLama = $b->total;

				$m = OrderServiceHistory::where("id",$p["order_id"])->first();
				$totalHargaLama = $m->price;
				$newTotalHargaLama = $totalHargaLama - $hargaBarangLama; //ini di gunakan sebgai harga dasar, tinggal di tambahkan

				$totalHargaBaru = $hargaBarangBaru + $newTotalHargaLama;

				$b->qty = $p['qty'];
				$b->harga_barang = $hargaBarangSatuanBaru;
				$b->total = $hargaBarangBaru;
				if($b->save()){
					$m->price = $totalHargaBaru;
					if($m->save()){
						return Res::cb($res,true,"Berhasil");
					}
				}

				return Res::cb($res,false,"Perubahan gagal");

		}

		public static function delOrderBarang(Request $req,Response $res){
				$p = $req->getParsedBody();

				//ambil harga barang lama * qty
				$b = OrderServiceBarangHistory::where("order_service_history_id",$p['order_id'])->where("barang_id",$p['barang_id'])->first();
				$hargaBarangLama = $b->total;

				$m = OrderServiceHistory::where("id",$p["order_id"])->first();
				$totalHargaLama = $m->price;
				$newTotalHargaLama = $totalHargaLama - $hargaBarangLama; //ini di gunakan sebgai harga dasar, tinggal di tambahkan

				$m->price = $newTotalHargaLama;
				if($m->save()){
					$b->delete();
					return Res::cb($res,true,"Berhasil hapus");
				}

				return Res::cb($res,false,"Perubahan gagal");

		}

		public static function getProduk(Request $req,Response $res){
			$tokoId = $req->getAttribute('toko_id');
			$katId = $req->getAttribute('kategori_id');
			$p	= $req->getParsedBody();
			$key = $p['key'];

			$t = DB::Select("select fb.path as foto,b.id,b.nama,k.nama as nama_kategori,b.harga from eo_barang b
								INNER JOIN eo_kategori_barang k on k.id = b.kategori_barang_id
								left join eo_foto_barang fb on fb.barang_id = b.id
								where b.toko_id = $tokoId
								and b.kategori_barang_id = $katId
								and b.nama like '%".$key."%'

                group by b.id
							");

			return Res::cb($res,true,"Berhasil",["barang" => $t]);
		}

		public static function getProdukDetail(Request $req,Response $res){
			$id = $req->getAttribute('id');
			$t = DB::Select("select fb.path,b.*,k.nama as nama_kategori,b.harga from eo_barang b
								INNER JOIN eo_kategori_barang k on k.id = b.kategori_barang_id
								inner join eo_foto_barang fb on fb.barang_id = b.id
								where b.id = $id
							");

			return Res::cb($res,true,"Berhasil",["barang" => $t]);
		}

		public static function delProduk(Request $req,Response $res){
			$id = $req->getAttribute("id");
			$m = Barang::where("id",$id);
      if( !is_null($m) )
        if($m->delete())
          return Res::cb($res,true,"Produk berhasil di hapus");
      else
        return Res::cb($res,false,"gagal");
		}

    public static function uploadImage(Request $req,Response $res){
      $p = $req->getParsedBody();
      $path = Helper::uploadBase64Image($p['photo']);
      $f = new FotoBarang;
      $f->barang_id = $p['barang_id'];
      $f->path = $path;
      if($f->save()){
        return Res::cb($res,true,"Berhasil",["path"=>$path]);
      }
    }

		public static function uploadTmpImage(Request $req,Response $res){
      $p = $req->getParsedBody();
      $path = Helper::uploadBase64Image($p['photo']);
      return Res::cb($res,true,"Berhasil",['path'=>$path]);
    }

    public static function deleteImage(Request $req,Response $res){
      $p = $req->getParsedBody();
      $f = FotoBarang::where("id",$p['id'])->first();
      unlink($f->path);
      $f = FotoBarang::where("id",$p['id'])->delete();
      return Res::cb($res,true,"Berhasil");
    }

    public static function editImage(Request $req,Response $res){
      $p = $req->getParsedBody();
      $path = Helper::uploadBase64Image($p['photo']);
      $f = FotoBarang::where("id",$p['id'])->first();
      if( !isset($f['id']) ){ //edit foto
        $f = new FotoBarang;
        $f->barang_id = $p['barang_id'];
      }else{ //unlink first
        unlink($f->path);
      }
      $f->path = $path;
      if($f->save()){
        return Res::cb($res,true,"Berhasil",['path'=>$path,"id"=>$f->id]);
      }

    }

    public static function save(Request $req,Response $res){
			$p = $req->getParsedBody();
			$isNewProduk = false;
			if( $p["barang_id"] != "" ){
				$m = Barang::where("id",$p['barang_id'])->first();
			}else{
				 $m= new Barang;
				 $isNewProduk = true;

			}

			$m->nama = $p['nama'];
			$m->harga = $p['harga'];
			$m->berat = $p['berat'];
			$m->stock = $p['stok'];
			$m->tipe_stock = $p['tipe_stok'];
			$m->deskripsi = $p['deskripsi'];
			$m->kategori_barang_id = $p['kategori_id'];
			$m->etalase_id = $p['etalase_id'];
			$m->kondisi = $p['kondisi'];
			$m->discount = $p['discount'];
			$m->visible = $p['visible'];
			$m->toko_id = $p['toko_id'];
			$m->tipe_isi = $p['tipe_isi'];
			$m->isi = $p['isi'];
			$m->min_beli = $p['min_beli'];
			$m->tipe_barang = 'lain';
			$m->is_promo = $p['is_promo'];
			$m->viewer = "0";


			if($p['discount'] == "") return Res::cb($res,false,"Discount tidak boleh kosong, isikan 0 jika tidak ada discount");

			if($isNewProduk){
				if($p['foto'] == "") return Res::cb($res,false,"Foto tidak boleh kosong");
			}


			if($m->save()){
				if( isset($p['is_update_foto']) ){
					$f = FotoBarang::where("id",$p["foto_id"])->first();
					$path = Helper::uploadBase64Image($p['foto']);
					$f->barang_id = $m->id;
					$f->path = $path;
					if($f->save())
						return Res::cb($res,true,"Berhasil");

				}else if( !isset($p['no_change_foto']) ){
					$f = new FotoBarang;

					$path = Helper::uploadBase64Image($p['foto']);
					$f->barang_id = $m->id;
					$f->path = $path;
					if($f->save())
						return Res::cb($res,true,"Berhasil");
				} else{
					return Res::cb($res,true,"Berhasil");
				}

			}
		}

    public static function saveNew(Request $req,Response $res){
			$p = $req->getParsedBody();
      DB::beginTransaction();
			$isNewProduk = false;
			if( $p["barang_id"] != "" ){
				$m = Barang::where("id",$p['barang_id'])->first();
			}else{
				 $m= new Barang;
				 $isNewProduk = true;
			}

			$m->nama = $p['nama'];
			$m->harga = $p['harga'];
			$m->berat = $p['berat'];
			$m->stock = $p['stok'];
			$m->tipe_stock = $p['tipe_stok'];
			$m->deskripsi = $p['deskripsi'];
			$m->kategori_barang_id = $p['kategori_id'];
			$m->etalase_id = $p['etalase_id'];
			$m->kondisi = $p['kondisi'];
			$m->discount = $p['discount'];
			$m->visible = $p['visible'];
			$m->toko_id = $p['toko_id'];
			$m->tipe_isi = $p['tipe_isi'];
			$m->isi = $p['isi'];
			$m->min_beli = $p['min_beli'];
			$m->tipe_barang = 'lain';
			$m->is_promo = $p['is_promo'];
			$m->viewer = "0";


			if($p['discount'] == "") return Res::cb($res,false,"Discount tidak boleh kosong, isikan 0 jika tidak ada discount");
			if( (int) $p['discount'] > 0 )
				if($p['min_beli'] == "" || $p['min_beli'] == "0") return Res::cb($res,false,"Min beli tidak boleh kosong / 0, isikan min 1");

			if($m->save()){
        DB::commit();
        return Res::cb($res,true,"Berhasil",["barang"=>$m]);
      }else{
        DB::rollback();
        return Res::cb($res,false,"Kesalahan server");
      }
		}

		public static function finishOrder(Request $req,Response $res){
			$tipe = "";
			$id = $req->getAttribute("history_id"); //history id
			$userId = $req->getAttribute("user_id");
			$bo = OrderServiceBarangHistory::where("order_service_history_id",$id)->first();
			$t = Toko::where("id",$bo->toko_id)->first();
			if( $t->services_id == 3 ){
				$tipe = "4";
			}else $tipe = "6";

			$m = OrderServiceHistory::where("id",$id)->first();
			$m->status = "Complete";
      $m->status_deskripsi = "By User";
			if( $m->save() ){

				if( $m->poin > 0 ){
					$poinHistory = new PoinHistory;
					$poinHistory->user_id = $m->user_id;
					$poinHistory->poin = $m->poin;
					$poinHistory->ket = "dapat poin dari no. transaksi #" . $m->id;
					$poinHistory->save();

					$poin = Poin::where("user_id",$m->user_id)->first();
					$user = User::where("id",$m->user_id)->first();
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
							PushNotif::pushTo("Selamat","Anda dapat poin ",$user['firebase_token'],$dataPushPoin);
					}

				}

				$o = Orders::where("order_id",$id)->where("order_type_id",$tipe)->first();
				$o->status = "Complete";

				if($o->save()){
					return Res::cb($res,true,"Berhasil");
				}else return Res::cb($res,false,"Kesalahan Server");
			}else return Res::cb($res,false,"Kesalahan Server");
		}

		public static function searchMakanan(Request $req,Response $res){
			$hasNext = true;
			$keys = $req->getAttribute("keys");
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes' )";
			}



			$total = DB::select("
		    						select count(*) as jum  from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id = 29
		    						and b.nama like '%".$keys."%'
		    						");

			$total = $total[0]->jum;

			$limit = 1000;
			$totalPages = ceil($total / $limit);

			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 2 ) $currentPage = 1;
			$offset = ($currentPage - 1) * $limit;
			//cek jika punya next item
            if( ($currentPage -$totalPages)  == 0){
                $hasNext = false;
            }



			$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
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
		    						sd.h_".strtolower($nameDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id = 29
		    						and b.nama like '%".$keys."%'

		    						limit $offset,$limit
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
		    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $b->toko_id );
		    	$hari = (array) $hari[0];
		    	foreach ($hari as $k => $v) {
		    		if( $hari[$k]  == "Buka" )
		    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
		    	}
		    	$b->days = $hariBuka;

				$newBarang[] = $b;
			}

			return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext , 'barang' => $newBarang]);
		}

		public static function getAllMakananByKatId(Request $req,Response $res){
			$hasNext  = true;
			$id = $req->getAttribute("id");
			$currentPage = $req->getAttribute("current_page");
			$nameDay = Helper::getNameDayInd(date("N"));
			$userId = $req->getAttribute('user_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first();
				$whKota = " and (t.city_id = " . $uKota->kota_id . " or t.is_toko_khusus = 'yes')";

			}

			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id = 29
		    						and b.kategori_makanan_id = $id
		    						");
			$total = $total[0]->jum;
			$limit = 1000;
			$totalPages = ceil($total / $limit );
			if($currentPage > $totalPages){
                return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
            }
            if( $currentPage < 0 ) $currentPage = 1;

            $offset = ($currentPage - 1) * $limit;

            //cek jika punya next item
            if( ($currentPage -$totalPages)  == 0){
                $hasNext = false;
            }


			$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
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
		    						sd.h_".strtolower($nameDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id = 29
		    						and b.kategori_makanan_id = $id
		    						limit $offset,$limit
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
		    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $b->toko_id );
		    	$hari = (array) $hari[0];
		    	foreach ($hari as $k => $v) {
		    		if( $hari[$k]  == "Buka" )
		    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
		    	}
		    	$b->days = $hariBuka;

				$newBarang[] = $b;
			}

			return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
		}

		public static function searchBarang(Request $req,Response $res){
			$hasNext = true;
			$keys = $req->getAttribute("keys");
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');

			$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
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
		    						sd.h_".strtolower($nameDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id != 29
		    						and b.nama like '%".$keys."%'

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
			    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $b->toko_id );
			    	$hari = (array) $hari[0];
			    	foreach ($hari as $k => $v) {
			    		if( $hari[$k]  == "Buka" )
			    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
			    	}
			    	$b->days = $hariBuka;

					$newBarang[] = $b;
				}

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
		}

		public static function searchBarangKhusus(Request $req,Response $res){
			$hasNext = true;
			$keys = $req->getAttribute("keys");
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");

			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.id = 33
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id != 29
		    						and b.nama like '%".$keys."%'
		    						");
			$total = $total[0]->jum;
			$limit = 1000;
			$totalPages = ceil( $total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 2 ) $currentPage = 1;
			//cek jika punya next item
            if( ($currentPage -$totalPages)  == 0){
                $hasNext = false;
            }

			$offset = ($currentPage - 1) * $limit;
			$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
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
		    						sd.h_".strtolower($nameDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.id = 33
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
		    						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id != 29
		    						and b.nama like '%".$keys."%'
		    						limit $offset,$limit
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
			    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $b->toko_id );
			    	$hari = (array) $hari[0];
			    	foreach ($hari as $k => $v) {
			    		if( $hari[$k]  == "Buka" )
			    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
			    	}
			    	$b->days = $hariBuka;

					$newBarang[] = $b;
				}

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
		}

		public static function getAllBarangByToko(Request $req,Response $res){
			$hasNext = true;
			$id = $req->getAttribute('toko_id'); //toko barang
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');
			$typeShop = $req->getAttribute('type_shop');
			$kategoriId = $req->getAttribute('kategori_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes')";
			}


			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    									and t.type_shop = '".$typeShop."'
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where t.id = $id and b.kategori_barang_id = $kategoriId
		    						  ");

		   	$total = $total[0]->jum;
		   	$limit = 1000;
		   	$totalPages = ceil($total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 0 ) $currentPage = 1;

			$offset = ($currentPage - 1) * $limit;

			//cek jika punya next item
			if( ($currentPage -$totalPages)  == 0){
				$hasNext = false;
			}

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
		    						b.viewer,
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
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    									and t.type_shop = '".$typeShop."'
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where t.id = $id and b.kategori_barang_id = $kategoriId limit $offset,$limit
		    						order by b.nama asc
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

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllBarang(Request $req,Response $res){
			$hasNext = true;
			$id = $req->getAttribute('id'); //kategori barang
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes')";
			}


			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes'  and b.kategori_barang_id = $id
		    						  ");
		   	$total = $total[0]->jum;
		   	$limit = 1000;
		   	$totalPages = ceil($total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 0 ) $currentPage = 1;

			$offset = ($currentPage - 1) * $limit;

			//cek jika punya next item
			if( ($currentPage -$totalPages)  == 0){
				$hasNext = false;
			}

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
		    						b.viewer,
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
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes'
		    						and b.kategori_barang_id = $id limit $offset,$limit");

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

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllProdukUtamaPromoAll(Request $req,Response $res){
			$nameDay = Helper::getNameDayInd(date("N"));
			$productId = $req->getAttribute("product_id");
			$userId = $req->getAttribute("user_id");

			$p = $req->getParsedBody();
			$sort = $p['sort'];
			$orderBy = "";
			if( $sort == "A to Z" )
				$orderBy = "order by b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = "order by b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = "order by b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = "order by b.created_at desc";

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
						b.kondisi,
						b.discount,
						b.berat,
						b.viewer,
						b.kategori_barang_id,
						b.tipe_stock,
						b.isi,
						b.tipe_isi,
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
						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
						from eo_barang b

						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
						inner join eo_setting_penjual p on p.toko_id = t.id
						inner join eo_setting_day_toko sd on sd.toko_id = t.id
						inner join eo_produk_utama pu on pu.id = $productId
						inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id and kb.main_kategori_id = pu.main_kategori_id
						left join eo_favorite f on f.tipe='Barang' and f.from_id = b.id and f.user_id = $userId

						where  b.visible = 'yes'
						and b.is_promo = 'Ya'
						$orderBy
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
								$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
						}
						$b->days = $hariBuka;

					$newBarang[] = $b;
				}

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllBarangPromoSort(Request $req,Response $res){
			$nameDay = Helper::getNameDayInd(date("N"));
			$pasarId = $req->getAttribute('pasar_id'); //pasar id
			$p = $req->getParsedBody();
			$sort = $p['sort'];
			$orderBy = "";
			if( $sort == "A to Z" )
				$orderBy = "order by b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = "order by b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = "order by b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = "order by b.created_at desc";


			if( isset($_GET['kota_id']) ){
				$whKota = "and t.default_city_id = " . $_GET["kota_id"];
			}else $whKota = "";

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
						b.kondisi,
						b.discount,
						b.berat,
						b.viewer,
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
						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
						from eo_barang b

						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' and t.pasar_id = $pasarId  $whKota
						inner join eo_setting_penjual p on p.toko_id = t.id
						inner join eo_setting_day_toko sd on sd.toko_id = t.id

						where  b.visible = 'yes'
						and b.is_promo = 'Ya'
						$orderBy
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

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllPromoByToko(Request $req,Response $res){
			$nameDay = Helper::getNameDayInd(date("N"));
			$tokoId = $req->getAttribute('toko_id'); //toko id
			$userId = $req->getAttribute('user_id'); //user id

			$p = $req->getParsedBody();
			$sort = $p['sort'];
			$orderBy = "";
			if( $sort == "A to Z" )
				$orderBy = "order by b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = "order by b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = "order by b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = "order by b.created_at desc";

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
						b.kondisi,
						b.discount,
						b.berat,
						b.viewer,
						b.tipe_stock,
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
						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
						case when f.id is null then 'no' else 'yes' end as is_favorite
						from eo_barang b

						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
						inner join eo_setting_penjual p on p.toko_id = t.id
						inner join eo_setting_day_toko sd on sd.toko_id = t.id
						left join eo_favorite f on f.tipe='Barang' and f.from_id = b.id and f.user_id = $userId

						where  b.visible = 'yes'
						and b.toko_id = $tokoId
						and b.is_promo = 'Ya'
						$orderBy
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

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}


		public static function getAllBarangSortByMainKat(Request $req,Response $res){
			$nameDay = Helper::getNameDayInd(date("N"));
			$id = $req->getAttribute('id'); //kategori barang
			$currentPage = $req->getAttribute("current_page");
			$p = $req->getParsedBody();
			$sort = $p['sort'];
			$orderBy = "";$filter="";
      $userId = isset($_GET['user_id']) ? $_GET['user_id'] : 0 ;
			if( $sort == "A to Z" )
				$orderBy = "order by b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = "order by b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = "order by b.created_at desc";


			if( isset($_GET['kota_id']) ){
				$whKota = "and t.default_city_id = " . $_GET["kota_id"];
			}else $whKota = "";

      $whFilter="";
      if( isset($_GET['filter']) ){
        $filter = $_GET['filter'];
        if( $filter == "Hanya Stok Ada" ){
          $whFilter = "and ( b.tipe_stock = 'Ready' or stock > 0 )";
        }else if( $filter == "Semua" ) $whFilter = "";
      }

		    try{
		    	if( !isset($_GET['promo']) ){
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
			    						b.type_berat,
											b.isi,
											b.tipe_isi,
			    						b.viewer,
			    						b.kategori_barang_id,
			    						t.nama as nama_toko,
			    						p.id as setting_id,
			    						p.type_biaya_antar,
			    						p.type_jasa_pengiriman,
			    						p.price_flat,
			    						t.alamat_jalan as alamat,
			    						t.city_id,
			    						t.province_id,
			    						t.toko_alamat_gmap,
											p.deskripsi_toko_tutup,
			    						sd.h_".strtolower($nameDay)." as status_toko,
			    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
                      case when f.id is not null then 'yes' else 'no' end as is_favorite
			    						from eo_barang b

			    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
			    						inner join eo_setting_penjual p on p.toko_id = t.id
			    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
											inner join eo_kategori_barang kb on kb.id = $id and kb.id = b.kategori_barang_id
                      left join eo_favorite f on f.from_id = b.id and f.user_id = $userId and f.tipe='Barang'

			    						where  b.visible = 'yes'

                     ".$whFilter."
			    						".$orderBy."
			    						");
			    	}else if( isset($_GET['promo']) && $_GET['promo'] == "yes" ){
							$mainKatId = $_GET['main_kat'];
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
			    						b.kategori_barang_id,
			    						t.nama as nama_toko,
			    						p.id as setting_id,
			    						p.type_biaya_antar,
			    						p.type_jasa_pengiriman,
			    						p.price_flat,
			    						t.alamat_jalan alamat,
			    						t.city_id,
			    						t.province_id,
			    						t.toko_alamat_gmap,
			    						sd.h_".strtolower($nameDay)." as status_toko,
			    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
                      case when f.id is not null then 'yes' else 'no' end as is_favorite
			    						from eo_barang b

			    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
			    						inner join eo_setting_penjual p on p.toko_id = t.id
			    						inner join eo_setting_day_toko sd on sd.toko_id = t.id
											inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id and kb.main_kategori_id = $mainKatId
                      left join eo_favorite f on f.from_id = b.id and f.user_id = $userId and tipe='Barang'

			    						where  b.visible = 'yes'
			    						and b.is_promo = 'Ya'
			    						$orderBy
			    				");
			    	}

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

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllMakanan(Request $req,Response $res){
			$hasNext = true;
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');
			$tokoId = $req->getAttribute('toko_id');
			$katId = $req->getAttribute('kat_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes')";
			}

			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id
		    							and t.visible = 'yes' $whKota
		    							and t.id = $tokoId
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes'
		    						and b.kategori_makanan_id = $katId ");

		   	$total = $total[0]->jum;
		   	$limit = 1000;
		   	$totalPages = ceil($total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 0 ) $currentPage = 1;

			$offset = ($currentPage - 1) * $limit;

			//cek jika punya next item
			if( ($currentPage -$totalPages)  == 0){
				$hasNext = false;
			}

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
		    						b.viewer,
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
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id
		    									and t.visible = 'yes' $whKota
		    									and t.id = $tokoId
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes'
		    						and b.kategori_makanan_id = $katId limit $offset,$limit");

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

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllMakananByResto(Request $req,Response $res){
			$hasNext = true;
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');
			$tokoId = $req->getAttribute('toko_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes')";
			}

			$total = DB::select("
		    						select count(1) as jum from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id
		    							and t.visible = 'yes' $whKota
		    							and t.id = $tokoId
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes' ");

		   	$total = $total[0]->jum;
		   	$limit = 1000;
		   	$totalPages = ceil($total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 0 ) $currentPage = 1;

			$offset = ($currentPage - 1) * $limit;

			//cek jika punya next item
			if( ($currentPage -$totalPages)  == 0){
				$hasNext = false;
			}

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,t.latitude,t.longitude,
		    						b.nama,
		    						b.id,
		    						b.toko_id,
		    						b.harga,
		    						b.stock,
		    						b.kondisi,
		    						b.discount,
		    						b.berat,
		    						b.viewer,
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
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id
		    									and t.visible = 'yes' $whKota
		    									and t.id = $tokoId
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where  b.visible = 'yes'
		    						limit $offset,$limit");

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

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getByIdBarangBisnis(Request $req,Response $res){
			$id = $req->getAttribute('id'); // barang id
		    $noDay = Helper::getNameDayInd(date("N"));

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,b.*,b.viewer as lihat,t.nama as nama_toko,t.alamat,
		    						p.id as setting_id,
		    						p.type_biaya_antar,
		    						p.type_jasa_pengiriman,
		    						p.price_flat,
		    						t.toko_alamat_gmap,
		    						sd.h_".strtolower($noDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

		    						where b.id = $id ");

		    	//get foto
		    	$photos = DB::Select("select path,id from eo_foto_barang where barang_id = $id");
		    	if( count($photos) <= 0  ){
		    		$barang[0]->photos = [["path" => "/home/tokokota/admintoko.tokokota.com/assets/tmp/noimg.png","id"=>"0"]];
		    		$barang[0]->photo = "/home/tokokota/admintoko.tokokota.com/assets/tmp/noimg.png";
		    	}else{
		    		$barang[0]->photos = $photos;
				}


		    	//get hari pelayanan
		    	$hariBuka = [];
		    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $barang[0]->toko_id );
		    	$hari = (array) $hari[0];
		    	foreach ($hari as $k => $v) {
		    		if( $hari[$k]  == "Buka" )
		    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
		    	}
		    	$barang[0]->hari_pelayanan = $hariBuka;

		    	//get jam pelayanan
		    	$jamBuka = [];
		    	$jam = DB::Select("select * from eo_setting_time_toko where toko_id = " . $barang[0]->toko_id . " and setting_day_id = " . date("N"));
		    	foreach ($jam as $v) {
		    		$jamBuka[] = [
		    						"id" => $v->id,"waktu" => $v->waktu_mulai . " s/d " . $v->waktu_akhir,
		    						"day" => $v->setting_day_id,
		    						"date_mulai" => date('Ymd') . " ". $v->waktu_mulai,
									"date_akhir" => date('Ymd') . " ". $v->waktu_akhir,
									"date_sekarang" => date('Ymd H:i:s'),
		    					];
		    	}
		    	$barang[0]->jam_pelayanan = $jamBuka;


		    	/*update view barang*/
		    	$m = Barang::where("id",$id)->first();
		    	$m->viewer= (int) $m->viewer + 1;
		    	$m->save();

				return Res::cb($res,true,"Berhasil",['barang' => $barang[0]]);
			}catch(Exception $e){
				return Res::cb($res,false,"sGagal, data tidak ditemukan",[]);
			}
		}

		public static function getByIdBarang(Request $req,Response $res){
				$id = $req->getAttribute('id'); // barang id
				$userId = $req->getAttribute('user_id'); // user id
		    $noDay = Helper::getNameDayInd(date("N"));

		    try{
		    	$barang = DB::select("
		    						select t.id as toko_id,b.*,b.viewer as lihat,t.nama as nama_toko,t.alamat,
		    						p.id as setting_id,
		    						p.type_biaya_antar,
		    						p.type_jasa_pengiriman,
		    						p.price_flat,
		    						t.toko_alamat_gmap,
										t.latitude as toko_lat,
										t.longitude as toko_lng,
		    						t.type_shop,
										t.no_telp,
										k.city_name as kota_nama,
		    						sd.h_".strtolower($noDay)." as status_toko,
		    						(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
										case when f.id is null then 'no' else 'yes' end is_favorite
		    						from eo_barang b

		    						inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes'
										inner join eo_city c on c.id = t.default_city_id
										inner join eo_kota k on k.city_id = c.kota_id
		    						inner join eo_setting_penjual p on p.toko_id = t.id
		    						inner join eo_setting_day_toko sd on sd.toko_id = t.id

										left join eo_favorite f on f.from_id = b.id and f.user_id = $userId and tipe = 'Barang'

		    						where  b.visible = 'yes'
		    						and b.id = $id ");


		    	//get foto
		    	$photos = DB::Select("select path,id from eo_foto_barang where barang_id = $id");
		    	$barang[0]->photos = $photos;

					//setting penjual, master dan pengiriman deksripsi
					$m = SettingPenjual::where("toko_id",$barang[0]->toko_id)->first();
					$barang[0]->master_deskripsi = $m['master_deskripsi'];
					$barang[0]->master_deskripsi_pengiriman = $m['master_deskripsi_pengiriman'];

		    	//get hari pelayanan
		    	$hariBuka = [];
		    	$hari = DB::Select("select * from eo_setting_day_toko where toko_id = " . $barang[0]->toko_id );
		    	$hari = (array) $hari[0];
		    	foreach ($hari as $k => $v) {
		    		if( $hari[$k]  == "Buka" )
		    			$hariBuka[] = ["hari" => ucfirst(explode("_",$k)[1])];
		    	}
		    	$barang[0]->hari_pelayanan = $hariBuka;

		    	//get jam pelayanan
		    	$jamBuka = [];
		    	$jam = DB::Select("select * from eo_setting_time_toko where toko_id = " . $barang[0]->toko_id . " and setting_day_id = " . date("N"));
		    	foreach ($jam as $v) {
		    		$jamBuka[] = [
		    						"id" => $v->id,"waktu" => $v->waktu_mulai . " s/d " . $v->waktu_akhir,
		    						"day" => $v->setting_day_id,
		    						"date_mulai" => date('Ymd') . " ". $v->waktu_mulai,
									"date_akhir" => date('Ymd') . " ". $v->waktu_akhir,
									"date_sekarang" => date('Ymd H:i:s'),
		    					];
		    	}
		    	$barang[0]->jam_pelayanan = $jamBuka;


		    	/*update view barang*/
		    	$m = Barang::where("id",$id)->first();
		    	$m->viewer= (int) $m->viewer + 1;
		    	$m->save();

				return Res::cb($res,true,"Berhasil",['barang' => $barang[0]]);
			}catch(Exception $e){
				return Res::cb($res,false,"sGagal, data tidak ditemukan",[]);
			}
		}

		public static function historyByStatus(Request $req,Response $res){
			$status = $req->getAttribute('status'); // 1 -> Pending dan Progress , 2 -> Complete dan Cancel
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];

		    if($status == 1){
		    	$status = "'Pending','Progress'";
		    }else{
		    	$status = "'Complete','Cancel'";
		    }

		    try{
		    	$orderQ = DB::select("
		    		select t.*,b.*,t.id as order_id,b.id as order_barang_id
		    		from eo_order_service_history t
		    		inner join eo_order_service_barang_history b on b.order_service_history_id = t.id
		    		where t.user_id = $user_id and t.status in($status)
		    	");

		    	foreach ($orderQ as $o) {
		    		if(!in_array($o->order_id,$orderId)){
		    			$orderId[] =  $o->order_id;
		    			$order = $o;
		    			$orderorder_barang = (object) ['order_barang' => [] ];
		    			$order->order_barang[] = [
		    				"barang_id" => $o->barang_id,
		    				"qty" => $o->qty,
		    				"services_id" => $o->services_id,
		    				"total" => $o->total,
		    				"toko_id" => $o->toko_id,
		    			];

		    		}else{
						$order->order_barang[] = [
		    				"barang_id" => $o->barang_id,
		    				"qty" => $o->qty,
		    				"services_id" => $o->services_id,
		    				"total" => $o->total,
		    				"toko_id" => $o->toko_id,
		    			];
		    		}
		    	}

				return Res::cb($res,true,"Berhasil",['history' => $order]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function historyById(Request $req,Response $res){
			$id = $req->getAttribute('id');
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];

		    try{
		    	$orderQ = DB::select("
		    		select b.*, case when v.id is null then 0 else v.id end as vendor_id
		    		from v_order_service_barang b
		    		left join eo_vendor v on v.id = b.vendor_id
		    		where b.user_id = $user_id and b.order_id = $id
		    	");

			/*	die("
		    		select b.*,
		    		from v_order_service_barang b
		    		inner join
		    		where b.user_id = $user_id and b.order_id = $id
		    	");*/

		    	foreach ($orderQ as $o) {
		    		if(!in_array($o->order_id,$orderId)){
		    			$orderId[] =  $o->order_id;
		    			$order = $o;
		    			//$order = (object) ['order_barang' => [] ];
		    			$order->order_barang[] = [
		    				"barang" => $o->nama_barang,
		    				"qty" => $o->qty,
		    				"total" => $o->total,
		    				"toko" => $o->nama_toko,
		    				"note" => $o->note,
		    			];
		    		}else{
						$order->order_barang[] = [
		    				"barang" => $o->nama_barang,
		    				"qty" => $o->qty,
		    				"total" => $o->total,
		    				"toko" => $o->nama_toko,
		    				"note" => $o->note,
		    			];
		    		}
		    	}

				return Res::cb($res,true,"Berhasil",['history' => $order]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function reorder(Request $req,Response $res){
			$id = $req->getAttribute("order_id");
		    try{
		    	$order = DB::select("
		    		select sh.*,o.order_type_id as type_order,u.no_telp,u.nama as nama_user,
		    		order_method as payment_method from eo_order_service_history sh
		    		inner join eo_orders o on o.order_id = sh.id and o.order_type_id in(4,6)
		    		inner join eo_user u on u.id = sh.user_id
		    		where sh.id = $id
		    	");

		    	$barang = DB::Select("select
		    							bh.nama_barang as nama,
		    							bh.barang_id,
		    							bh.toko_id,
		    							bh.qty,
		    							bh.total,
		    							bh.berat_barang as berat,
		    							bh.note,
		    							bh.is_deleted,
											b.harga,
		    							case when bh.kd_print_barang is null then '' else bh.kd_print_barang end as kd_print,
                      t.latitude as toko_lat,
                      t.longitude as toko_lng,

		    							t.nama as toko_nama,
		    							t.alamat as toko_alamat,
		    							t.alamat as toko_alamat_gmap,
		    							kb.nama as kategori_nama,
		    							(select path from eo_foto_barang fb where fb.barang_id = bh.barang_id order by id desc limit 1) as photo

		    							from eo_order_service_barang_history bh
		    							inner join eo_toko t on t.id = bh.toko_id
											inner join eo_barang b on b.id = bh.barang_id

		    							inner join eo_kategori_barang kb on kb.id = bh.kategori_barang_id

		    							where bh.order_service_history_id = $id

		    						");
				if( count($barang) > 0 && count($order) > 0  ){
          $order[0]->toko_lat = $barang[0]->toko_lat;
          $order[0]->toko_lng = $barang[0]->toko_lng;
					$history["order"] = $order[0];
		    	$history["barang"] = $barang;
					$jamAntar = [];
					$jam = DB::Select("select * from eo_setting_time_toko where toko_id = " . $barang[0]->toko_id . " and setting_day_id = " . date("N"));
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
					$history['master_deskripsi_pengiriman'] = DB::select("select  * from eo_setting_penjual where toko_id = " .  $barang[0]->toko_id )[0]->master_deskripsi_pengiriman;

				}else{
					$history["order"] = [];
		    		$history["barang"] = [];

				}


				return Res::cb($res,true,"Berhasil",['history' => $history]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function historyBarangById(Request $req,Response $res){
			$id = $req->getAttribute("order_id");
		    try{
		    	$barang = DB::Select("select
		    							bh.nama_barang as nama,
		    							bh.barang_id,
		    							bh.toko_id,
		    							bh.qty,
		    							bh.total,
		    							bh.berat_barang as berat,
											bh.isi,
											bh.tipe_isi,
		    							bh.note,
		    							bh.is_deleted,
		    							case when bh.kd_print_barang is null then '' else bh.kd_print_barang end as kd_print,

		    							t.nama as toko_nama,
		    							t.alamat_jalan as toko_alamat,
		    							t.alamat_jalan as toko_alamat_jalan,
		    							t.alamat as toko_alamat_lengkap,
		    							t.toko_alamat_gmap as toko_alamat_gmap,
		    							t.latitude as toko_lat,
		    							t.longitude as toko_lng,
		    							kb.nama as kategori_nama,
		    							(select path from eo_foto_barang fb where fb.barang_id = bh.barang_id order by id desc limit 1) as photo

		    							from eo_order_service_barang_history bh
		    							inner join eo_toko t on t.id = bh.toko_id

		    							inner join eo_kategori_barang kb on kb.id = bh.kategori_barang_id

		    							where bh.order_service_history_id = $id and bh.is_deleted = 'no'

		    						");


						$order = DB::select("
			    		select '".$barang[0]->toko_nama."' as toko_nama,'".$barang[0]->toko_alamat."' as toko_alamat,
							'".$barang[0]->toko_alamat_gmap."' as toko_alamat_gmap, '".$barang[0]->toko_alamat_lengkap."' as toko_alamat_lengkap,
							'".$barang[0]->toko_alamat_jalan."' as toko_alamat_jalan,
							".$barang[0]->toko_lat." as toko_lat, ".$barang[0]->toko_lng." as toko_lng,
							u.no_telp as no_telp_user,u.alamat as alamat_user,
							ekspedisi_nama as ekspedisi,sh.*,o.order_type_id as type_order,u.no_telp,u.nama as nama_user,
			    		order_method as payment_method
							from eo_order_service_history sh
			    		inner join eo_orders o on o.order_id = sh.id and o.order_type_id in(4,6)
			    		inner join eo_user u on u.id = sh.user_id
			    		where sh.id = $id
			    	");

				if( count($barang) > 0 && count($order) > 0  ){
					$history["order"] = $order[0];
		    	$history["barang"] = $barang;
					$jamAntar = [];
		    	$jam = DB::Select("select * from eo_setting_time_toko where toko_id = " . $barang[0]->toko_id . " and setting_day_id = " . date("N"));
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
					$history['master_deskripsi_pengiriman'] = DB::select("select  * from eo_setting_penjual where toko_id = " .  $barang[0]->toko_id )[0]->master_deskripsi_pengiriman;
				}else{
					$history["order"] = [];
		    		$history["barang"] = [];
				}





				return Res::cb($res,true,"Berhasil",['history' => $history]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

    public static function cekCoord($address){
      //$address="Komplek Taman Sungai Raya No.b6, Sungai Raya, Kabupaten Kubu Raya, Kalimantan Barat, Indonesia null";
      //echo $address;die();
      $addr = urlencode($address);
      $url = "https://maps.google.com/maps/api/geocode/json?address=".$addr."&key=AIzaSyCmABK4yYnveOGZf-kwxNn6fvqr1xMfH_o";
      $r = Helper::getCoordcURL($url);
      $j=json_decode($r,true);
      if( count($j['results']) > 0 ){
        $ret =  [
                  "lat"=> $j['results'][0]['geometry']["location"]["lat"],
                  "lng"=> $j['results'][0]['geometry']["location"]["lng"]
              ];

        return $ret;
      }else return [];
    }

    public static function order(Request $req,Response $res){
			$post = $req->getParsedBody();
			DB::beginTransaction();
			$isGetPoin  = "no";
      $isAntarSendiri=false;
      //var_dump($post);die();
			if(  !isset($post['version_code']) ){
				return Res::cb($res,false,"Untuk melanjutkan pesanan, silahkan update dulu aplikasi TokoKota yang terbaru di Playstore, terimakasih");
			}

			$userId 		= $post['user_id'];
			$vendorId 		= null;
			$orderLat 		= $post['order_lat'];
			$orderLng 		= $post['order_lng'];
			$orderKetLain = $post['order_ket_lain'];
			$orderMethod 	= $post['order_method'];
			$orderStatus 	= $post['status'];
			$totalPrice 	= (float) $post['price'];
			$priceAntar 	= (float) $post['price_antar'];
			$orderAlamat 	= $post['order_alamat'];
			$km 			    = $post['km'];
			$typeShop 		= $post['type_shop'];
      /*$coord = BarangController::cekCoord($orderAlamat);

      if( isset($coord["lat"]) ){
        $post['order_lat'] = $coord["lat"];
        $post['order_lng'] = $coord["lng"];
        $orderLat = $coord["lat"];
        $orderLng = $coord["lng"];
      }*/

      //https://maps.google.com/maps/api/geocode/json?address=Komplek+Taman+Sungai+Raya+No.b6,+Sungai+Raya,+Kabupaten+Kubu+Raya,+Kalimantan+Barat,+Indonesia+null&key=AIzaSyCmABK4yYnveOGZf-kwxNn6fvqr1xMfH_o


			//barang yang dipesan
			/*
				[
					{
						"barang_id" : "1",
						"qty"		: "2",
						"note"		: "note 1"
						"services_id" : "1",
						"total"		: "50000",
						"toko_id" : "1"
					},{
						"barang_id" : "2",
						"qty"		: "1",
						"note"		: "note 2"
						"services_id" : "1",
						"total"		: "18000",
						"toko_id" : "1"
					}
				]
			*/

			$jsonBarang  = $post['order_barang'];
			for( $i=0;$i<500;$i++  ){
				$jsonBarang = str_replace("\n",'\n',$jsonBarang);
			}
			$barang = json_decode($jsonBarang,true);

			$isBroadcastToDriver = false;

			$toko  = [];
			$tokoId = "";

		    try{
          $post['toko_id'] = $barang[0]['toko_id'];
          $mToko = Toko::where("id",$post["toko_id"])->first();

		    	$m = new OrderServiceHistory;
		    	$m->user_id = $userId;
		    	$m->vendor_id = $vendorId;
		    	$m->order_lat = $orderLat;
		    	$m->order_lng = $orderLng;
		    	$m->order_ket_lain = $orderKetLain;
		    	$m->order_method = $orderMethod;
		    	$m->status = $orderStatus;
		    	$m->price = $totalPrice;
		    	$m->price_antar = $priceAntar;
		    	$m->km = $km;
		    	$m->order_alamat = $orderAlamat;
					$m->type_shop = $typeShop;
					if( $post["kode_voucher"] != "" && !is_null($post["kode_voucher"]) )
						$m->is_using_voucher = "yes";
					$m->voucher_kode = $post['kode_voucher'];
					$m->voucher_tipe = $post['tipe_voucher'];
					$m->voucher_nominal = $post['nominal_voucher'];
					$m->ekspedisi_id = $post['ekspedisi_id'];
					$m->ekspedisi_nama = $post['ekspedisi_nama'];
					$m->total_berat = $post['total_berat'];
          if( $post['ekspedisi_nama'] == "Jasa " . $mToko['nama'] ){
            $isAntarSendiri=true;
            $m->is_antar_sendiri="yes";
          }


	        if( $post['kode_voucher'] != "" && !is_null($post['kode_voucher']) ){
	            if( $post['tipe_voucher'] == "Prosentase" ){
	                $totalPrice = $totalPrice - (($totalPrice *  $post['nominal_voucher']) / 100);
	            }
	        }

				//cek poin
				if($mToko->is_using_poin=="yes"){
					$setPoin = PoinSetting::first();
					$priceForCheckPoin = $post['price_after_voucher'];
					if( $priceForCheckPoin <= 0 ){
						$priceForCheckPoin = $totalPrice;
					}

					if( $setPoin["is_point_active"] == "yes" && $priceForCheckPoin > $setPoin["nominal"] ){
							$poin = $setPoin['poin'];
							$kelipatan = $priceForCheckPoin / $setPoin["nominal"];
							$kelipatan = floor($kelipatan);
							$m->poin = $kelipatan;
							$isGetPoin = "yes";
					}
				}

				//end of cek poin

		    	if( isset($post["jasa_kirim"]) ){
		    		$m->jasa_kirim = $post["jasa_kirim"];
		    	}

		    	if($m->save()){
		    		//masukkan dalam order history
		    		$mOrder = new Orders;
		    		$mOrder->order_type_id = $post["tipe_barang"];
		    		$mOrder->order_id = $m->id;
		    		$mOrder->user_id = $post["user_id"];
		    		$mOrder->save();

						//simpan voucher , jika ada voucher maka hitung stok voucher
						if( !is_null($post['kode_voucher']) && $post['kode_voucher'] != ""){
							self::cekVoucher($post,$res);
							$vH = new VoucherHistory;
							$vH->user_id = $post['user_id'];
							$vH->voucher_kode = $post['kode_voucher'];
							$vH->voucher_tipe = $post['tipe_voucher'];
							$vH->voucher_nominal = $post['nominal_voucher'];
							$vH->created_at = date("Y-m-d H:i:s");
							$vH->order_service_history_id = $m->id;
							$vH->toko_id = $barang[0]['toko_id'];
							$vH->deskripsi = "no. transaksi #".$m->id;
							$vH->save();
						}


						//mengurangi pembagian hasil
						$besarBagiHasil = $mToko['nominal_bagi_hasil'];
						$tipeBagihasil  = $mToko['tipe_bagi_hasil'];
						$tipeBagihasilAmount = $mToko['tipe_bagi_hasil_amount'];
						if( $tipeBagihasil=="Prosentase" ){
							$ambil = ($totalPrice * $besarBagiHasil)/100;
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

            //bagi hasil ongkir antar sendiri, langunsung di potong bagi hasilnya
            if($isAntarSendiri){
              $besarBagiHasil = $mToko['nominal_bagi_hasil_ongkir'];
              $ambil = ($priceAntar * $besarBagiHasil)/100;
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
            }else{ //jika pakai jasa ekspedisi lainnya, ini di ganti di pemeili
              /*$e = Ekspedisi::where("id",$post['ekspedisi_id'])->first();
              $besarBagiHasil = $e['nominal_bagi_hasil'];
              $ambil = ($priceAntar * $besarBagiHasil)/100;
							$historySaldo = new TopupHistoryEkspedisi;
							$historySaldo->nominal = $ambil;
							$historySaldo->ekspedisi_id = $e->id;
							$historySaldo->status = "Success";
							$historySaldo->type = "kurang";
							$historySaldo->deskripsi = "Pengurangan transaksi (bagi hasil) ongkir, no. transaksi #" . $m->id;
							$historySaldo->save();

							//update ekspedisi
							$topupEkspedisi = TopupEkspedisi::where("ekspedisi_id",$e->id)->first();
							if( !isset($topupEkspedisi["ekspedisi_id"]) ){
								$topupEkspedisi = new TopupEkspedisi;
								$topupEkspedisi->ekspedisi_id = $e->id;
                $topupEkspedisi->nominal = 0;
							}
							$topupEkspedisi->nominal -= $ambil;
							$topupEkspedisi->save();*/
            }

		    		//jika pembayaran menggunakan saldo
						if( $m->order_method == 'Saldo'){
							$mSaldo = Topup::where("user_id",$userId)->first();
							$mSaldo->nominal = (float) $mSaldo->nominal - ( (float) $totalPrice + (float) $post['price_antar'] );
							if( $mSaldo->save() ){
								//catat di history
								$mSaldoHistory = new TopupHistory;
								$mSaldoHistory->nominal =  $totalPrice + $post['price_antar'];
								$mSaldoHistory->type = "kurang";
								$mSaldoHistory->status = "Success";
								$mSaldoHistory->user_id = $userId;
								$mSaldoHistory->alasan = "Pembelian, no transaksi #" . $m->id;
								$mSaldoHistory->save();
							}
						}

					$tokoArr = [];
					$tokoId = "";
		    	foreach ($barang as $b) {
		    			$mB = new OrderServiceBarangHistory;
		    			$mB->order_service_history_id = $m->id;
		    			$mB->barang_id = $b['barang_id'];
		    			$mB->services_id = $b['services_id'];
		    			$mB->toko_id = $b['toko_id'];
		    			$mB->qty = $b['qty'];
		    			$mB->total = $b['total'];
		    			$mB->note = $b['note'];

							$namaBarang = Barang::where("id",$b["barang_id"])->first();
							$tokoId = $b["toko_id"];

              //cek stok dan pengurangnnya
              if( $namaBarang->tipe_stock == "Real" ){
                  $namaBarang->stock -= $b['qty'];
                  $namaBarang->save();
              }
		    			//cek setting armada toko
		    			$setToko = SettingPenjual::where("toko_id",$b["toko_id"])->first();

		    			if( $setToko->type_jasa_pengiriman != "sendiri" ){
		    				$isBroadcastToDriver = true;
								$toko[] = ["lat" => $mToko->latitude , "lng" => $mToko->longitude ];
		    			}

						//temporaty barang
						$mB->nama_barang = $namaBarang->nama;
						$mB->harga_barang = $namaBarang->harga;
						$mB->kategori_barang_id = $namaBarang->kategori_barang_id;
						$mB->discount_barang = $namaBarang->discount;
						$mB->berat_barang = $namaBarang->berat;
						$mB->kd_print_barang = $namaBarang->kd_print;
						$mB->isi = $namaBarang->isi;
						$mB->tipe_isi = $namaBarang->tipe_isi;


						if( !isset($tokoArr[ $b["toko_id"] ]) )
							$tokoArr[ $b["toko_id"] ] = [];


						$tokoArr[ $b["toko_id"] ][] = [
							"nama_barang" => $namaBarang->nama,
							"note" => $b["note"],
							"qty" => $b["qty"],
							"nomor" => $mToko->no_telp,
							"nama_toko" => $mToko->nama
						];

						if( $orderMethod == "Ekspedisi" )
							$isBroadcastToDriver = false;

		    			$mB->save();
		    		}

					//push notification to UKM
					$pd = [
						"intent" => "move",
				    	"id" => $m->id,
				    	"action" => "pesanan_baru"
					];

					$pushD = PushNotif::pushTo("Hai Ada Pesanan Baru","Klik untuk detail",$mToko['firebase_token'],$pd);

		    	}
					//

		    	//jika toko mempunyai armada sendiri, maka tidak ada broadcast ke driver
					$isBroadcastToDriver=false;
		    	if($isBroadcastToDriver){
		    		//cari driver denga radius yang ada di setting
					$radius = (int) Settings::first()["radius_driver"];
					$inMiles = 3959;
					$inKm = 6371;

					$tokens = [];
					foreach($toko as $t){
						$sqlRadius = DB::Select("SELECT firebase_token FROM eo_vendor a
									  WHERE (
								          acos(sin(a.last_latitude * 0.0175) * sin(".$t['lat']." * 0.0175)
								               + cos(a.last_Latitude * 0.0175) * cos(".$t['lat']." * 0.0175) *
								                 cos((".$t['lng']." * 0.0175) - (a.last_longitude * 0.0175))
								              ) * $inKm
								      )  <= $radius and a.is_have_order = '0' and a.is_login = '1' and is_active  = '1' ");
						foreach ($sqlRadius as $v) {
							$tokens[] = $v->firebase_token;
						}

						if( count($tokens) > 0 ){
							break;
						}
					}

					if( count($tokens) <= 0 ){
						DB::rollBack();
						return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);
					}

				  $groupName = $m->id."barang"."order";
					$notifKey = PushNotif::requestDeviceGroup($groupName,$tokens);
					if( isset($notifKey["notification_key"]) ){
						$pushData = [
								"action" => "new_order",
				    		"intent" => "move",
				    		"id" => $m->id,
				    		"type_order" => $post["tipe_barang"],
				    		"type_vendor" => "Motor",
				    		"user_id" => $userId,

				    		"order_alamat" => $post["order_alamat"],
				    		"order_ket_lain" => $post["order_ket_lain"],
				    		"order_lat" => $post["order_lat"],
				    		"order_lng" => $post["order_lng"],
				    		"km" => $post["km"],
				    		"price_antar" => $post["price_antar"],
				    		"price" => $post["price"],
				    		"barang" => json_encode( DB::Select("select t.nama as toko_nama
				    				from eo_order_service_barang_history h
				    				inner join eo_toko t on t.id = h.toko_id
				    				where h.order_service_history_id = " . $m->id) )
						];

						$tipeBarang =  $post["tipe_barang"] == "4" ? "Makanan" : "Belanja";
						$push = PushNotif::pushTo("Pesanan Baru " . $tipeBarang,
							"Pesanan Baru telah di terima, klik untuk detail",$notifKey["notification_key"],$pushData);



						if( isset($push["success"]) && $push["success"] > 0 	 ){
							$m = OrderServiceHistory::where("id",$m->id)->first();
							$m->notif_name = $groupName;
							$m->notif_key = $notifKey["notification_key"];
							$m->save();
							DB::commit();
							return Res::cb($res,true,"Berhasil",['order'=>$barang]);
						}else{
							DB::rollBack();
							return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",[]);
						}
					}else{
						if( Settings::first()["is_mitra_get_order_by_sms"] ){
							//send notification by sms to mitra
							foreach ($tokoArr as $t) {
								$pes = "";
								$nomor = "";$namaToko = "";
								foreach ($t as $v) {
									$pes 	.= $v["nama_barang"] ." " . $v["qty"] . "\n";
									$pes 	.= "Note : " . $v["note"] . "\n";
									$nomor = $v["nomor"];
									$namaToko = $v["nama_toko"];
								}
								$prefix = "Pesanan Baru ".date('d-M-y H:i:s')."\n";
								$alamat = "\n\n" . $post['order_alamat'];
								$messageTosend = $prefix . $pes . $alamat;

								//send it
								PushNotif::sms($messageTosend,"",$nomor);

							}
						}
						DB::commit();
						return Res::cb($res,true,"Berhasil",['order'=>$barang]);
					}

		    	}else{
		    		if( Settings::first()["is_mitra_get_order_by_sms"] ){
			    		//send notification by sms to mitra
						foreach ($tokoArr as $t) {
							$pes = "";
							$nomor = "";$namaToko = "";
							foreach ($t as $v) {
								$pes 	.= $v["nama_barang"] ." " . $v["qty"] . "\n";
								$pes 	.= "Note : " . $v["note"] . "\n";
								$nomor = $v["nomor"];
								$namaToko = $v["nama_toko"];
							}
							$prefix = "Pesanan Baru ".date('d-M-y H:i:s')."\n";
							$alamat = "\n\n" . $post['order_alamat'];
							$messageTosend = $prefix . $pes . $alamat;

							//send it
							PushNotif::sms($messageTosend,"",$nomor);

						}
					}
		    		DB::commit();
						$barang["is_get_poin"] = $isGetPoin;
		    		return Res::cb($res,true,"Berhasil",['barang' => $barang]);
		    	}
			}catch(Exception $e){
				DB::rollBack();
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

    public static function order2(Request $req,Response $res){
			$post = $req->getParsedBody();
			DB::beginTransaction();
			$isGetPoin  = "no";
      $isAntarSendiri=false;
      //var_dump($post);die();
			if(  !isset($post['version_code']) ){
				return Res::cb($res,false,"Untuk melanjutkan pesanan, silahkan update dulu aplikasi TokoKota yang terbaru di Playstore, terimakasih");
			}

			$userId 		= $post['user_id'];
			$vendorId 		= null;
			$orderLat 		= $post['order_lat'];
			$orderLng 		= $post['order_lng'];
			$orderKetLain 	= $post['order_ket_lain'];
			$orderMethod 	= $post['order_method'];
			$orderStatus 	= $post['status'];
			$totalPrice 	= (float) $post['price'];
			$priceAntar 	= (float) $post['price_antar'];
			$orderAlamat 	= $post['order_alamat'];
			$km 			= $post['km'];
      $coord = BarangController::cekCoord($orderAlamat);

      if( isset($coord["lat"]) ){
        $post['order_lat'] = $coord["lat"];
        $post['order_lng'] = $coord["lng"];
        $orderLat = $coord["lat"];
        $orderLng = $coord["lng"];
      }

      //https://maps.google.com/maps/api/geocode/json?address=Komplek+Taman+Sungai+Raya+No.b6,+Sungai+Raya,+Kabupaten+Kubu+Raya,+Kalimantan+Barat,+Indonesia+null&key=AIzaSyCmABK4yYnveOGZf-kwxNn6fvqr1xMfH_o


			//barang yang dipesan
			/*
				[
					{
						"barang_id" : "1",
						"qty"		: "2",
						"note"		: "note 1"
						"services_id" : "1",
						"total"		: "50000",
						"toko_id" : "1"
					},{
						"barang_id" : "2",
						"qty"		: "1",
						"note"		: "note 2"
						"services_id" : "1",
						"total"		: "18000",
						"toko_id" : "1"
					}
				]
			*/

			$jsonBarang  = $post['order_barang'];
			for( $i=0;$i<500;$i++  ){
				$jsonBarang = str_replace("\n",'\n',$jsonBarang);
			}

			//return Res::cb($res,false,$jsonBarang);
			if( !is_array($jsonBarang) )
				$barang = json_decode($jsonBarang,true);
			else $barang = $post['order_barang'];
			//echo $barang;die();
			$isBroadcastToDriver = false;

			$toko  = [];
			$tokoId = "";

		    try{
          $post['toko_id'] = $barang[0]['toko_id'];
          $mToko = Toko::where("id",$post["toko_id"])->first();

					$m = new OrderServiceHistory;
		    	$m->user_id = $userId;
		    	$m->vendor_id = $vendorId;
		    	$m->order_lat = $orderLat;
		    	$m->order_lng = $orderLng;
		    	$m->order_ket_lain = $orderKetLain;
		    	$m->order_method = $orderMethod;
		    	$m->status = $orderStatus;
		    	$m->price = $totalPrice;
		    	$m->price_antar = $priceAntar;
		    	$m->km = $km;
		    	$m->order_alamat = $orderAlamat;
					if( $post["kode_voucher"] != "" && !is_null($post["kode_voucher"]) )
						$m->is_using_voucher = "yes";
					$m->voucher_kode = $post['kode_voucher'];
					$m->voucher_tipe = $post['tipe_voucher'];
					$m->voucher_nominal = $post['nominal_voucher'];
					$m->ekspedisi_id = $post['ekspedisi_id'];
					$m->ekspedisi_nama = $post['ekspedisi_nama'];
					$m->total_berat = $post['total_berat'];
          if( $post['ekspedisi_nama'] == "Jasa " . $mToko['nama'] ){
            $isAntarSendiri=true;
            $m->is_antar_sendiri="yes";
          }


	        if( $post['kode_voucher'] != "" && !is_null($post['kode_voucher']) ){
	            if( $post['tipe_voucher'] == "Prosentase" ){
	                $totalPrice = $totalPrice - (($totalPrice *  $post['nominal_voucher']) / 100);
	            }
	        }

				//cek poin
				$setPoin = PoinSetting::first();
				$priceForCheckPoin = $post['price_after_voucher'];
				if( $priceForCheckPoin <= 0 ){
					$priceForCheckPoin = $totalPrice;
				}

				if( $setPoin["is_point_active"] == "yes" && $priceForCheckPoin > $setPoin["nominal"] ){
						$poin = $setPoin['poin'];
            $kelipatan = $priceForCheckPoin / $setPoin["nominal"];
            $kelipatan = floor($kelipatan);
						$m->poin = $kelipatan;
						$isGetPoin = "yes";
				}

		    	if( isset($post["jasa_kirim"]) ){
		    		$m->jasa_kirim = $post["jasa_kirim"];
		    	}

		    	if($m->save()){
		    		//masukkan dalam order history
		    		$mOrder = new Orders;
		    		$mOrder->order_type_id = $post["tipe_barang"];
		    		$mOrder->order_id = $m->id;
		    		$mOrder->user_id = $post["user_id"];
		    		$mOrder->save();

						//simpan voucher , jika ada voucher maka hitung stok voucher
						if( !is_null($post['kode_voucher']) && $post['kode_voucher'] != ""){
							self::cekVoucher($post,$res);
							$vH = new VoucherHistory;
							$vH->user_id = $post['user_id'];
							$vH->voucher_kode = $post['kode_voucher'];
							$vH->voucher_tipe = $post['tipe_voucher'];
							$vH->voucher_nominal = $post['nominal_voucher'];
							$vH->created_at = date("Y-m-d H:i:s");
							$vH->order_service_history_id = $m->id;
							$vH->toko_id = $barang[0]['toko_id'];
							$vH->deskripsi = "no. transaksi #".$m->id;
							$vH->save();
						}


						//mengurangi pembagian hasil
						$besarBagiHasil = $mToko['nominal_bagi_hasil'];
						$tipeBagihasil  = $mToko['tipe_bagi_hasil'];
						$tipeBagihasilAmount = $mToko['tipe_bagi_hasil_amount'];
						if( $tipeBagihasil=="Prosentase" ){
							$ambil = ($totalPrice * $besarBagiHasil)/100;
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

            //bagi hasil ongkir antar sendiri, langunsung di potong bagi hasilnya
            if($isAntarSendiri){
              $besarBagiHasil = $mToko['nominal_bagi_hasil_ongkir'];
              $ambil = ($priceAntar * $besarBagiHasil)/100;
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
            }else{ //jika pakai jasa ekspedisi lainnya, ini di ganti di pemeili
              /*$e = Ekspedisi::where("id",$post['ekspedisi_id'])->first();
              $besarBagiHasil = $e['nominal_bagi_hasil'];
              $ambil = ($priceAntar * $besarBagiHasil)/100;
							$historySaldo = new TopupHistoryEkspedisi;
							$historySaldo->nominal = $ambil;
							$historySaldo->ekspedisi_id = $e->id;
							$historySaldo->status = "Success";
							$historySaldo->type = "kurang";
							$historySaldo->deskripsi = "Pengurangan transaksi (bagi hasil) ongkir, no. transaksi #" . $m->id;
							$historySaldo->save();

							//update ekspedisi
							$topupEkspedisi = TopupEkspedisi::where("ekspedisi_id",$e->id)->first();
							if( !isset($topupEkspedisi["ekspedisi_id"]) ){
								$topupEkspedisi = new TopupEkspedisi;
								$topupEkspedisi->ekspedisi_id = $e->id;
                $topupEkspedisi->nominal = 0;
							}
							$topupEkspedisi->nominal -= $ambil;
							$topupEkspedisi->save();*/
            }

		    		//jika pembayaran menggunakan saldo
						if( $m->order_method == 'Saldo'){
							$mSaldo = Topup::where("user_id",$userId)->first();
							$mSaldo->nominal = (float) $mSaldo->nominal - ( (float) $totalPrice + (float) $post['price_antar'] );
							if( $mSaldo->save() ){
								//catat di history
								$mSaldoHistory = new TopupHistory;
								$mSaldoHistory->nominal =  $totalPrice + $post['price_antar'];
								$mSaldoHistory->type = "kurang";
								$mSaldoHistory->status = "Success";
								$mSaldoHistory->user_id = $userId;
								$mSaldoHistory->alasan = "Pembelian, no transaksi #" . $m->id;
								$mSaldoHistory->save();
							}
						}

					$tokoArr = [];
					$tokoId = "";
		    	foreach ($barang as $b) {
		    			$mB = new OrderServiceBarangHistory;
		    			$mB->order_service_history_id = $m->id;
		    			$mB->barang_id = $b['barang_id'];
		    			$mB->services_id = $b['services_id'];
		    			$mB->toko_id = $b['toko_id'];
		    			$mB->qty = $b['qty'];
		    			$mB->total = $b['total'];
		    			$mB->note = $b['note'];

							$namaBarang = Barang::where("id",$b["barang_id"])->first();
							$tokoId = $b["toko_id"];

              //cek stok dan pengurangnnya
              if( $namaBarang->tipe_stock == "Real" ){
                  $namaBarang->stock -= $b['qty'];
                  $namaBarang->save();
              }
		    			//cek setting armada toko
		    			$setToko = SettingPenjual::where("toko_id",$b["toko_id"])->first();

		    			//if( $setToko->type_jasa_pengiriman != "sendiri" ){
		    				//$isBroadcastToDriver = true;
								//$toko[] = ["lat" => $mToko->latitude , "lng" => $mToko->longitude ];
		    		//	}

						//temporaty barang
						$mB->nama_barang = $namaBarang->nama;
						$mB->harga_barang = $namaBarang->harga;
						$mB->kategori_barang_id = $namaBarang->kategori_barang_id;
						$mB->discount_barang = $namaBarang->discount;
						$mB->berat_barang = $namaBarang->berat;
						$mB->kd_print_barang = $namaBarang->kd_print;
						$mB->isi = $namaBarang->isi;
						$mB->tipe_isi = $namaBarang->tipe_isi;


						if( !isset($tokoArr[ $b["toko_id"] ]) )
							$tokoArr[ $b["toko_id"] ] = [];

						$tokoArr[ $b["toko_id"] ][] = [
							"nama_barang" => $namaBarang->nama,
							"note" => $b["note"],
							"qty" => $b["qty"],
							"nomor" => $mToko->no_telp,
							"nama_toko" => $mToko->nama
						];

						if( $orderMethod == "Ekspedisi" )
								$isBroadcastToDriver = false;

		    			$mB->save();
		    		}


					//push notification to UKM
					$pd = [
						"intent" => "move",
				    	"id" => $m->id,
				    	"action" => "pesanan_baru"
					];

					$pushD = PushNotif::pushTo("Hai Ada Pesanan Baru","Klik untuk detail",$mToko['firebase_token'],$pd);

		    	}
					//

		    	//jika toko mempunyai armada sendiri, maka tidak ada broadcast ke driver
					$isBroadcastToDriver=false;
		    	if($isBroadcastToDriver){
		    		//cari driver denga radius yang ada di setting
					$radius = (int) Settings::first()["radius_driver"];
					$inMiles = 3959;
					$inKm = 6371;

					$tokens = [];
					foreach($toko as $t){
						$sqlRadius = DB::Select("SELECT firebase_token FROM eo_vendor a
									  WHERE (
								          acos(sin(a.last_latitude * 0.0175) * sin(".$t['lat']." * 0.0175)
								               + cos(a.last_Latitude * 0.0175) * cos(".$t['lat']." * 0.0175) *
								                 cos((".$t['lng']." * 0.0175) - (a.last_longitude * 0.0175))
								              ) * $inKm
								      )  <= $radius and a.is_have_order = '0' and a.is_login = '1' and is_active  = '1' ");
						foreach ($sqlRadius as $v) {
							$tokens[] = $v->firebase_token;
						}

						if( count($tokens) > 0 ){
							break;
						}
					}

					if( count($tokens) <= 0 ){
						DB::rollBack();
						return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);
					}

				  $groupName = $m->id."barang"."order";
					$notifKey = PushNotif::requestDeviceGroup($groupName,$tokens);
					if( isset($notifKey["notification_key"]) ){
						$pushData = [
								"action" => "new_order",
				    		"intent" => "move",
				    		"id" => $m->id,
				    		"type_order" => $post["tipe_barang"],
				    		"type_vendor" => "Motor",
				    		"user_id" => $userId,

				    		"order_alamat" => $post["order_alamat"],
				    		"order_ket_lain" => $post["order_ket_lain"],
				    		"order_lat" => $post["order_lat"],
				    		"order_lng" => $post["order_lng"],
				    		"km" => $post["km"],
				    		"price_antar" => $post["price_antar"],
				    		"price" => $post["price"],
				    		"barang" => json_encode( DB::Select("select t.nama as toko_nama
				    				from eo_order_service_barang_history h
				    				inner join eo_toko t on t.id = h.toko_id
				    				where h.order_service_history_id = " . $m->id) )
						];

						$tipeBarang =  $post["tipe_barang"] == "4" ? "Makanan" : "Belanja";
						$push = PushNotif::pushTo("Pesanan Baru " . $tipeBarang,
							"Pesanan Baru telah di terima, klik untuk detail",$notifKey["notification_key"],$pushData);



						if( isset($push["success"]) && $push["success"] > 0 	 ){
							$m = OrderServiceHistory::where("id",$m->id)->first();
							$m->notif_name = $groupName;
							$m->notif_key = $notifKey["notification_key"];
							$m->save();
							DB::commit();
							return Res::cb($res,true,"Berhasil",['order'=>$barang]);
						}else{
							DB::rollBack();
							return Res::cb($res,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",[]);
						}
					}else{
						if( Settings::first()["is_mitra_get_order_by_sms"] ){
							//send notification by sms to mitra
							foreach ($tokoArr as $t) {
								$pes = "";
								$nomor = "";$namaToko = "";
								foreach ($t as $v) {
									$pes 	.= $v["nama_barang"] ." " . $v["qty"] . "\n";
									$pes 	.= "Note : " . $v["note"] . "\n";
									$nomor = $v["nomor"];
									$namaToko = $v["nama_toko"];
								}
								$prefix = "Pesanan Baru ".date('d-M-y H:i:s')."\n";
								$alamat = "\n\n" . $post['order_alamat'];
								$messageTosend = $prefix . $pes . $alamat;

								//send it
								PushNotif::sms($messageTosend,"",$nomor);

							}
						}
						DB::commit();
						return Res::cb($res,true,"Berhasil",['order'=>$barang]);
					}

		    	}else{
		    		if( Settings::first()["is_mitra_get_order_by_sms"] ){
			    		//send notification by sms to mitra
						foreach ($tokoArr as $t) {
							$pes = "";
							$nomor = "";$namaToko = "";
							foreach ($t as $v) {
								$pes 	.= $v["nama_barang"] ." " . $v["qty"] . "\n";
								$pes 	.= "Note : " . $v["note"] . "\n";
								$nomor = $v["nomor"];
								$namaToko = $v["nama_toko"];
							}
							$prefix = "Pesanan Baru ".date('d-M-y H:i:s')."\n";
							$alamat = "\n\n" . $post['order_alamat'];
							$messageTosend = $prefix . $pes . $alamat;

							//send it
							PushNotif::sms($messageTosend,"",$nomor);

						}
					}
		    		DB::commit();
						$barang["is_get_poin"] = $isGetPoin;
		    		return Res::cb($res,true,"Berhasil",['barang' => $barang]);
		    	}
			}catch(Exception $e){
				DB::rollBack();
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllBarangByEtalase(Request $req,Response $res){
			$nameDay = Helper::getNameDayInd(date("N"));
			$id = $req->getAttribute('id'); //etalase id
			$uId = $req->getAttribute('user_id'); //user id
			$p = $req->getParsedBody();
			$sort = $p['sort'];
			$tokoId = $p['toko_id'];
			$orderBy = "";$filter="";
			if( $sort == "A to Z" )
				$orderBy = "order by b.nama asc";
			else if( $sort == "Z to A" )
				$orderBy = "order by b.nama desc";
			else if( $sort == "Terbaru" )
				$orderBy = "order by b.created_at desc";
			else if( $sort == "Diskon Terbanyak" )
				$orderBy = "order by b.created_at desc";

      $whFilter="";
      if( isset($_GET['filter']) ){
        $filter = $_GET['filter'];
        if( $filter == "Ready" ){
          $whFilter = "and ( b.tipe_stock = 'Ready' or stock > 0 )";
        }else if( $filter == "Semua" ) $whFilter = "";
      }



			if( isset($_GET['kota_id']) ){
				$whKota = "and t.default_city_id = " . $_GET["kota_id"];
			}else $whKota = "";
			$whEtalase = "	and etalase_id = $id";
			if($id =="0"){
				$whEtalase = "";
			}

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
								b.kondisi,
								b.discount,
								b.isi as berat,
								b.tipe_isi as tipe_berat,
								b.viewer,
								b.stock,
								b.type_berat,
								b.tipe_stock,
								b.kategori_barang_id,
								t.nama as nama_toko,
								p.id as setting_id,
								p.type_biaya_antar,
								p.type_jasa_pengiriman,
								p.price_flat,
								t.alamat_jalan alamat,
								t.city_id,
								t.province_id,
								t.toko_alamat_gmap,
								sd.h_".strtolower($nameDay)." as status_toko,
								(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo,
								case when f.id is null then 'no' else 'yes' end as is_favorite
								from eo_barang b

								inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota and t.id  = $tokoId
								inner join eo_setting_penjual p on p.toko_id = t.id
								inner join eo_setting_day_toko sd on sd.toko_id = t.id


								left join eo_favorite f on f.from_id = b.id and f.tipe = 'Barang' and f.user_id = $uId

								where  b.visible = 'yes' and b.kategori_barang_id != 0

								$whEtalase

                $whFilter

								$orderBy
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

				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function cekVoucher($p,$res){
      $kode = $p['kode_voucher'];
			$tokoId = $p['toko_id']; //sample barangid
			$c = Voucher::where("kode",$kode)->where("toko_id",$tokoId)->first();
      if( isset($c['kode']) ){
					//cek penggunaan user
					$maxPerUser = $c->max_per_user;
					$max = $c->max_penggunaan;
					$tipe = $c->tipe;
					$nominal = $c->nominal;
					$h = VoucherHistory::where("user_id",$p['user_id'])->where("voucher_kode",$kode)->get();
					if( count($h) < $maxPerUser ){
						//cek jumlah maksimal
						$h = VoucherHistory::where("voucher_kode",$kode)->get();
						if( count($h) < $max){
								//Iinput history_id
								return Res::cb($res,true,'Berhasil',["voucher" => $c]);
						}else{
								return Res::cb($res,false,'Maaf jatah penggunaan Voucher ini sudah habis');
						}
					}else{
							return Res::cb($res,false,'Maaf jatah penggunaan Voucher ini sudah habis');
					}

      }else
        return Res::cb($res,false,'Kode voucher tidak valid');
		}

    public static function getAllBarangByTokoByKat(Request $req,Response $res){
			$hasNext = true;
			$id = $req->getAttribute('toko_id'); //toko
			$nameDay = Helper::getNameDayInd(date("N"));
			$currentPage = $req->getAttribute("current_page");
			$userId = $req->getAttribute('user_id');
			$kategoriId = $req->getAttribute('kategori_id');

			//setting belanja by kota
			$whKota = "";
			if( Settings::first()->is_belanja_by_kota == 'yes' ){
				$uKota = User::where("id",$userId)->first()->kota_id;
				$whKota = " and (t.city_id = " . $uKota . " or t.is_toko_khusus = 'yes')";
			}


			$total = DB::select("
										select count(1) as jum from eo_barang b
										inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
										inner join eo_setting_penjual p on p.toko_id = t.id
										inner join eo_setting_day_toko sd on sd.toko_id = t.id

										where t.id = $id and b.kategori_barang_id = $kategoriId
											");

				$total = $total[0]->jum;
				$limit = 1000;
				$totalPages = ceil($total / $limit);
			if($currentPage > $totalPages){
				return Res::cb($res,true,"Berhasil",['hasNext' => false,'barang' => []]);
			}
			if( $currentPage < 0 ) $currentPage = 1;

			$offset = ($currentPage - 1) * $limit;

			//cek jika punya next item
			if( ($currentPage -$totalPages)  == 0){
				$hasNext = false;
			}

				try{
					$barang = DB::select("
										select t.id as toko_id,t.latitude,t.longitude,
										b.nama,
										b.id,
										b.toko_id,
										b.harga,
										b.stock,
										b.kondisi,
										b.discount,
										b.berat,
										b.tipe_stock,
										b.viewer,
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
										(select path from eo_foto_barang fb where fb.barang_id = b.id limit 1) as photo
										from eo_barang b

										inner join eo_toko t on t.id = b.toko_id and t.visible = 'yes' $whKota
										inner join eo_setting_penjual p on p.toko_id = t.id
										inner join eo_setting_day_toko sd on sd.toko_id = t.id
										inner join eo_kategori_barang kb on kb.id = b.kategori_barang_id

										where t.id = $id and b.kategori_barang_id = $kategoriId
										order by b.nama asc
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

				return Res::cb($res,true,"Berhasil",['hasNext' => $hasNext,'barang' => $newBarang]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}


	}
