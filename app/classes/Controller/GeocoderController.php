<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Goutte\Client;
	use GuzzleHttp\Client as GuzzleClient;
	use Tools\Res;

	class GeocoderController
	{
		protected $ci;
    public static $client;
    public static $guzzleClient;
    public $crawler;

		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}


    static function init(){
			GeocoderController::$client = new \Goutte\Client();
			GeocoderController::$guzzleClient = new \GuzzleHttp\Client(array(
            'timeout' => 60,
            'curl' => array( CURLOPT_SSL_VERIFYPEER => false)
        ));


    }

    static function  getAddress(Request $req, Response $res){
      //$r = self::reqq();

			$client = new \Goutte\Client();
			$guzzleClient = new \GuzzleHttp\Client(array(
					'timeout' => 60,
					'curl' => array( CURLOPT_SSL_VERIFYPEER => false)
			));
			//$url = "http://www.freegeocoder.com/";
			$url = "https://google-developers.appspot.com/maps/documentation/utils/geocoder/#q%3D-6.868256%252C111.994747";
			$client->setClient($guzzleClient);
      $crawler = $client->request('POST', $url);
			/*$crawler->filter('.result-formatted-address')->each(function($n){
				echo $n->text() . '<br/>';
			});*/
      //$r =  (object) ['client' => $client,'crawler'=> $crawler];
			//$filter = $crawler->filter("input[type='submit']");

			//echo "<pre>"; var_dump($filter);
			//die();
			//$form = $crawler->filter("#geocode-button")->form();
      //$form['q'] = '-6.860352, 111.992988';
      //$crawler = $client->submit($form,['q' => -6.860352, 111.992988 ]);
      echo $client->getResponse();
			//$crawler->filter('strong')->each(function($n){
			//	echo $n->text() . '<br/>';
			//});
    }


    static function reqq(){
				self::init();
        $url = "http://www.freegeocoder.com/";
        GeocoderController::$client->setClient(self::$guzzleClient);
        GeocoderController::$crawler = GeocoderController::$client->request('POST', $url);
        return (object) ['client' => GeocoderController::$client,'crawler'=> GeocoderController::$crawler];
    }

	}
