<?php ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset=utf-8 />
	<title>CSScaffold Error</title>
	
	<style>
		html { background:#e7e7e7; }
		.content { width:70%; margin:30px auto; font:15px/18px Arial; padding:20px; background:#fff; color:#595959; border:1px solid #aaa; margin-bottom: 20px; }
		h1 { color:#000;}
		pre { background:#eee; padding:10px; font-size:11px; overflow:auto; }
		.backtrace { list-style:none; padding:0; margin:0; }
		tt { background:#59a0bb; color:#fff; display:block; margin:-10px -10px 10px -10px; padding:10px; font-weight:bold; font:13px/18px Arial; }
		p strong { color: #000; }

	</style>
</head>
<body>
	
	<div class="content">
		<h1><?php echo $error ?></h1>
		<p id="message"><?php echo $message; ?></p>
		
		<?php if($PHP_ERROR): ?>
		<pre><code>line <?php echo $line; ?><br/><?php echo $file ?></code></pre>
		<?php endif; ?>
	</div>
	
	<div class="content">
		<?php 
			if(isset($trace))
			{
				echo "<h2>Back Trace</h2>";
				echo $trace;
			}
		?>
	</div>
	
	<?php if(CSS::$css != ""): ?>
	<div class="content">
		<h2>CSS</h2>
		
		<pre><code><?php echo CSS::pretty(true); ?></code></pre>
	</div>
	<?php endif; ?>
</body>
</html>