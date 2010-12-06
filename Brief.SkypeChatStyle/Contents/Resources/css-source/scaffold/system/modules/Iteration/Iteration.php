<?php

/**
 * Iterator
 *
 * @author Anthony Short
 * @dependencies None
 **/
class Iteration extends Scaffold_Module
{
	
	/**
	 * This function occurs before everything else
	 *
	 * @author Anthony Short
	 * @param $css
	 */
	public static function parse()
	{		
		# Find all the @server imports
		self::parse_fors(CSS::$css);
	}
	
	/**
	 * Parses @fors within the css
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return string
	 */
	public static function parse_fors($string)
	{
		if($found = self::find_fors($string))
		{			
			foreach($found[0] as $key => $value)
			{				
				$s = "";
				
				$from = $found[2][$key];
				$to = $found[3][$key];
				$var = $found[1][$key];
				
				for ($i = $from; $i <= $to; $i++)
				{
					$s .= str_replace("!{$var}", $i, $found[5][$key]);	
				}
				
				CSS::replace($found[0][$key], $s);				
			}
		}
	}
	
	/**
	 * Finds for statements in a string
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return array
	 */
	public static function find_fors($string = "")
	{
		$recursive = 4; 
		
		$regex = 
			"/
				
				# Find the @if's
				(?:@(?:for))\s\!(.*?)\sfrom\s(\d+)\sto\s(\d+)\s*
				
				# Return all inner selectors and properties
				(
					(?:[0-9a-zA-Z\_\-\*&]*?)\s*
					\{	
						((?:[^{}]+|(?{$recursive}))*)
					\}
				)
				
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
	
	/**
	 * Takes a string, a seperator and a max number and generates
	 * a long string from them
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return string
	 */
	public static function enumerate($string, $min, $max, $sep = ",")
	{
		$ret = array();
		
		for ($i = $min; $i <= $max; $i++)
		{
			$ret[] = $string . $i;
		}
		
		return implode($sep, $ret);
	}


}