<?php
	namespace Controller;



	use \Psr\Http\Message\ServerRequestInterface as Request;

	use \Psr\Http\Message\ResponseInterface as Response;

	use Illuminate\Database\Capsule\Manager as DB;

	

	use Models\OrderCourierHistory;
	use Models\Topup;
	use Models\TopupHistory;
	use Models\Orders;
	use Models\Settings;
	
	use Tools\Res;
	use Tools\PushNotif;
	
	class CourierController
	{
		public static function save(Request $request, Response $response){
			$post 		= $request->getParsedBody();
			DB::beginTransaction();
			$tipeKurir = "";
			//var_dump($post);die();
			$user_id 	= $post["user_id"];

			if( isset($post['order_id']) ){
				$vendor_id = $post['vendor_id'];
			} else $vendor_id = null;

			if(!is_null($user_id)  ){
				$m = new OrderCourierHistory;
				$m->user_id = $user_id;
				
				$m->from_alamat = $post['pengirim_alamat'];
				$m->from_ket_lain = $post['pengirim_ket'];
				$m->from_lat = $post['pengirim_lat'];
				$m->from_lng = $post['pengirim_lng'];
				$m->telp_pengirim = $post['pengirim_nomor'];
				
				$m->to_alamat = $post['penerima_alamat'];
				$m->to_ket_lain = $post['penerima_ket'];
				$m->to_lat = $post['penerima_lat'];
				$m->to_lng = $post['penerima_lng'];
				$m->telp_penerima= $post['penerima_nomor'];
				
				$m->status = $post['status'];
				$m->payment_method = $post['payment_method'];
				$m->vendor_id = $vendor_id;
				$m->km = $post['km'];
				$m->price = $post['price'];
				$m->tipe_kurir = $post['tipe_kurir'];
				$m->deskripsi = $post['deskripsi'];
				
				//get tipe kurir
				if($m->tipe_kurir == "Pickup")
					$tipeKurir = "6";
				else if( $m->tipe_kurir == "Motor" ) $tipeKurir = "3";
				
				
				if( $m->save() ){
					//save to orders
					$o = new Orders;
					$o->order_id = $m->id;
					$o->order_type_id = 5;
					$o->user_id = $post["user_id"];
					$o->save();


					//jika pembayaran menggunakan saldo
					if( $m->payment_method == 'Saldo'  ){
						$mSaldo = Topup::where("user_id",$user_id)->first();
						$mSaldo->nominal = (float) $mSaldo->nominal - (float) $post['price'];
						if( $mSaldo->save() ){
							//catat di history
							$mSaldoHistory = new TopupHistory;
							$mSaldoHistory->nominal =  $post['price'];
							$mSaldoHistory->type = "kurang";
							$mSaldoHistory->status = "Success";
							$mSaldoHistory->user_id = $user_id;
							$mSaldoHistory->save();
						}	
					}

					//cari driver denga radius yang ada di setting
					$radius = (int) Settings::first()["radius_driver"];
					$inMiles = 3959;
					$inKm = 6371;
					$sqlRadius = DB::Select("SELECT firebase_token FROM eo_vendor a 
									  WHERE (
								          acos(sin(a.last_latitude * 0.0175) * sin(".$m->from_lat." * 0.0175) 
								               + cos(a.last_Latitude * 0.0175) * cos(".$m->from_lat." * 0.0175) *    
								                 cos((".$m->from_lng." * 0.0175) - (a.last_longitude * 0.0175))
								              ) * $inKm
								      )  <= $radius and a.is_have_order = '0' and a.is_login = '1' 
								      and a.type_vendor_id = $tipeKurir ");
								      
					

					$tokens = [];
					foreach ($sqlRadius as $v) {
						$tokens[] = $v->firebase_token;
					}
					
					if( count($tokens) <= 0 ){
						DB::rollBack();
						return Res::cb($response,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);	
					}
					
					
				    $groupName = $m->id."kurir"."order";
					$notifKey = PushNotif::requestDeviceGroup($groupName,$tokens);
					
					if( isset($notifKey["notification_key"]) ){
						$pushData = [
							"action"		=> "new_order",
							"type_order"	=> "Ojek",
							"intent"		=> "move",
							"type_vendor"	=> "Kurir",
							"id"			=> $m->id,
							"from_alamat"	=> $post['pengirim_alamat'],
							"to_alamat"		=> $post['penerima_alamat'],
							"to_ket_lain"	=> $post['penerima_ket'],
							"from_ket_lain"	=> $post['pengirim_ket'],
							"from_lat"		=> $post['pengirim_lat'],
							"from_lng"		=> $post['pengirim_lng'],
							"to_lat"		=> $post['penerima_lat'],
							"to_lng"		=> $post['penerima_lng'],
							"km"			=> $post['km'],
							"price"			=> $post['price'],
							"catatan"		=> "-",
							"deskripsi"		=> $post['deskripsi'],		
						];
						
						$push = PushNotif::pushTo("Pesanan Baru Layanan Kurir",
							"Pesanan Baru telah di terima, klik untuk detail",$notifKey["notification_key"],$pushData);
						
						if( isset($push["success"]) && $push["success"] > 0 	 ){
							$m = OrderCourierHistory::where("id",$m->id)->first();
							$m->notif_name = $groupName;
							$m->notif_key = $notifKey["notification_key"];
							$m->save();
							DB::commit();
							
							return Res::cb($response,true,"Berhasil",['order'=>$m]);	
						}else{
							DB::rollBack();
							return Res::cb($response,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order' => []]);
						}
					}
					
					
				}else{
					DB::rollBack();
					return Res::cb($response,false,"Gagal",[]);
				}
			}else{
				DB::rollBack();
				return Res::cb($response,false,"Order tidak valid",[]);
			}
		}
		public static function getById(Request $request, Response $response){
			$order_id = $request->getAttribute('order_id');
			
			$m =  DB::select(" select *,
							case when from_ket_lain is null then '-' else from_ket_lain end as from_ket_lain,
							case when to_ket_lain is null then '-' else to_ket_lain end as to_ket_lain,
							case when vendor_id is null then 0 else vendor_id end as vendor_id 
					from eo_order_courier_history where id = '".$order_id."' ");
			return Res::cb($response,true,"Berhasil",["history"=>$m[0]]);		
			
		}
	}