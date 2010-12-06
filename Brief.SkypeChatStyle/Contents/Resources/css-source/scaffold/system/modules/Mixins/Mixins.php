<?php

/**
 * Mixins
 *
 * Allows you to use SASS-style mixins, essentially assigning classes
 * to selectors from within your css. You can also pass arguments through
 * to the mixin.
 * 
 * @author Anthony Short
 */
class Mixins extends Scaffold_Module
{
	/**
	 * Stores the mixins for debugging purposes
	 *
	 * @var array
	 */
	public static $mixins = array();

	/**
	 * The main processing function called by Scaffold. MUST return $css!
	 *
	 * @author Anthony Short
	 * @return $css string
	 */
	public static function parse()
	{
		global $bases;
	
		# This will store our nicely formated bases array
		# This lets us loop through all the of +mixins and just 
		# pull in the properties of that mixin and then parse it individually
		# based on the parameters
		$bases = array();
		
		# Finds any selectors starting with =mixin-name
		if( $found = CSS::find_selectors('\=(?P<name>[0-9a-zA-Z_-]*)(\((?P<args>.*?)\))?', 5) )
		{
			# Just to make life a little easier
			$full_base 		= $found[0];
			$base_names 	= $found['name'];
			$base_args 		= $found['args'];
			$base_props 	= $found['properties'];
			
			# Clean up memory
			unset($found);
			
			# Puts the mixin bases into a more suitable array
			foreach($base_names as $key => $value)
			{	
				$bases[$value]['properties'] = $base_props[$key];
				
				# If there are mixin arguments, add them
				$bases[$value]['params'] = ( $base_args[$key] != "" ) ? explode(',', $base_args[$key]) : array();
			}
						
			# Store this away for debugging
			self::$mixins = $bases;
			
			# Remove all of the mixin bases
			CSS::remove($full_base);
			
			# Clean up memory
			unset($full_base, $base_names, $base_args, $base_props);
			
			# Find the mixins
			if($mixins = self::find_mixins(CSS::$css))
			{
				# Loop through each of the found +mixins
				foreach($mixins[2] as $mixin_key => $mixin_name)
				{
					CSS::replace($mixins[0][$mixin_key], self::build_mixins($mixin_key, $mixins));
				}
				
				# Remove all of the +mixins (if they still exist)
				CSS::replace($mixins[0], '');
			}
			
			# Clean up
			unset($bases, $mixins);
		}
	}
	
	/**
	 * Replaces the mixins with their properties
	 *
	 * @author Anthony Short
	 * @param $mixin_key - The bases array key corrosponding to the current mixin
	 * @param $mixins - An array of found mixins
	 * @return string
	 */
	public static function build_mixins($mixin_key, $mixins, $current_mixin = "")
	{
		global $bases;
		
		$mixin_name = $mixins[2][$mixin_key];
				
		if(isset($bases[$mixin_name]))
		{	
			$base_properties = $bases[$mixin_name]['properties'];
							
			# If there is no base for that mixin and we aren't in a recursion loop
			if(is_array($bases[$mixin_name]) AND $current_mixin != $mixin_name)
			{
				$current_mixin = $mixin_name;
				
				# Parse the parameters of the mixin
				$params = self::parse_params($mixins[0][$mixin_key], $mixins[4][$mixin_key], $bases[$mixin_name]['params']);
				
				# Create the property string
				$new_properties = str_replace(array_keys($params),array_values($params),$base_properties);
				
				# Parse conditionals if there are any in there
				$new_properties = self::parse_conditionals($new_properties);
	
				# Find nested mixins
				if($inner_mixins = self::find_mixins($new_properties))
				{
					# Loop through all the ones we found, skipping on recursion by passing
					# through the current mixin we're working on
					foreach($inner_mixins[0] as $key => $value)
					{
						# Prase the mixin and replace it within the property string
						$new_properties = str_replace($value, self::build_mixins($key, $inner_mixins, $current_mixin), $new_properties);
					}
				}	
							
				# Clean up memory
				unset($inner_mixins, $params, $mixins);
	
				return $new_properties;
			}
		}
		else
		{
			throw new Scaffold_Exception("The mixin doesn't exist - $mixin_name");
		}
		
	}
	
	/**
	 * Finds +mixins
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return array
	 */
	public static function find_mixins($string)
	{	
		return Utils::match('/\+(([0-9a-zA-Z_-]*?)(\((.*?)\))?)\;/', $string);
	}
	
