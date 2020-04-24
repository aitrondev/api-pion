<?php

    namespace Controller;

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    use Illuminate\Database\Capsule\Manager as DB;

    use Models\Chat;
    use Models\User;
    use Models\InputUser;
    use Models\Admin;
    use Models\Foto;
	use Models\OrderChat;
	use Models\Vendor;

	use Tools\Res;
	use Tools\Encrypt;
	use Tools\Helper;
	use Tools\PushNotif;

    class ChatController extends AppController{
        protected $ci;
        public function __construct(ContainerInterface $ci) {
    		$this->ci = $ci;
    	}

		public static function orderChat(Request $request, Response $response){ //khusus barang (makanan dan belanja)
			$p = $request->getParsedBody();
			$m = new OrderChat;
			$m->content = $p['content'];
			$m->order_id = $p['order_id'];
			$m->type_user = "user";
			$m->type_order = $p['type_order'];


			if($m->save()){
				return Res::cb($response,true,"Berhasil",["chat" => $m]);
			}
		}

		public static function orderChatOjekKurir(Request $request, Response $response){
			 //khusus ojek dan kurir
			$p = $request->getParsedBody();
			$m = new OrderChat;
			$m->content = $p['content'];
			$m->order_id = $p['order_id'];
			$m->type_user = "user";
			$m->type_order = $p['type_order'];
			$m->user_id = $p["user_id"];


			//get vendor firebase
			$v  = Vendor::where("id",$p['vendor_id'])->first();

			//kirim ke vendor yang bersangkkutan
			$dataPush = [
						"action" => "new_message_order",
						"id" => $m->order_id,
						"content" => $p["content"],
						"type_user" => "lain",
						"intent" => "move" ,
						"user_id" => $p["user_id"],
						"type_order" => $p["type_order"] //ojek //barang //kurir

					];

			$push = PushNotif::pushTo(self::App()->app_name,"New Message",$v->firebase_token,$dataPush);
			if( $push["success"] == 1 ||  $push["success"] == "1" ){
				if($m->save()){
					return Res::cb($response,true,"Berhasil",["chat" => $m]);
				}
			}
		}

		public static function orderChatVendor(Request $request, Response $response){ //khusus vendor ke user
			$p = $request->getParsedBody();
			$m = new OrderChat;
			$m->content = $p['content'];
			$m->order_id = $p['order_id'];
			$m->type_user = "vendor";
			$m->type_order = $p['type_order'];
			$m->user_id = $p['vendor_id'];

			//get vendor firebase
			$u = User::where("id",$p['user_id'])->first();

			//kirim ke vendor yang bersangkkutan
			$dataPush = [
							"action" => "new_message_chat_order",
							"id" => $m->order_id,
							"content" => $p["content"],
							"type_user" => "vendor",
							"intent" => "move",
							"type_order" => $p["type_order"],
							"vendor_id" => $p["vendor_id"]
						];
			$push = PushNotif::pushTo(self::App()->app_name,"New Message",$u->firebase_token,$dataPush);
			if( $push["success"] == 1 ||  $push["success"] == "1" ){
				if($m->save()){
					return Res::cb($response,true,"Berhasil",["chat" => $m]);
				}
			}else{
				return Res::cb($response,false,"Gagal");
			}
		}

		public static function setStatus(Request $req, Response $res){
			$userId  = $req->getAttribute("user_id");
			$statusId =  $req->getAttribute("status");

			$m = InputUser::where("id",$userId)->first();
			$m->is_socket_active = $statusId;

			if( $m->save() ){
				return Res::cb($res,true,"Berhasil",['set'=>"" ]);
			}
		}

		public static function setStatusVendor(Request $req, Response $res){
			$vendorId  = $req->getAttribute("vendor_id");
			$statusId =  $req->getAttribute("status");

			$m = Vendor::where("id",$vendorId)->first();
			$m->is_socket_active = $statusId;

			if( $m->save() ){
				return Res::cb($res,true,"Berhasil",['set'=>"" ]);
			}
		}

		public static function saveSocket(Request $req, Response $res){ //jika ada chat dari user
			$body = file_get_contents("php://input");
			$p = json_decode($body);
			$m = new Chat;
			$m->content = $p->content;
			$m->user_id = $p->user_id;
			$m->type_user = $p->type_user;
			if($m->save()){
				return Res::cb($res,true,"Berhasil",['chat'=>$m]);
			}
		}

		public static function checkSocket(Request $req, Response $res){
			$userId  = $req->getAttribute("user_id");
			$m = DB::Select("select is_socket_active from eo_user where id = " . $userId);
			if(!$m){
				$m = DB::Select("select is_socket_active from eo_vendor where id = " . $userId);
			}
			Return Res::cb($res,true,"Berhasil",['user'=>$m[0] ]);
		}

		public  static function http_query_decode($url){
			$data = [];
			$urls = explode('&',$url);
			foreach ($urls as $u) {
				$key = explode('=',$u)[0];
				$val = explode('=',$u)[1];
				$data[$val] =  $key;
			}

			return $data;
		}


    	public static function save(Request $req, Response $res){
    	    $post = $req->getParsedBody();
			if( is_null($post) || count($post) <= 0 ){
				$body = file_get_contents("php://input");
				if(!empty($body)){
					echo $body;
					$post = self::http_query_decode($body);
				}
			}

    	    $m = new Chat;
    	    $m->content = $post['content'];
    	    $m->type_user = $post["type_user"];
    	    if( isset($post["user_id"]) )
    	        $m->user_id = $post["user_id"];
    	    else{
    	        $m->replay_to = $post["replay_to_id"];
    	    }


    	    if($m->save()){
    	        if( !isset($post["user_id"]) ){
    	            //send notification

					$user = User::where("id",$m->replay_to)->first();
					if(!$user || is_null($user)){
						$user = Vendor::where("id",$m->replay_to)->first();
						$foto = $user->path_thumbnail;
					}else{
						$f = Foto::where("from_id",$user->id)->where("from_table","eo_user")->first();
						$foto = $f->path_thumbnail;
					}
					$dataPush = [
							"action" => "new_message_admin",
							"id" => $m->id,
							"content" => $post["content"],
							"type_user" => "admin",
							"intent" => "move"
						];
					$push = PushNotif::pushTo(self::App()->app_name,"New Message",$user->firebase_token,$dataPush);
					if( $push["success"] == 1 ||  $push["success"] == "1" ){
        	            return Res::cb($res,true,"Berhasil",['chat'=>$m]);
        	        }
    	        }else{
    	        	$admin = Admin::where("p_user_id",2)->first();
					$user = User::where("id",$post["user_id"])->first();
					if(!$user || is_null($user)){
						$user = Vendor::where("id",$post["user_id"])->first();
						$foto = $user->path_thumbnail;
					}else{
						$f = Foto::where("from_id",$user->id)->where("from_table","eo_user")->first();
						$foto = $f->path_thumbnail;
					}


					$dataPush = [
							"action" => "new_message_admin",
							"user_id" => $user->id,
							"content" => $post["content"],
							"type_user" => 'user',
							"user_name" => $user->nama,
							"path_thumbnail" => is_null($foto) ? "ada": $foto,
							"time" => date("H:i"),
							"content_preview" => strlen($post["content"]) > 20 ? substr($post["content"],"20") . "..." : $post["content"],
						];


					$push = PushNotif::pushTo(self::App()->app_name,"New Message",$admin->firebase_token,$dataPush);
					if( $push["success"] == 1 ||  $push["success"] == "1" ){
        	            $u = InputUser::where("id",$post["user_id"])->first();
    	            	$u->is_new_chat = "yes";
	    	            if( $u->save() )
	    	                return Res::cb($res,true,"Berhasil",['chat'=>$m]);
	        	        }
    	        }

    	    }
    	}

    	public static function getChatById(Request $req, Response $res){
    	    $id = $req->getAttribute("id");
    	    $s = "select *,type_user as type
    	            from eo_chats where (user_id = '".$id."' or replay_to = '".$id."' )";
    	    $q = DB::Select($s);

    	    //update to not new chat
    	    $u = InputUser::where("id",$id)->first();
			if( !$u || is_null($u) ){
				 $u = Vendor::where("id",$id)->first();
			}
    	    $u->is_new_chat = "no";
    	    if($u->save()){
    	        return Res::cb($res,true,"Berhasil",['chats'=>$q]);
    	    }
    	}

    	public static function getOtherChatNotInId(Request $req, Response $res){
    	    $id = $req->getAttribute("id");
    	    if( $id != 0 ) $wh = "  where user_id != ".$id."
    	                            and replay_to is null
    	                            and date_format(created_at,'%Y-%m-%d') = curdate()";
    	    else $wh = " where date_format(created_at,'%Y-%m-%d') = curdate() ";
    	    $s = "  select *
    	            from eo_chats " . $wh;

    	    $q = DB::Select($s);

    	    return Res::cb($res,true,"Berhasil",['chats'=>$q]);
    	}

    	public static function rowCount(Request $req, Response $res){
    	    $id = $req->getAttribute("id");
    	    $s = "select count(*) as c from chats where member_id = '".$id."'";
    	    $q = DB::Select($s);

    	    return Res::cb($res,true,"Berhasil",['row'=>$q[0]->c]);
    	}

    	public static function getContacts(Request $req, Response $res){
    	    $s =
	    	    "select contact.* from
	    	    (

	        	    select v.is_new_chat,a.*,
	        	    date_format(a.created_at,'%H:%i') as jam,
	        	    concat(substr(a.content,1,10),' ','...') as preview,
	        	    v.path_thumbnail,v.nama
	        	    from (
	                    select * from eo_chats order by id desc
	                ) as a
	                inner join v_user v on v.id = a.user_id
	                where a.user_id is not null and a.type_user = 'user'
	                group by a.user_id
	                order by a.id desc
	            ) as contact
	            order by contact.is_new_chat desc
	            ";

            $q = DB::Select($s);

            return Res::cb($res,true,"Berhasil",["contacts" => $q]);
    	}

    	public static function getContactsVendor(Request $req, Response $res){
    	    $s =
	    	    "select contact.* from
	    	    (

	        	    select v.is_new_chat,a.*,
	        	    date_format(a.created_at,'%H:%i') as jam,
	        	    concat(substr(a.content,1,10),' ','...') as preview,
	        	    v.path_thumbnail,v.nama
	        	    from (
	                    select * from eo_chats order by id desc
	                ) as a
	                inner join v_vendor v on v.id = a.user_id
	                where a.user_id is not null and a.type_user = 'vendor'
	                group by a.user_id
	                order by a.id desc
	            ) as contact
	            order by contact.is_new_chat desc
	            ";

            $q = DB::Select($s);

            return Res::cb($res,true,"Berhasil",["contacts" => $q]);
    	}

    	public static function isFreshChat(Request $req, Response $res){
    	    $post = $req->getParsedBody();

    	    //cek apakah chat ini chat fresh
    	    $s = "select a.*,
        	    date_format(a.created_at,'%H:%i') as jam,
        	    concat(substr(a.content,1,30),' ','...') as preview,
        	    v.path_thumbnail,v.nama
        	    from (
                    select * from eo_chats order by id desc
                ) as a
                inner join v_user v on v.id = a.user_id
                where a.user_id is not null and user_id = ".$post["user_id"]."
                group by a.user_id";
            $cek  = DB::Select($s);
            if( count($cek) <= 0 ){ //jika fresh chat
                return Res::cb($res,true,"NEW_FRESH_SORT",["chat" => $cek]);
            }else{
                return Res::cb($res,false,"",["chat" => []]);
            }
    	}

    	public static function getContact(Request $req, Response $res){
    	    $id = $req->getAttribute("id");
    	    $s = "select a.*,
    	    date_format(a.created_at,'%H:%i') as jam,
    	    concat(substr(a.content,1,30),' ','...') as preview,
    	    v.path_thumbnail,v.nama
    	    from (
                select * from eo_chats order by id desc
            ) as a
            inner join v_user v on v.id = a.user_id
            where a.user_id = ".$id."
            group by a.user_id";

            $q = DB::Select($s);
            foreach ($q as $v) {
                $v->path_thumbnail = Helper::convertUrl($v->path_thumbnail);
            }
            return Res::cb($res,true,"Berhasil",["contact" => $q]);
    	}

		public static function getAllUser(Request $req, Response $res){
			$m = DB::Select("select *,u.id as user_id from eo_user u inner join eo_foto f on f.from_table='eo_user' and f.from_id = u.id ");
			return Res::cb($res,true,"Berhasil",["users" => $m]);
		}

		public static function getAllVendor(Request $req, Response $res){
			$m = DB::Select("select *,u.id as user_id from eo_vendor u ");
			return Res::cb($res,true,"Berhasil",["users" => $m]);
		}

		public static function searchUser(Request $req, Response $res){
			$key = $req->getAttribute("key");
			$m = DB::Select("select *,u.id as user_id from eo_user u 
						inner join eo_foto f on f.from_table='eo_user' and f.from_id = u.id
						where u.nama like '%".$key."%' ");
			return Res::cb($res,true,"Berhasil",["users" => $m]);
		}

		public static function searchVendor(Request $req, Response $res){
			$key = $req->getAttribute("key");
			$m = DB::Select("select *,u.id as user_id from eo_vendor u where u.nama like '%".$key."%' ");
			return Res::cb($res,true,"Berhasil",["users" => $m]);
		}

    }
?>
