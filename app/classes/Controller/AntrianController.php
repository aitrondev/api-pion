<?php
	
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\Antrian;
	use Models\Faskes;
	use Models\FaskesSetting;

	use Tools\Res;



	class AntrianController
	{
		
		protected $ci;
	
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function save(Request $request, Response $response){
			
			$post = $request->getParsedBody();
			
			/* get setting */
			$faskes = Faskes::find($post['faskes_id'])->first();
			$faskesSetting = Faskes::find($post['faskes_id'])->FaskesSetting;

			$max_antrian 			= $faskesSetting->max_antrian;
			$now_antrian 			= $faskes->now_antrian;		//jumlah antrian sekarang
			$is_close 				= $faskes->is_close;
			$last_number_antrian 	= $faskes->last_number_antrian;
			
			if( $is_close == 'yes' ){		//jika antrian di tutup
				return $response->withJson(
					[
						'status'  => false,
						'message' => 'Maaf antrian sudah di tutup, silahkan mengantri besok'
					]
				);
			}else if( $now_antrian == $max_antrian  ){
				return $response->withJson(  //jika antrian habis
					[
						'status'  => false,
						'message' => 'Maaf antrian penuh, silahkan coba beberapa saat lagi'
					]
				);
			}else{	
				$antrian  = new Antrian;
				$antrian->type =   $post['type'];
                $antrian->antrian_date = date('Y-m-d H:i:s');
                $antrian->antrian_number = $last_number_antrian + 1;
                $antrian->faskes_id = $post['faskes_id'];
                if(isset($post['klinik_id'])) $antrian->klinik_id = $post['klinik_id'];   
                $antrian->user_id = $post['user_id'];

                if( $antrian->save() ){
                	/*update last_number_antrian*/
                	$faskes->last_number_antrian =  $antrian->antrian_number;

                	/*update antrian now*/
                	$faskes->now_antrian += 1;

                	if($faskes->save()){
                		return $response->withJson(
							[
								'status'  => false,
								'data' => ['antrian' => $antrian]
							]
						);
                	}
                }
			}
		}

		public static function getByUser(Request $request, Response $response){
			$id = $request->getAttribute('id');
			$status = $request->getAttribute('status');


			if( $status != "all" )
				$antrian = Antrian::where('user_id',$id)->where('is_finish',$status)->get();
			else
				$antrian = Antrian::where('user_id',$id)->get();

			if( $antrian->count() ){
				return Res::cb($response,true,'Berhasil',$antrian);
			}else{
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
			}
			

		}
	}