<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Models\User;


	class CheckoutController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function chekcout(Request $req, Response $res){
      $trxId = $req->getAttribute("txid");
      $trxId =  str_replace("BId","",$trxId);
      $id =  str_replace("x2lm","",$trxId);
			$m = DB::select("
        select u.id,b.nama as bank_nama, b.no_rek,b.atas_nama,u.*,
        p.nama as paket_nama,p.harga,
        b.icon as bank_icon
        from b_user u
        inner join b_bank b on u.bank_id = b.id
        inner join b_paket p on p.id = u.paket_id
        where u.id = $id
      ");
			return Res::cb($res,true,'Berhasil',$m[0]);
		}




	}
