<?php

namespace Models;

class SettingTimeToko extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'eo_setting_time_toko';

	public function User(){
		return $this->belongsTo('Models\User');
	}
}