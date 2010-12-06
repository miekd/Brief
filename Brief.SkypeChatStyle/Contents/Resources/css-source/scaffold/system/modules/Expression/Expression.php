<?php

/**
 * Math
 *
 * Lets you do simple math equations within your css via math()
 *
 * @author Anthony Short
 * @dependencies None
 **/
class Expression extends Scaffold_Module
{
	/**
	 * The final process before it is cached. This is usually just
	 * formatting of css or anything else just before it's cached
	 *
	 * @author Anthony Short
	 * @param $css
	*/
	public static function parse()
	{
		CSS::$css = self::parse_expressions();
	}
	
	/**
	 * Finds eval chunks in property values
	 *
	 * @author Anthony Short
	 * @return null
	 */
	public static function find_expressions($css)
	{
		return Utils::match('/(\#\[[\'\"]?([^]]*?)[\'\"]?\])/', $css);
	}
	
	/**
	 * Parses the expressions in an array from find_expressions
	 *
	 * @author Anthony Short
	 * @return null
	 */
	public static function parse_expressions($css = "")
	{
		# If theres no css string given, use the master css
		if($css == "") $css = CSS::$css;
		
		# Find all of the property values which have [] in them.
		if($matches = self::find_expressions($css))
		{
			# So we don't double up on the same expression
			$originals 		= array_unique($matches[1]);
			$expressions 	= array_unique($matches[2]);
					
			foreach($expressions as $key => $expression)
			{
				$result = false;
							
				# Remove units and quotes
				$expression = preg_replace('/(px|em|%)/','', $expression); 
				
				$result = eval("return $expression;");
				
				if($result !== false)
				{
					# Replace the string in the css
					$css = str_replace($originals[$key], $result, $css);
				}
				else
				{
					throw new Scaffold_Exception("Cannot parse expression " . $matches[0][$key]);
				}
			}
		}
		
		return $css;
	}
	
}
