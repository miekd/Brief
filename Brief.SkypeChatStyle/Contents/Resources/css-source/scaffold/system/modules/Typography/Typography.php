<?php

/**
 * Type_suite
 *
 * Outputs a HTML page of every type element using the parsed CSS
 **/
class Typography extends Scaffold_Module
{
	public static function output()
	{
		if(CSScaffold::config('core.output') == "typography")
		{
			# Make sure we're sending HTML
			header('Content-Type: text/html');
			
			# Load the test suite markup
			$type = CSScaffold::load_view('TS_typography','Typography/views');
			
			# Echo and out!
			echo($type); 
			exit;
		}
	}
} 
