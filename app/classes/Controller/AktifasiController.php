<?php
    namespace Controller;
    
	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	
	use Models\Aktifasi;
	use Models\User;
	use Models\Credit;
	
	use Tools\Res;
	use Tools\Encrypt;
	
    class AktifasiController {
       
        protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		public static function activate(Request $request, Response $response){
		    return Res::cb($response,true,'Berhasil',['aktifasi' => [] ]);	
		    $post = $request->getParsedBody();
		    if( isset($post['kode_aktifasi']) && isset($post['user_id']) ){
		    	
		    	//cek apakah kode aktivasi terdaftar atau tidak
		    	$findCode = DB::Select("select * from eo_aktifasi 
		    				where user_id = ".$post['user_id']."  order by id ");
							
				//var_dump($findCode[ count($findCode) -1 ]);die();
				if( $findCode[ count($findCode) -1 ]->kode_aktifasi != $post["kode_aktifasi"] ){
					return Res::cb($response,false,'Kode Tidak Valid',['aktifasi' => [ ] ]);
				}
		    	
		        //$activation = Aktifasi::where('kode_aktifasi',$post['kode_aktifasi'])->/*where('is_expire','no')->*/first();
		      
		        //if(count($activation) > 0){
		            //$activation = Aktifasi::find($activation->id);
    		        //$activation->is_active = 'yes';
    		        //if( $activation->save() ){
    		        	if( isset($post["nomor_baru"]) ){
    		        		$u =  DB::statement("update eo_user set no_telp='".$post["nomor_baru"]."' where no_telp = ".$post['nomor_lama']);
    		        		if($u){
    		        			$credit = Credit::where("user_id",$activation->user_id)->first();
    		        			 return Res::cb($response,true,'Berhasil',['aktifasi' => []  ]);
    		        		}else{
    		        			return Res::cb($response,false,'Kesalahan Server',['aktifasi' => $credit ]);
    		        		}
    		        	}else{
    		        		 return Res::cb($response,true,'Berhasil',['aktifasi' => [] ]);
    		        	}
    		        	
    		        //}else{
    		        //    return Res::cb($response,false,'Gagal Aktivasi',[]);
    		        //}
		       /*
				}else{
					return Res::cb($response,false,'Gagal Aktivasi, Kode Aktivasi Expired',[]);
				}*/
			   
		    }
		    
		}
		
		public static function reactivation(Request $request, Response $response){
		     $post = $request->getParsedBody();
		     
		     if(isset($post['phone'])  ){
		     	 $user = User::where("no_telp",$post['phone'])->first();
		         $activation_code = Encrypt::generateActivationCode($post['phone']);
		         $activation = new Aktifasi;
		         $activation->user_id = $user->id;
		         $activation->kode_aktifasi = $activation_code;
		         $activation->expire_date = date('Y-m-d H:i:s', strtotime( date('Y-m-d H:i:s') . "+3 minutes"));
		         
		         /*make all aktivation code expired*/
		         DB::statement("update eo_aktifasi set is_expire='yes' where user_id = ".$user->id);
		         if( $activation->save() ){ 
		             /*update user*/
		            $user->kode_aktifasi = $activation_code;
		            if( $user->update() ){
		                $msg = urlencode(self::App()->app_name . " Kode Aktivasi Anda ".$activation_code." ");
						$url="https://reguler.zenziva.net/apps/smsapi.php?userkey=631mrc&passkey=okedehsiplah&nohp=".$user->no_telp."&pesan=".$msg;

				        $c = curl_init();
						curl_setopt($c, CURLOPT_URL,$url);
						curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
						curl_setopt($c, CURLOPT_HEADER, 0);
						curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
						curl_exec ($c);
						
		                return Res::cb($response,true,'Berhasil Generate Kode Aktivasi',['aktifasi' => $activation]);
		             }else
		                 return Res::cb($response,false,'Gagal Update User');
		         }else{
		             return Res::cb($response,false,'Gagal Generate Kode Aktivasi');
		         }
		         
		     }
		     
		}
		
    }
