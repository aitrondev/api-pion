<?php

namespace Models;

class Blog extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_blog';

	public function Comments(){
		return $this->hasOne('Models\BlogComment');
	}
}