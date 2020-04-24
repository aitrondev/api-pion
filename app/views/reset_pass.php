<html>
	<head>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	</head>
	
	<body>
		<?php echo $_GET['a'];die();if( $_GET['a'] == "r" ){ ?>
			<div class="container">
            <div id="passwordreset" style="margin-top:50px" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="panel-title">Create New Password</div>
                    </div>                     
                    <div class="panel-body">
                        <form id="signupform" class="form-horizontal" role="form">
                            <div class="form-group">
                                <label for="email" class=" control-label col-sm-3">Registered email</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="email" placeholder="Please input your email used to register with us">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email" class=" control-label col-sm-3">New password</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="password" placeholder="create your new password">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email" class=" control-label col-sm-3">Confirm password</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="confirm your new password">
                                </div>
                            </div>
                            <div class="form-group">
                                <!-- Button -->                                 
                                <div class="  col-sm-offset-3 col-sm-9">
                                    <button id="btn-signup" type="button" class="btn btn-success">Submit</button>
                                </div>
                            </div>                             
                        </form>
                    </div>
                </div>
            </div>             
        </div>
		<?php }elseif($_GET["a"] == "s"){ ?>
			<div class="alert alert-success">
				<b>Password Berhasil direset!</b> <br/>
				Silakan Login menggunakan akun baru di aplikasi mobile <b>Kingpulsa</b> 
			</div>
		<?php } ?>
		
	</body>
</html>