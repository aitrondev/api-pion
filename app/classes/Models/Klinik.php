<?php

namespace Models;

class Klinik extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_klinik';

    public function KlinikSetting(){
		return $this->hasOne('Models\KlinikSetting');
	}
}