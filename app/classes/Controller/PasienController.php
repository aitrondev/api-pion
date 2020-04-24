<?php
	/**
	* 
	*/
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\Pasien;

	use Tools\Res;

	class PasienController
	{
		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function save(Request $request, Response $response){
			$post = $request->getParsedBody();

			if( isset($post['pasien_id']) )
				$pasien = Pasien::find($post['pasien_id']);
			else
				$pasien = new Pasien;
			
            $pasien->name = $post['name'];
            $pasien->provinces_id= $post['provinces_id'];
            $pasien->regencies_id= $post['regencies_id'];
            $pasien->districts_id= $post['districts_id'];
            $pasien->villages_id= $post['villages_id'];
            $pasien->other_address= $post['other_address'];
            $pasien->gender= $post['gender'];
            $pasien->identity_type= $post['identity_type'];
            $pasien->identity_number= $post['identity_number'];
            $pasien->status= $post['status'];
            $pasien->wali_name= $post['wali_name'];
            $pasien->wali_phone= $post['wali_phone'];
            $pasien->penjamin_name= $post['penjamin_name'];
            $pasien->penjamin_phone= $post['penjamin_phone'];
            $pasien->born_at= $post['born_date'];
            $pasien->born_date= $post['born_date'];
            $pasien->bpjs_number= $post['bpjs_number'];
            $pasien->user_id= $post['user_id'];

            if($pasien->save()){
				return Res::cb($res,true,"Berhasil",['pasien' => $pasien]); 
            }else{
            	return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
            }

		}

	}

?>