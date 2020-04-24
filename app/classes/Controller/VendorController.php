<?php



namespace Controller;



use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;



use Models\Vendor;
use Models\Info;
use Models\Review;
use Models\VVendor;
use Models\OrderOjekHistory;
use Models\OrderLaundryHistory;
use Models\OrderServiceHistory;
use Models\OrderHistoryCancelBooking;
use Models\User;
use Models\VendorIncomeHistory;
use Models\VendorPosition;
use Models\OrderCourierHistory;
use Models\Rate;
use Models\Settings;
use Models\TopupVendor;
use Models\VendorType;
use Models\Orders;
use Models\Topup;
use Models\SuspendHistory;
use Models\OrderPijatHistory;
use Models\TopupHistory;
use Models\Ekspedisi;
use Models\OrderServiceBarangHistory;
use Models\Toko;
use Models\TopupToko;
use Models\TopupHistoryToko;
use Models\Bank;
use Models\Barang;
use Models\VoucherHistory;
use Models\TopupEkspedisi;
use Models\TopupHistoryEkspedisi;
use Models\Poin;
use Models\PoinHistory;
use Models\OrderTabungGalonHistory;
use Models\OrderTabungGalonItemHistory;

use Tools\Res;
use Tools\Encrypt;
use Tools\PushNotif;

class VendorController {
	protected $ci;
	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

  public static function ubahPassword( Request $request, Response $response){
		$post = $request->getParsedBody();

		if (isset($post['password_baru']) && isset($post['vendor_id']) ){

			$password 	= $post['password_baru'];
			$vendorId 	= $post['vendor_id'];
			$vendor = Vendor::where('id',$vendorId)->first();

			if( isset($vendor['username']) ){
          //check password lama apakah sesuai
          if( $vendor["password"] != md5($post['password_lama']) ) return Res::cb($response,false,'Password lama tidak sesuai !');

    			$vendor->password = md5($post['password_baru']);
    			if($vendor->save()){
    				return Res::cb($response,true,"Berhasil",["vendor" => $vendor]);
    			}else{
    				return Res::cb($response,false,"Terdapat kesalahan, mohon dicoba lagi",[]);
    			}
			}else{
			    return Res::cb($response,false,"User tidak di ketahui",[]);
			}

		}
	}

