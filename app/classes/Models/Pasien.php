<?php

namespace Models;

class Pasien extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_pasien';

	public function User(){
		return $this->belongsTo('Models\User');
	}
}

//name=Maratus sholihah 2&provinces_id=1&regencies_id=1&districts_id=1&villages_id=1&other_address=merakurak&gender=P&identity_type=KTP&identity_number=3423534&status=Menikah&wali_name=Khudori&wali_phone=56789678&penjamin_name=Khudori 2&penjamin_phone=456789&born_at=Tuban&born_date=1990-02-06&bpjs_number=43567890&user_id=1&pasien_id=2