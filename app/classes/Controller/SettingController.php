<?php

namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use Models\Settings;

class SettingController {

	protected $ci;
	
	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

	public static function getAll( Request $request, Response $response ){

		$setting = new Settings;
    	
    	$result = $setting->get();

	    if ($result->count()) {
			$newResponse = $response->withJson($result);

		} else {
			$newResponse = $response->withJson( array( 'status' => false ) );
		}

	    return $newResponse;
	}

	public static function getbyAlias( Request $request, Response $response, $args ){

    	if (isset($args['alias'])){

			$setting = Settings::where('alias', $args['alias']);
	  	 	$result = $setting->get();

		    if ($result->count()) {
				$newResponse = $response->withJson($result);

			} else {
				$newResponse = $response->withJson( array( 'status' => false ) );
			}
		} else {
			$newResponse = $response->withJson( array( 'status' => false ) );
		}

	    return $newResponse;
	}
}