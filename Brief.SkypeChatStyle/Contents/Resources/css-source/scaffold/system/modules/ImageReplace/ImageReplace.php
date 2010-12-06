<?php

/**
 * ImageReplacement class
 *
 * @author Anthony Short
 * @dependencies None
 **/
class ImageReplace extends Scaffold_Module
{

	/**
	 * The second last process, should only be getting everything
	 * syntaxically correct, rather than doing any heavy processing
	 *
	 * @author Anthony Short
	 * @return $css string
	 */
	public static function post_process()
	{			
		if($found = CSS::find_properties_with_value('image-replace', 'url\([\'\"]?([^)\'\"]+)[\'\"]?\)'))
		{				
			foreach ($found[4] as $key => $value) 
			{
				$path = CSScaffold::find_css_file($value);
						
				if( file_exists($path) )
				{
					# Make sure it's an image
					if(!Utils::is_image($path))
						FB::log("ImageReplace - File is not an image: $path");
																					
					// Get the size of the image file
					$size = GetImageSize($path);
					$width = $size[0];
					$height = $size[1];
					
					// Make sure theres a value so it doesn't break the css
					if(!$width && !$height)
					{
						$width = $height = 0;
					}
					
					// Build the selector
					$properties = "
						background:url($value) no-repeat 0 0;
						height:{$height}px;
						width:{$width}px;
						display:block;
						text-indent:-9999px;
						overflow:hidden;
					";
	
					CSS::replace($found[2][$key], $properties);
				}
				else
				{
					FB::log('Couldn\'t find image for image-replace: ' . $value);
				}
			}
			
			# Remove any left overs
			CSS::replace($found[1], '');
		}
	}

}