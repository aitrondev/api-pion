<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Models\Lpk;
	use Models\PUser;
	use Models\PUserRole;
	use Models\ProgramPendidikan;
	use Models\Peserta;
	use Models\PesertaVsProgramPendidikan;

	use Tools\Helper;
	class PesertaController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

    public static function terbitkan(Request $req, Response $res){
      $id = $req->getAttribute('id');
      $m = Peserta::where("id",$id)->first();
      $m->is_terbitkan = "yes";
      if($m->save())
        return Res::cb($res,true,"Berhasil");
    }

  	public static function detail(Request $req, Response $res){
      $id = $req->getAttribute('id');
      $m = DB::select("select * from h_peserta where id = " . $id);
      $p = DB::select("select pp.nama from h_peserta_vs_program_pendidikan p
                        inner join  h_program_pendidikan pp on pp.id = p.program_id
                        where p.peserta_id = " . $id);
      $m[0]->program_pendidikan = $p[0]->nama;

        return Res::cb($res,true,"Berhasil",['peserta'=>$m[0]]);
    }

    public static function list(Request $req, Response $res){
      $id = $req->getAttribute('id');
      $m = DB::select("select * from h_peserta where lpk_id = " . $id);
      foreach( $m as $v ){
        if( $v->status == null ) $v->status = "Peserta";
        $p = DB::select("select pp.nama from h_peserta_vs_program_pendidikan p
                        inner join  h_program_pendidikan pp on pp.id = p.program_id
                        where p.peserta_id = " . $v->id );
        $v->program_pendidikan = $p[0]->nama;
      }

        return Res::cb($res,true,"Berhasil",['peserta'=>$m]);
    }

		public static function getProgramPendidikan(Request $req, Response $res){
			$id = $req->getAttribute("id");
      $m = DB::select("select * from h_program_pendidikan where lpk_id = " . $id);

        return Res::cb($res,true,"Berhasil",['programs'=>$m]);
    }

		public static function register(Request $req, Response $res){
      $p = $req->getParsedBody();
      $p = json_decode($p['body'],true);
      DB::beginTransaction();

      //check username
      $u = PUser::where("username",$p['username'])->first();
      if( isset($u['username']) ){
          return Res::cb($res,false,"Maaf username sudah di gunakan, gunakan username yang lain");
      }

      $m = new Peserta;
      $m->nik = $p['nik'];
      $m->nama = $p['nama'];
      $m->tempat_lahir = $p['tmptLahir'];
      $m->tgl_lahir = Helper::dateFormatSQL($p['tglLahir']);;
      $m->agama = $p['agama'];
      $m->nama_ayah = $p['namaAyah'];
      $m->nama_ibu = $p['namaIbu'];
      $m->alamat = $p['alamat'];
      $m->asal_sekolah = $p['asalSekolah'];
      $m->hobi = $p['hobi'];
      $m->gol_darah = $p['golDarah'];
      $m->no_telp = $p['noTelp'];
      $m->no_hp_wali = $p['noTelpWali'];
      $m->email =$p['email'] ;
      $m->lpk_id =$p['lpkId'] ;
      $m->status = $p['status'];
      if( $m->save() ){
        $u = new PUser;
        $u->username = $p['username'];
        $u->password = password_hash($p['password'],PASSWORD_DEFAULT);
        $u->email = $p['email'];
        if($u->save()){
          $m->p_user_id = $u->id;
          if($m->save()){
              $pu = new PUserRole;
              $pu->user_id = $u->id;
              $pu->role_id = 10;
              $pu->is_default_role = "yes";
              $m['role'] = "peserta";
              if($pu->save()){
                //insert programPendidikan
                $pp = new PesertaVsProgramPendidikan;
                $pp->peserta_id = $m->id;
                $pp->program_id = $p['programPendidikanId'];
                $pp->save();
                
                
                
                $pushData = [
    				"action"		=> "new_peserta",
    				"intent"		=> "move"
    			];
    			$puser = PUser::where("id",$p['lpkId'])->first();
    			if( !is_null($puser->firebase_token) )
    			    $push = PushNotif::pushTo("Ada Pendaftar Baru","Klik untuk detail",$puser->firebase_token,$pushData);
    			DB::commit();
                return Res::cb($res,true,"Registrasi Berhasil",['user'=>$m]);
              }else{
                DB::rollback();
                return Res::cb($res,false,"Pendaftaran gagal");
              }
          }else{
            DB::rollback();
            return Res::cb($res,false,"Pendaftaran gagal");
          }
        }else{
          DB::rollback();
          return Res::cb($res,false,"Pendaftaran gagal");
        }
      }else{
        DB::rollback();
        return Res::cb($res,false,"Pendaftaran gagal");
      }

		}

    public static function getKota(Request $req, Response $res){
				$m = DB::select("select * from h_kota");
				return Res::cb($res,true,"success",['kota'=>$m]);
		}

	}
