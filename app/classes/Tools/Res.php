<?php
	namespace Tools;

	class Res
	{

		public static function cb($res,$status,$message,$data=[ "action" => "null" ]){
			return $res->withJson(
				[
					'status'  	=> $status,
					'message' 	=> $message,
					'data'		=> $data
				]
			)->withHeader('Access-Control-Allow-Origin', '*');
		}
	}
