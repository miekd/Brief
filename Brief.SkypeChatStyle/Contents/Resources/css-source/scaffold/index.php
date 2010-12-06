<?php
/**
 * DO NOT EDIT THIS FILE!
 *
 * This file acts as the front controller for CSScaffold.
 *
 * It's fairly straightforward. It loads a config file, sets an include path,
 * and then runs CSScaffold. 
 *
 * You should be able to pull CSScaffold out and use it however you wish as
 * long as the config file is in the right format.
 *
 * If you're looking to do something unique, like moving files and folders
 * around, you'll probably need to change this file or create a custom
 * front controller for Scaffold. Just keep in mind that the run method
 * NEEDS those three parameters, the SYSPATH constant and all of the required 
 * files. Apart from that, you should be able to drop it into anything.
 *
 * @package CSScaffold
 */

# Errors. This is overridden by the debug option later.
ini_set('display_errors', TRUE);
error_reporting(E_ALL & ~E_STRICT);

# Include the config file
include 'config.php';

# Path to the system directory
define('SYSPATH', str_replace('\\', '/', realpath($path['system'])). '/');

# Set the server variable for document root
if(!isset($_SERVER['DOCUMENT_ROOT']))
{
	$_SERVER['DOCUMENT_ROOT'] = $path['document_root'];
}

# Set timezone, just in case it isn't set
if (function_exists('date_default_timezone_set'))
{
	date_default_timezone_set('GMT');
}

# Include the classes
require SYSPATH . 'core/Utils.php';
require SYSPATH . 'core/Benchmark.php';
require SYSPATH . 'core/Module.php';
require SYSPATH . 'core/CSS.php';
require SYSPATH . 'core/Controller.php';
require SYSPATH . 'core/Exception.php';
require SYSPATH . 'vendor/FirePHPCore/fb.php';
require SYSPATH . 'vendor/FirePHPCore/FirePHP.class.php';

# And finally... the one that actually does the work
require SYSPATH . 'controllers/CSScaffold.php';

# And we're off!
if(isset($_GET['request']))
{
	CSScaffold::run($_GET, $config, $path);
}