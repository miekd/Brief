<?php

/**
 * CSS
 *
 * The CSS object. Has methods for interacting with the CSS string
 * and makes it very easy to find properties and values within the css
 * 
 * @author Anthony Short
 */
class CSS
{
	/**
	 * The CSS string
	 *
	 * @var string
	 */
	public static $css;
	
	/**
	 * This string is added to the css at the very end
	 * @var string
	 */
	public static $append;
	
	/**
	 * The constructor
	 *
	 * @author Anthony Short
	 * @param $css
	 * @return void
	 */
	public static function load($css)
	{
		self::$css = file_get_contents($css);
	}
	
	/**
	 * Appends the string to the css string. Setting $later to true will
	 * add the string to the css just before caching the CSS file to make
	 * the parsing process faster. Do this if the string you're adding doesn't
	 * need any more parsing.
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return null
	 */
	public static function append($string, $later = false)
	{
		if($later === false)
		{
			self::$css .= $string;
		}
		elseif($later === true)
		{
			self::$append .= $string;
		}
	}
	
	/**
	 * Makes all URLs absolute in a CSS string
	 *
	 * @author Anthony Short
	 * @return void
	 */
	public static function convert_to_absolute_urls($css = "")
	{
		if($css == "") $css =& self::$css;
		
		if($found = self::find_functions('url', '', $css))
		{
			foreach($found[1] as $key => $value)
			{
				$url = Utils::unquote($value);
								
				# Absolute Path
				if($url[0] == "/" || $url[0] == "\\")
				{
					continue;
				}
				
				# Relative Path
				else
				{
					$url = str_replace($_SERVER['document_root'], "/", realpath($url));
					$css = str_replace($value, $url, $css);
				}
			}
		}
	}
	
	/**
	 * Compresses down the CSS file. Not a complete compression,
	 * but enough to minimize parsing time.
	 *
	 * @author Anthony Short
	 * @return null
	 */	
	public static function compress(&$css)
	{
		if($css == "") $css =& self::$css;
		
		# Remove comments
		$css = self::remove_comments($css);
			
		# Remove extra white space
		$css = preg_replace('/\s+/', ' ', $css);
		
		# Remove line breaks
		$css = preg_replace('/\n|\r/', '', $css);
	}

	/**
	 * Replaces a matched string with another matched string.
	 * Can be either a direct string replace or a regular expression.
	 *
	 * @author Anthony Short
	 * @param $match The string to match
	 * @param $replace Replace the match with this string
	 * @param $regex Is it a regular expression
	 * @return null
	 */
	public static function replace($match, $replace, $regex = false)
	{
		if($regex === true)
		{
			if(self::$css = preg_replace($match, $replace, self::$css))
			{
				return true;
			}
		}
		else
		{
			if(self::$css = str_replace($match, $replace, self::$css))
			{
				return true;
			}
		}
		
		FB::log("CSS::replace - nothing to replace: " . $match);
	}
	
	/**
	 * Resolves a path used in the CSS so that the user can use
	 * paths like normal css rather than PHP
	 *
	 * @author Anthony Short
	 * @param $path
	 * @return string
	 */
	public static function resolve_path($path)
	{
		if($path[0] == "/")
		{
			$path = DOCROOT.$path;
		}
		else
		{
			# Join the CSS directory with the requested directory
			$path = join_path(CSSPATH,CSScaffold::config('core.request.relative_dir'),$path);
			
			# Get the full server path to the file
			$path = realpath($path);
		}
		
		return $path;
	}
	
	/**
	 * Removes the string from the css rather than replacing it.
	 *
	 * @author Anthony Short
	 * @param $string
	 * @return null
	 */
	public static function remove($string)
	{
		if(is_array($string))
		{
			foreach($string as $key => $value)
			{
				self::replace($value, '');
			}
		}
		else
		{
			self::replace($string, '');
		}
	}
	
