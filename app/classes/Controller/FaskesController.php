<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\Faskes;

	use Tools\Res;

	class FaskesController
	{
		protected $ci;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getAll(Request $req, Response $res){
			try{
				$faskes = DB::table('bx_faskes')->get();
				return Res::cb($res,true,"Berhasil",['faskes' => $faskes]); 
			}catch(Exception $e){
				return Res::cb($res,false,"Gagal, data tidak ditemukan",[]); 
			}
			
		}
	}