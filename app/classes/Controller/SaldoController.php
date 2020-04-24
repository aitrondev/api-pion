<?php

    namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\TopupHistory;
	use Models\Topup;
	use Models\TopupVendor;
	use Models\TopupHistoryVendor;
	use Models\TopupHistoryToko;
	use Models\TopupSetting;
	use Models\TopupToko;
	use Models\Bank;

	use Tools\Res;
	use Tools\Encrypt;

    class SaldoController {
        protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function saldoUser(Request $req, Response $res){
			$id = $req->getAttribute("user_id");
			$m = Topup::where("user_id",$id)->first();
			if( is_null($m) ) $saldo = 0;
			else $saldo = $m->nominal;
			return Res::cb($res,true,"Berhasil",["saldo" => $saldo]);

		}

		public static function konfirmTopupUser(Request $req, Response $res){
			$p = $req->getParsedBody();
			if( isset($p["user_id"]) && isset($p["topup_id"])  ){


				if($p["no_rek"] == "")
					return Res::cb($res,false,"Nomor Rekening tidak boleh kosong");
				if($p["atas_nama"] == "")
					return Res::cb($res,false,"Atas Nama Rekening tidak boleh kosong");
				if($p["bank"] == "")
					return Res::cb($res,false,"Bank tidak boleh kosong");


				$m = TopupHistory::where("id",$p["topup_id"])->where("user_id",$p["user_id"])->first();
				$m->status = "Confirm";
				$m->to_norek = $p["no_rek"];
				$m->to_bank = $p["bank"];
				$m->to_atasnama = $p["atas_nama"];
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

		public static function batalTopupUser(Request $req, Response $res){
			$p = $req->getParsedBody();
			if( isset($p["user_id"]) && isset($p["topup_id"])  ){
				$m = TopupHistory::where("id",$p["topup_id"])->where("user_id",$p["user_id"])->first();
				$m->status = "Batal";
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

		public static function topup(Request $req, Response $res){
			$post = $req->getParsedBody();
      $set = TopupSetting::first();

			if(!is_numeric($post["nominal"])){
				return Res::cb($res,false,"Nominal tidak valid");
			}

			if( $post["nominal"] == "" || empty($post["nominal"] )
					|| $post["nominal"]  == null  || (float) $post["nominal"] < $set["min_topup"] ){
				return Res::cb($res,false,"Maaf minimial pengisian saldo adalah " . $set["min_topup"],['saldo'=>[]]);
			}

      if( $post['nominal'] > $set["max_topup"] ){
        	return Res::cb($res,false,"Maaf maximal pengisian saldo adalah " . $set["max_topup"],['saldo'=>[]]);
      }



			if( isset($post['user_id']) && isset($post['bank']) &&  isset($post['nominal']) ){
				$cek = DB::Select("select id from eo_topup_history where user_id = " . $post["user_id"] . " and (status = 'Pending' or status = 'Confirm' )");
				if( count($cek) > 0 ){
					return Res::cb($res,false,"Maaf Anda masih memilik topup yang masih tertunda, harap selesaikan topup atau batalkan topup",['saldo'=>[]]);
				}

				if( strlen($post["nominal"]) < 4 ){
					return Res::cb($res,false,"Maaf minimial pengisian saldo adalah Rp. 10.000,-",['saldo'=>[]]);
				}


				//save to history
				$mTopupHistory = new TopupHistory;
				$mTopupHistory->user_id = $post['user_id'];
				$mTopupHistory->type = "tambah";
				$mTopupHistory->nominal = $post['nominal'];
				$mTopupHistory->bank = $post['bank'];
				$mTopupHistory->tuqu_bank_id = $post['tuqu_bank_id'];
				$mTopupHistory->tuqu_bank_nama = $post['tuqu_bank_nama'];
				$mTopupHistory->tuqu_bank_icon = $post['tuqu_bank_icon'];
				$mTopupHistory->tuqu_bank_atasnama = $post['tuqu_bank_atasnama'];
				$mTopupHistory->tuqu_bank_norek = $post['tuqu_bank_norek'];

				//generate random 3 digits
				//bandingkan kode dengan yang ada di db

				while(1){
					$rand = rand(387,915);
					$digits3  = substr($rand, 0,3);
					$f = TopupHistory::where("kode_unik",$digits3)->where("status","Pending")->get();
					if( count($f) > 0 ){
						continue;
					}else break;
				}


				$pref = 0;
				if( strlen($post["nominal"])  == 5 ){
					$pref = substr($post["nominal"],0,2);
				}else if( strlen($post["nominal"])  == 6 ){
					$pref = substr($post["nominal"],0,3);
				}else if( strlen($post["nominal"])  == 7 ){
					$pref = substr($post["nominal"],0,4);
				}

				//$mTopupHistory->nominal = $pref . $rand;  // ini pakai kode unik
				$mTopupHistory->nominal = $post["nominal"];
				$mTopupHistory->kode_unik = $rand;

				if($mTopupHistory->save()){
					//update topup
					//cek jika sudah ada user_id
					$u = Topup::where("user_id",$post["user_id"])->get();
					if( count($u) <= 0 ){ //jika tidak ada
						$u = new Topup;
						$u->user_id =  $post['user_id'];
						$u->nominal = 0;
						$u->save();
					}
					return Res::cb($res,true,"Berhasil",['saldo'=>[ "nominal" => $mTopupHistory->nominal,"id"=>$mTopupHistory->id ] ]);
				}else{
					return Res::cb($res,false,"Gagal",['saldo'=>[]]);
				}


			}else{
				return Res::cb($res,false,"Request Anda tidak valid",[]);
			}
		}

    public static function topupVendor(Request $req, Response $res){
      $post = $req->getParsedBody();
      $set = TopupSetting::first();

			if( isset($post['vendor_id'])
        && isset($post['nominal'])
        && isset($post['bank_id']) ){

        //cek minimal saldo
        if( $post['nominal'] < $set['min_topup'] ){
          return Res::cb($res,false,"Maaf minimal pengisian deposit adalah Rp. " . $set['min_topup'],['saldo'=>[]]);
        }

        //cek max saldo
        if( $post['nominal'] > $set['max_topup'] ){
          return Res::cb($res,false,"Maaf maximal pengisian deposit adalah Rp. " . $set['max_topup'],['saldo'=>[]]);
        }

				//check apakah masih ada topup yang belum di konfirmasi
			//	$cek = TopupHistoryVendor::where("vendor_id",$post["vendor_id"])->where("status","Pending")->get();
				$cek = DB::Select("select id from eo_topup_history_vendor where vendor_id = " . $post["vendor_id"] . " and (status = 'Pending' or status = 'Confirm' )");
				if( count($cek) > 0 ){
					return Res::cb($res,false,"Maaf Anda masih memilik deposit yang masih tertunda, harap selesaikan deposit atau batalkan deposit",['saldo'=>[]]);
				}


				//save to history
				$mTopupHistory = new TopupHistoryVendor;
				$mTopupHistory->vendor_id = $post['vendor_id'];
				$mTopupHistory->type = "tambah";
				$mTopupHistory->nominal = $post['nominal'];
				$mTopupHistory->bank_id = $post['bank_id'];

				//generate random 3 digits
				//bandingkan kode dengan yang ada di db

				while(1){
					$rand = rand(387,915);
					$digits3  = substr($rand, 0,3);
					$f = TopupHistoryVendor::where("kode_unik",$digits3)->where("status","Pending")->get();
					if( count($f) > 0 ){
						continue;
					}else break;
				}


				$pref = 0;
				if( strlen($post["nominal"])  == 5 ){
					$pref = substr($post["nominal"],0,2);
				}else if( strlen($post["nominal"])  == 6 ){
					$pref = substr($post["nominal"],0,3);
				}else if( strlen($post["nominal"])  == 7 ){
					$pref = substr($post["nominal"],0,4);
				}

				//$mTopupHistory->nominal = $pref . $rand;  //topup dengan 3 kode unik
				//$mTopupHistory->nominal = $post["nominal"];
				//$mTopupHistory->kode_unik = $rand;


				if($mTopupHistory->save()){
					$u = TopupVendor::where("vendor_id",$post["vendor_id"])->get();
					if( count($u) <= 0 ){ //jika tidak ada
						$u = new TopupVendor;
						$u->vendor_id =  $post['vendor_id'];
						$u->nominal = 0;
						$u->save();
					}

					//ambil data dari vendor
					$mVendor = DB::Select("select
								b.atas_nama,
								b.no_rek,
								b.nama as nama_bank
								from eo_vendor v
								inner join eo_bank b on b.id = v.bank_id");
					$mVendor = $mVendor[0];
					$data = [
							"id" => $mTopupHistory->id,
							"atas_nama" => $mVendor->atas_nama,
							"bank" => $mVendor->nama_bank,
							"transfer" => $mTopupHistory->nominal,
							"no_rek" => $mVendor->no_rek
					];

					return Res::cb($res,true,"Berhasil",['saldo'=>$data ]);
				}else{
					return Res::cb($res,false,"Gagal",['saldo'=>[]]);
				}


			}else{
				return Res::cb($res,false,"Request Anda tidak valid",[]);
			}

		}

    public static function topupToko(Request $req, Response $res){
      $post = $req->getParsedBody();
      $set = TopupSetting::first();

      if( isset($post['toko_id']) && isset($post['nominal']) ){
        //cek minimal saldo
        if( $post['nominal'] < $set['min_topup'] ){
          return Res::cb($res,false,"Maaf minimal pengisian deposit adalah Rp. " . $set['min_topup'],['saldo'=>[]]);
        }

        //cek max saldo
        if( $post['nominal'] > $set['max_topup'] ){
          return Res::cb($res,false,"Maaf maximal pengisian deposit adalah Rp. " . $set['max_topup'],['saldo'=>[]]);
        }

        //check apakah masih ada topup yang belum di konfirmasi
        //$cek = TopupHistoryVendor::where("vendor_id",$post["vendor_id"])->where("status","Pending")->get();
        $cek = DB::Select("select id from eo_topup_history_toko where toko_id = " . $post["toko_id"] . " and (status = 'Pending' or status = 'Confirm' )");
        if( count($cek) > 0 ){
          return Res::cb($res,false,"Maaf Anda masih memilik deposit yang masih tertunda, harap selesaikan deposit atau batalkan deposit",['saldo'=>[]]);
        }


        //save to history
        $mTopupHistory = new TopupHistoryToko;
        $mTopupHistory->toko_id = $post['toko_id'];
        $mTopupHistory->type = "tambah";
        $mTopupHistory->nominal = $post['nominal'];
        $mTopupHistory->bank_id = $post['bank_id'];
        $mTopupHistory->status = "Pending";

        //generate random 3 digits
        //bandingkan kode dengan yang ada di db
        while(1){
          $rand = rand(387,915);
          $digits3  = substr($rand, 0,3);
          $f = TopupHistoryVendor::where("kode_unik",$digits3)->where("status","Pending")->get();
          if( count($f) > 0 ){
            continue;
          }else break;
        }


        $pref = 0;
        if( strlen($post["nominal"])  == 5 ){
          $pref = substr($post["nominal"],0,2);
        }else if( strlen($post["nominal"])  == 6 ){
          $pref = substr($post["nominal"],0,3);
        }else if( strlen($post["nominal"])  == 7 ){
          $pref = substr($post["nominal"],0,4);
        }

        //$mTopupHistory->nominal = $pref . $rand;  //topup dengan 3 kode unik
        //$mTopupHistory->nominal = $post["nominal"];
        $mTopupHistory->kode_unik = $rand;


        if($mTopupHistory->save()){
          $u = TopupToko::where("toko_id",$post["toko_id"])->get();
          if( count($u) <= 0 ){ //jika tidak ada
            $u = new TopupToko;
            $u->toko_id =  $post['toko_id'];
            $u->nominal = 0;
            $u->save();
          }

          //ambil data dari vendor
          $bank = Bank::where("id",$post['bank_id'])->first();
          $data = [
              "id" => $mTopupHistory->id,
              "atas_nama" => $bank->atas_nama,
              "bank" => $bank->nama,
              "transfer" => $mTopupHistory->nominal,
              "no_rek" => $bank->no_rek
          ];

          return Res::cb($res,true,"Berhasil",['saldo'=>$data ]);
        }else{
          return Res::cb($res,false,"Gagal",['saldo'=>[]]);
        }


      }else{
        return Res::cb($res,false,"Request Anda tidak valid",[]);
      }

    }

    public static  function konfirmToko(Request $req, Response $res)
    {

    	$p = $req->getParsedBody();
			if( isset($p["toko_id"]) && isset($p["topup_id"])  ){
				$m = TopupHistoryToko::where("id",$p["topup_id"])->where("toko_id",$p["toko_id"])->first();
				$m->status = "Confirm";
				$m->to_norek = $p["no_rek"];
				$m->to_bank = $p["bank"];
				$m->to_atasnama = $p["atas_nama"];
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

		public static function batalToko(Request $req, Response $res){
			$p = $req->getParsedBody();
			if( isset($p["toko_id"]) && isset($p["topup_id"])  ){
				$m = TopupHistoryToko::where("id",$p["topup_id"])->where("toko_id",$p["toko_id"])->first();
				$m->status = "Batal";
        $m->deskripsi = "di batalkan oleh toko";
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

    public static function historyToko(Request $req, Response $res){
      $p = $req->getParsedBody();
    //  var_dump($p);die();
      $m = DB::Select("select h.*,b.nama,b.atas_nama,b.no_rek from eo_topup_history_toko h
          inner join eo_bank b on b.id = h.bank_id
          where toko_id = ".$p["toko_id"]." and (status = 'Pending' or status = 'Confirm') ");
      $h = [];
      if(count($m) > 0){
        $h = $m[ count($m) -1 ];
      }
      return Res::cb($res,true,"Berhasil",["history" => $h]);
    }

    public static function depositToko(Request $req, Response $res){
      $id = $req->getAttribute("toko_id");
      $m = TopupToko::where("toko_id",$id)->first();
      if( is_null($m) ) $saldo = 0;
      else $saldo = $m->nominal;
      return Res::cb($res,true,"Berhasil",["saldo" => $saldo]);

    }

    public static function historyUserDetail(Request $req, Response $res){
      $id = $req->getAttribute("history_id");
      $m = DB::select("select * from eo_topup_history
      where id = ". $id);
      return Res::cb($res,true,"Berhasil",["history" => $m[0]]);
    }

		public static function historyUser(Request $req, Response $res){
			$p = $req->getParsedBody();
			$m = DB::Select("select h.*,b.nama,b.atas_nama,b.no_rek from eo_topup_history h
					inner join eo_bank b on b.nama = h.bank
					where user_id = ".$p["user_id"] . " order by h.id desc limit 5");

			return Res::cb($res,true,"Berhasil",["history" => $m]);
		}

    public static function batalVendor(Request $req, Response $res){
			$p = $req->getParsedBody();
			if( isset($p["vendor_id"]) && isset($p["topup_id"])  ){
				$m = TopupHistoryVendor::where("id",$p["topup_id"])->where("vendor_id",$p["vendor_id"])->first();
				$m->status = "Batal";
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

    public static function konfirmVendor(Request $req, Response $res){
			$p = $req->getParsedBody();
			if( isset($p["vendor_id"]) && isset($p["topup_id"])  ){

        if($p["no_rek"] == "")
          return Res::cb($res,false,"Nomor Rekening tidak boleh kosong");
        if($p["atas_nama"] == "")
          return Res::cb($res,false,"Atas Nama Rekening tidak boleh kosong");
        if($p["bank"] == "")
          return Res::cb($res,false,"Bank tidak boleh kosong");

				$m = TopupHistoryVendor::where("id",$p["topup_id"])->where("vendor_id",$p["vendor_id"])->first();
				$m->status = "Confirm";
				if($m->save()){
					return Res::cb($res,true,"Berhasil",["data" => "null"]);
				}
			}else{
				return Res::cb($res,true,"Request Tidak Valid");
			}
		}

		public static function historyVendor(Request $req, Response $res){
			$p = $req->getParsedBody();
			$m = DB::Select("select h.*,b.nama as bank,
							b.atas_nama,b.no_rek
							from eo_topup_history_vendor h
							inner join eo_vendor v on v.id = h.vendor_id
							inner join eo_bank b on b.id = v.bank_id
							where h.vendor_id = ".$p["vendor_id"]."
							and h.status in ('Pending','Confirm')  ");

			$h = [];
			if(count($m) > 0){
				$h = $m[ count($m) -1 ];
			}
			$h = count($h) <= 0 ? [[]] : $h;
			return Res::cb($res,true,"Berhasil",["history" => $h]);
		}

    public static function saldoVendor(Request $req, Response $res){
      $id = $req->getAttribute("vendor_id");
      $m = TopupVendor::where("vendor_id",$id)->first();
      if( is_null($m) ) $saldo = 0;
      else $saldo = $m->nominal;
      return Res::cb($res,true,"Berhasil",["saldo" => $saldo]);

    }
	}
