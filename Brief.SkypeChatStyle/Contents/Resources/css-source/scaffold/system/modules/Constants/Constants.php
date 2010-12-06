<?php

/**
 * Constants
 *
 * Allows you to use constants within your css by defining them
 * within @constants and then using a property list.
 *
 * @author Anthony Short
 */
class Constants extends Scaffold_Module
{
	/**
	 * Stores all of the constants for the app
	 *
	 * @var array
	 */
	public static $constants = array();
	
	/**
	 * The pre-processing function occurs after the importing,
	 * but before any real processing. This is usually the stage
	 * where we set variables and the like, getting the css ready
	 * for processing.
	 *
	 * @author Anthony Short
	 * @param $css
	 */
	public static function parse()
	{				
		# Find the constants group and values
		$found = CSS::find_at_group('constants');
		
		# Set the global constants
		foreach(CSScaffold::config('core.constants') as $key => $value)
		{
			self::set($key, $value);
		}

		# If there are some constants, let do it.
		if($found !== false)
		{
			# Sort the constants by length
			uksort($found['values'], array('self','sortByLength'));
			
			# Create our template style constants
			foreach($found['values'] as $key => $value)
			{
				unset(self::$constants[$key]);
				self::set($key, $value);
			}
	
			# Remove the @constants groups
			CSS::replace($found['groups'], array());		
		}
	}
	
	/**
	 * Sorts array elements by length
	 *
	 * @param $param
	 * @return return type
	 */
	public static function sortByLength($a,$b)
	{
		if($a == $b) return 0;
		return (strlen($a) > strlen($b) ? -1 : 1);
	}
	
	/**
	 * Sets constants
	 *
	 * @author Anthony Short
	 * @param $key
	 * @param $value
	 * @return null
	 */
	public static function set($key, $value = "")
	{
		# So we can pass through a whole array
		# and set them all at once
		if(is_array($key))
		{
			foreach($key as $name => $val)
			{
				self::$constants[$name] = $val;
			}
		}
		else
		{
			self::$constants[$key] = $value;
		}	
	}
	
	/**
	 * Returns the constant value
	 *
	 * @author Anthony Short
	 * @param $key
	 * @return string
	 */
	public static function get($key)
	{
		return self::$constants[$key];
	}
		
	/**
	 * Replace constants
	 *
	 * @author Anthony Short
	 * @param $
	 * @return return type
	 */
	public static function replace()
	{
		if (!empty(self::$constants))
		{
			foreach(self::$constants as $key => $value)
			{
				if($value != "")
				{
					if(CSScaffold::config('core.use_css_constants') === true)
					{
						CSS::replace( "const({$key})", unquote($value));
					}
					else
					{
						CSS::replace( "!{$key}", Utils::unquote($value));
					}
				}
			}
			
			self::$constants = array();
		}
		else
		{
			if(preg_match_all('/![a-zA-Z0-9-_]+/', CSS::$css, $matches))
			{
				$missing = array_values(array_unique($matches[0]));
				
				# Remove !important
				unset($missing[array_search('!important', $missing)]);
				
				if(!empty($missing))
				{
					$missing = "<ul><li>" . implode("</li><li>", $missing) . "</li></ul>";
					throw new Scaffold_Exception("The following constants are used, but not defined: $missing");
				}
			}
		}
	}

}