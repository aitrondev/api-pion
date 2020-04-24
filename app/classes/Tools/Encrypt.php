<?php

namespace Tools;

class Encrypt
{
	public static function encodePassword($password)
	{
        $salt = 'tXu^z?gm9m6Z8;Fq)x6[4!_<VSUgNgk^;~(5br5sJ&Ff/Z6"bV~w)P+9X-.KBJ!B#7ux';
        $enpassword = hash('sha512', $salt.$password.$password.$salt.$password);
        $fpassword = md5($enpassword);
		return $fpassword;
    }

    public static function generateToken(){
    	$str = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890!@#%^&*()-=_+[];,./{}|:<>?~";
        $random_text = substr(str_shuffle($str),0,80);
        $token = self::encodeToken($random_text);
        $_SESSION['token'] = $token;
        return $token;
    }

    public static function encodeToken($token){
    	$salt = 'smn?^nQ_<WkKY}.}5q9xrT~DY]Xzte{wvkgt.yB3*sC6^:';
        $entoken = hash('sha512', $token.$salt.$salt.$token.$salt.$token);
        $ftoken = md5($entoken);
        return $ftoken;
    }

    public static function generateActivationCode($phone){
        $code = sha1(mt_rand(10000,99999).time().$phone);
        $code = preg_replace( '/[^0-9]/', '', $code );
        $code = substr($code,0,6);
        return strtoupper($code);
    }
    
    public static function generateCreditCode($prefix,$phone){
        $code = sha1(mt_rand(10000,99999).time().$phone);
        return $prefix . strtoupper(substr($code,0,4));
    }

}