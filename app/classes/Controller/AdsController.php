<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	
	class AdsController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getAll(Request $req, Response $res){
			$ads = DB::Select("select * from eo_ads where visible = 'yes' " );
			return Res::cb($res,true,'Berhasil',["ads" => $ads]);
		}
		
		public static function getBroadcastHistory(Request $req, Response $res){
			$userId = $req->getAttribute("user_id");
			$ads1 = DB::Select("select b.id,h.created_at,b.title from eo_broadcast_history h 
								inner join eo_broadcast b on b.id = h.broadcast_id 
								and h.tipe = 'selected'
								and h.user_id = " . $userId);
			$ads2 = DB::Select("select b.id,h.created_at,b.title from eo_broadcast_history h 
								inner join eo_broadcast b on b.id = h.broadcast_id 
								and h.tipe = 'alluser'");
			$ads = array_merge($ads1,$ads2);
			return Res::cb($res,true,'Berhasil',["ads" => $ads]); 
		}
		
	}


?>