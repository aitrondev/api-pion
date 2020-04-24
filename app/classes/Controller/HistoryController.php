<?php

	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	use Models\OrderServiceHistory;
	use Models\OrderServiceBarangHistory;

	use Tools\Res;



	class HistoryController
	{

		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}


		public static function getAllByUser(Request $req,Response $res){
			$id = $req->getAttribute('user_id');
		    try{
		    	$barang = DB::select("select b.id,b.created_at,'Makanan' as type,b.status from eo_order_service_history b
		    						where  b.user_id = $id ");

		    	$ojek = DB::select("select b.id,b.created_at,'Ojek' as type,b.status from eo_order_ojek_history b
		    						where  b.user_id = $id");

		    	$courier = DB::select("select b.id,'Kurir' as type,b.created_at,b.status from eo_order_courier_history b
		    						where  b.user_id = $id");

		    	$history = array_merge($barang,$ojek);
		    	$history = array_merge($history,$courier);

				return Res::cb($res,true,"Berhasil",['history' => $history]);
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getAllByStatus(Request $req,Response $res){
			$status = $req->getAttribute('status_id'); // 1 -> Pending dan Progress , 2 -> Complete dan Cancel
			$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];
		    if($status == 1){
		    	$status = "'Pending','Progress'";
		    	$hv = "";
		    	//$hv = "and TIMESTAMPDIFF(MINUTE,o.created_at,now()) <= 60";
		    }else{
		    	$status = "'Complete','Cancel'";
		    	$hv = "";
		    }

		    try{

		    	$orders = DB::Select("select u.nama as nama_user,t.nama as toko_nama, o.*,ot.name as type from eo_orders o
		    		inner join eo_order_type ot on ot.id = o.order_type_id
						inner join eo_order_service_history h on h.id = o.order_id
						inner join eo_order_service_barang_history bh on bh.order_service_history_id = h.id
						inner join eo_user u on u.id = h.user_id
						inner join eo_toko t on t.id = bh.toko_id
		    		where o.status in ($status) and o.user_id = $user_id $hv group by o.id,order_type_id order by o.id desc");


		    	$newHistory = [];
		    	foreach ($orders as $v) {
		    		$newTime = explode(' ',$v->created_at)[1];
		    		$newHistory[] = [
		    							'tgl' => explode(' ',$v->created_at)[0],
		    							'time'=>  explode(':',$newTime)[0] . ":" . explode(':',$newTime)[1],
		    							'type'=> $v->type,
		    							'status'=> $v->status,
		    							'id' => $v->order_id,
		    							'full_date' => $v->created_at,
		    							'toko_nama' => $v->toko_nama,
		    						] ;
		    	}

		    	if( count($newHistory) > 0 ){
					return Res::cb($res,true,"Berhasil",['history' => $newHistory]);
		    	}else{
		    		return Res::cb($res,true,"Berhasil,tetapi data tidak ditemukan",[ 'history'=> [] ]);
		    	}
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]);
			}
		}

		public static function getById(Request $req,Response $res){
				$id = $req->getAttribute('id');
				$user_id = $req->getAttribute('user_id');
		    $orderId = [];
		    $order = [];
		    try{

		    	$orderQ = DB::select("
		    		select t.*,b.*,t.id as order_id,b.id as order_barang_id
		    		from eo_order_service_history t
		    		inner join eo_order_service_barang_history b on b.order_service_history_id = t.id
		    		inner join v_barang b on b.id = b.barang_id and b.type_jasa_pengiriman = 'tokokota'
						inner join
		    		where t.user_id = $user_id and t.id = $id

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
	}
