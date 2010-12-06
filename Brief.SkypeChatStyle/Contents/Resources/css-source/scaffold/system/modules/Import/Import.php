<?php

/**
 * Import
 *
 * This allows you to import files before processing for compiling
 * into a single file and later cached. This is done via @import ''
 *
 * @author Anthony Short
 * @dependencies None
 **/
class Import extends Scaffold_Module
{
	/**
	 * Stores which files have already been included
	 *
	 * @var array
	 */
	private static $loaded = array();
	
	/**
	 * This function occurs before everything else
	 *
	 * @author Anthony Short
	 * @param $css
	 */
	public static function parse()
	{
		# Find all the @server imports
		CSS::$css = self::server_import(CSS::$css);
	}
	
	/**
	 * Imports css via @import statements
	 * 
	 * @author Anthony Short
	 * @param $css
	 */
	public static function server_import($css)
	{
		# If they want to override the CSS syntax
		if(CSScaffold::config('core.override_import') === true)
		{
			$import = 'import';
		}
		else
		{
			$import = 'include';
		}
			
		if(preg_match_all('/\@'.$import.'\s+(?:\'|\")([^\'\"]+)(?:\'|\")\;/', $css, $matches))
		{
			$unique = array_unique($matches[1]);
			$include = str_replace("\\", "/", Utils::unquote($unique[0]));
			
			# If they haven't supplied an extension, we'll assume its a css file
			if(pathinfo($include, PATHINFO_EXTENSION) == "")
				$include .= '.css';
			
			# Make sure it's a CSS file
			if(!Utils::is_css($include))
				throw new Scaffold_Exception("Included file isn't a CSS file ($include)");

			# Find the file
			$include = CSScaffold::find_css_file($include);
			
			if(file_exists($include))
			{	
				# Make sure it hasn't already been included	
				if(!in_array($include, self::$loaded))
				{
					self::$loaded[] = $include;
					$css = str_replace($matches[0][0], file_get_contents($include), $css);
				}

				# It's already been included, we don't need to import it again
				else
				{
					$css = str_replace($matches[0][0], '', $css);
				}
				
				# Removes any commented out @imports
				CSS::remove_comments($css);

				# Check the file again for more imports
				$css = self::server_import($css);
			}
			else
			{
				throw new Scaffold_Exception("Included CSS file doesn't exist ($include)");
			}
		}
		return $css;
	}
}