<?php

	namespace Controller;

	use Illuminate\Database\Capsule\Manager as DB;
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	use Tools\Res;
	use Tools\PushNotif;

	use Models\Lpk;
	use Models\PUser;
	use Models\PUserRole;
	use Models\ProgramPendidikan;
	use Models\Peserta;
	class LpkController
	{

		protected $ci;
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

  	public static function detail(Request $req, Response $res){
      $id = $req->getAttribute('id');
      $m = DB::select("select * from h_lpk l
                      inner join h_kota k on k.city_id = l.kota_id
                      inner join h_provinsi p on p.province_id = l.provinsi_id
                      where l.id = " . $id);

        return Res::cb($res,true,"Berhasil",['lpk'=>$m[0]]);
    }

    public static function list(Request $req, Response $res){
      $m = DB::select("select l.*,k.city_name,p.province_name from h_lpk l
                      inner join h_kota k on k.city_id = l.kota_id
                      inner join h_provinsi p on p.province_id = l.provinsi_id
                      where l.is_approve =  'Approved' ");

        return Res::cb($res,true,"Berhasil",['lpk'=>$m]);
    }

     public static function approvePeserta(Request $req, Response $res){
        $id = $req->getAttribute("id");
        $m = Peserta::where("id",$id)->first();
        $m->status = "Peserta";
        if($m->save()){
        	$pushData = [
				"action"		=> "accept_peserta",
				"intent"		=> "move"

			];
			$puser = PUser::where("id",$m->p_user_id)->first();
			$push = PushNotif::pushTo("Anda telah menjadi Peserta Pelatihan","Klik untuk detail",$puser->firebase_token,$pushData);
			return Res::cb($res,true,"Berhasil",['lpk'=>$m]);
        }

    }

		public static function getProgramPendidikan(Request $req, Response $res){
			$id = $req->getAttribute("id");
      $m = DB::select("select * from h_program_pendidikan where lpk_id = " . $id);

        return Res::cb($res,true,"Berhasil",['programs'=>$m]);
    }

		public static function updateProgramPendidikan(Request $req, Response $res){
			$id = $req->getAttribute("id");
			$p = $req->getParsedBody();
      $p = json_decode($p['body'],true);
			foreach( $p as $v ){
				if( !isset($v['id']) ){
					$m = new ProgramPendidikan;
					$m->kejuruan = $v['kejuruan'];
					$m->max_kapasitas = $v['kapasitas'];
					$m->lama_pelatihan = $v['lamaPelatihan'];
					$m->nama = $v['namaProgram'];
					$m->lpk_id = $id;
					$m->save();
				}
			}

				return Res::cb($res,true,"Berhasil");
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

      $m = new Lpk;
      $m->nama_lembaga = $p['namaLembaga'];
      $m->nama_pimpinan = $p['namaPempimpin'];
      $m->no_telp_pimpinan = $p['noHpPemimpin'];
      $m->email_pimpinan = $p['emailPemimpin'];
      $m->nama_pic_lembaga = $p['namaPICLembaga'];
      $m->no_telp_pic_lembaga = $p['noHpPICLembaga'];
      $m->jenis_lembaga = $p['jenisLembaga'];
      $m->kondisi = $p['kondisi'];
      $m->alamat_lembaga = $p['alamatLembaga'];
      $m->provinsi_id = $p['provinsiId'];
      $m->kota_id = $p['kotaId'];
      $m->no_telp_lembaga = $p['noTelpLembaga'];
      $m->email_lembaga = $p['emailLembaga'];
      $m->alamat_website = $p['alamatWebsite'];
      $m->no_perizinan = $p['noPerizinan'];
      $m->instansi_pemberi_izin =$p['instansiPemberiIzin'] ;
      $m->berlaku_tgl_izin =$p['tglBerlakuPerizinan'] ;
      $m->upload_file = "no-image";
      $m->status = $p['statusSK'];
      $m->no_sk = $p['noSK'];
      $m->luas_area = $p['luasArea'];
      $m->tgl_sk = $p['tglSK'];
      $m->berlaku_tgl_sk = $p['tglSKHabis'];
      $m->jum_tenaga_pelatih = $p['jumTenagaPelatih'];
      $m->jum_struktur = $p['jumInstruktur'];
      $m->is_approve = 'Pending';
      if( $m->save() ){
        $u = new PUser;
        $u->username = $p['username'];
        $u->password = password_hash($p['password'],PASSWORD_DEFAULT);
        $u->email = $p['emailLembaga'];
        if($u->save()){
          $m->p_user_id = $u->id;
          if($m->save()){
              $pu = new PUserRole;
              $pu->user_id = $u->id;
              $pu->role_id = 5;
              $pu->is_default_role = "yes";
              $m['role'] = "lpk";
              if($pu->save()){
                //insert programPendidikan
                foreach( $p['programPendidikans'] as $pr  ){
                  $pp = new ProgramPendidikan;
                  $pp->max_kapasitas = $pr['kapasitas'];
                  $pp->kejuruan = $pr['kejuruan'];
                  $pp->lama_pelatihan = $pr['lamaPelatihan'];
                  $pp->nama = $pr['namaProgram'];
                  $pp->lpk_id = $m->id;
                  $pp->save();
                }
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
