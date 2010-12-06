<?php

/**
 * Minify Plugin
 **/
class Minify extends Scaffold_Module
{
	public static function compress()
	{
		if (!class_exists('Minify_CSS_Compressor'))
		{
			include(dirname(__FILE__).'/libraries/Minify_Compressor.php');
			CSS::$css = Minify_CSS_Compressor::process(CSS::$css);
		}
	}
} 
