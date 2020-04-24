<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;

	use Models\MootaGmailClient;

	class GmailController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}
		
		
		

	}