	public static function updateToCancelDriverNotFound(Request $request, Response $response){
		$p = $request->getParsedBody();
		$id = $request->getAttribute("id");
		if( $p["type_order"] == "Ojek" )
			$m = OrderOjekHistory::where("id",$id)->first();
		else if($p["type_order"] == "Kurir")
			$m = OrderCourierHistory::where("id",$id)->first();
		else if($p["type_order"] == "Food" || $p["type_order"] == "Barang")
				$m = OrderServiceHistory::where("id",$id)->first();
		else if($p["type_order"] == "Laundry")
				$m = OrderLaundryHistory::where("id",$id)->first();
		else if($p["type_order"] == "Teraphist")
				$m = OrderPijatHistory::where("id",$id)->first();

		if( $m->status == "Progress" ){ //jika sudah di acept, ini untuk menghindari notifikasi lama terkirim ke user
			$v = Vendor::where("id",$m->vendor_id)->first();
			return Res::cb($response,true,"Berhasil",["vendor"=>$v]);
		}else{
			$m->status = "Cancel";
			$m->alasan_cancel = "Driver tidak di temukan";
			if($m->save()){
				$o = Orders::where("order_id",$m->id)->where("order_type_id",$p["type_order_id"])->first();
				$o->status = "Cancel";
				$o->save();
				return Res::cb($response,true,"Berhasil");
			}
		}
	}
	public static function accept(Request $request, Response $response){
		$post = $request->getParsedBody();

		//var_dump($post);die();

		if( isset($post['vendor_id']) && isset($post['order_id']) && isset($post['type_order']) ){
			if( $post['type_order'] == "Motor" || $post['type_order'] == "Ojek"
				|| $post['type_order'] == "Mobil" || $post['type_order'] == "Taxi" ){

					DB::beginTransaction();
					$m = OrderOjekHistory::where('id',$post['order_id'])->where('status','Pending')->first();
					if( count($m) > 0 ){
						$pros = VendorType::where("name",$post['type_order'])->first()["percent"];
						if( $m->payment_method == "Cash" ){
							$topupVendor = TopupVendor::where("vendor_id",$post["vendor_id"])->first();
							$saldoCheck  = ( (float) $m['price'] *  $pros ) / 100;
							//yg di ambil perusahaan
							$untungPerusahaan = 100 - $pros;
							$saldoYgDiAmbil = ( (float) $m['price'] *  $untungPerusahaan ) / 100;
							if( (float) $topupVendor["nominal"] < $saldoYgDiAmbil )
								return Res::cb($response,false,"Maaf saldo Anda tidak mencukupi untuk menerima pesanan ini");
						}

						$mV = VVendor::where("id",$post["vendor_id"])->first();
						$m->vendor_id = $post['vendor_id'];
						$m->status = 'Progress';
						if($m->save()){
							$lastId = $m->id;
							$user =  User::where('id',$m->user_id)->first();
							$m['no_telp'] = $user->no_telp;
							if($post['type_order'] == "Motor"){
								$orderTypeId = "1";
							}else if($post['type_order'] == "Mobil"){
								$orderTypeId = "2";
							}else if($post['type_order'] == "Taxi"){
								$orderTypeId = "3";
							}
							$data = [
									"action"=>"accept_driver",
									"intent"=>"move",
									"vendor_id"=>$post['vendor_id'],
									"order_id"=>$post['order_id'],
									"nama" => $mV->nama,
									"no_telp" => $mV->no_telp,
									"type_vendor" => $mV->type_vendor,
									"foto_url" => $mV->path_thumbnail,
									"type_order" => "Ojek",
									"plat_nomor" => $mV->plat_nomor,
									"tipe_motor" => $mV->tipe_motor,
									"order_type_id" => $orderTypeId,
									];

							$push= PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
									$user->firebase_token,$data,"background");
							if( (int) $push['success'] >= 1){

								$mVendor = Vendor::where("id",$post["vendor_id"])->first();
								$mVendor->is_have_order = "1";
								$mVendor->save();

								$mOrder = Orders::where("order_id",$post["order_id"])->
											where("order_type_id",$post["order_type_id"])->first();
								$mOrder->status = 'Progress';
								$mOrder->save();

								//update user deposit
								if( $m->payment_method == 'Saldo'  ){
									$mUserSaldo = Topup::where("user_id",$m->user_id)->first();
									$mUserSaldo->nominal = (float) $mUserSaldo->nominal - (float) $m->price;
									if( $mUserSaldo->save() ){
										//catat di history
										$mSaldoHistory = new TopupHistory;
										$mSaldoHistory->nominal =  $m->price;
										$mSaldoHistory->type = "kurang";
										$mSaldoHistory->status = "Success";
										$mSaldoHistory->user_id = $m->user_id;
										$mSaldoHistory->save();
									}
								}

								DB::commit();

								return Res::cb($response,true,"Berhasil",["order" => [ "no_telp"=>$m->no_telp ]  ]);
							}else{
								//DB:statement("delete eo_order_ojek_history where id = " . $lastId);
								//delete order
								DB::rollback();
								return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
							}
						}
				}else{
					return Res::cb($response,false,"Maaf Order Sudah cancel atau sudah  di ambil driver lain",[ 'order'=> [] ]);
				}
			}else if($post['type_order'] == "4" ||  $post['type_order'] == "6" ){
				$typeOrder = $post["type_order"] == 4 ? "Makanan" : "Barang";
				DB::beginTransaction();
				$m = OrderServiceHistory::where('id',$post['order_id'])->where('status','Pending')->first();
				if( count($m) > 0 ){
					$pros = VendorType::where("name","Motor")->first()["percent"];
					if( $m->order_method == "Cash" ){
						$topupVendor = TopupVendor::where("vendor_id",$post["vendor_id"])->first();
						$untungPerusahaan  = ( (float) $m['price_antar'] *  (100-$pros) ) / 100;
						if( (float) $topupVendor["nominal"] < $untungPerusahaan )
							return Res::cb($response,false,"Maaf saldo Anda tidak mencukupi untuk menerima pesanan ini");
					}

					$mV = VVendor::where("id",$post["vendor_id"])->first();
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';
					if($m->save()){
						$user =  User::where('id',$m->user_id)->first();
						$m['no_telp'] = $user['no_telp'];
						$dataPush = [
								"action"=>"accept_driver",
								"intent"=>"move",
								"vendor_id"=>$post['vendor_id'],
								"order_id"=>$post['order_id'],
								"type_order" => "Barang",
								"nama" => $mV->nama,
								"no_telp" => $mV->no_telp,
								"type_vendor" => $mV->type_vendor,
								"foto_url" => $mV->path_thumbnail,
								"plat_nomor" => $mV->plat_nomor,
								"tipe_motor" => $mV->tipe_motor,
								"order_type_id" => $post['type_order'],


						];
						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",$user->firebase_token,$dataPush);

						if($push['success'] == 1 || $push['success'] == "1"){

							if( $m->order_method == 'Saldo'){
								$mSaldo = Topup::where("user_id",$m->user_id)->first();
								$mSaldo->nominal = (float) $mSaldo->nominal - (float) $m->price_antar;
								if( $mSaldo->save() ){
									//catat di history
									$mSaldoHistory = new TopupHistory;
									$mSaldoHistory->nominal =  $m->price_antar;
									$mSaldoHistory->type = "kurang";
									$mSaldoHistory->status = "Success";
									$mSaldoHistory->user_id = $m->user_id;
									$mSaldoHistory->save();
								}
							}


							$mVendor = Vendor::where("id",$post["vendor_id"])->first();
							$mVendor->is_have_order = "1";
							$mVendor->save();

							$mOrder = Orders::where("order_id",$post["order_id"])->
										where("order_type_id",$post["order_type_id"])->first();
							$mOrder->status = 'Progress';
							$mOrder->save();

								DB::commit();

							return Res::cb($response,true,"Berhasil",["order" => ['no_telp'=> $user['no_telp']]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain");
				}
			}else if( $post['type_order'] == "Kurir" ){
				DB::beginTransaction();
				$m = OrderCourierHistory::where('id',$post['order_id'])->where('status','Pending')->first();
				if( count($m) > 0 ){
					$pros = VendorType::where("name","Motor")->first()["percent"];
					if( $m->payment_method == "Cash" ){
						$topupVendor = TopupVendor::where("vendor_id",$post["vendor_id"])->first();
						$saldoCheck  = ( (float) $m['price'] *  $pros ) / 100;
						//yg di ambil perusahaan
						$untungPerusahaan = 100 - $pros;
						$saldoYgDiAmbil = ( (float) $m['price'] *  $untungPerusahaan ) / 100;
						if( (float) $topupVendor["nominal"] < $saldoYgDiAmbil )
							return Res::cb($response,false,"Maaf saldo Anda tidak mencukupi untuk menerima pesanan ini");
					}

					$mV = VVendor::where("id",$post["vendor_id"])->first();
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';

					if($m->save()){
						$user =  User::where('id',$m->user_id)->first();
						$m['no_telp'] = $user->no_telp;

						$data = [
								"action"=>"accept_driver",
								"intent"=>"move",
								"vendor_id"=>$post['vendor_id'],
								"order_id"=>$post['order_id'],
								"nama" => $mV->nama,
								"no_telp" => $mV->no_telp,
								"type_vendor" => $mV->type_vendor,
								"foto_url" => $mV->path_thumbnail,
								"type_order" => "Kurir",
								"plat_nomor" => $mV->plat_nomor,
								"tipe_motor" => $mV->tipe_motor,
								"order_type_id" => "5",
								];

						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
								$user->firebase_token,$data,"background");

						if($push['success'] == 1 || $push['success'] == "1"){

							//update user deposit
							if( $m->payment_method == 'Saldo'  ){
								$mUserSaldo = Topup::where("user_id",$m->user_id)->first();
								$mUserSaldo->nominal = (float) $mUserSaldo->nominal - (float) $m->price;
								if( $mUserSaldo->save() ){
									//catat di history
									$mSaldoHistory = new TopupHistory;
									$mSaldoHistory->nominal =  $m->price;
									$mSaldoHistory->type = "kurang";
									$mSaldoHistory->status = "Success";
									$mSaldoHistory->user_id = $m->user_id;
									$mSaldoHistory->save();
								}
							}


							$mVendor = Vendor::where("id",$post["vendor_id"])->first();
							$mVendor->is_have_order = "1";
							$mVendor->save();

							$mOrder = Orders::where("order_id",$post["order_id"])->
										where("order_type_id","5")->first();
							$mOrder->status = 'Progress';
							$mOrder->save();

							DB::commit();
							return Res::cb($response,true,"Berhasil",["order" => [ "no_telp"=>$m->no_telp ]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain",[ 'order'=> [] ]);
				}

				//});
			}else if( $post['type_order'] == "Laundry" ){
				DB::beginTransaction();
				$m = OrderLaundryHistory::where('id',$post['order_id'])->where('status','Pending')->first();
				if( count($m) > 0 ){
					if( $m->payment_method == "Cash" ){
						$topupVendor = TopupVendor::where("vendor_id",$post["vendor_id"])->first();
						$saldoCheck  = ( (float) $m['ongkir'] *  $pros ) / 100;
						//yg di ambil perusahaan
						$untungPerusahaan = 100 - $pros;
						$saldoYgDiAmbil = ( (float) $m['ongkir'] *  $untungPerusahaan ) / 100;
						if( (float) $topupVendor["nominal"] < $saldoYgDiAmbil )
							return Res::cb($response,false,"Maaf saldo Anda tidak mencukupi untuk menerima pesanan ini");
					}

					$mV = VVendor::where("id",$post["vendor_id"])->first();
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';

					if($m->save()){
						$user =  User::where('id',$m->user_id)->first();
						$m['no_telp'] = $user->no_telp;

						$data = [
								"action"=>"accept_driver",
								"intent"=>"move",
								"vendor_id"=>$post['vendor_id'],
								"order_id"=>$post['order_id'],
								"nama" => $mV->nama,
								"no_telp" => $mV->no_telp,
								"type_vendor" => $mV->type_vendor,
								"foto_url" => $mV->path_thumbnail,
								"type_order" => "Laundry",
								"plat_nomor" => $mV->plat_nomor,
								"tipe_motor" => $mV->tipe_motor,
								"order_type_id" => "7",
								];

						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
								$user->firebase_token,$data,"background");


						if($push['success'] == 1 || $push['success'] == "1"){

							if($m->payment_method == "Saldo"){
								$mSaldo = Topup::where("user_id",$m->user_id)->first();
								$mSaldo->nominal = (float) $mSaldo->nominal -  (  $m->total_price +  $m->ongkir );
								if( $mSaldo->save() ){
									//catat di history
									$mSaldoHistory = new TopupHistory;
									$mSaldoHistory->nominal = (  $m->total_price +  $m->ongkir );
									$mSaldoHistory->type = "kurang";
									$mSaldoHistory->status = "Success";
									$mSaldoHistory->user_id = $m->user_id;
									$mSaldoHistory->save();
								}
							}

							$mVendor = Vendor::where("id",$post["vendor_id"])->first();
							$mVendor->is_have_order = "1";
							$mVendor->save();

							$mOrder = Orders::where("order_id",$post["order_id"])->
										where("order_type_id","7")->first();
							$mOrder->status = 'Progress';
							$mOrder->save();

							DB::commit();
							return Res::cb($response,true,"Berhasil",["order" => [ "no_telp"=>$m->no_telp ]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain",[ 'order'=> [] ]);
				}

				//});
			}else if( $post['type_order'] == "Teraphist" ){
				DB::beginTransaction();
				$m = OrderPijatHistory::where('id',$post['order_id'])
														->where("vendor_id",$post['vendor_id'])
														->where('status','Pending')->first();
				if( isset($m['id']) ){
					$mV = VVendor::where("id",$post["vendor_id"])->first();
					$m->vendor_id = $post['vendor_id'];
					$m->status = 'Progress';

					if($m->save()){
						$user =  User::where('id',$m->user_id)->first();
						$m['no_telp'] = $user->no_telp;

						$data = [
								"action"=>"accept_driver",
								"intent"=>"move",
								"vendor_id"=>$post['vendor_id'],
								"order_id"=>$post['order_id'],
								"nama" => $mV->nama,
								"no_telp" => $mV->no_telp,
								"type_vendor" => $mV->type_vendor,
								"foto_url" => $mV->path_thumbnail,
								"type_order" => "Kurir",
								"plat_nomor" => $mV->plat_nomor,
								"tipe_motor" => $mV->tipe_motor,
								"order_type_id" => "5",
								];

						$push=PushNotif::pushTo("Pesanan diterima","Driver Menerima pesanan Anda",
								$user->firebase_token,$data,"background");


						if($push['success'] == 1 || $push['success'] == "1"){

							$mVendor = Vendor::where("id",$post["vendor_id"])->first();
							$mVendor->is_have_order = "1";
							$mVendor->save();

							$mOrder = Orders::where("order_id",$post["order_id"])->
										where("order_type_id","11")->first();
							$mOrder->status = 'Progress';
							$mOrder->save();

							DB::commit();
							return Res::cb($response,true,"Berhasil",["order" => [ "no_telp"=>$m->no_telp ]  ]);
						}else{
							DB::rollback();
							return Res::cb($response,true,"Gagal Token " . $user->firebase_token,["order" => []]);
						}
					}
				}else{
					return Res::cb($response,false,"Order Sudah di ambil driver lain",[ 'order'=> [] ]);
				}

				//});
			}

			return Res::cb($response,false,"Request Tidak valid",['order'=>[] ]);
		}
	}
	public static function logout(Request $req, Response $res){
		$p = $req->getParsedBody();
		$m = Vendor::where("id",$p["vendor_id"])->first();
		$m->is_login ="0";
		if($m->save()){
			return Res::cb($res,true,"Berhasil",['vendor'=>[]]);
		}else{
			return Res::cb($res,false,"Gagal",['vendor'=>[]]);
		}
	}
	public static function getSaldo(Request $req, Response $res){
		$p = $req->getParsedBody();
		$m = TopupVendor::where("id",$p["vendor_id"])->first();
		if( !isset($m["vendor_id"]) )
			return Res::cb($res,true,"Berhasil",['saldo'=> "0" ]);
		else
			return Res::cb($res,true,"Berhasil",['saldo'=> $m["nominal"] ]);
	}
	public static function getUserRate(Request $req, Response $res){
		$vendorId = $req->getAttribute("id");
		$m = DB::Select("select * from v_rate where vendor_id = " . $vendorId);
		if( count($m) <= 0 ){
			$m = [];
			$m[0] = [
				"star_5" =>0,
				"star_4" =>0,
				"star_3" =>0,
				"star_2" =>0,
				"star_1" =>0,
				"total_user" =>0,
			];
		}
		return Res::cb($res,true,"Berhasil",$m[0]);
	}
	public static function getPendapatan(Request $req, Response $res){
		$p = $req->getParsedBody();
		if( $p["type_vendor"] == "Motor"
			|| $p["type_vendor"] == "Mobil"
			|| $p["type_vendor"] == "Taxi" || $p["type_vendor"] == "Pickup" ){

			$month = date("m");
			$pros = DB::Select("select percent from eo_vendor_type where name = '".$p["type_vendor"]."'  ");

			$pros = $pros[0]->percent;

			/*
				pendaptan dari ojek
			*/
			$total = 0;
			if( $p["type_vendor"] != "Pickup" ){
				$m = DB::Select("select h.price
						from eo_order_ojek_history h
						where h.vendor_id = '".$p["vendor_id"]."'
					  	and date_format(h.created_at,'%m') = '".$month."'
					  	and h.status = 'Complete'  ");

				$total = 0;
				foreach($m as $v){
					$pp = ( $v->price * $pros ) / 100;
					$total += $pp;
				}

			}
			/*
				pendaptan dari makanan dan belanja
			*/
			if( $p["type_vendor"] == "Motor" ){
				$m = DB::Select("select s.price,s.price_antar from eo_order_service_history s
							where s.vendor_id = ".$p['vendor_id']." and s.status = 'Complete'
							and date_format(s.created_at,'%m') = '".$month."'
							");

				foreach($m as $v){
					$pp = ( $v->price_antar  * $pros ) / 100;
					$total += $pp;
				}
			}

			/*
				pendaptan dari kurir
			*/
			if( $p["type_vendor"] == "Motor" || $p["type_vendor"] == "Pickup" ){
				$m = DB::Select("select s.price from eo_order_courier_history s
							where s.vendor_id = ".$p['vendor_id']." and s.status = 'Complete'
							and date_format(s.created_at,'%m') = '".$month."'
							");

				foreach($m as $v){
					$pp = ( $v->price * $pros ) / 100;
					$total += $pp;
				}
			}

			return Res::cb($res,true,"Berhasil",['pendapatan'=> $total  ]);

		}
	}
	public static function getTotalOrder(Request $req, Response $res){
		$p = $req->getParsedBody();
		$total = 0;
		if($p["type_vendor"] == "Motor"
			|| $p["type_vendor"] == "Mobil"
			|| $p["type_vendor"] == "Taxi" ){

			$month = date("m");
			$m = DB::Select("select count(1) as jum
					from eo_order_ojek_history h
					where h.vendor_id = '".$p["vendor_id"]."'
				  	and date_format(h.created_at,'%m') = '".$month."'
				  	and h.status = 'Complete'  ");
			$total+= $m[0]->jum;

			/*
				jika motor ambil dari makanan dan belanja
			*/
			if( $p["type_vendor"] == "Motor" ){
				$m = DB::Select("select count(1) as jum
					from eo_order_service_history h
					where h.vendor_id = '".$p["vendor_id"]."'
				  	and date_format(h.created_at,'%m') = '".$month."'
				  	and h.status = 'Complete'  ");
					$total+= $m[0]->jum;
			}

			/*
				jika motor ambil dari kurir
			*/
			if( $p["type_vendor"] == "Motor" ){
				$m = DB::Select("select count(1) as jum
					from eo_order_courier_history h
					where h.vendor_id = '".$p["vendor_id"]."'
				  	and date_format(h.created_at,'%m') = '".$month."'
				  	and h.status = 'Complete'  ");
					$total+= $m[0]->jum;
			}



			return Res::cb($res,true,"Berhasil",['total_order'=> $total]);

		}
	}
	public static function login( Request $request, Response $response ){
		$post = $request->getParsedBody();
		$totalOrder = 0;
		$totalPendapatan = 0;
		if ( isset($post['no_telp']) ){
			$phone = $post['no_telp'];
			$vendor = VVendor::where('no_telp',$phone)->first();


			if ($vendor) {
				//cek apakah driver di suspend
				if( $vendor->is_suspend == "yes" ){
					return Res::cb($response,false,"Maaf, akun Anda tidak bisa digunakan ",['vendor'=>$vendor]);
				}

				//update vendor firebase token
				$v = Vendor::where("no_telp",$phone)->first();
				$v->firebase_token = $post["firebase_token"];
				$v->is_login = "1";
				if($v->save()){
					//get total order
					$mT = OrderOjekHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
					$totalOrder += count($mT);
					if( $vendor->type_vendor == "Ojek" ){
						$mT = OrderServiceHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
						$totalOrder += count($mT);
					}
					$vendor["total_order"] = $totalOrder;

					//get total pendapatan
					$mP = DB::select("select sum(income) as income from eo_vendor_income_history where vendor_id = ".$v->id);
					$vendor["total_pendapatan"] = is_null($mP[0]->income) ? 0 : $mP[0]->income;

					//cek apa masih punya status order yang belum di selesaikan
					$cO = json_encode(DB::Select("	select esh.id id_1,eooh.id id_2,eoch.id id_3 from eo_order_service_history esh

										left join eo_order_ojek_history eooh
										on eooh.vendor_id = ". $v->id." and  eooh.status = 'Progress'

										left join eo_order_courier_history eoch
										on eoch.vendor_id = ". $v->id." and  eoch.status = 'Progress'

										where esh.vendor_id = ".$v->id."  and  esh.status = 'Progress'
										"));
					$cO = json_decode($cO,true);

					$index = 1;
					$vendor["is_have_order"] = "no";
					if( isset($cO[0]) ){
						$cO = $cO[0];
						foreach ($cO as $c) {
							if( !is_null( $c ) ){
								$vendor["is_have_order"] = "yes";
								break;
							}
							$index++;
						}
					}

					//cek is vendor active
					$vendor["is_order_active"] = $v->is_active;

					//get rare
					$rate = Rate::where("vendor_id",$v->id)->first();
					$vendor["rate"] = $rate;


					//get setting
					$vendor["setting"] = Settings::first();

					//cek apakah punya image
					if( $vendor["path_thumbnail"] == ""
						|| is_null($vendor["path_thumbnail"])
						|| empty($vendor["path_thumbnail"]) ){
							$vendor["path_thumbnail"] ="/public_html/admintrip/assets/tmp/person-default.png";
						}
 					return Res::cb($response,true,"Berhasil",['vendor'=>$vendor]);
				}
			} else {
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[ 'vendor'=>[] ]);
			}

		} else {
			return Res::cb($response,false,"Nomor Telp tidak valid",[]);

		}

	}
	public static function getById( Request $request, Response $response ){
		$id = $request->getAttribute("vendor_id");
		$vendor = VVendor::where('id',$id)->first();

		if ($vendor->count()) {
			return Res::cb($response,true,"Berhasil",['vendor'=>$vendor]);
		} else {
			return Res::cb($response,false,"Vendor belum terdaftar",[ 'vendor'=>[] ]);
		}

	}
	public static function newestOrder2( Request $req, Response $res){
		/*
			* tampilkan pesanan terbaru yg hanya ada di sekitar radius driver
		*/
		$p = $req->getParsedBody();
		$typeVendor = $req->getAttribute("type_vendor");
		$vendorId = $req->getAttribute("vendor_id");
		$v = Vendor::where("id",$vendorId)->first();
		$driverLat = $v['last_latitude'];
		$driverLng = $v['last_longitude'];

		if( $typeVendor == "Motor" )
			$in = '1,4,5,6,7';
		else if( $typeVendor == "Taxi" )
			$in = '3';
		else if( $typeVendor == "Mobil" )
			$in = '2';
		else if( $typeVendor == "Pickup" )
			$in = '5';

		$data = [];
		$radius = (int) Settings::first()["radius_driver"];
		$inMiles = 3959;
		$inKm = 6371;
		$m = [];
		$ojek = DB::Select("SELECT o.user_id,o.order_id as id,date_format(o.created_at,'%d-%m-%Y') as tgl,
		    				date_format(o.created_at,'%H:%i') as time,o.status,o.order_type_id
		    		 		from eo_orders o
								inner join eo_order_ojek_history h on h.id = o.order_id
		    		 		where o.status = 'Pending' and o.order_type_id in ($in)
								and TIMESTAMPDIFF(SECOND, o.created_at, '".date('Y-m-d H:i:s')."') <= 45
								and  (
											acos(sin(h.from_lat * 0.0175) * sin(".$driverLat." * 0.0175)
													 + cos(h.from_lat * 0.0175) * cos(".$driverLat." * 0.0175) *
														 cos((".$driverLng." * 0.0175) - (h.from_lng * 0.0175))
													) * $inKm
									)  <= $radius

									order by o.created_at desc
								");
			$kurir = DB::Select("SELECT o.user_id,o.order_id as id,date_format(o.created_at,'%d-%m-%Y') as tgl,
			    				date_format(o.created_at,'%H:%i') as time,o.status,o.order_type_id
			    		 		from eo_orders o
									inner join eo_order_courier_history h on h.id = o.order_id
			    		 		where o.status = 'Pending' and o.order_type_id in ($in)
									and TIMESTAMPDIFF(SECOND, o.created_at, '".date('Y-m-d H:i:s')."') <= 45
									and  (
												acos(sin(h.from_lat * 0.0175) * sin(".$driverLat." * 0.0175)
														 + cos(h.from_lat * 0.0175) * cos(".$driverLat." * 0.0175) *
															 cos((".$driverLng." * 0.0175) - (h.from_lng * 0.0175))
														) * $inKm
										)  <= $radius

										order by o.created_at desc
									");

			$laundry = DB::Select("SELECT o.user_id,o.order_id as id,date_format(o.created_at,'%d-%m-%Y') as tgl,
									date_format(o.created_at,'%H:%i') as time,o.status,o.order_type_id
									from eo_orders o
									inner join eo_order_laundry_history h on h.id = o.order_id
									where o.status = 'Pending' and o.order_type_id in ($in)
									and TIMESTAMPDIFF(SECOND, o.created_at, '".date('Y-m-d H:i:s')."') <= 45
									and  (
												acos(sin(h.order_lat * 0.0175) * sin(".$driverLat." * 0.0175)
														 + cos(h.order_lat * 0.0175) * cos(".$driverLat." * 0.0175) *
															 cos((".$driverLng." * 0.0175) - (h.order_lng * 0.0175))
														) * $inKm
										)  <= $radius

										order by o.created_at desc
									");

			$shop = DB::Select("SELECT o.user_id,o.order_id as id,date_format(o.created_at,'%d-%m-%Y') as tgl,
									date_format(o.created_at,'%H:%i') as time,o.status,o.order_type_id
									from eo_orders o
									inner join eo_order_service_history h on h.id = o.order_id
									where o.status = 'Pending' and o.order_type_id in ($in)
									and TIMESTAMPDIFF(SECOND, o.created_at, '".date('Y-m-d H:i:s')."') <= 45
									and  (
												acos(sin(h.order_lat * 0.0175) * sin(".$driverLat." * 0.0175)
														 + cos(h.order_lat * 0.0175) * cos(".$driverLat." * 0.0175) *
															 cos((".$driverLng." * 0.0175) - (h.order_lng * 0.0175))
														) * $inKm
										)  <= $radius

										order by o.created_at desc
									");

			$m = array_merge($ojek,$kurir);
			$m = array_merge($m,$laundry);
			$m = array_merge($m,$shop);

	  	foreach($m as $v){
	  		if( $v->order_type_id == "1" || $v->order_type_id == "3"  || $v->order_type_id == "2")
	  			$v->type = "Ojek";
				else if( $v->order_type_id == "4")
		  			$v->type = "Makanan";
				else if( $v->order_type_id == "6")
		  			$v->type = "Belanja";
		  	else if( $v->order_type_id == "5")
		  			$v->type = "Kurir";
				else if( $v->order_type_id == "7")
		  			$v->type = "Laundry";
				$data[] = $v;
	  	}

		return Res::cb($res,true,"Berhasil",[ 'order'=> $data ]);
	}
	public static function newestOrderOld( Request $req, Response $res){
		$typeVendor = $req->getAttribute("type_vendor");
		$vendorId = $req->getAttribute("vendor_id");

		if( $typeVendor == "Motor" )
			$in = '1,4,5,6,7';
		else if( $typeVendor == "Taxi" )
			$in = '3';
		else if( $typeVendor == "Mobil" )
			$in = '2';
		else if( $typeVendor == "Pickup" )
			$in = '5';
		else if( $typeVendor == "Teraphist" )
				$in = "11";
		else if( $typeVendor == "Laundry" )
				$in = "7";

		$data = [];
		if( $typeVendor != "Teraphist" )
		$m = DB::Select("select user_id,order_id as id,date_format(created_at,'%d-%m-%Y') as tgl,
		    				date_format(created_at,'%H:%i') as time,status,order_type_id
		    		 		from eo_orders
		    		 		where status = 'Pending' and order_type_id in ($in)
		    		 		and TIMESTAMPDIFF(MINUTE,created_at,now()) <= 60");
		else
			$m = DB::Select("select o.user_id,o.order_id as id,date_format(o.created_at,'%d-%m-%Y') as tgl,
								date_format(o.created_at,'%H:%i') as time,o.status,o.order_type_id
								from eo_orders o
								inner join eo_order_pijat_history h on h.id = o.order_id and h.vendor_id = $vendorId
								where o.status = 'Pending' and o.order_type_id in ($in)
								");

	  	foreach($m as $v){
		  		if( $v->order_type_id == "1" || $v->order_type_id == "3"  || $v->order_type_id == "2")
		  			$v->type = "Ojek";
					else if( $v->order_type_id == "4")
			  			$v->type = "Makanan";
					else if( $v->order_type_id == "6")
			  			$v->type = "Belanja";
		  		else if( $v->order_type_id == "5")
		  			$v->type = "Kurir";
	  			else if( $v->order_type_id == "7")
	  			    $v->type = "Laundry";
					else if( $v->order_type_id == "11")
							$v->type = "Pijat";
				$data[] = $v;
	  	}

		return Res::cb($res,true,"Berhasil",[ 'order'=> $data ]);



	}
	public static function newestOrder( Request $req, Response $res){
			$typeVendor = $req->getAttribute("type_vendor");
			$dateNow = date('d-m-Y');
			try{
				if($typeVendor == 'Ojek' || $typeVendor == 'Motor')
		    	$barang = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,
		    		date_format(b.created_at,'%H:%i') as time,'Makanan' as type,b.status from eo_order_service_history b
		    		where  b.status = 'Pending'
		    		and date_format(b.created_at,'%d-%m-%Y') = date_format( str_to_date('".$dateNow."','%d-%m-%Y'),'%d-%m-%Y')
		    		and TIMESTAMPDIFF(MINUTE,b.created_at,now()) <= 30
		    		order by b.id desc ");
		    	else $barang = [];


		    	$ojek = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,
		    		date_format(b.created_at,'%H:%i') as time,type_vendor as type,b.status from eo_order_ojek_history b
		    						where b.status = 'Pending'
		    						and date_format(b.created_at,'%d-%m-%Y') = date_format( str_to_date('".$dateNow."','%d-%m-%Y'),'%d-%m-%Y')
		    						and type_vendor = '".$typeVendor."'
									and TIMESTAMPDIFF(MINUTE,b.created_at,now()) <= 30
		    						order by b.id desc ");

		    	$courier = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,
		    			date_format(b.created_at,'%H:%i') as time,
		    			'Kurir' as type,b.status from eo_order_courier_history b
		    						where b.status = 'Pending'
		    						and date_format(b.created_at,'%d-%m-%Y') = date_format( str_to_date('".$dateNow."','%d-%m-%Y'),'%d-%m-%Y')
		    						 and TIMESTAMPDIFF(MINUTE,b.created_at,now()) <= 30
		    						 order by b.id desc ");

		    	$order = array_merge($barang,$ojek);
		    	$order = array_merge($order,$courier);

		    	if( count($order) > 0 ){
		    		$sortOrder = [];
					$dates = [];
		    		foreach($order as $o){
			    		$sortOrder[] = $o;
			    		$dates[] = date('d-m-Y H:i:s',strtotime( $o->tgl . " " . $o->time ));
			    	}
			    	array_multisort($dates, SORT_DESC, $sortOrder);
					return Res::cb($res,true,"Berhasil",['order' => $sortOrder]);
		    	}else{
		    		return Res::cb($res,true,"Berhasil,tetapi data tidak ditemukan",[ 'order'=> [] ]);
		    	}


			}catch(Exception $e){

				return Res::cb($res,false,"Gagal, data tidak ditemukan",[ 'order'=> [] ]);

			}

	}
	public static function cancelOrder(Request $req, Response $res){
		$userId = $req->getAttribute("user_id");
		$post = $req->getParsedBody();
		$vendorId = null;
    //var_dump($post);die();
		if( isset( $post['order_id'] ) && isset( $post['type_order'] ) ){
			if( $post['type_order'] == "Ojek" ){
				$m = OrderOjekHistory::where('id',$post['order_id'])->first();
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id  = " . $post["type_order_id"] );

			}else if($post['type_order'] == "Kurir"){
				$m = OrderCourierHistory::where("id",$post["order_id"])->first();
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id in (5) ");
			}else if($post['type_order'] == "Laundry"){
				$m = OrderLaundryHistory::where("id",$post["order_id"])->first();
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id in (7) ");


			}else if( $post['type_order'] == "Teraphist" ){
				$m = OrderPijatHistory::where('id',$post['order_id'])->first();
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id  =  " . $post["type_order_id"]);

			}else if( $post['type_order'] == "Tabung Gas & Air Galon" ){
				$m = OrderTabungGalonHistory::where('id',$post['order_id'])->first();
				$tokoId = $m['outlet_id'];
				$m->status_deskripsi = "By User";
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id  =  14 ");

			}else{
				$m = OrderServiceHistory::where('id',$post['order_id'])->first();
				if( isset($post['comment']) ) $m->alasan = $post['comment'];
				$m->status_deskripsi = "By User";

				$ob = OrderServiceBarangHistory::where("order_service_history_id",$m->id)->first();
				$tokoId = $ob['toko_id'];
				$o = DB::statement("update eo_orders
									set status = 'Cancel'
									where order_id = ".$post["order_id"]."
									and order_type_id  =  " . $post["type_order_id"]);

				//jika pakai voucher, maka ubah voucher mencaji cancel, dan beri deksripsi
				if( $m->voucher_kode != "" && !is_null($m->voucher_kode) ){
					$vh = VoucherHistory::where("voucher_kode",$m->voucher_kode)
					->where("user_id",$m->user_id)
					->where("order_service_history_id",$m->id)
					->where("toko_id",$tokoId)->where("status","Success")->first();
					$vh->status = "Cancel";
					$vh->deskripsi = "Transaksi di batalkan";
					$vh->save();
				}

        //kembalikan stok jika real
        $bh = OrderServiceBarangHistory::where("order_service_history_id",$m->id)->get();
        foreach($bh as $v){
          $cb = Barang::where("id",$v['barang_id'])->first();
          if( $cb["tipe_stock"] == "Real" ){
            $cb->stock += $v['qty'];
            $cb->save();
          }
        }

			}

			//input history cancel order
			if( $m->order_method == "Saldo" ){
			    $nominal = 0;
				if( $m->voucher_kode != "" && !is_null($m->voucher_kode) ){
				    if($m->voucher_tipe == "Prosentase" ){
				        $nominal = $m->price - (($m->price * $m->voucher_nominal)/100);
				    }
				}else{
				    $nominal = $m->price;
				}

				$mSaldoHistory = new TopupHistory;
				$mSaldoHistory->nominal = $nominal +  $m->price_antar ;
				$mSaldoHistory->type = "tambah";
				$mSaldoHistory->status = "Success";
				$mSaldoHistory->user_id = $m->user_id;
				$mSaldoHistory->alasan = "Pembatalan pembelian oleh user, no transaksi #" . $m->id;
				$mSaldoHistory->save();

			}

			//kembalikan deposit umkm
			//mengurangi pembagian hasil
			$mToko = Toko::where("id",$tokoId)->first();
			$besarBagiHasil = $mToko['nominal_bagi_hasil'];
			$tipeBagihasil = $mToko['tipe_bagi_hasil'];
			$tipeBagihasilAmount = $mToko['tipe_bagi_hasil_amount'];
			if( $tipeBagihasil=="Prosentase" ){
				$ambil = ($m->price * $besarBagiHasil)/100;
				$historySaldo = new TopupHistoryToko;
				$historySaldo->nominal = $ambil;
				$historySaldo->toko_id = $tokoId;
				$historySaldo->status = "Success";
				$historySaldo->type = "tambah";
				$historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) karena transaksi di batalkan oleh user, no. transaksi #" . $m->id;
				$historySaldo->save();

				//update toko
				$topupToko = TopupToko::where("toko_id",$tokoId)->first();
				$topupToko->nominal += $ambil;
				$topupToko->save();
			}else if( $tipeBagihasil == "Amount" ){
				if( $tipeBagihasilAmount == "Peritem" ){
					$ambil = 0;
					$historySaldo = new TopupHistoryToko;
					$historySaldo->nominal = $ambil;
					$historySaldo->toko_id = $tokoId;
					$historySaldo->status = "Success";
					$historySaldo->type = "tambah";
					$historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) karena transaksi di batalkan oleh user, no. transaksi #" . $m->id;
					$historySaldo->save();

					//update toko
					$topupToko = TopupToko::where("toko_id",$tokoId)->first();
					$topupToko->nominal += $ambil;
					$topupToko->save();
				}
			}

      //pengembalian bagi hasil ongkir jika antar sendiri
      if( $m->is_antar_sendiri=="yes" ){
        $besarBagiHasil = $mToko['nominal_bagi_hasil_ongkir'];
        $ambil = ($m->price_antar * $besarBagiHasil)/100;
				$historySaldo = new TopupHistoryToko;
				$historySaldo->nominal = $ambil;
				$historySaldo->toko_id = $tokoId;
				$historySaldo->status = "Success";
				$historySaldo->type = "tambah";
				$historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) ongkir karena transaksi di batalkan oleh user, no. transaksi #" . $m->id;
				$historySaldo->save();

				//update toko
				$topupToko = TopupToko::where("toko_id",$tokoId)->first();
				$topupToko->nominal += $ambil;
				$topupToko->save();
      }else{ //kembalikan ke masing2 ekspedisi
        /*$e = Ekspedisi::where("id",$m->ekspedisi_id)->first();
        $besarBagiHasil = $e['nominal_bagi_hasil'];
        $ambil = ($m->price_antar * $besarBagiHasil)/100;
        $historySaldo = new TopupHistoryEkspedisi;
        $historySaldo->nominal = $ambil;
        $historySaldo->ekspedisi_id = $e->id;
        $historySaldo->status = "Success";
        $historySaldo->type = "tambah";
        $historySaldo->deskripsi = "Pengembalian deposit transaksi (bagi hasil) ongkir karena transaksi di batalkan oleh user, no. transaksi #" . $m->id;
        $historySaldo->save();

        //update ekspedisi
        $topupEkspedisi = TopupEkspedisi::where("ekspedisi_id",$e->id)->first();
        if( !isset($topupEkspedisi["ekspedisi_id"]) ){
          $topupEkspedisi = new TopupEkspedisi;
          $topupEkspedisi->ekspedisi_id = $e->id;
          $topupEkspedisi->nominal = 0;
        }
        $topupEkspedisi->nominal += $ambil;
        $topupEkspedisi->save();*/
      }


			$vendorId = $m->vendor_id;
			$m->status = 'Cancel';

			if( $m->save() ){
				//note in history cancel
				$mC = new OrderHistoryCancelBooking;
				$mC->order_id 	= $post['order_id'];
				$mC->user_id 	= $userId;
				$mC->vendor_id 	= $post["vendor_id"];
				$mC->comment = $post['comment'];
				$mC->type_order = $post['type_order'];

				//send notif to vendor
				if( $post["vendor_id"] == "" || $post["vendor_id"] == "0" || $post["vendor_id"] == "null" ){
						if( $m->payment_method == "Saldo" ||  $m->order_method == "Saldo"  ){
							$tp = Topup::where("user_id",$m->user_id)->first();
							if( $post["type_order"] == "Barang" || $post["type_order"] == "Makanan" ){
									$tp->nominal +=( $m->price_antar + $m->price );
							}else{
									$tp->nominal += $m->price;
							}

							$tp->save();
							return Res::cb($res,true,"Order Telah dibatalkan",['order'=>['saldo' => $tp->nominal]]);
						}else
							return Res::cb($res,true,"Order Telah dibatalkan",['order'=>[]]);
				}else if(isset($post["cancel_from_vendor"])){ //jika cancel dari vendor
						$m->status_deskripsi = "By Driver";
				}else{
					$mV = VVendor::where("id",$post["vendor_id"])->first();
					$data = [
						"order_id" => $post["order_id"],
						"action" => "order_reject",
						"intent" => "move",
						"type_order" => $post['type_order'],
						"nama" => $mV->nama,
						"no_telp" => $mV->no_telp,
						"type_vendor" => $mV->type_vendor,
						"foto_url" => $mV->path_thumbnail,
						"user_id" => $userId,
					];

					$mV = Vendor::where("id",$post["vendor_id"])->first();
					$firebseToken = $mV->firebase_token;
					$mV->is_have_order = "0";
					$mV->save();

					$push = PushNotif::pushTo(
						"Maaf, Pesanan telah dibatalkan",
						"Klik untuk detail"
						,$firebseToken,$data,"background");
					if( $push["success"]=="1" || $push["success"]==1 ){
						if($mC->save()){
							return Res::cb($res,true,"Order Telah dibatalkan",['order'=>[]]);
						}else{
							return Res::cb($res,false,"Kesalahan masukan, mohon ulangi",[]);
						}
					}else{
						return Res::cb($res,false,"Kesalahan server, ID 208",[]);
					}
				}
			}else{
				return Res::cb($res,false,"Gagal",[]);
			}
		}else{
			return Res::cb($res,false,"Request Tidak Valid",[]);
		}
	}
	public static function initSetting( Request $request, Response $response ){
		$setting = Settings::first();
		$banks = Bank::get();
		return Res::cb($response,true,"Berhasil ",['setting'=>$setting,"bank"=>$banks]);
	}
	public static function loginModePass( Request $request, Response $response ){
		$post = $request->getParsedBody();
		$totalOrder = 0;
		$totalPendapatan = 0;
		$bank = Bank::get();
		if ( isset($post['username']) && isset($post['password'])
		&& $post['password'] != "" && $post['username']
		&& !empty($post['username']) && !empty($post['password'])   ){
			$username = $post['username'];
			$password = $post['password'];

			$vendor = VVendor::where('username',$username)->where("password" , md5($password))->first();


			if ($vendor) {
				//cek apakah driver di suspend
				if( $vendor->is_suspend == "yes" ){
					$sh = SuspendHistory::where("user_type","vendor")->where("from_id",$vendor['id'])->get();
					return Res::cb($response,false,"Maaf, akun suspend, karena '" . $sh[ count($sh) - 1 ]->ket."'");
				}

				//update vendor firebase token
				$v = Vendor::where('username',$username)->where("password" , md5($password))->first();
				$eks = Ekspedisi::where("id",$v->ekspedisi_id)->first();
				$v->firebase_token = $post["firebase_token"];
				$v->is_login = "1";
				if($v->save()){
					//get total order
					$mT = OrderOjekHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
					$totalOrder += count($mT);
					if( $vendor->type_vendor == "Ojek" ){
						$mT = OrderServiceHistory::where("vendor_id",$v->id)->where("status","Complete")->get();
						$totalOrder += count($mT);
					}else if( $vendor->type_vendor == "Teraphist" ){
							$tipeTeraphist = DB::select("select v.pijat_id,p.nama from eo_vendor_vs_pijat v
							 	inner join eo_pijat p on p.id = v.pijat_id
								where vendor_id = " . $vendor->id);
							$vendor["tipe_teraphist"] = $tipeTeraphist;
					}
					$vendor["total_order"] = $totalOrder;

					//get total pendapatan
					$mP = DB::select("select sum(income) as income from eo_vendor_income_history where vendor_id = ".$v->id);
					$vendor["total_pendapatan"] = is_null($mP[0]->income) ? 0 : $mP[0]->income;

					//cek apa masih punya status order yang belum di selesaikan
					/*$cO = json_encode(DB::Select("	select esh.id id_1,eooh.id id_2,eoch.id id_3 from eo_order_service_history esh

										left join eo_order_ojek_history eooh
										on eooh.vendor_id = ". $v->id." and  eooh.status = 'Progress'

										left join eo_order_courier_history eoch
										on eoch.vendor_id = ". $v->id." and  eoch.status = 'Progress'

										where esh.vendor_id = ".$v->id."  and  esh.status = 'Progress'
										"));
					$cO = json_decode($cO,true);

					$index = 1;
					$vendor["is_have_order"] = "no";
					if( isset($cO[0]) ){
						$cO = $cO[0];
						foreach ($cO as $c) {
							if( !is_null( $c ) ){
								$vendor["is_have_order"] = "yes";
								break;
							}
							$index++;
						}
					}*/
					$fs = DB::Select("select * from v_vendor_status v where v.id = " . $v->id);
					if(  $fs[0]->status == "on_order" )
						$vendor["is_have_order"] = "yes";
					else $vendor["is_have_order"] = "no";

					//cek is vendor active
					$vendor["is_order_active"] = $v->is_active;

					//get rare
					$rate = Rate::where("vendor_id",$v->id)->first();
					$vendor["rate"] = $rate;

					//bank
					$bank = DB::select("select * from eo_bank ");
					$vendor["bank"] = $bank;

					//get setting
					$vendor["setting"] = Settings::first();
					$vendor["setting"]["driver_prosentase"] = $vendor["percent"];
					$vendor["setting"]["whatsapp_number_vendor"] = $eks['whatsapp_number_vendor'];

					//cek apakah punya image
					if( $vendor["path_thumbnail"] == ""
						|| is_null($vendor["path_thumbnail"])
						|| empty($vendor["path_thumbnail"]) ){
							$vendor["path_thumbnail"] ="/admintoko.tokokota.com/assets/tmp/person-default.png";
						}
 					return Res::cb($response,true,"Berhasil",['vendor'=>$vendor]);
				}
			} else {
				return Res::cb($response,false,"Username atau Password salah",[ 'vendor'=>[] ]);
			}

		} else {
			return Res::cb($response,false,"Login tidak valid",[]);

		}

	}
	public static function history( Request $req, Response $res){
		$status_id = $req->getAttribute("status_id");
		$id = $req->getAttribute("vendor_id");

		if($status_id == 1){
	    	$status = "'Progress'";
	    }else{
	    	$status = "'Complete','Cancel','Laundry DiTerima'";
	    }

		try{
			//get type vendor
			$typeVendor = VVendor::where("id",$id)->first()["type_vendor"];
			//if($typeVendor == "Motor" || $typeVendor == "Ojek" )
	    		$barang = DB::select("select u.nama as nama_user,
	    		b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y')
	    		as tgl,date_format(b.created_at,'%H:%i') as time,
	    		case when o.order_type_id = 4 then 'Makanan' else 'Belanja' end as type,
	    		b.status from eo_order_service_history b
	    		inner join eo_orders o on o.order_id = b.id
					inner join eo_user u on u.id = b.user_id
	    		where b.vendor_id = $id and b.status in($status)
	    		and o.order_type_id in (4,6)");
	    	//else $barang = [];


	    	/*$ojek = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,type_vendor as type,b.status from eo_order_ojek_history b
	    						where b.vendor_id = $id and b.status in($status) and type_vendor='".$typeVendor."' ");
				$courier = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,'Kurir' as type,b.status from eo_order_courier_history b
	    						where b.vendor_id = $id and b.status in($status)  ");
				$laundry = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,'Laundry' as type,b.status from eo_order_laundry_history b
	    						where b.vendor_id = $id and b.status in($status)  ");
				$pijat = DB::select("select b.user_id,b.id,date_format(b.created_at,'%d-%m-%Y') as tgl,date_format(b.created_at,'%H:%i') as time,'Pijat' as type,b.status from eo_order_pijat_history b
	    						where b.vendor_id = $id and b.status in($status)  ");*/



	    	$order = $barang;
	    	//$order = array_merge($order,$courier);
	    	//$order = array_merge($order,$laundry);
	    	//$order = array_merge($order,$pijat);

			return Res::cb($res,true,"Berhasil",['history' => $order]);

		}catch(Exception $e){

			return Res::cb($res,false,"Gagal, data tidak ditemukan",[ 'history'=> [] ]);

		}

	}
	public static function setOrder(Request $req, Response $res){
		$p = $req->getParsedBody();
		$mVendor = Vendor::where("id",$p['vendor_id'])->first();
		$mVendor->is_have_order = $p["have_order"];
		if($mVendor->save()){
			return Res::cb($res,true,"Berhasil",[ 'vendor'=> "null" ]);
		}else{
			return Res::cb($res,false,"Gagal",[ 'vendor'=> "null" ]);
		}


	}
	public static function finishOrder( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post["order_id"]) && isset($post["type_order"]) ){
			//cek apakah deposit vendor masih cukup untuk menerima Pesanan
			$pendapatan = 0;
			if( $post["type_order"] == "Ojek" ){
				//cek apakah vandor ada di radius tujuan
				$mOrder = Orders::where("order_id",$post["order_id"])->where("order_type_id",$post["order_type_id"])->first();
				$mOrder->status = 'Complete';
				$mOrder->save();
				switch ($post["order_type_id"]) {
					case '1':
						$name = "Motor";
						break;
					case '2':
						$name = "Mobil";
						break;
					case '3':
						$name = "Taxi";
						break;
				}


				$pros = VendorType::where("name",$name)->first()["percent"];
				$pendapatan = ( (float) $m->price *  $pros ) / 100;
				$m = OrderOjekHistory::where("id",$post['order_id'])->first();

				//get last position driver
				$v = Vendor::where("id",$m->vendor_id)->first();
				$radius = (int) Settings::first()["radius_driver_action_finish_order"] / 1000;
				$inKm = 6371;


				/*update deposit vendor*/
				$tV = TopupVendor::where("vendor_id",$m->vendor_id)->first();
				if( $m->payment_method == "Cash" ){
					$untungPerusahaan =  ( (float) $m->price *  (100-$pros) ) / 100;
					$tV->nominal = (float) $tV->nominal - $untungPerusahaan;
				}else if($m->payment_method == "Saldo"){
					$untungVendor =  ( (float) $m->price *  $pros ) / 100;
					$tV->nominal = (float) $tV->nominal + $untungVendor;
				}
				$tV->save();




			}else if( $post["type_order"] == "Kurir" ){
				$mOrder = Orders::where("order_id",$post["order_id"])->where("order_type_id",$post["order_type_id"])->first();
				$mOrder->status = 'Complete';
				$mOrder->save();

				$m = OrderCourierHistory::where("id",$post['order_id'])->first();
				$pros = VendorType::where("name","Motor")->first()["percent"];
				/*update deposit vendor*/
				$tV = TopupVendor::where("vendor_id",$m->vendor_id)->first();
				if( $m->payment_method == "Cash" ){
					$pros = 100 - $pros;
					$untungPerusahaan =  ( (float) $m->price *  $pros ) / 100;
					$tV->nominal = (float) $tV->nominal - $untungPerusahaan;
				}else if($m->payment_method == "Saldo"){
					$untungVendor =  ( (float) $m->price *  $pros ) / 100;
					$tV->nominal = (float) $tV->nominal + $untungVendor;
				}
				$tV->save();
			}else if ( $post["type_order"] == "Barang" ){
				$mOrder = Orders::where("order_id",$post["order_id"])->where("order_type_id",$post["order_type_id"])->first();
				$mOrder->status = 'Complete';
				$mOrder->save();

				$m = OrderServiceHistory::where("id",$post['order_id'])->first();
				$m->status_deskripsi = "By Kurir";
				$pros = VendorType::where("name","Motor")->first()["percent"];

				//cek jika punya poin
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
							$push = PushNotif::pushTo("Selamat","Anda dapat poin ",$user['firebase_token'],$dataPushPoin);
					}

				}

				/*update deposit vendor*/
				/*$tV = TopupVendor::where("vendor_id",$m->vendor_id)->first();
				if( $m->order_method == "Cash" ){
					$untungPerusahaan =  ( (float) $m->price_antar *  (100-$pros) ) / 100;
					$tV->nominal = (float) $tV->nominal - $untungPerusahaan;
				}else if($m->order_method == "Saldo"){
					$untungVendor =  ( (float) $m->price_antar *  $pros ) / 100;
					$tV->nominal = (float) $tV->nominal + $untungVendor;
				}
				$tV->save();*/

			}else if ( $post["type_order"] == "Laundry" ){
				$mOrder = Orders::where("order_id",$post["order_id"])->where("order_type_id",$post["order_type_id"])->first();
				$mOrder->status = 'Laundry DiTerima';
				$mOrder->save();

				$m = OrderLaundryHistory::where("id",$post['order_id'])->first();
				$pros = VendorType::where("name","Motor")->first()["percent"];

				/*update deposit vendor*/
				$tV = TopupVendor::where("vendor_id",$m->vendor_id)->first();
				if( $m->order_method == "Saldo" ){
					$untungVendor = ( $m->ongkir * $pros ) / 100;
					$tV->nominal = (float) $tV->nominal + $untungVendor ;
					$tV->save();
				} else if($m->order_method == "Cash" ){
					$untungPerusahaan = ( $m->ongkir * (100-$pros) ) / 100;
					$tV->nominal = (float) $tV->nominal - $untungPerusahaan ;
					$tV->save();
				}
			}else if ( $post["type_order"] == "Teraphist" ){
				$mOrder = Orders::where("order_id",$post["order_id"])->where("order_type_id",$post["order_type_id"])->first();
				$mOrder->status = 'Compelete';
				$mOrder->save();

				$m = OrderPijatHistory::where("id",$post['order_id'])->first();
				$pros = VendorType::where("name","Motor")->first()["percent"];
				$pros= 100 - (float) $pros;


				/*update deposit vendor*/
				$tV = TopupVendor::where("vendor_id",$m->vendor_id)->first();
				$price = (float) $m->biaya_jalan;
				$prosentasePendaptan =  ($price *  $pros ) / 100;
				$tV->nominal = (float) $tV->nominal - $price ;
				$tV->save();

			}

			if( $post["type_order"] == "Laundry" )
				$m->status = "Laundry DiTerima";
			else
				$m->status = "Complete";

			//sebelum disimpan , pastikan kalau sudah mengirim notifikasi ke user,
			//notifikasi kalau booking sdah selssai, dan bisa di rating
			$u = User::where("id",$m->user_id)->first();
			$mV = VVendor::where("id",$m->vendor_id)->first();

			$mVendor = Vendor::where("id",$m->vendor_id)->first();
			$mVendor->is_have_order = "0";
			$mVendor->save();

			$data = [
				"action" => "order_finish",
				"intent" => "move",
				"vendor_id" => $m->vendor_id,
				"type_order" => $post["type_order"],
				"order_id" => $post["order_id"],
				"nama" => $mV->nama,
				"no_telp" => $mV->no_telp,
				"type_vendor" => $mV->type_vendor,
				"foto_url" => $mV->path_thumbnail,
			];

			$push = PushNotif::pushTo("Update Pesanan","Pesanan Selesai & telah di kirim oleh kurir",
					$u->firebase_token,$data,"background");


			//if( isset($push["success"]) && ($push["success"] == "1" || $push["success"]  == 1) ){

				if( $m->save() ){
					//update total order
					$mVendor = Vendor::where("id",$m->vendor_id)->first();
					$mVendor->total_order = (int) $mVendor->total_order + (int) 1;
					if($mVendor->save()){
						//baca settingan typeVendor
						$vvendor = VVendor::where("id",$m->vendor_id)->first();
						if($vvendor->sistem_gaji == "BAGI HASIL"){
							$percent  = $vvendor->percent;
							$potongan = ($m->price * $percent) / 100;

							//update income vendor
							$mVendorIncomeHistory = new VendorIncomeHistory;
							$mVendorIncomeHistory->vendor_id = $m->vendor_id;
							$mVendorIncomeHistory->income  = $m->price - $potongan;
							$mVendorIncomeHistory->potongan = $potongan;
							$mVendorIncomeHistory->save();
						}
					}


					return Res::cb($res,true,"Order telah diselesaikan",['order'=>[]]);
				}else{
					return Res::cb($res,false,"Terjadi kesalahan pada server, save tidak bekerja",["order"=>[]] );
				}
			/*}else{
				return Res::cb($res,false,"Terjadi kesalahan pada server",["order"=>[]]);
			}*/

		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}
	}
	public static function updateLastPosition(Request $req, Response $res){
		$post = $req->getParsedBody();
		$m = new VendorPosition;
		$m->latitude = $post["latitude"];
		$m->longitude = $post["longitude"];
		$m->vendor_id = $post["vendor_id"];
		if( $m->save() ){
			//update position vendor
			$v = Vendor::where("id",$post["vendor_id"])->first();
			$v->last_latitude = $post["latitude"];
			$v->last_longitude = $post["longitude"];
			if($v->save()){
				return Res::cb($res,true,"Updated",['pos'=>[]]);
			}
		}
	}
	public static function getVendorPosition(Request $req, Response $res){
		$typeVendor = $req->getAttribute("type_vendor");
		$vendorId = $req->getAttribute("vendor_id");

		if( $typeVendor == "Motor" ){
			$typeVendor = 3;
		}else if($typeVendor == "Mobil"){
			$typeVendor = 4;
		}else if($typeVendor == "Taxi"){
			$typeVendor = 2;
		}else if($typeVendor == "Pickup"){
			$typeVendor = 5;
		}

		//get last position driver
		$v = Vendor::where("id",$vendorId)->first();
		$radius = (int) Settings::first()["radius_driver"];
		$inMiles = 3959;
		$inKm = 6371;


		$sqlRadius = DB::Select("SELECT last_latitude,last_longitude FROM eo_vendor a
					  WHERE (
				          acos(sin(a.last_latitude * 0.0175) * sin(".$v->last_latitude." * 0.0175)
				               + cos(a.last_Latitude * 0.0175) * cos(".$v->last_latitude." * 0.0175) *
				                 cos((".$v->last_longitude." * 0.0175) - (a.last_longitude * 0.0175))
				              ) * $inKm
				      )  <= $radius
				      and type_vendor_id = $typeVendor
				      and id != $vendorId
				      and !isnull(a.last_latitude)
				      and !isnull(a.last_longitude)");

		return Res::cb($res,true,"Berhasil",
			[
				'position'=>$sqlRadius ,
				"my_position" => [ "latitude" => $v->last_latitude,"longitude" => $v->last_longitude ]
			]);

	}
	public static function getReview(Request $req, Response $res){
		$id = $req->getAttribute("id"); //vendor id
		$s = DB::Select("select * from v_review where vendor_id = $id order by id desc limit 5");
		return Res::cb($res,true,"Berhasil",["reviews" => $s]);
	}
	public static function getInfo(Request $req, Response $res){
		$s = DB::Select("select content,date_format(updated_at,'%d-%m-%Y') as date from eo_info order by id desc limit 1 ");
		return Res::cb($res,true,"Berhasil",["info" =>  $s[0] ]);
	}
	public static function getRating(Request $req, Response $res){
		$p = $req->getParsedBody();
		$m = DB::Select("select * from v_review where vendor_id = '".$p["vendor_id"]."' ");
		if(count($m) <= 0){
			return Res::cb($res,true,"Berhasil",["rating" =>  "0" ]);
		}
		$totalRate = 0;
		foreach ($m as $v) {
			$totalRate+= $v->rate;
		}

		return Res::cb($res,true,"Berhasil",["rating" => round($totalRate/count($m),1)  ]);
	}
	public static function getRadius(Request $req, Response $res){
		$post = $req->getParsedBody();
		$lat = (float) $post["latitude"];
		$lng = (float) $post["longitude"];
		$type = $req->getAttribute("type_vendor");

		//get setting radius
		$set = Settings::first();
		$radius = $set->radius_driver;
		//    inner join v_vendor_status s on s.id = v.id and s.status != 'on_order'
		$s = "select
		v.last_latitude,v.last_longitude from v_vendor v

        inner join eo_vendor vv on vv.id = v.id and (vv.is_active = 'yes' or vv.is_active = '1') and vv.is_login = '1'

        where (v.last_latitude is not null and v.last_latitude != '')
        and (v.last_longitude is not null and v.last_longitude != '')
        and v.name = '".$type."'
        and (acos(sin(v.last_latitude * 0.0175) * sin(".$lat." * 0.0175)
	         + cos(v.last_latitude * 0.0175) * cos(".$lat." * 0.0175) *
	         cos((".$lng." * 0.0175) - (v.last_longitude * 0.0175))
	         ) * 6371 ) < ". $radius . " and (acos(sin(v.last_latitude * 0.0175) * sin(-6.892417 * 0.0175)
                                       	         + cos(v.last_latitude * 0.0175) * cos(-6.892417 * 0.0175) *
                                       	         cos((112.0428238 * 0.0175) - (v.last_longitude * 0.0175))
                                       	         ) * 6371 ) > 0 ";

        $q = DB::Select($s);
		return Res::cb($res,true,"berhasil",["drivers" =>$q]);
	}
	public static function changeStatus(Request $req, Response $res){
		$status = $req->getAttribute("status");
		$vendorid = $req->getAttribute("vendor_id");

		$m = Vendor::where("id",$vendorid)->first();
		$m->is_active = $status;
		if( $m->save() ){
			return Res::cb($res,true,"berhasil",["drivers" =>$m]);
		}else
			return Res::cb($res,false,"Gagal",["drivers" =>[]]);


	}

}
