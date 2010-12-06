<?php

/**
 * NestedSelectors
 *
 * @author Anthony Short
 * @dependencies None
 **/
class NestedSelectors extends Scaffold_Module
{

	/**
	 * Array of selectors to skip and keep them nested.
	 * It just checks if the string is present, so it can
	 * just be part of a string, like the @media rule is below.
	 *
	 * @var array
	 */
	protected static $skip = array
	(
		'@media'
	);
	
	/**
	 * The main processing function called by Scaffold. MUST return $css!
	 *
	 * @author Anthony Short
	 * @return $css string
	 */
	public static function parse()
	{
		$xml = CSS::to_xml();
		
		$css = "";
		
		foreach($xml->rule as $key => $value)
		{
			$css .= self::parse_rule($value);
		}

		CSS::$css = CSS::convert_entities('decode', $css);
	}
	
	/**
	 * Parse the css selector rule
	 *
	 * @author Anthony Short
	 * @param $rule
	 * @return return type
	 */
	public static function parse_rule($rule, $parent = '')
	{
		$css_string = "";
		$property_list = "";
		$parent = trim($parent);
		$skip = false;
	
		# Get the selector and store it away
		foreach($rule->attributes() as $type => $value)
		{
			$child = (string)$value;
			
			# if its NOT a root selector and has parents
			if($parent != "")
			{
				$parent = explode(",", $parent);

				foreach($parent as $parent_key => $parent_value)
				{
					$parent[$parent_key] = self::parse_selector(trim($parent_value), $child);
				}
				
				$parent = implode(",", $parent);
			}
			
			# Otherwise it's a root selector
			else
			{
				$parent = $child;
			}
		}

		foreach($rule->property as $p)
		{
			$property = (array)$p->attributes(); 
			$property = $property['@attributes'];
			
			$property_list .= $property['name'].":".$property['value'].";";
		}
		
		# Create the css string
		if($property_list != "")
		{
			$css_string .= $parent . "{" . $property_list . "}";
		}

		foreach($rule->rule as $inner_rule)
		{			
			# If the selector is in our skip array in the 
			# member variable, we'll leave the selector as nested.
			foreach(self::$skip as $selector)
			{				
				if(strstr($parent, $selector))
				{
					$skip = true;
					continue;
				}
			}
			
			# We don't want the selectors inside @media to have @media before them
			if($skip)
			{
				$css_string .= self::parse_rule($inner_rule, '');
			}
			else
			{
				$css_string .= self::parse_rule($inner_rule, $parent);
			}
		}
		
		# Build our @media string full of these properties if we need to
		if($skip)
		{
			$css_string = $parent . "{" . $css_string . "}";
		}

		return $css_string;
	}
		
	/**
	 * Parses the parent and child to find the next parent
	 * to pass on to the function
	 *
	 * @author Anthony Short
	 * @param $parent
	 * @param $child
	 * @param $atmedia Is this an at media group?
	 * @return string
	 */
	public static function parse_selector($parent, $child)
	{		
		# If there are listed parents eg. #id, #id2, #id3
		if(strstr($child, ","))
		{
			$parent = self::split_children($child, $parent);
		}
		
		# If the child references the parent selector
		elseif (strstr($child, "#SCAFFOLD-PARENT#"))
		{						
			$parent = str_replace("#SCAFFOLD-PARENT#", $parent, $child);
		}
		
		# Otherwise, do it normally
		else
		{
			$parent = "$parent $child";
		}
		
		return $parent;
	}
	
	/**
	 * Splits selectors with , and adds the parent to each
	 *
	 * @author Anthony Short
	 * @param $children
	 * @param $parent
	 * @return string
	 */
	public static function split_children($children, $parent)
	{
		$children = explode(",", $children);
												
		foreach($children as $key => $child)
		{
			# If the child references the parent selector
			if (strstr($child, "#SCAFFOLD-PARENT#"))
			{
				$children[$key] = str_replace("#SCAFFOLD-PARENT#", $parent, $child);	
			}
			else
			{
				$children[$key] = "$parent $child";
			}
		}
		
		return implode(",",$children);
	}

}