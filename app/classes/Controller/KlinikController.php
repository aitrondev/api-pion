<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\Klinik;
	use Tools\Res;

	class KlinikController
	{
		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getAll(Request $req, Response $res){
			$post = $req->getParsedBody();
			try{
				$faskes  = DB::table('bx_klinik')->where("faskes_id",$post['faskes_id'])->get();
				return Res::cb($res,true,SUCCESS,"Berhasil",['klinik' => $faskes]); 
			}catch(Exception $e){
				return Res::cb($res,false,NO_DATA,"Gagal, data tidak ditemukan"); 
			}
			
		}
	}