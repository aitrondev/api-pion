<?php
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;


	use Models\Faq;

	use Tools\Res;

	class FaqController
	{
		public static function getAll(Request $request, Response $response){
		    $f = Faq::get();
		    return Res::cb($response,true,"Berhasil",["faq"=> $f]);
		}


	}