	/**
	 * Parses the parameters of the base
	 *
	 * @author Anthony Short
	 * @param $params
	 * @return array
	 */
	public static function parse_params($mixin_name, $params, $function_args = array())
	{
		$parsed = array();
		
		# Make sure any commas inside ()'s, such as rgba(255,255,255,0.5) are encoded before exploding
		# so that it doesn't break the rule.
		if(preg_match_all('/\([^)]*?,[^)]*?\)/',$params, $matches))
		{
			foreach($matches as $key => $value)
			{
				$original = $value;
				$new = str_replace(',','#COMMA#',$value);
				$params = str_replace($original,$new,$params);
			}
		}
		
		$mixin_params = explode(',', $params);
		
		# Loop through each function arg and create the parsed params array
		foreach($function_args as $key => $value)
		{
			$v = explode('=', $value);
								
			# If the user didn't include one of thesparams, we'll check to see if a default is available			
			if(empty($mixin_params[$key]))
			{			
				# If there is a default value for the param			
				if(strstr($value, '='))
				{
					$parsed[trim($v[0])] = Utils::unquote(trim($v[1]));
				}
				
				# Otherwise they've left one out
				else
				{
					throw new Scaffold_Exception("Mixins.missing_param", $mixin_name);
				}
			}
			else
			{
				$p = explode(",", $params);
				$value = Utils::unquote(trim($p[$key]));
				$parsed[trim($v[0])] = str_replace('#COMMA#',',',$value);
			}		
		}
		
		return $parsed;
	}		

	/**
	 * Import mixins
	 *
	 * @author Anthony Short
	 * @return string
	 */
	public static function import_mixins($dir)
	{		
		if($mixin_files = CSScaffold::list_files($dir, true))
		{
			foreach($mixin_files as $item)
			{
				if (!Utils::is_css($item)) { continue; }
				
				# Add it to our css
				CSS::append(file_get_contents($item));
			}
		}
		else
		{
			throw new Scaffold_Exception('Cannot find the mixin directory - ' . $dir);
		}
	}
	
	/**
	 * Parses a string for CSS-style conditionals
	 *
	 * @param $string A string of css
	 * @return void
	 **/
	public static function parse_conditionals($string = "")
	{		
		# Find all @if, @else, and @elseif's groups
		if($found = self::find_conditionals($string))
		{
			# Go through each one
			foreach($found[1] as $key => $value)
			{
				$result = false;
				
				# Find which equals sign was used and explode it
				preg_match("/\!=|\!==|===|==/", $value, $match); 
				
				# Explode it out so we can test it.
				$exploded = explode($match[0], $value);
				$val = trim($exploded[0]);
				
				if(preg_match('/[a-zA-Z]/', $val) && (strtolower($val) != "true" && strtolower($val) != "false") )
				{
					$value = str_replace($val, quote($val), $value);
				}
				
				eval("if($value){ \$result = true;}");
				
				# When one of them is if true, replace the whole group with the contents of that if and continue
				if($result)
				{
					$string = str_replace($found[0][$key], $found[3][$key], $string);
				}
				# If there is an @else
				elseif($found[5] != "")
				{
					$string = str_replace($found[0][$key], $found[7][$key], $string);
				}
				else
				{
					$string = str_replace($found[0][$key], '', $string);
				}	
			}
		}
		return $string;
	}
	
	/**
	 * Finds if statements in a string
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return array
	 */
	public static function find_conditionals($string = "")
	{
		$recursive = 2; 
		
		$regex = 
			"/
				
				# Find the @if's
				(?:@(?:if))\((.*?)\)
				
				# Return all inner selectors and properties
				(
					(?:[0-9a-zA-Z\_\-\*&]*?)\s*
					\{	
						((?:[^{}]+|(?{$recursive}))*)
					\}
				)
				
				\s*
				
				(
					# Find the @elses if they exist
					(@else)

					# Return all inner selectors and properties
					(
						(?:[0-9a-zA-Z\_\-\*&]*?)\s*
						\{	
							((?:[^{}]+|(?{$recursive}))*)
						\}
					)
				)?
				
			/xs";
		
		if(preg_match_all($regex, $string, $match))
		{
			return $match;
		}
		else
		{
			return array();
		}
	}

}