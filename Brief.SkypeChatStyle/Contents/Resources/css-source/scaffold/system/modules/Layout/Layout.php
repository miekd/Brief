<?php

/**
 * Grid class
 *
 * @author Anthony Short
 * @dependencies None
 **/
class Layout extends Scaffold_Module
{

	/**
	 * Width of a single column
	 *
	 * @var string
	 */
	public static $column_width;
	
	/**
	 * Number of columns in the grid
	 *
	 * @var string
	 */
	public static $column_count;
	
	/**
	 * Total width of the gutters combined
	 *
	 * @var string
	 */
	public static $gutter_width;
	
	/**
	 * Left gutter width
	 *
	 * @var string
	 */
	public static $left_gutter_width;
	
	/**
	 * Right gutter width
	 *
	 * @var string
	 */
	public static $right_gutter_width;
	
	/**
	 * The total width of the grid
	 *
	 * @var string
	 */
	public static $grid_width;
	
	/**
	 * The baseline height
	 *
	 * @var string
	 */
	public static $baseline;

	/**
	 * The pre-processing function occurs after the importing,
	 * but before any real processing. This is usually the stage
	 * where we set variables and the like, getting the css ready
	 * for processing.
	 *
	 * @author Anthony Short
	 * @param $css
	 */
	public static function parse_grid()
	{		
		# Find the @grid - this returns an array of 'groups' and 'values'		
		if( $settings = CSS::find_at_group('grid') )
		{
			# All the @grids
			$groups = $settings['groups'];
			
			# Store it so it's easier to grab
			$settings = $settings['values'];
			
			# Make sure none of the required options are missing
			foreach(array('column-count', 'column-width') as $option)
			{
				if(!isset($settings[$option]))
				{
					throw new Scaffold_Exception('Layout module requires the column-count and column-width properties');
				}
			}
			
			# Remove it from the css
			CSS::replace($groups, array()); 
			
			# The number of columns, baseline and unit
			$cc 	= $settings['column-count'];
			$unit 	= (isset($settings['unit'])) ? $settings['unit'] : 'px';
			$bl 	= (isset($settings['baseline'])) ? $settings['baseline'] : 18;
			$cw 	= $settings['column-width'];
			
			# Get the gutters
			$lgw = (isset($settings['left-gutter-width'])) ? $settings['left-gutter-width'] : 0;
			$rgw = (isset($settings['right-gutter-width'])) ? $settings['right-gutter-width'] : 0;
			
			# Get the total gutter width
			$gw	= $settings['gutter-width'] = $lgw + $rgw;
			
			# The total grid width
			$grid = ($cw + $gw) * $cc;
			
			$grid_settings = array(
				'column-count' 			=> $cc,
				'column-width' 			=> $cw . $unit,
				'gutter-width' 			=> $gw . $unit,
				'left-gutter-width' 	=> $lgw . $unit,
				'right-gutter-width' 	=> $rgw . $unit,
				'grid-width' 			=> $grid . $unit,
				'baseline' 				=> $bl . $unit
			);

			# Set them as constants we can use in the css
			foreach($grid_settings as $key => $value)
			{
				Constants::set($key,$value);
			}
			
			# Path to the image
			$img = CSScaffold::config('core.path.cache') . "Layout/{$lgw}_{$cw}_{$rgw}_{$bl}_grid.png";
			
			# Generate the grid.png
			self::create_grid_image($cw, $bl, $lgw, $rgw, $img);
			
			$img = str_replace(CSScaffold::config('core.path.docroot'),'/',$img);
			
			CSS::append(".showgrid{background:url('".$img."');}");

			# Round to baselines
			self::round_to_baseline($bl);
			
			# Make each of the column variables a member variable
			self::$column_count = $cc;
			self::$column_width = $cw;
			self::$gutter_width = $gw;
			self::$left_gutter_width = $lgw;
			self::$right_gutter_width = $rgw;
			self::$grid_width = $grid;
			self::$baseline = $bl;
		}
	}
	
	public static function output()
	{
		if(CSScaffold::config('core.output') == "grid" && isset(self::$column_width))
		{
			# Make sure we're sending HTML
			header('Content-Type: text/html');
			
			# Load the test suite markup
			$page = CSScaffold::load_view('Layout_grid', 'Layout/views/');

			# Echo and out!
			echo($page); 
			exit;
		}
	}
	
	/**
	 * Finds any round(n) and rounds the number 
	 * to the nearest multiple of the baseline
	 *
	 * @author Anthony Short
	 * @param $css
	 */
	private static function round_to_baseline($baseline)
	{
		if($found = CSS::find_functions('round'))
		{
			foreach($found[0] as $key => $match)
			{
				
				CSS::replace($match, round($found[1][$key]/$baseline)*$baseline."px");
			}
		}
	}

	/**
	* Generates the background grid.png
	*
	* @author Anthony Short
	* @param $cl Column width
	* @param $bl Baseline
	* @param $gw Gutter Width
	* @return null
	*/
	private static function create_grid_image($cw, $bl, $lgw, $rgw, $file)
	{		
		if(!file_exists($file))
		{
			$image = ImageCreate($cw + $lgw + $rgw,$bl);
			
			$colorWhite		= ImageColorAllocate($image, 255, 255, 255);
			$colorGrey		= ImageColorAllocate($image, 200, 200, 200);
			$colorBlue		= ImageColorAllocate($image, 240, 240, 255);
			
			# Draw left gutter
			Imagefilledrectangle($image, 0, 0, ($lgw - 1), $bl, $colorWhite);
			
			# Draw column
			Imagefilledrectangle($image, $lgw, 0, $cw + $lgw - 1, $bl, $colorBlue);
			
			# Draw right gutter
			Imagefilledrectangle($image, ($lgw + $cw + 1), 0, $lgw + $cw + $rgw, $bl, $colorWhite);
		
			# Draw baseline
			imageline($image, 0, ($bl - 1 ), $lgw + $cw + $rgw, ($bl - 1), $colorGrey);
			
			CSScaffold::cache_create(dirname($file));
			ImagePNG($image, $file);
			
			# Kill it
			ImageDestroy($image);
		}
	}
}