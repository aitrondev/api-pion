<?php

namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

use Models\Slider;
use Tools\Res;
class SliderController {
    	protected $ci;

	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

	public static function getAll( Request $request, Response $response ){
    
		$s = DB::select("select * from eo_slider where visible = 'yes' and id != 1 and tipe = 'User'  ");
        return Res::cb($response,true,"Berhasil Ambil Data",["slider"=>$s]);
    }

  public static function getBisnis( Request $request, Response $response ){
		    $s = DB::select("select * from eo_slider where visible = 'yes' and id != 1 and tipe = 'Bisnis'  ");
        return Res::cb($response,true,"Berhasil Ambil Data",["slider"=>$s]);
    }

	public static function getInWa( Request $request, Response $response ){
		$s = DB::select("select * from eo_slider where visible = 'yes' and id = 1 and tipe = 'User'  ");
		if( count($s) > 0 ) $s = $s[0];
		else $s = [];
        return Res::cb($response,true,"Berhasil Ambil Data",$s);
    }
}

?>
