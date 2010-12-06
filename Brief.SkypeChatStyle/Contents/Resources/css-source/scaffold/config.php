<?php

/**
 * The document root for the server. If you're server doesn't set this
 * variable, you can manually enter in the server path to the document root
 */
$path['document_root'] = $_SERVER['DOCUMENT_ROOT'];

/**
 * CSS directory. This is where you are storing your CSS files.
 *
 * This path can be relative to this file or absolute from the document root.
 */
$path['css'] = '../';

/**
 * The path to the system folder.
 */
$path['system'] = 'system';

/**
 * Sets the cache path. By default, this is inside of the system folder.
 * You can set it to a custom location here. Be aware that when Scaffold
 * recaches, it empties the whole cache to removes all flagged cache files. 
 */
$path['cache'] = 'system/cache';

/**
 * Debug
 *
 * Enable Firebug output. You need Firebug and FirePHP for Firefox.
 * This is handy when you're viewing the page the CSS is used on,
 * as it will display CSScaffold errors in the console.
 *
 */
$config['debug'] = true;

/**
 * Mode
 *
 * Either 'production' or 'development'. In development the cache is always
 * refreshed each time you reload the CSS. In production, the cache is locked
 * and will never be recached. This means the load on your server will be much
 * less when the site is live. 
 */
$config['in_production'] = false;

/**
 * Force Recache
 *
 * By default, Scaffold will only recache your CSS if there
 * have been changes made to the requested file. If you want
 * it to always recache for development, set this to true.
 */	
$config['force_recache'] = true;

/**
 * Show CSS rendering information
 *
 * Output information at the top of your cached file.
 */
$config['show_header'] = false;

/**
 * Automatically include mixins
 *
 * By default, Scaffold includes any and all mixin files stored
 * in framework/mixins, to save the user the trouble of including
 * them by themselves. If you want Scaffold to run faster, you can
 * include them manually.
 *
 * Setting this to false means you need to include the framework/mixins manually.
 */
$config['auto_include_mixins'] = true;

/**
 * Override CSS @import
 *
 * Scaffold normally uses @include to import files, rather than
 * overriding the standard CSS @import. You can change this, and
 * use @import instead by setting this to true.
 *
 * Setting this to true means you'll use @import instead of @include
 */
$config['override_import'] = true;

/**
 * Make all URL paths absolute
 *
 * If you're calling CSS using scaffold/index.php?request=path/to/css method,
 * the relative paths to images will break in the browser, as it will
 * be looking for the images inside the scaffold folder. To fix this, Scaffold
 * can make all url() paths absolute.
 *
 * If you're having image path issues, set this to true.
 */
$config['absolute_urls'] = false;

/**
 * Use CSS-style constants
 *
 * You can use a syntax similar to the proposed CSS variable syntax which is
 * const(constantname) instead of the SASS-style !constantname
 *
 * Setting this to true uses the const(constantname) syntax for constants
 */
$config['use_css_constants'] = false;

/**
 * Minify/Prettify
 *
 * You can use the minify library to compress your CSS. Minify strips all 
 * unnecessary whitespace, empty and redundant selectors etc.
 *
 * By setting this to false, instead of minifying your CSS, it will prettify it, 
 * making it easier to read instead of worrying about compressing down the size. 
 */
$config['minify_css'] = false;

/**
 * Custom Global CSS Constants
 *
 * You can set basic constants here that can be access throughout your 
 * entire project. I'd advise that you don't add stylesheet-specific styles
 * here (like colours), instead, just add any constants you might need,
 * like $_SERVER variables.
 *
 * If you want to add more complex constant-setting logic, create a plugin.
 */
$config['constants'] = array
(
	'name' => 'value',
);

/**
 * Enabled Plugins
 *
 * Set which plugins are currently enabled. Plugins may also have
 * their own configuration options. Check the plugin folder for a 
 * config.php file to customize the way the plugin works.
 *
 * All of these plugins are already installed. This is where you can 
 * turn them on or off.
 * 
 * To install a new plugin, drop it in scaffold/plugins, then add it to
 * this list. For more information on any of these plugins, or about
 * creating your own plugins, check the wiki on Github.
 * 
 */
$config['disabled_plugins'] = array
(
	'XML_constants'
);