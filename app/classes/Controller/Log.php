<?php
	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Models\MLog;
	use Tools\Res;

	class Log
	{
		public function __construct($user_id,$logname){
			$log = new MLog;
			$log->user_id = $user_id;
			$log->log_name = $logname;
			$log->save();
		}
	}