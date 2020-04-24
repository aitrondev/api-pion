<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Pasar;


	class PasarController
	{

		protected $ci; 
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function getPasar(Request $request, Response $response){
			$pasar = Pasar::where("visible","yes")->get();
			return Res::cb($response,true,'Berhasil',["pasar" => $pasar]);
		}



	}
