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


	class AddressController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}



		public static function getDistanceCoord(Request $req, Response $res){
			$p = $req->getParsedBody();

			$key = "AIzaSyCbm96Usj6rsZqd9CKk87KXwDTqz01GCpA";

			$originLat = $p['origin_lat'];
			$originLng = $p['origin_lng'];

			$destLat = $p['dest_lat'];
			$destLng = $p['dest_lng'];

			$oLng = $p['origin_lng'];
			// create curl resource
        $ch = curl_init();

        // set url
				$url  = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $originLat . "," . $originLng;
				$url .= "&destination=". $destLat . "," . $destLng;
				$url .= "&sensor=false&avoid=highways&mode=BICYCLING";
				$url .= "&key=". $key;

        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

				return Res::cb($res,true,'Berhasil',json_decode($output,true));

		}

		public function getKota(Request $request, Response $response){
			$provId = $request->getAttribute("province_id");

			$kota = DB::Select("select * from eo_kota where province_id = " . $provId);
			return Res::cb($response,true,'Berhasil',["kota" => $kota]);
		}

		public function getAll(Request $request, Response $response){
			$prov = DB::table('provinces')->where('id','35')->get();
			$regencies =  DB::table('regencies')->where('province_id','35')->get();
			$regencies_id = [];
			foreach ($regencies as $k => $v) {
				$regencies_id[] = $v->id;
			}

			$districts = DB::table('districts')->whereIn('regency_id',$regencies_id)->limit(100)->get();
			$districts_id = [];
			foreach ($districts as $k => $v) {
			 	$districts_id[] = $v->id;
			}

			$villages = DB::table('villages')->whereIn('district_id',$districts_id)->limit(100)->get();

			$data = [
				'provinces' => $prov,
				'regencies' => $regencies,
				'districts' => $districts,
				'villages'  => $villages
			];

			return Res::cb($response,true,SUCCESS,'Berhasil',$data);

		}



	}
