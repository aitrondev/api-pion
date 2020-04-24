<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Voucher;
	use Models\VoucherHistory;


	class VoucherController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function cekVoucher(Request $req, Response $res){
      $p = $req->getParsedBody();
      $kode = (string) $p['kode'];
			$tokoId = $p['toko_id']; //sample barangid
			$c = Voucher::where("kode",$kode)->where("toko_id", (int) $tokoId)->first();
      if( isset($c['kode']) ){
					//cek penggunaan user
					$maxPerUser = $c->max_per_user;
					$max = $c->max_penggunaan;
					$tipe = $c->tipe;
					$nominal = $c->nominal;
					$h = VoucherHistory::where("user_id",$p['user_id'])->where("voucher_kode",$kode)->where("status","Success")->get();
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




	}
