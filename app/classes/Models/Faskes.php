<?php

namespace Models;

class Faskes extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_faskes';

	public function FaskesSetting(){
		return $this->hasOne('Models\FaskesSetting');
	}

}