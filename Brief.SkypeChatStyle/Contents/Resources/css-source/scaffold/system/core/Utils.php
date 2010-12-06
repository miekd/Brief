<?php

/**
 * Utils
 *
 * Holds various utility functions used by CSScaffold
 * 
 * @author Anthony Short
 */
abstract class Utils
{
	/**
	 * Fixes a path (including Windows paths), finds the full path,
	 * and adds a trailing slash. This way we always know what our paths
	 * will look like.
	 */
	public static function fix_path($path)
	{
		return str_replace('\\', '/', realpath($path)). '/';
	}
	
	/**
	 * Checks if a file is an image.
	 *
	 * @author Anthony Short
	 * @param $path string
	 */
	public static function is_image($path)
	{
		if (array_search(self::extension($path), array('gif', 'jpg', 'jpeg', 'png')))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Checks if a file is css.
	 *
	 * @author Anthony Short
	 * @param $path string
	 */	
	public static function is_css($path)
	{
		return (self::extension($path) == 'css') ? true : false;
	}
	
	/**
	 * Prints out the value and exits. Used for debugging.
	 *
	 * @author Anthony Short
	 * @param $var
	 */
	public static function stop($var) 
	{
		header('Content-Type: text/plain');
		print_r($var);
		exit;
	}
	
	/**
	 * Quick regex matching
	 *
	 * @author Anthony Short
	 * @param $regex
	 * @param $subject
	 * @param $i
	 * @return array
	 */
	public static function match($regex, $subject, $i = "")
	{
		if(preg_match_all($regex, $subject, $match))
		{
			return ($i == "") ? $match : $match[$i];
		}
		else
		{
			return array();
		}
	}
	
	/** 
	 * Removes all quotes from a string
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function remove_all_quotes($str)
	{
		return str_replace(array('"', "'"), '', $str);
	}
	
	/** 
	 * Removes quotes surrounding a string
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function unquote($str)
	{
		return preg_replace('#^("|\')|("|\')$#', '', $str);
	}
	
	/** 
	 * Wraps a string with quotes.
	 */
	public static function quote($str)
	{
		return '"' . $str . '"';
	}
		
	 /**
	  * Outputs a filesize in a human readable format
	  *
	  * @author Anthony Short
	  * @param $val The filesize in bytes
	  * @param $round
	 */
	public static function readable_size($val, $round = 0)
	{
		$unit = array('','K','M','G','T','P','E','Z','Y');
		
		while($val >= 1000)
		{
			$val /= 1024;
			array_shift($unit);
		}
		
		return round($val, $round) . array_shift($unit) . 'B';
	}
	
	/**
	 * Takes a relative path, gets the full server path, removes
	 * the www root path, leaving only the url path to the file/folder
	 *
	 * @author Anthony Short
	 * @param $relative_path
	 */
	public static function urlpath($relative_path) 
	{
		return  self::reduce_double_slashes(str_replace( $_SERVER['DOCUMENT_ROOT'], '/', realpath($relative_path) ));
	}
	
	/** 
	 * Makes sure the string ends with a /
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function right_slash($str)
	{
		return rtrim($str, '/') . '/';
	}
	
	/** 
	 * Makes sure the string starts with a /
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function left_slash($str)
	{
		return '/' . ltrim($str, '/');
	}
	
	/** 
	 * Makes sure the string doesn't end with a /
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function trim_slashes($str)
	{
		return trim($str, '/');
	}
	
	/** 
	 * Replaces double slashes in urls with singles
	 *
	 * @author Anthony Short
	 * @param $str string
	 */
	public static function reduce_double_slashes($str)
	{
		return preg_replace("#//+#", "/", $str);
	}
	
	/**
	 * Joins any number of paths together
	 *
	 * @param $path
	 */
	public static function join_path()
	{
		$num_args = func_num_args();
		$args = func_get_args();
		$path = $args[0];
		
		if( $num_args > 1 )
		{
			for ($i = 1; $i < $num_args; $i++)
			{
				$path .= DIRECTORY_SEPARATOR.$args[$i];
			}
		}
		
		return self::reduce_double_slashes($path);
	}
	
	/**
	 * Returns the extension of the file
	 *	
	 * @param $path
	 */
	public static function extension($path) 
	{
	  $qpos = strpos($path, "?");
	
	  if ($qpos!==false) $path = substr($path, 0, $qpos);
	
	  return pathinfo($path, PATHINFO_EXTENSION);;
	} 

}