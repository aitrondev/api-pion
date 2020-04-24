<?php
namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

use Models\User;
use Models\Review;
use Models\Aktifasi;
use Models\Foto;
use Models\RateHistory;
use Models\VVendor;
use Models\InputUser;
use Models\VendorType;
use Models\Credit;
use Models\CreditSetting;
use Models\Settings;
use Models\Bank;
use Models\AppProfile;
use Models\TopupSetting;
use Models\LaundrySetting;
use Models\Saran;
use Models\UserAlamat;


use Tools\Res;
use Tools\Encrypt;
use Tools\PushNotif;
use Tools\Helper;

class UserController extends AppController{
	protected $ci;

	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

	public static function updateProfile( Request $request, Response $response ){
		$p = $request->getParsedBody();
		$m = InputUser::where("id",$p['user_id'])->first();
		$m->nama = $p['nama'];
		$m->email = $p['email'];
		$m->jenis_kelamin = $p['jenis'];
		$m->norek = $p['norek'];
		$m->atasnama = $p['atasnama'];
		$m->bank = $p['bank'];
		if( $m->save() ){
			return Res::cb($response,true,"Berhasil");
		}
	}

	public static function updatePhotoProfile( Request $request, Response $response ){
		$p = $request->getParsedBody();
		$m = Foto::where("from_id",$p['user_id'])->where("from_table","eo_user")->first();
		$path = Helper::uploadBase64Image($p['photo']);;
		$m->path_origin = $p['photo'];
		$m->path_thumbnail = $path;
		if( $m->save() ){
			return Res::cb($response,true,"Berhasil");
		}
	}

	public static function updatePhotoRumah( Request $request, Response $response ){
		$p = $request->getParsedBody();
		$m = InputUser::where("id",$p['user_id'])->first();
		$path = Helper::uploadBase64Image($p['photo']);;
		$m->photo_rumah = $p['photo'];
		if( $m->save() ){
			return Res::cb($response,true,"Berhasil");
		}
	}

