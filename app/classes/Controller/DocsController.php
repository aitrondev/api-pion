<?php

namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class DocsController {

	protected $ci;
	
	public function __construct(ContainerInterface $ci) {
		$this->ci = $ci;
	}

	public static function show( Request $request, Response $response){
		ContainerInterface::view()->render($response, 'docs.html', [
	        'name' => $args['name']
	    ]);
	}
}