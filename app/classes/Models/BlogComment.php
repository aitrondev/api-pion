<?php

namespace Models;

class BlogComment extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'bx_blog_comment';

	public function Blog(){
		return $this->belongsTo('Models\Blog');
	}
}