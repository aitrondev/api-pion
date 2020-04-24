<!DOCTYPE html>
<html>
<head>
<title><?php echo $title; ?></title>
<link rel="stylesheet" type="text/css" href="css/materialize.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>

<div id="daftarisi">
	<ul class="table-of-contents">
	<?php
	foreach ($routes as $route) {
		$name = $route->getName();
		$identifier = $route->getIdentifier();
		if ($name!='/' && $name!='API Docs') {
			?>
			<li><a href="#<?php echo $identifier; ?>"><?php echo $name; ?></a></li>
			<?php
		}
	}
	?>
	</ul>
</div>

<div class="container apidocs">

<h4><?php echo $title; ?></h4>
<?php
foreach ($routes as $route) {

	$method = $route->getMethods();
	$name = $route->getName();
	$argument = $route->getArguments();
	$group = $route->getGroups();
	$identifier = $route->getIdentifier();

	if ($name!='/' && $name!='API Docs') {

		$params = 'None';
		if ( isset($argument['params']) && is_array($argument['params']) ) {
			$params = $argument['params'];
		}

		?>
		<div id="<?php echo $identifier; ?>" class="section scrollspy">
			<div class="card">
				<div class="card-content">
					<span class="card-title"><?php echo $name; ?></span>
					<div class="row">
						<div class="col s4 m4 l4"><b>End Point:</b></div>
						<div class="col s8 m8 l8"><?php echo 'http://'.$_SERVER['HTTP_HOST'].$argument['endpoint']; ?></div>
					</div>
					<div class="row">
						<div class="col s4 m4 l4"><b>Method:</b></div>
						<div class="col s8 m8 l8"><?php echo $method[0]; ?></div>
					</div>
					<div class="row">
						<div class="col s4 m4 l4"><b>Parameter:</b></div>
						<div class="col s8 m8 l8">
							<?php
							if (is_array($params)){
								?>
								<table class="striped">
								<?php
								foreach ($params as $key => $value) {
									?>
									<tr>
										<td style="width:100px;"><b><?php echo $key; ?></b></td>
										<td><?php echo $value; ?></td>
									</tr>
									<?php
								}
								?>
								</table><br><br>
								<?php
							} else {
								echo "None";
							}
							?>
						</div>
					</div>
					<?php if (isset($argument['output'])){ ?>
					<div class="row">
						<div class="col s4 m4 l4"><b>Output:</b></div>
						<div class="col s8 m8 l8"><?php echo $argument['output']; ?></div>
					</div>
					<?php } ?>
					
				</div>
			</div>
		</div>
		<?php
	}
}
?>
</div>

<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script type="text/javascript" src="js/materialize.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$('.scrollspy').scrollSpy();
});
</script>
</body>
</html>