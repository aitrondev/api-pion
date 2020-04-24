<?php
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;
	
	use Models\Withdraw;
	use Models\Tagihan;

	use Tools\Res;
	use Tools\Helper;
	use Tools\PushNotif;
	
	class TagihanController{
		
		protected $ci;
		public $setting;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
			self::$setting = Settings::first();
		}
		
		public static function cekSaldo(Request $req,Response $res){
			$tokoId = $req->getAttribute('toko_id');
			$m = DB::select("select * from eo_tagihan where toko_id = " . $tokoId);
			
			return Res::cb($res,true,"Berhasil",["saldo" => $m[0]->nominal]);  
		}
		
		public static function getHistoryWithdraw(Request $req,Response $res){
			$tokoId = $req->getAttribute('toko_id');
			$m = DB::select("select * from eo_withdraw where toko_id = " . $tokoId . " order by created_at desc");
			
			return Res::cb($res,true,"Berhasil",["withdraw" => $m]);  
		}
		
		public static function withdraw(Request $req,Response $res){
			$p = $req->getParsedBody(); 
			$tokoId = $p['toko_id'];
						
			$saldo = (float)  self::getTotalSaldoReal($tokoId);
            
			$r =  DB::select("select * from eo_toko where id = " . $tokoId);
      
			//cek apa punya rekening
			if( $r[0]->rek_no == "" || empty($r[0]->rek_no)  ){
				return Res::cb($res,false,"Informasi Rekening Anda kosong, silahkan hub Admin dan berikan data rekening Anda");  
			}
			
			if( (float) $p['nominal'] > $saldo  ){
				return Res::cb($res,false,"Maaf, Saldo Anda tidak mencukupi");  
			}
			
			$w = new Withdraw;
			$w->nominal = $p['nominal'];
			$w->toko_id = $tokoId;
			$w->status = "Pending";
			$w->alasan = "";
			if($w->save()){
				//kurangi rekening
				$ta = Tagihan::where("toko_id",$tokoId)->first();
				$ta['nominal'] = (float) $ta['nominal'] -  (float) $p['nominal'];
				if($ta->save())
					return Res::cb($res,true,"Withdraw Anda Sukses, silahkan menunggu konfirmasi dari Admin");  	
			}
			
		}
    
    static function getTotalSaldoReal($tokoId){
      $m = DB::Select("select * from eo_order_service_history s
            inner join eo_order_service_barang_history b on b.order_service_history_id = s.id and b.toko_id = " . $tokoId
            . " where s.updated_at >= '2018-06-01' and s.order_method = 'saldo' 
             and s.status in ('Success','Complete') 
            group by s.id ");
            
            $total = 0; //total pesanan dari saldo
            foreach( $m as $v ){
                $total += $v->price;
            }
            
            //menghitung withdraw
            $w = DB::Select("select * from eo_withdraw w where updated_at >= '2018-06-01' 
            and toko_id = " .  $tokoId  . " and status in ('Pending','Approve') " );
            
            $totalW = 0; //total withdraw
            foreach( $w as $v ){
                $totalW += $v->nominal;
            }
            
            $total -= $totalW; //total pesanan pakai saldo di kurang total withdraw
      
      return  $total;
    }
		
		static function getSinkronSaldo(Request $req,Response $res){
		      $tokoId = $req->getAttribute('toko_id');
          $total  =self::getTotalSaldoReal($tokoId);
          
          return Res::cb($res,true,"Berhasil",["saldo" => $total]);  
    }
	}
?>