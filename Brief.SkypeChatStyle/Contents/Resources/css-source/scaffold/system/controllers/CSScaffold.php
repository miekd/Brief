<?php
/**
 * Class CSScaffold
 * @package CSScaffold
 */

/**
 * CSScaffold
 *
 * Handles all of the inner workings of the framework and juicy goodness.
 * This is where the metaphorical cogs of the system reside. 
 *
 * Requires PHP 5.0.0
 * Tested on PHP 5.3.0
 *
 * @package CSScaffold
 * @author Anthony Short <anthonyshort@me.com>
 * @copyright 2009 Anthony Short. All rights reserved.
 * @license http://opensource.org/licenses/bsd-license.php  New BSD License
 * @link https://github.com/anthonyshort/csscaffold/master
 */
class CSScaffold extends Scaffold_Controller
{
	/**
	 * CSScaffold Version
	 */
	const VERSION = '1.5.0';

	/**
	 * Successfully loaded modules
	 *
	 * @var array
	 */
	private static $loaded_modules;
	
	/**
	 * Internal cache
	 */
	private static $internal_cache;
	 
	/**
	 * Sets the initial variables, checks if we need to process the css
	 * and then sends whichever file to the browser.
	 *
	 * @return void
	 * @author Anthony Short
	 **/
	public static function run($get, $config = array(), $path = array()) 
	{
		static $run;

		# This function can only be run once
		if ($run === TRUE)
			return;
		
		# The default options
		$default_config = array
		(
			'debug' 				=> false,
			'in_production' 		=> false,
			'force_recache' 		=> false,
			'show_header' 			=> true,
			'auto_include_mixins' 	=> true,
			'override_import' 		=> false,
			'absolute_urls' 		=> false,
			'use_css_constants' 	=> false,
			'minify_css' 			=> true,
			'constants' 			=> array(),
			'disabled_plugins' 		=> array()		
		);
		
		# Merge them with our set options
		$config = array_merge($default_config, $config);
		
		# The default paths
		$default_paths = array
		(
			'document_root' 		=> $_SERVER['DOCUMENT_ROOT'],
			'css' 					=> '../',
			'system' 				=> 'system',
			'cache' 				=> 'cache'
		);
		
		# Merge them with our set options
		$path = array_merge($default_paths, $path);
		
		# If we want to debug (turn on errors and FirePHP)
		if($config['debug'])
		{	
			# Set the error reporting level.
			error_reporting(E_ALL & ~E_STRICT);
			
			# Set error handler
			set_error_handler(array('CSScaffold', 'exception_handler'));
		
			# Set exception handler
			set_exception_handler(array('CSScaffold', 'exception_handler'));
			
			# Turn on FirePHP
			FB::setEnabled(true);
		}
		else
		{
			# Turn off errors
			error_reporting(0);
			FB::setEnabled(false);
		}
		
		# Set the options and paths in the config
		self::config_set('core', $config);

		# Set the paths in the config	
		self::config_set('core.path.docroot', 	Utils::fix_path( $path['document_root']) );
		self::config_set('core.path.system', 	Utils::fix_path( $path['system']) );
		self::config_set('core.path.cache', 	Utils::fix_path( $path['cache']) );
		self::config_set('core.path.css', 		Utils::fix_path( $path['css']) );
		self::config_set('core.url.css', 		Utils::urlpath( self::config('core.path.css') ));
		self::config_set('core.url.system', 	Utils::urlpath( SYSPATH ) );

		# Load the include paths
		self::include_paths(TRUE);
		
		# Set the output
		if(isset($get['output']))
			self::config_set('core.output', $get['output']);
		
		# Parse the $_GET['request'] and set it in the config
		self::config_set('core.request', self::parse_request($get['request']));
					
		# Get the modified time of the CSS file
		self::config_set('core.request.mod_time', filemtime(self::config('core.request.path')));

		# Tell CSScaffold where to cache and tell if we want to recache
		self::cache_set(self::config('core.path.cache'));		
	
		# Set it back to false if it's locked
		if( $config['in_production'] AND file_exists(self::$cached_file) )
		{
			$recache = false;
		}
			
		# If we need to recache
		elseif( 
			$config['force_recache'] OR 
			isset($get['recache']) OR 
			self::config('core.cache.mod_time') <= self::config('core.request.mod_time') OR 
			!file_exists(self::$cached_file)
		)
		{
			$recache = true;
			self::cache_clear();
		}
		else
		{
			$recache = false;
		}
		
		# Load the modules
		self::load_modules($config['disabled_plugins']);
		
		# Create a new CSS object
		CSS::load( self::config('core.request.path') );
		
		# Parse it
		if($recache) self::parse_css();
		
		# Log to Firebug
		FB::log(self::config('core'));
		
		# Output it
		self::output(CSS::$css);
		
		# Setup is complete, prevent it from being run again
		$run = TRUE;
	}