	/**
	 * Finds CSS 'functions'. These are things like url(), embed() etc.
	 *
	 * @author Anthony Short
	 * @param $name
	 * @param $capture_group
	 * @return array
	 */
	public static function find_functions($name, $capture_group = "", $css = "")
	{
		if($css == "") $css =& self::$css;
		
		if(preg_match_all('/'.$name.'\(([^\)]+)\)/', $css, $match))
		{
			return ($capture_group == "") ? $match : $match[$capture_group];
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Finds all url()'s that start with ~/ and replaces it
	 * with the CSS url.
	 *
	 * @return void
	 */
	public static function replace_css_urls()
	{
		if($found = CSS::find_functions('url'))
		{
			foreach($found[1] as $url)
			{
				$url = Utils::unquote($url);

				if($url[0] == "~")
				{
					self::replace($url, str_replace('~/', CSScaffold::config('core.url.css'), $url));
				}
			}
		}
	}
	
	/**
	 * Finds @groups within the css and returns
	 * an array with the values, and groups.
	 *
	 * @author Anthony Short
	 * @param $group string
	 * @param $css string
	 */
	public static function find_at_group($group, $css = "")
	{
		if($css == "") $css =& self::$css;
		
		$found['values'] = $found['groups'] = array();
		
		if(preg_match_all('#@'.$group.'[^{]*?\{\s*([^\}]+)\s*\}\s*#i', $css, $matches))
		{	
			$found['groups'] = $matches[0];
						
			foreach($matches[1] as $key => $value)
			{
				$a = explode(";", substr($value, 0, -1));
									
				foreach($a as $value)
				{
					$t = explode(":", $value);
					
					if(isset($t[1]))
					{
						$found['values'][trim($t[0])] = $t[1];
					}
				}
			}
			
			return $found;		
		}
		
		return false;
	}
	
	/**
	 * FIND SELECTORS WITH PROPERTY
	 * 
	 * Finds selectors which contain a particular property
	 *
	 * @author Anthony Short
	 * @param $css
	 * @param $property string
	 * @param $value string
	 */
	public static function find_selectors_with_property($property, $value = ".*?", $css = "")
	{
		if($css == "") $css =& self::$css;
		
		if(preg_match_all("/([^{}]*)\s*\{\s*[^}]*(".$property."\s*\:\s*(".$value.")\s*\;).*?\s*\}/sx", $css, $match))
		{
			return $match;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Finds all properties with a particular value
	 *
	 * @author Anthony Short
	 * @param $property
	 * @param $value
	 * @param $css
	 * @return array
	 */
	public static function find_properties_with_value($property, $value = ".*?", $css = "")
	{
		if($css == "") $css =& self::$css;
		
		# Make the property name regex-friendly
		#$property = str_replace('-', '\-', preg_quote($property));
		
		if(preg_match_all("/\{([^\}]*({$property}\:\s*({$value})\s*\;).*?)\}/sx", $css, $match))
		{
			return $match;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Alias for find_properties_with_value
	 *
	 * @author Anthony Short
	 * @return array
	 */
	public static function find_property_with_value($property, $value, $css = "")
	{
		if($css == "") $css =& self::$css;
		return self::find_properties_with_value($property, $value, $css);
	}
		
	/**
	 * FIND SELECTORS
	 * 
	 * Finds a selector and returns it as string
	 *
	 * @author Anthony Short
	 * @param $selector string
	 * @param $css string
	 */
	public static function find_selectors($selector, $recursive = "", $css = "")
	{
		if($css == "") $css =& self::$css;
		
		if($recursive != "")
		{
			$recursive = "|(?{$recursive})";
		}

		$regex = 
			"/
				
				# This is the selector we're looking for
				({$selector})
				
				# Return all inner selectors and properties
				(
					([0-9a-zA-Z\_\-\*&]*?)\s*
					\{	
						(?P<properties>(?:[^{}]+{$recursive})*)
					\}
				)
				
			/xs";
		
		if(preg_match_all($regex, $css, $match))
		{
			return $match;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Finds all selectors of a certain name in the css. This finds
	 * the selector in any context. eg #id or .class, #id, .class etc.
	 *
	 * @author Anthony Short
	 * @param $selector
	 * @param $css
	 * @return array
	 */
	public static function find_selector_names($selector, $css = "")
	{
		if($css == "") $css =& self::$css;
		
		# Get it ready to be put in regex
		$selector = preg_quote($selector);
		
		$regex = "/
			[^}]*
			{$selector}
			[^{]*
		/sx";

		if(preg_match_all($regex, $css, $match))
		{
			return $match;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * FIND PROPERTY
	 * 
	 * Finds all properties within a css string
	 *
	 * @author Anthony Short
	 * @param $property string
	 * @param $css string
	 */
	public static function find_property($property, $css = "")
	{
		if($css == "") $css =& self::$css;
		
		if(preg_match_all('/(?P<property_name>'.str_replace('-', '\-', preg_quote($property)).')\s*\:\s*(?P<property_value>.*?)\s*\;/', $css, $matches))
		{
			return (array)$matches;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Check if a selector exists
	 *
	 * @param $name
	 * @return boolean
	 */
	public static function selector_exists($name, $css = "")
	{
		if($css == "") $css =& self::$css;
		
		return preg_match('/'.preg_quote($name).'\s*?({|,)/', $css);
	}
	
	/**
	 * Alias for find_property
	 *
	 * @author Anthony Short
	 * @param $property
	 * @param $css
	 * @return array
	 */
	public static function find_properties($property, $css = "")
	{
		if($css == "") $css =& self::$css;
		return find_property($property, $css);
	}
	
	/**
	 * REMOVE PROPERTIES
	 * 
	 * Removes all instances of a particular property from the css string
	 *
	 * @author Anthony Short
	 * @param $property string
	 * @param $value string
	 * @param $css string
	 */
	public static function remove_properties($property, $value, $css = "")
	{
		if($css == "") $css =& self::$css;
		return preg_replace('/'.$property.'\s*\:\s*'.$value.'\s*\;/', '', $css);
	}
	
	/**
	 * REMOVE CSS COMMENTS
	 * 
	 * Removes css style comments
	 *
	 * @author Anthony Short
	 * @param $css string
	 */
	public static function remove_comments($css = "")
	{
		if($css == "") $css =& self::$css;
		
		# Remove normal CSS comments
		$css = trim(preg_replace('#/\*[^*]*\*+([^/*][^*]*\*+)*/#', '', $css));
	
		# Remove single line comments. Thanks Naonak!
		$css = preg_replace('#//.*$#Umsi', '', $css);

		return $css;
	}

	/**
	 * Transforms CSS into XML
	 *
	 * @author Shaun Inman
	 * @param $css
	 * @return string
	 */
	public static function to_xml($css = "")
	{
		if($css == "") $css =& self::$css;

		# Strip comments to prevent parsing errors
		$xml = self::remove_comments($css);
		
		# These will break the xml, so we'll transform them for now
		$xml = self::convert_entities('encode', $xml);

		# Add semi-colons to the ends of property lists which don't have them
		$xml = preg_replace('/((\:|\+)[^;])*?\}/', "$1;}", $xml);

		# Transform properties
		$xml = preg_replace('/([-_A-Za-z*]+)\s*:\s*([^;}{]+)(?:;)/ie', "'<property name=\"'.trim('$1').'\" value=\"'.trim('$2').'\" />\n'", $xml);

		# Transform selectors
		$xml = preg_replace('/(\s*)([_@#.0-9A-Za-z\+~*\|\(\)\[\]^\"\'=\$:,\s-]*?)\{/me', "'$1\n<rule selector=\"'.preg_replace('/\s+/', ' ', trim('$2')).'\">\n'", $xml);
		
		# Close rules
		$xml = preg_replace('/\;?\s*\}/', "\n</rule>", $xml);
		
		# Indent everything one tab
		$xml = preg_replace('/\n/', "\r\t", $xml);
		
		# Tie it up with a bow
		$xml = '<?xml version="1.0" ?'.">\r<css>\r\t$xml\r</css>\r"; 

		return simplexml_load_string($xml);
	}
		
	/**
	 * Encodes or decodes parts of the css that break the xml
	 *
	 * @author Anthony Short
	 * @param $css
	 * @return string
	 */
	public static function convert_entities($action = 'encode', $css)
	{
		$css_replacements = array(
			'"' => '#SCAFFOLD-QUOTE#',
			'>' => '#SCAFFOLD-GREATER#',
			'&' => '#SCAFFOLD-PARENT#',
			'data:image/PNG;' => '#SCAFFOLD-IMGDATA-PNG#',
			'data:image/JPG;' => "#SCAFFOLD-IMGDATA-JPG#",
			'http://' => "#SCAFFOLD-HTTP",
		);
		
		switch ($action)
		{
			case 'decode':
				$css = str_replace(array_values($css_replacements),array_keys($css_replacements), $css);
				break;
			
			case 'encode':
				$css = str_replace(array_keys($css_replacements),array_values($css_replacements), $css);
				break;  
		}
		
		return $css;
	}
	
	/**
	 * Adds a selector to the CSS
	 *
	 * @author Anthony Short
	 * @param $selector
	 * @param $properties
	 * @return null
	 */
	public static function add($selector, $properties)
	{
		self::$css .= $selector ."{". $properties . "}";
	}
	
	/**
	 * Makes the CSS readable
	 */
	public static function pretty($output = false)
	{
		$css =& self::$css;
		
		$css = preg_replace('#(/\*[^*]*\*+([^/*][^*]*\*+)*/|url\(data:[^\)]+\))#e', "'esc('.base64_encode('$1').')'", $css); // escape comments, data protocol to prevent processing
			
		$css = str_replace(';', ";\r\r", $css); // line break after semi-colons (for @import)
		$css = preg_replace('#([-a-z]+):\s*([^;}{]+);\s*#i', "$1: $2;\r\t", $css); // normalize property name/value space
		$css = preg_replace('#\s*\{\s*#', "\r{\r\t", $css); // normalize space around opening brackets
		$css = preg_replace('#\s*\}\s*#', "\r}\r\r", $css); // normalize space around closing brackets
		$css = preg_replace('#,\s*#', ",\r", $css); // new line for each selector in a compound selector
		// remove returns after commas in property values
		if (preg_match_all('#:[^;]+,[^;]+;#', $css, $m))
		{
			foreach($m[0] as $oops)
			{
				$css = str_replace($oops, preg_replace('#,\r#', ', ', $oops), $css);
			}
		}
		$css = preg_replace('#esc\(([^\)]+)\)#e', "base64_decode('$1')", $css); // unescape escaped blocks
		
		// indent nested @media rules
		if (preg_match('#@media[^\{]*\{(.*\}\s*)\}#', $css, $m))
		{
			$css = str_replace($m[0], str_replace($m[1], "\r\t".preg_replace("#\r#", "\r\t", trim($m[1]))."\r", $m[0]), $css);
		}
		
		if($output === true)
		{
			return $css;
		}
	}

}