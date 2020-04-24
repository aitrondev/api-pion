<?php

    namespace Tools;
    class Helper
    {

        public static function uploadBase64Image($base64_string) {
            $uploadDir = "/home/u6653202/public_html/tuqu/admin/assets/tmp/";
            //$uploadDir = "/home/etripind/public_html/admintrip/assets/tmp/";
			      //echo $uploadDir;die();

            //$imgExt =  explode("/",explode(";",$base64_string)[0])[1];
            //$base64String = explode(";",$base64_string)[1];
            //$base64String = explode(",",$base64String)[1];
            //if($imgExt == "jpeg") $imgExt = "jpg";

            $ext = "jpg";
            //find png
            $findPng = strpos(strtolower($base64_string), "png");
            if($findPng){
                $ext = "png";
            }

            //find jpeg
            $findJpg = strpos(strtolower($base64_string), "jpeg");
            if($findJpg){
                $ext = "jpg";
            }

            $imgExt = $ext;
            $filename_path = md5(time().uniqid()).".".$imgExt;
            $decoded=base64_decode($base64_string);
            $upload = file_put_contents($uploadDir.$filename_path,$decoded);
            if($upload){
                return $uploadDir.$filename_path;
            }
        }

        public static function convertUrl($url){
            if( $url != "ada" ){
                $BASE_URL = "http://" . $_SERVER['SERVER_NAME']  . "/dev";
                //echo $BASE_URL;
                $url = explode("assets",$url);
                return $BASE_URL  . "/assets" ;
            }else return $url;
        }

      public static function getCoordcURL($url){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
         return $response;
        }

      }

        public static function isEmailValid($email){
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }

        public static function getNameDayInd($no){
            switch ($no) {
                case 1:
                    return "Senin";
                case 2:
                    return "Selasa";
                case 3:
                    return "Rabu";
                case 4:
                    return "Kamis";
                case 5:
                    return "Jumat";
                case 6:
                    return "Sabtu";
                case 7:
                    return "Ahad";

            }
        }

	}