	private static function parse_request($path)
	{
		# Get rid of those pesky slashes
		$requested_file	= Utils::trim_slashes($path);
		
		# Remove anything after .css - like /typography/
		$requested_file = preg_replace('/\.css(.*)$/', '.css', $requested_file);
		
		# Remove the start of the url if it exists (http://www.example.com)
		$requested_file = preg_replace('/https?\:\/\/[^\/]+/i', '', $requested_file);
		
		# Add our requested file var to the array
		$request['file'] = $requested_file;
		
		# Path to the file, relative to the css directory
		$request['relative_file'] = Utils::trim_slashes(str_replace( Utils::trim_slashes(self::config('core.url.css')), '', $requested_file));

		# Path to the directory containing the file, relative to the css directory		
		$request['relative_dir'] = pathinfo($request['relative_file'], PATHINFO_DIRNAME);

		# Otherwise we'll try to find it inside the CSS directory
		$request['path'] = self::find_file($request['relative_dir'], basename($requested_file, '.css'), true, 'css');
		
		# Set the directory
		$request['directory'] = dirname($request['path']);
		
		# If the file doesn't exist
		if(!file_exists($request['path']))
			throw new Scaffold_Exception("Requested CSS file doesn't exist:" . $request['file']); 

		# or if it's not a css file
		if (!Utils::is_css($requested_file))
			throw new Scaffold_Exception("Requested file isn't a css file: $requested_file" );
		
		# or if the requested file wasn't from the css directory
		if(!substr(pathinfo($request['path'], PATHINFO_DIRNAME), 0, strlen(self::config('core.path.css'))))
			throw new Scaffold_Exception("Requested file wasn't within the CSS directory");
		
		return $request;
	}

	/**
	 * Loads modules and plugins
	 *
	 * @param $addons An array of addon names
	 * @param $directory The directory to look for these addons in
	 * @return void
	 */
	private static function load_modules($disabled = array())
	{
		# Get each of the folders inside the Plugins and Modules directories
		$modules = self::list_files('modules');
		
		foreach($modules as $module)
		{
			$name = basename($module);
			
			if(in_array($name, $disabled))
			{
				continue;
			}
			
			# The addon folder
			$folder = $module;
					
			# The controller for the plugin (Optional)
			$controller = Utils::join_path($folder,$name.'.php');

			# The config file for the plugin (Optional)
			$config_file = $folder.'/config.php';
			
			# Set the paths in the config
			self::config_set("$name.support", Utils::join_path($folder,'support'));
			self::config_set("$name.libraries", Utils::join_path($folder,'libraries'));

			# Include the addon controller
			if(file_exists($controller))
			{
				require_once($controller);
				
				# Any flags this module sets
				call_user_func(array($name,'flag'));
				
				# It's loaded
				self::$loaded_modules[] = $name;
			}
			
			# If there is a config file
			if(file_exists($config_file))
			{
				include $config_file;
				
				foreach($config as $key => $value)
				{
					self::config_set($name.'.'.$key, $value);
				}
				
				unset($config);
			}
		}
	}

	/**
	 * Output the CSS to the browser
	 *
	 * @return void
	 * @author Anthony Short
	 */
	public static function output()
	{	
		if (
			isset($_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['SERVER_PROTOCOL']) && 
			self::config('core.cache.mod_time') <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
		)
		{
			header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
			exit;
		}
		else
		{
			# Set the default headers
			header('Content-Type: text/css');
			header("Vary: User-Agent, Accept");
			header('Last-Modified: '. gmdate('D, d M Y H:i:s', self::config('core.cache.mod_time')) .' GMT');

			echo file_get_contents(self::$cached_file);
			exit;
		}
	}
	
