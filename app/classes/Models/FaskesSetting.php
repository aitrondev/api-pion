<?php

namespace Models;

class FaskesSetting extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_faskes_setting';

	public function User(){
		return $this->belongsTo('Models\Faskes');
	}
}