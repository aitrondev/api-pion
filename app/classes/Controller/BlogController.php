<?php
	namespace Controller;

	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	use Illuminate\Database\Capsule\Manager as DB;


	use Models\Blog;
	use Models\BlogComment;
	use Tools\Res;

	class BlogController
	{
		protected $ci;
	
		public function __construct(ContainerInterface $ci) {
			$this->ci = $ci;
		}

		public static function save(Request $request, Response $response){
			$post = $request->getParsedBody();

			if( count($post) > 0 ){
				if( isset($post['blog_id']) ){
					$blog = Blog::find($post['blog_id']);
					$commentCount = $post['comment_count'];
				}else{
					$blog = new Blog;
					$commentCount = 0;
				}

				$blog->content= $post['content'];
                $blog->attach = $post['attach'];
                $blog->comment_count = $commentCount;
                $blog->faskes_id = $post['faskes_id'];
                if( isset($post['klinik_id']) ) $blog->klinik_id = $post['klinik_id'];
                $blog->user_id = $post['user_id'];
                $blog->blog_date = date('Y-m-d H:i:s');

                if($blog->save()){
                	return Res::cb($response,true,'Berhasil',[]);
                }else{
                	Res::cb($response,false,'Gagal',[]);
                }
			}
		}

		public static function getAll(Request $request, Response $response){
			$blog = DB::table("bx_blog")->get();
			if(count( $blog ) > 0){
				return Res::cb($response,true,'Berhasil',['blog' => $blog]);
			}else
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
		}

		public static function getById(Request $request, Response $response){
			$id = $request->getAttribute('id');
			$blog = Blog::where('id',$id)->get();

			if($blog->count()){
				return Res::cb($response,true,'Berhasil',['blog' => $blog]);
			}else
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
		}

		public static function delete(Request $request, Response $response){
			$id = $request->getAttribute('id');
			$blog = Blog::where('id',$id)->get();
			if($blog->count()){
				if($blog->delete()){
					return Res::cb($response,true,'Berhasil menghapus blog',[]);
				}else{
					return Res::cb($response,false,'Gagal menghapus blog',[]);
				}
			}else{
				return Res::cb($response,false,'Gagal menghapus blog',[]);
			}
		}

		public static function getComments(Request $request, Response $response){
			$id = $request->getAttribute('id');
			$blogComments = Blog::find($id)->Comments;

			if($blogComments->count()){
				return Res::cb($response,true,'Berhasil',['blog' => $blogComments]);
			}else
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
		}

		public static function saveComment(Request $request, Response $response){
			$id = $request->getAttribute('id');
			
			$post = $request->getParsedBody();
			if( isset($post['comment_id']) )
				$blogComment = new BlogComment;
			else
				$blogComment = BlogComment::find($post['comment_id']);
			$blogComment->user_id = $post['user_id'];
			$blogComment->content = $post['content'];
			$blogComment->blog_id = $id;

			if($blogComment->save()){
				return Res::cb($response,true,'Berhasil',['blog' => $blogComment]);
			}else
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
		}

		public static function deleteComment(Request $request, Response $response){
			$id = $request->getAttribute('id');
			$blog_id = $request->getAttribute('blog_id');
			
			$post = $request->getParsedBody();
			$blogComment = BlogComment::find($blog_id);


			if($blogComment->delete()){
				return Res::cb($response,true,'Berhasil',[]);
			}else
				return Res::cb($response,false,'Gagal, data tidak ditemukan',[]);
		}


	}