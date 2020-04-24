<?php

namespace Tools;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

class MootaAPI{
    private $BASE_URL = "https://app.moota.co/api/v1/";
    private $DETAIL_BANK = "bank/";


    public function __construct($res,$apikey,$action,$params,$method = 'GET'){
        $callback = null;
        if($action == "DETAIL_BANK"){
            $callback =  $this->reqq($apikey,$this->BASE_URL . $this->DETAIL_BANK . $params['bank_kode']);
        }
        $data = json_decode($callback);
        if(isset($data->error)){
            return Res::cb($res,false,"Bank not found");
        }

        return Res::cb($res,true,"Berhasil",$data);


    }

    public function reqq($apikey,$url,$fields = []) {
      $headers = [
         "Accept: application/json",
         "Authorization: Bearer " . $apikey
      ];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
      if($method == 'POST'){
			   curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
      }
      else curl_setopt($ch, CURLOPT_POST, false);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			$result = curl_exec($ch);
			curl_close($ch);

			return $result;
		}

}

?>
