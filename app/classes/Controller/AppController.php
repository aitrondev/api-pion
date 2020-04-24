<?php
	namespace Controller;
	
	use Models\AppProfile as app;
	
    class AppController{
		public function App(){
			return app::first();
		}
		 
	}

?>