<!DOCTYPE html>
<html>
<head>
	<meta charset=utf-8 />
	<title>Grid | CSScaffold</title>
	
	<style type="text/css">

		<?php echo file_get_contents(CSScaffold::$cached_file); ?>

		.page-intro 
		{
			font-size:21px;
			line-height:28px;
		}
		
		.wireframe div
		{ 
			background:rgba(255,0,0,0.4); 
			min-height:<?php echo Layout::$baseline * 3; ?>px; 
			margin-bottom:<?php echo Layout::$baseline; ?>px; 
		}

	</style>

</head>
<body>

	<div class="container showgrid wireframe">

		<?php for ($i = 1; $i < Layout::$column_count; $i++) : ?>
		<div class="columns-<?php echo $i; ?>"></div>
		<div class="columns-<?php echo (Layout::$column_count - $i); ?> last"></div>
 		<?php endfor; ?>	
 	
	</div>

	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	<script>
		$(document).ready(function(){
			if (window.location.href.indexOf('grid')>0) 
			{
				$('.container').addClass('showgrid');
			}
		});
	</script>

</body>
</html>