<?php

/**
 * XML Constants
 *
 * You can set CSScaffold constants using XML. This allows you to create
 * constants using a CMS or by any other means to tie it in with your CSS.
 *
 * XML must be in this format:
 
	<?xml version="1.0" ?>
	<constants>
	
		<constant>
			<name>Foo</name>
			<value>Bar</value>
		</constant>
	
	</constants>
 *
 * By default, it requires a constants.xml file in the root of the CSS directory.
 * You can change this in the plugins config.
 * 
 * @author Anthony Short
 */
class XML_constants extends Scaffold_Module
{
	/**
	 * Gets the XML and sets each of the nodes as constants
	 *
	 * @author Anthony Short
	 * @return Void
	 */
	public static function pre_process()
	{
		$file = CSScaffold::config('XML_constants.xml_path');
		
		# If the xml file doesn't exist
		if(!file_exists($file))
			throw new Scaffold_Exception("XML File doesn't exist - $file");
		
		# Load the xml
		$xml = simplexml_load_file($file);
		
		# Loop through them and set them as constants
		foreach($xml->constant as $key => $value)
		{
			Constants::set((string)$value->name, (string)$value->value);
		}
	}
}