	/**
	 * Parse the CSS
	 *
	 * @return string - The processes css file as a string
	 * @author Anthony Short
	 */
	public static function parse_css()
	{								
		# Start the timer
		Scaffold_Benchmark::start("parse_css");
		
		# Compress it before parsing
		CSS::compress(CSS::$css);
		
		# Import CSS files
		if(class_exists('Import'))
			Import::parse();
		
		if(self::config('core.auto_include_mixins') === true && class_exists('Mixins'))
		{
			# Import the mixins in the plugin/module folders
			Mixins::import_mixins('framework/mixins');
		}
													
		# Parse our css through the plugins
		foreach(self::$loaded_modules as $module)
		{
			call_user_func(array($module,'import_process'));
		}
		
		# Compress it before parsing
		CSS::compress(CSS::$css);

		# Parse the constants
		if(class_exists('Constants'))
			Constants::parse();

		foreach(self::$loaded_modules as $module)
		{
			call_user_func(array($module,'pre_process'));
		}
		
		# Parse the @grid
		if(class_exists('Layout'))
			Layout::parse_grid();
		
		# Replace the constants
		if(class_exists('Constants'))
			Constants::replace();
		
		# Parse @for loops
		if(class_exists('Iteration'))
			Iteration::parse();
		
		foreach(self::$loaded_modules as $module)
		{
			call_user_func(array($module,'process'));
		}
		
		# Compress it before parsing
		CSS::compress(CSS::$css);
		
		# Parse the mixins
		if(class_exists('Mixins'))
			Mixins::parse();
		
		# Find missing constants
		if(class_exists('Constants'))
			Constants::replace();
		
		# Compress it before parsing
		CSS::compress(CSS::$css);
		
		foreach(self::$loaded_modules as $module)
		{
			call_user_func(array($module,'post_process'));
		}
		
		# Parse the expressions
		if(class_exists('Expression'))
			Expression::parse();
		
		# Parse the nested selectors
		if(class_exists('NestedSelectors'))
			NestedSelectors::parse();
		
		# Convert all url()'s to absolute paths if required
		if(self::config('core.absolute_urls') === true)
		{
			CSS::convert_to_absolute_urls();
		}
		
		# Replaces url()'s that start with ~ to lead to the CSS directory
		CSS::replace_css_urls();
		
		# Add the extra string we've been storing
		CSS::$css .= CSS::$append;
		
		# If they want to minify it
		if(self::config('core.minify_css') === true && class_exists('Minify'))
		{
			Minify::compress();
		}
		
		# Otherwise, we'll make it pretty
		else
		{
			CSS::pretty();
		}
		
		# Formatting hook
		foreach(self::$loaded_modules as $module)
		{
			call_user_func(array($module,'formatting_process'));
		}
		
		# Validate the CSS
		if(class_exists('Validate'))
			Validate::check();
		
		# Stop the timer...
		Scaffold_Benchmark::stop("parse_css");
		
		if (self::config('core.show_header') === TRUE)
		{		
			CSS::$css  = "/* Processed by CSScaffold on ". gmdate('r') . " in ".Scaffold_Benchmark::get("parse_css", "time")." seconds */\n\n" . CSS::$css;
		}

		# Write the css file to the cache
		self::cache_write(CSS::$css,self::$cached_file);

		# Output process hook for plugins to display views.
		# Doesn't run in production mode.
		if(self::config('core.in_production') === false)
		{				
			foreach(self::$loaded_modules as $module)
			{
				call_user_func(array($module,'output'));
			}
		}
	}
	
	/**
	 * Finds a file requested in the CSS
	 *
	 * @author Anthony Short
	 * @param $param
	 * @return return type
	 */
	public static function find_css_file($path)
	{
		if(isset(self::$internal_cache['find_css_file'][$path]))
		{
			return self::$internal_cache['find_css_file'][$path];
		}
		
		# If the url starts with ~, we'll assume it's from the root of the css directory
		if($path[0] == "~")
		{
			$path = str_replace('~', CSScaffold::config('core.path.css'), $path);
		}
		
		# If they're getting an absolute file
		elseif($path[0] == "/")
		{
			$path = Utils::join_path( CSScaffold::config('core.path.docroot'), $path );
		}
		
		# Otherwise it's relative to the called CSS file
		else
		{
			$path = Utils::join_path( CSScaffold::config('core.request.directory'), $path ); 				
		}
		
		return self::$internal_cache['find_css_file'][$path] = realpath($path);
	}

}