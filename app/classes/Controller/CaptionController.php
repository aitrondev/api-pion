<?php

    namespace Controller;

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    use Illuminate\Database\Capsule\Manager as DB;
    use Models\Caption;
  	use Tools\Res;
  	use Tools\Encrypt;
  	use Tools\Helper;
  	use Tools\PushNotif;

    class CaptionController extends AppController{
      protected $ci;
      public function __construct(ContainerInterface $ci) {

  		}
      public static function sync(Request $req, Response $res){
        $userId = $req->getAttribute("user_id");
        $m = DB::select("select * from b_captions where user_id = " . $userId);
        return Res::cb($res,true,"Berhasil",["captions" => $m]);
      }



      public static function delete(Request $req, Response $res){
        $id = $req->getAttribute("id");
        $m = Caption::where("id",$id)->delete();
        if($m){
          return Res::cb($res,true,"Berhasil",["captions" => $m]);
        }else return Res::cb($res,false,"Gagal hapus, kesalahan koneksi");
      }

      public static function update(Request $req, Response $res){
        $p = $req->getParsedBody();
        $id = $req->getAttribute("id");
        $m = Caption::where("id",$id)->first();
        if($m){
          $m->title   = $p['title'];
          $m->caption = $p['caption'];
          if($m->save())
            return Res::cb($res,true,"Berhasil");
        }else return Res::cb($res,false,"Gagal hapus, kesalahan koneksi");
      }

      public static function save(Request $req, Response $res){
        $p = $req->getParsedBody();
        $m = new Caption;
        $m->caption = $p['caption'];
        $m->user_id = $p['user_id'];
        $m->title = $p['title'];
        if($m->save()){
          return Res::cb($res,true,"Berhasil",$m);
        }
      }

    }

?>
