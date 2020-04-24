<?php

namespace Models;

class Aktifasi extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'eo_aktifasi';

	public function User(){
		return $this->belongsTo('Models\User');
	}
}