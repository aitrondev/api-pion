<?php
	namespace Controller;



	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;

	

	use Models\OrderOjekHistory;
	use Models\Topup;
	use Models\TopupHistory;
	use Models\Orders;
	use Models\Settings;
	
	use Tools\Res;
	use Tools\PushNotif;
	
	class OjekController
	{
		public static function save(Request $request, Response $response){
			DB::beginTransaction();	
			$post 		= $request->getParsedBody();
			$user_id 	= $request->getAttribute("user_id");
			
			//var_dump($post);die();


			if( isset($post['order_id'])  ){
				$vendor_id = $post['vendor_id'];
			} else $vendor_id = 0;
			

			if( isset($post['from_lat']) && isset($post['to_lat']) && !is_null($user_id)  ){
				$m = new OrderOjekHistory;
				$m->user_id = $user_id;
				$m->from_alamat = $post['from_alamat'];
				$m->from_ket_lain = $post['from_ket_lain'];
				$m->from_lat = $post['from_lat'];
				$m->from_lng = $post['from_lng'];
				$m->to_alamat = $post['to_alamat'];
				$m->to_ket_lain = $post['to_ket_lain'];
				$m->to_lat = $post['to_lat'];
				$m->to_lng = $post['to_lng'];
				$m->status = $post['status'];
				$m->payment_method = $post['payment_method'];
				$m->vendor_id = $vendor_id;
				$m->km = $post['km'];
				$m->price = $post['price'];
				$m->type_vendor = $post['type_vendor'];
				$m->catatan = $post['catatan'];
				
						
				if( $m->save() ){
					
					//save to orders
					$o = new Orders;
					$o->order_id = $m->id;
					$o->order_type_id = $post["order_type"];
					
					$o->user_id = $user_id;
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
					$sqlRadius = DB::Select("SELECT * FROM eo_vendor a 
								  WHERE (
							          acos(sin(a.last_latitude * 0.0175) * sin(".$m->from_lat." * 0.0175) 
							               + cos(a.last_Latitude * 0.0175) * cos(".$m->from_lat." * 0.0175) *    
							                 cos((".$m->from_lng." * 0.0175) - (a.last_longitude * 0.0175))
							              ) * $inKm
							      )  <= $radius and a.is_have_order = '0' 
							      and a.is_login = '1' 
							      and is_active = '1' 
							      and a.type_vendor_id = ".$post["type_vendor_id"]);
					
					$tokens = [];
					foreach ($sqlRadius as $v) {
						$tokens[] = $v->firebase_token;
					}
					
					
					if( count($tokens) <= 0 ){
						DB::rollBack();	
						return Res::cb($response,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",['order'=>"null"]);	
					}
					$groupName = $m->id.$m->type_vendor."order";
					$notifKey = PushNotif::requestDeviceGroup($groupName,$tokens);
					 
					 
					if( isset($notifKey["notification_key"]) ){
						$pushData = [
							"action"		=> "new_order",
							"type_order"	=> "Ojek",
							"intent"		=> "move",
							"type_vendor"	=> $m->type_vendor,
							"id"			=> $m->id,
							"from_alamat"	=> $post['from_alamat'],
							"to_alamat"		=> $post['to_alamat'],
							"to_ket_lain"	=> $post['to_ket_lain'],
							"from_ket_lain"	=> $post['from_ket_lain'],
							"from_lat"		=> $post['from_lat'],
							"from_lng"		=> $post['from_lng'],
							"to_lat"		=> $post['to_lat'],
							"to_lng"		=> $post['to_lng'],
							"km"			=> $post['km'],
							"price"			=> $post['price'],
							"catatan"		=> $post['catatan'],
							"deskripsi"		=> "-",
									
						];
						
						$push = PushNotif::pushTo("Pesanan Baru Layanan " . $m->type_vendor,
							"Pesanan Baru telah di terima, klik untuk detail",$notifKey["notification_key"],$pushData);
						
						if( isset($push["success"]) && $push["success"] > 0 ){
								
							$mo = OrderOjekHistory::where("id",$m->id)->first();
							$mo->notif_name = $groupName;
							$mo->notif_key = $notifKey["notification_key"];
							$mo->save();
							DB::commit();
							return Res::cb($response,true,"Berhasil",['order'=>$m]);	
						}else{
							DB::rollBack();
							return Res::cb($response,false,"Maaf, Tidak ada driver tersedia, lakukan pemesanan beberapa saat lagi",[]);
						}
					}else{
						DB::rollBack();
						return Res::cb($response,false,"Notifikasi gagal dikirim , kode : " . $notifKey["error"],[]);
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
			$m =  DB::select(" select h.*,
			case when v.id is null then 0  else v.no_telp end as no_telp, 
			case when h.vendor_id is null then 0  else h.vendor_id end as vendor_id ,
			o.order_type_id as type_order
			from eo_order_ojek_history h
			left join eo_vendor v on v.id = h.vendor_id
			inner join eo_orders o on o.order_id = h.id and o.order_type_id in (1,2,3)
			where h.id = '".$order_id."' ");
			//$m = OrderOjekHistory::where("id" ,$order_id)->first();
			return Res::cb($response,true,"Berhasil",["history"=>$m[0]]);		
			
		}
	}