<?php
namespace Controller;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;


use Models\MootaForgotpassHistory;

use Tools\Res;
use Tools\Encrypt;
use Tools\PushNotif;
use Tools\Helper;
use Tools\MootaScrap;
use Tools\MootaURL;


/**
 *
 */
class BridgeController{
  protected $ci;
  public function __construct(ContainerInterface $ci) {
    $this->ci = $ci;
  }

  public static function getLoginStatus(Request $req, Response $res){
    $today = date('Y-m-d');
    $f=DB::Select("	select is_login from  moota_forgotpass_history
                    where date_format(created_at,'%Y-%m-%d') = '".$today."' ");
    if( $f[0]->is_login == "waiting" ){
        #update to progress
         DB::statement("update moota_forgotpass_history set is_login = 'progress' where date_format(created_at,'%Y-%m-%d') = '".$today."' ");
    }
    return Res::cb($res,true,"Berhasil",[ 'status' => $f[0]->is_login ]);
  }

  public static function updateLoginStatus(Request $req, Response $res){
    $today = date('Y-m-d');
    $u = DB::statement("update moota_forgotpass_history set is_login = 'waiting' where date_format(created_at,'%Y-%m-%d') = '".$today."' ");
    if($u)
      return Res::cb($res,true,"Berhasil",[ 'status' => $f[0]->status ]);
    else return Res::cb($res,true,"Berhasil",[ 'status' => $f[0]->status ]);
  }

}
