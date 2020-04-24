<?php

	namespace Tools;
	/**
	* 
	*/
	class Auth 
	{
		public $apikey = 'okegan';
		public static function apikey($key)
		{
			return $this->apikey == $key;
		}
	}