	public static function addAlamat( Request $request, Response $response ){
		$p = $request->getParsedBody();
		$m = new UserAlamat;
		$m->user_id = $p['user_id'];
		$m->kota_id = $p['kota_id'];
		$m->jalan = $p['jalan'];
		$m->gang = $p['gang'];
		$m->nama = $p['nama'];
		$m->no_telp = $p['no_telp'];
		$m->penerima = $p['penerima'];
		if($m->save()){

			$addresses = DB::select("select
				a.*,k.*, c.id as id,a.id as alamatId
				from eo_user_alamat a
				inner join eo_city c on c.id = a.kota_id
				inner join eo_kota k on k.city_id = c.kota_id
				where user_id = " . $p['user_id']);

			return Res::cb($response,true,"Berhasil",["addresses"=>$addresses]);
		}
	}

	public static function getAlamat( Request $request, Response $response ){
		$userId = $request->getAttribute("user_id");
		$m = DB::select("select
			a.*,k.*, c.id as id,a.id as alamatId
			from eo_user_alamat a
			inner join eo_city c on c.id = a.kota_id
			inner join eo_kota k on k.city_id = c.kota_id
			where user_id = " . $userId);
		return Res::cb($response,true,"Berhasil",[ "addresses" => $m ]);
	}

	public static function setAlamatUtama( Request $request, Response $response ){
		$id = $request->getAttribute("id");
		$userId = $request->getAttribute("user_id");

		//jadikan alamat lain sebagai tidak utama
		$l = UserAlamat::where("user_id",$userId)->get();
		foreach($l as $v){
				$v->is_utama="no";
				$v->save();
		}

		$m = UserAlamat::where("id",$id)->first();
		$m->is_utama = "yes";
		if($m->save()){
			$addresses = DB::select("select
					a.*,k.*, c.id as id,a.id as alamatId
				from eo_user_alamat a
				inner join eo_city c on c.id = a.kota_id
				inner join eo_kota k on k.city_id = c.kota_id
				where user_id = " . $userId);
			return Res::cb($response,true,"Berhasil",["addresses"=>$addresses]);
		}

	}

	public static function checkNewVersion( Request $request, Response $response ){
		$m = AppProfile::first();
		return Res::cb($response,true,"Berhasil",["version_code" => $m['version_code'] ]);
	}

	public static function changeCity( Request $request, Response $response ){
		$userId=$request->getAttribute("user_id");
		$cityId=$request->getAttribute("city_id");
		$m = InputUser::where("id",$userId)->first();
		$m->kota_id = $cityId;
		if( $m->save() ){
			return Res::cb($response,true,"Berhasil",[]);
		}
	}

	public static function saveSaran( Request $request, Response $response ){
		$p = $request->getParsedBody();
		$m = new Saran();
		$m->user_id = $p['user_id'];
		$m->sarankritik = $p['saran'];
		if( $m->save() ){
			return Res::cb($response,true,"Berhasil",[]);
		}
	}

	public static function login( Request $request, Response $response ){
		$post = $request->getParsedBody();
		if ( isset($post['no_telp']) ){

			$phone = $post['no_telp'];
			$user = User::where('no_telp',$phone)->first();

			if( count($user) <= 0 ){
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[]);
			}

			/*cek user ini di suspend apa tidak*/
			if( $user->is_suspend == "yes" ){
				return Res::cb($response,false,"Maaf, Akun Anda tidak bisa digunakan",[]);
			}

			if ($user->count()) {
				$expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes")) ;
				$i = InputUser::where('no_telp',$phone)->first();
				$i->firebase_token = $post["firebase_token"];
				if( $i->save() ){
					$activation_code =  Encrypt::generateActivationCode($phone);
					$mActivation = new Aktifasi;
					$mActivation->kode_aktifasi = $activation_code;
					$mActivation->is_active = "yes";
					$mActivation->user_id = $i->id;
					$mActivation->expire_date =$expire_date;

					if( $mActivation->save() ){
						//send sms notifikasi

						if( !isset($post['is_after_register']) ){
							if(self::getVerificationSetting()["number"])
								PushNotif::sms(self::getSetting()->sms_text,$activation_code,$i->no_telp);

							if( self::getVerificationSetting()["email"] )
								PushNotif::email(self::getSetting()->sms_text,$activation_code,$i->email);
						}

						return Res::cb($response,true,"Berhasil",['user'=>$user]);
					}

				}else{
					return Res::cb($response,false,"Kesalahan pada server, coba beberapa saat lagi",[]);
				}

			} else {
				return Res::cb($response,false,"Nomor Telp belum terdaftar",[]);
			}
		} else {
			return Res::cb($response,false,"Nomor Telp tidak valid",[]);
		}

	    return $newResponse;

	}

	public static function UpdateAkunLogin( Request $request, Response $response){
		$post = $request->getParsedBody();
		if (isset($post['password_baru']) && isset($post['user_id']) ){

			$password = $post['password_baru'];
			$userId 	= $post['user_id'];
			$user 		= User::where('id',$userId)->first();

			if( isset($user['username']) ){
          //check password lama apakah sesuai
          if( $user["password"] != md5($post['password_lama']) )
						return Res::cb($response,false,'Password lama tidak sesuai !');
          $newUser = InputUser::where('id',$userId)->first();
    			$newUser->password = md5($post['password_baru']);

    			if($newUser->save()){
    				return Res::cb($response,true,"Berhasil",["user" => $newUser]);
    			}else{
    				return Res::cb($response,false,"Terdapat kesalahan, mohon dicoba lagi",[]);
    			}

			}else{
			    return Res::cb($response,false,"User tidak di ketahui",[]);
			}

		}
	}

	public static function loginModeUserPass( Request $request, Response $response ){
		$post = $request->getParsedBody();
		if ( isset($post['username']) && isset($post['password']) ){

			$username = $post['username'];
			$password = $post['password'];
			$user = User::where('no_telp',$username)->where("password",md5($password))->first();

			if( count($user) <= 0 ){
				return Res::cb($response,false,"Nomor atau password Anda salah",[]);
			}

			/*cek user ini di suspend apa tidak*/
			if( $user->is_suspend == "yes" ){
				return Res::cb($response,false,"Maaf, Akun Anda tidak bisa digunakan",[]);
			}

			if ($user->count()) {
				$expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes")) ;
				$i = InputUser::where('no_telp',$username)->where("password",md5($password))->first();
				$i->firebase_token = $post["firebase_token"];
				if( $i->save() ){

					//alamat user
					$alamat = DB::select("select
						a.*,k.*, c.id as id,a.id as alamatId
						from eo_user_alamat a
						inner join eo_city c on c.id = a.kota_id
						inner join eo_kota k on k.city_id = c.kota_id
						where user_id = " . $i->id);
						
					$user = User::where('no_telp',$username)->where("password",md5($password))->first();
					$user->addresses = $alamat;

					return Res::cb($response,true,"Berhasil",['user'=>$user]);
				}else{
					return Res::cb($response,false,"Kesalahan pada server, coba beberapa saat lagi",[]);
				}

			} else {
				return Res::cb($response,false,"Nomor atau password Anda salah",[]);
			}
		} else {
			return Res::cb($response,false,"Nomor atau password tidak valid",[]);
		}


	}


	public static function refreshToken( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post['token']) ){
			$m = User::where('firebase_token',$post['token'])->first();
			if( count( $m ) > 0 ){
				$m->firebase_token = $post['token'];
				if( $m->save() ){
					return Res::cb($res,true,"Token berhasil diupdate",["token"=>$post['token']]);
				}else{
					return Res::cb($res,false,"Token terdaftar tapi gagal di simpan",[]);
				}
			}else{
				return Res::cb($res,false,"Token Belum terdaftar",[]);
			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}

	}

	public static function giveRating( Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset( $post["vendor_id"] ) && isset( $post["user_id"] ) && isset( $post["rate"] ) ){
			$mRateHistory = new RateHistory;
			$mRateHistory->vendor_id = $post["vendor_id"];
			$mRateHistory->user_id = $post["user_id"];
			$mRateHistory->rate = $post["rate"];
			$mRateHistory->msg = $post["msg"];
			if( $mRateHistory->save() ){
				//send notification to driver
				$mVendor = VVendor::where("id",$post["vendor_id"])->first();
				$data = [
					"action" => "give_rating",
					"intent" => "move",
					'rate'	 => $mVendor->rate,
					"msg"	 => $post["msg"]
				];

				$push = PushNotif::pushTo("Anda mendapat nilai","Klik untuk detail"
						,$mVendor->firebase_token,$data,"background");

				return Res::cb($res,true,"Berhasil",['rate'=>[]]);

			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}
	}

	public static function getSetting(){
		return Settings::first();
	}

	public static function getVerificationSetting(){
		$useVerificationEmail  = false;
		$useVerificationNumber = false;

		$s =  Settings::first();
		if( $s->is_verification_number == "yes"
			&& $s->is_verification_email == "yes"  ){
			$useVerificationEmail = true;
			$useVerificationNumber = true;
		}else if( $s->is_verification_number == "yes" ){
			$useVerificationNumber = true;
		}else{
			$useVerificationEmail = true;
		}

		return ["number" => $useVerificationNumber,"email"=>$useVerificationEmail];
	}

	public static function register( Request $request, Response $response ){
		$post = $request->getParsedBody();
		//return Res::cb($response,false,"oh",$post);

		if ( isset($post['no_telp']) && isset($post['name'])  ){
				$name = $post['name'];
        $jenis_kelamin = $post['jenis_kelamin'];
        $foto = isset($post['foto']) ? $post['foto'] : "no_edit";
        $email = isset($post['email']) ? $post['email'] : "-";
        if( $post['email'] != "" && !Helper::isEmailValid($email) ){
        	return Res::cb($response,false,"Email Anda tidak valid");
        }

				if($post['name'] == ""){
					return Res::cb($response,false,"Nama tidak boleh kosong");
				}

		   /*if( $post['email'] == ""){
				return Res::cb($response,false,"Email tidak boleh kosong");
			}*/

		   if($post['password'] == ""){
				return Res::cb($response,false,"Password tidak boleh kosong");
			}

			if($post['no_telp'] == ""){
				return Res::cb($response,false,"Nomor telp tidak boleh kosong");
			}

			if($post['alamat_nama_jalan'] == ""){
				return Res::cb($response,false,"Nama Jalan tidak boleh kosong");
			}

			if($post['alamat_nama_gang'] == ""){
				return Res::cb($response,false,"Nama Gang tidak boleh kosong");
			}

			if($post['default_kota_id'] == ""){
				return Res::cb($response,false,"Kota tidak boleh kosong");
			}

			if(!is_numeric($post['no_telp'])){
				return Res::cb($response,false,"Nomor Telp tidak valid");
			}

			$firebaseToken = isset($post['firebase_token']) ? $post['firebase_token'] : null;
			$phone = $post['no_telp'];
			$user_exists = User::where('no_telp', $phone)->first();
			$email_exists = User::where('email', $email)->first();

			if ( $email != "" && isset($email_exists["email"]) ){
				return Res::cb($response,false,'Email  '.$email.' sudah digunakan');
			}
			if ( isset($user_exists["no_telp"]) ){
				return Res::cb($response,false,'Nomor  '.$phone.' sudah digunakan');
			} else {
				$activation_code =  Encrypt::generateActivationCode($phone);

				$newUser = new InputUser;
				$newUser->nama = $name;
        $newUser->no_telp = $phone;
        $newUser->kode_aktifasi = $activation_code;
        $newUser->jenis_kelamin = $jenis_kelamin;
        $newUser->firebase_token = $firebaseToken;
        $newUser->is_input_code_invite = 'no';
        $newUser->email = $email;
				$newUser->alamat_nama_jalan = $post['alamat_nama_jalan'];
				$newUser->password = md5($post["password"]);
				$newUser->default_kota_id = $post['default_kota_id'];

				if ($newUser->save()) {
					//input foto

							//simpan alamat
							$userAlamat = new UserAlamat;
							$userAlamat->user_id = $newUser->id;
							$userAlamat->jalan = $post['alamat_nama_jalan'];
							$userAlamat->gang = $post['alamat_nama_gang'];
							$userAlamat->no_telp = $post['no_telp'];
							$userAlamat->penerima = $post['name'];
							$userAlamat->kota_id = $post['default_kota_id'];
							$userAlamat->nama = "Rumah";
							$userAlamat->is_utama = "yes";
							$userAlamat->save();



			        	$creditSetting = CreditSetting::where("id","1")->first();
			        	if( count($creditSetting) > 0  ){
			        		/*generate code credit*/
				        	$creditCode = strtoupper(Encrypt::generateCreditCode($creditSetting->prefix_code_invite_friend,$newUser->no_telp));
				        	$credit = new Credit;
				        	$credit->user_id = $newUser->id;
				        	$credit->code_credit = $creditCode;
				        	$credit->nominal = 0;
				        	$credit->max_share = 0;
				        	$credit->save();
			        	}


			        	/*input activation*/
						$activation = new Aktifasi;
						$activation->kode_aktifasi = $activation_code;
						$activation->user_id = $newUser->id;
						if( $activation->save() ){

							$activation = Aktifasi::find($activation->id);
							$activation->expire_date = date('Y-m-d H:i:s', strtotime( date($activation->created_at) . "+3 minutes")) ;
							if($activation->save()){
								if( self::getVerificationSetting()["number"] ){
									PushNotif::sms(self::getSetting()->sms_text,$activation_code,$newUser->no_telp);
								}

								if( self::getVerificationSetting()["email"] ){
									PushNotif::email(self::getSetting()->sms_text,$activation_code,$newUser->email);
								}


								$activation["path_thumbnail"] =  "";
								$activation['code_invite'] = $creditCode;
								$activation['is_input_code_invite'] = $newUser->is_input_code_invite;
								$activation['email'] = $newUser->email;

								$alamat = DB::select("select
									a.*,k.*, c.id as id,a.id as alamatId
									from eo_user_alamat a
									inner join eo_city c on c.id = a.kota_id
									inner join eo_kota k on k.city_id = c.kota_id
									where user_id = " . $newUser->id);
								$user = User::where('id',$newUser->id)->first();
								$user->addresses = $alamat;

								return Res::cb($response,true,"Berhasil",['user'=> $user]);
							}
						}

				} else {
					return Res::cb($response,false,"Gagal nih");
				}
			}
		} else {
			return Res::cb($response,false,"Register failed, form tidak lengkap");
		}

	}

	public static function rateVendor(Request $req, Response $res){
		$post = $req->getParsedBody();
		if( isset($post["user_id"]) && isset($post["rate"]) && isset($post["vendor_id"]) ){
			$m = new RateHistory;
			$m->vendor_id = $post["vendor_id"];
			$m->user_id = $post["user_id"];
			$m->rate = $post["rate"];
			if( $m->save() ){
				return Res::cb($res,true,"Terimakasih telah merating ",[]);
			}else{
				return Res::cb($res,false,"Terdapat kesalahan pada server",[]);
			}
		}else{
			return Res::cb($res,false,"Request tidak valid",[]);
		}
	}

	public static function changeNumber(Request $req, Response $res){
		$post = $req->getParsedBody();
		if($post['nomor_lama'] && $post["nomor_baru"]){
			$chekNumber = InputUser::where("no_telp",$post["nomor_lama"])->first();
			if( count($chekNumber) > 0  ){
				$activation_code =  Encrypt::generateActivationCode($post["nomor_baru"]);
				$expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes")) ;

				$m = new Aktifasi;
				$m->kode_aktifasi = $activation_code;
				$m->is_active = 'yes';
				$m->user_id = $chekNumber->id;
				$m->expire_date = 	$expire_date;
				$m->is_expire = "no";

				$chekNumber->no_telp = $post["nomor_baru"];

				$useEmail = false;
				$useNumber = false;
				if(self::getVerificationSetting()["number"]){
					$sendSms = PushNotif::sms(self::getSetting()->sms_text,$m->kode_aktifasi,$post["nomor_baru"]);
					$useNumber = true;
				}

				if( self::getVerificationSetting()["email"] ){
					PushNotif::email(self::getSetting()->sms_text,$m->kode_aktifasi,$chekNumber->email);
					$useEmail = true;
				}

				if( $useNumber ){
					if( isset($sendSms['status']) && $sendSms['status'] == '0' ){
						if($m->save()){
							$chekNumber->save();
							if( $useEmail )
								return Res::cb($res,true,"Kode Aktifasi telah kami kirim ke Nomor dan Email Anda",["data" => "null"]);
							else {
								return Res::cb($res,false,"Kode Aktifasi telah kami kirim ke Nomor Anda",["data" => "null"]);
							}
						}
					}else{
						return Res::cb($res,false,$sendSms["text"],["data" => "null"]);
					}
				}else{
					if($m->save()){
						$chekNumber->save();
						return Res::cb($res,true,"Kode Aktifasi telah kami kirim ke Email Anda",["data" => "null"]);
					}
				}

			}else{
				return Res::cb($res,false,"Nomor Anda tidak terdaftar",[]);
			}
		}
	}

	public static function syncSetting(Request $req, Response $res){
		$m = VendorType::get();
		$sS = DB::select("select * from eo_service_setting");
		$inviteSetting = CreditSetting::get();
		$bank = Bank::get();
		$settings = Settings::first();
		$setTopup = TopupSetting::first();
		$setLaundry = LaundrySetting::first();
		//get kota availabel
		$kota = DB::Select("select *,c.id as id from eo_city c
		inner join eo_kota k on k.city_id = c.kota_id
		inner join eo_provinsi p on p.province_id = c.prov_id");

		if( $m->count() > 0 ){


			return Res::cb($res,true,"Berhasil",
				[
					"tarif" => $m,"service_setting"=>$sS[0],
					"invite_frend_setting"=>$inviteSetting[0],
					"laundry_setting" => $setLaundry,
					"other_setting" =>
									[
										"running_text_user" => $settings->running_text_user,
										"is_verification_number" => $settings->is_verification_number,
										"is_verification_email" => $settings->is_verification_email,
										"is_ads_active" => $settings->is_ads_active,
										"latitude" => $settings["latitude"],
										"longitude" => $settings["longitude"],
										"tipe_komunikasi" => $settings["media_komunikasi_order"],
										"is_belanja_ekspedisi" => $settings["is_belanja_ekspedisi"],
										"is_belanja_motor" => $settings["is_belanja_motor"],
										"is_belanja_by_kota" => $settings["is_belanja_by_kota"],
										"kg_max_kurir_motor" => $m[1]->kg_max_kurir_motor,
										"kg_max_kurir_pickup" => $m[3]->kg_max_kurir_pickup,
										"satuan_max_kurir_motor" => $m[1]->satuan_max_kurir_motor,
										"satuan_max_kurir_pickup" => $m[3]->satuan_max_kurir_pickup,
										"max_topup" => $setTopup->max_topup,
										"min_topup" => $setTopup->min_topup,
										"is_topup_active" => $setTopup->is_active,
										"mode_login" => $settings->login_mode_user,
										"no_cs_wa_partner" => $settings->no_cs_wa_partner,
										"no_cs_wa_tokokota" => $settings->no_cs_wa_tokokota,
										"no_cs_wa_global" => $settings->no_cs_wa_global,
										"wa_msg_tokokota" => $settings->wa_msg_tokokota,
										"wa_msg_partner" => $settings->wa_msg_partner,
										"wa_msg_global" => $settings->wa_msg_global,
										"btn_promo_name" => $settings->btn_promo_name,
										"label_wa_partner" => $settings->label_wa_partner,
										"label_wa_tokokota" => $settings->label_wa_tokokota,

									],
					"modul" => DB::Select("select * from eo_modul where is_available = 'yes' and visible = 'yes' "),
					"bank" => $bank,
					"kota" => $kota


				]);
		}else{
			return Res::cb($res,false,"Data Tidak ditemukan",[]);
		}

	}

	public static function updateLastPosition(Request $req, Response $res){
		$post = $req->getParsedBody();
		$m = InputUser::where("id",$post["user_id"])->first();
		$m->last_latitude = $post["latitude"];
		$m->last_longitude = $post["longitude"];
		if( $m->save() ){
			return Res::cb($res,true,"Updated",['pos'=>[]]);

		}
	}

}
