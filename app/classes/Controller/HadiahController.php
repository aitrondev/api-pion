<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Models\Hadiah;
	use Models\PertukaranHistory;
	use Models\Poin;
	use Models\PoinHistory;



	class HadiahController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getAllHadiah(Request $req, Response $res){
			$h = DB::select("select * from eo_hadiah where visible = 'yes' ");
			return Res::cb($res,true,'Berhasil',["hadiah" => $h]);
		}

		public static function getHadiahById(Request $req, Response $res){
		    $id = $req->getAttribute("hadiah_id");
			$h = DB::select("select * from eo_hadiah where id =  " . $id);

			$f = DB::select("select * from eo_foto_hadiah where hadiah_id = " . $id);
			if( count($f) > 0 ){
			    $h[0]->photos = $f;
			    $h[0]->photos[] = ["path"=>$h[0]->foto];
			}else $h[0]->photos = [["path"=>$h[0]->foto]];


			return Res::cb($res,true,'Berhasil',["hadiah" => $h[0]]);
		}

		public static function getPenukaranById(Request $req, Response $res){
			$id = $req->getAttribute("id");
			$m = DB::select('select * from eo_penukaran_history where id = ' . $id);
			return Res::cb($res,true,'Berhasil',["history" => $m[0] ]);
		}

		public static function getRiwayatPenukaran(Request $req, Response $res){
			$userId = $req->getAttribute("user_id");
			$m = DB::select('select * from eo_penukaran_history where user_id = ' . $userId);
			return Res::cb($res,true,'Berhasil',["history" => $m ]);
		}


		public static function ambilHadiah(Request $req, Response $res){

			$p = $req->getParsedBody();
			$gh = Hadiah::where("id",$p['hadiah_id'])->first();
			$poin = Poin::where("user_id",$p['user_id'])->first();
			DB::beginTransaction();

			$pointNeeded = (int) $p['qty'] * (int) $gh['poin'];

			if( $pointNeeded > (int) $poin["poin"] ){
				return Res::cb($res,false,'Maaf poin Anda tidak mencukupi, poin Anda saat ini : ' . $poin['poin']);
			}

			$h  = new PertukaranHistory();
			$h->user_id = $p['user_id'];
			$h->hadiah_id = $p['hadiah_id'];
			$h->hadiah_poin = $gh['poin'];
			$h->hadiah_nama = $gh['nama'];
			$h->poin_needed = $pointNeeded;
			$h->qty = $p['qty'];
			$h->before_poin_user = $p['before_poin'];
			$h->after_poin_user = $p['after_poin'];
			$h->catatan = $p['catatan'];
			if( $h->save() ){
				//input history poin
				$ph = new PoinHistory;
				$ph->user_id = $p['user_id'];
				$ph->poin = $pointNeeded;
				$ph->ket = "Penukaran hadiah";
				$ph->tipe = "Kurang";
				$ph->created_at = date("Y-m-d H:i:s");
				$ph->updated_at = date("Y-m-d H:i:s");
				$ph->status = 'Success';
				$ph->save();

				$gh->jumlah_stock -= (int) $h->qty;
				if( $gh->save() ){
					$u = Poin::where("user_id",$p['user_id'])->first();
					$u->poin -= $pointNeeded;
					if($u->save()){
						DB::commit();
						return Res::cb($res,true,'Berhasil');
					}else{
						DB::rollback();
						return Res::cb($res,false,'Gagal 0');
					}
				}else{
					DB::rollback();
					return Res::cb($res,false,'Gagal 1');
				}
			}else{
				DB::rollback();
				return Res::cb($res,false,'Gagal 2');
			}
		}
	}
?>
