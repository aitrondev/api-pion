<?php
namespace Tools;

class PushNotif {

	public static function init() {
		//tokokta web api
		@define("API_ACCESS_KEY","AAAAX1Uja0o:APA91bH8EzyJ7A6ihcjKVyeLD2U18qzLgbEPpI6TnCnZxilUo1lUye0YDvQBnKuQto1ufTe0JfdBtt4s5n1jj4jDe3jIhCAvuTWSn7Lzv1ZUFnddtq537cZ-AMI8caGvsZ01opysdQaY");
		//tokokota project id
		@define("PROJECT_ID","409450277706");
		
	}

		public static function email($text,$kode,$email){
		//varifikasi menggunakan email
		$url="http://adminjek.java-jek.com/index.php?r=api/sendEmail&";
		$emailPost = [
			"subject" => $text,
			"body" => "Kode aktifasi JavaJek Anda adalah " . $kode . " ,  Silahkan masukkan kode tersebut pada aplikasi \n\n Terimakasih.",
			"to" => $email
		];
		$qParam = $url . http_build_query($emailPost);


        $c = curl_init();
		curl_setopt($c,CURLOPT_URL,$url . $qParam);
		curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($c,CURLOPT_HEADER, 0);
		curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec ($c);
		curl_close($c);

		return $result;

		//http://adminjek.java-jek.com/index.php?r=api/sendEmail&subject=testaufa&body=kode&to=aufamutawakkil@gmail.com

	}

	public static function sms($text,$kode,$notelp){
		$msg = urlencode($text.$kode." ");
		$userkey="8gde3p"; //8gde3p //2cipsl
		$passkey="okedehsip";
		$url="https://reguler.zenziva.net/apps/smsapi.php?userkey=".$userkey."&passkey=".$passkey."&nohp=".$notelp."&pesan=".$msg;


        $c = curl_init();
		curl_setopt($c, CURLOPT_URL,$url);
		curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec ($c);
		curl_close($c);

		$xml = simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
		$json = json_encode($xml);
		return json_decode($json,TRUE)['message'];


	}


	public static function requestDeviceGroup($grupName = "", $tokens = []) {
		self::init();
		$headers = array('Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json', 'project_id:' . PROJECT_ID);

		$fields = array('operation' => "create", 'notification_key_name' => $grupName, 'registration_ids' => $tokens);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/notification');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result, true);
	}


	public static function pushTo($title, $body, $token, $data = ["action"=>"null"], $state = 'background') {
		self::init();
		$msg = array('body' => $body, 'title' => $title, 'vibrate' => 1, 'sound' => 1, 'icon' => 'ic_launcher_n');

		if ($state == "foreground") {
			$fields = array('notification' => $msg, 'to' => $token, 'data' => $data);
		} else {
			$data["title"] = $title;
			$data["body"] = $body;
			$fields = array('to' => $token, 'data' => $data);
		}

		$headers = array('Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json');

		return self::send($headers, $fields);

	}

	public static function pushToWeb($title, $body, $token, $data = ["action"=>"null"], $state = 'background') {
		self::init();
		$msg = array('body' => $body, 'title' => $title, 'vibrate' => 1, 'sound' => 1, 'icon' => 'ic_launcher_n');

		if ($state == "foreground") {
			$fields = array('notification' => $msg, 'to' => $token, 'data' => $data);
		} else {
			$data["title"] = $title;
			$data["body"] = $body;
			$fields = array('to' => $token, 'data' => array("notification" =>$data) );
		}

//echo json_encode($fields);die();

		$headers = array('Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json');

		return self::send($headers, $fields);

	}



	public static function send($headers, $fields) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result, true);
	}

}
?>
