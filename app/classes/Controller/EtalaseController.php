<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\Provinces;
	use Models\Regencies;
	use Models\Districts;
	use Models\Villages;


	class EtalaseController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		} 

    public static function getAllByToko(Request $req, Response $res){
      $tokoId = $req->getAttribute("toko_id");
      $e = DB::Select("select * from eo_etalase where toko_id = ". $tokoId);
			return Res::cb($res,true,'Berhasil',["etalase" => $e]);
    }

  }

?>
