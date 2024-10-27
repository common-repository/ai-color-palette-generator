<?php
/**
 * Plugin Name:     Red8 - AI Color Palette Generator
 * Description:     Create a 60/30/10 website color palette with seven entries based on a primary color and target audience.
 * Author:          Red8 Interactive
 * Author URI:      https://red8interactive.com
 * Text Domain:     ai-color-palette
 * Domain Path:     /languages
 * Version:         0.1.2
 * License: GPLv2 or later
 *
 * @package         Red8_AI_Color_Palette
 */

namespace Red8;

define( 'RED8_PATH', plugins_url( '', __FILE__ ) );
require_once 'vendor/autoload.php';
require_once 'includes/class-ai-color-palette.php';
require_once 'includes/class-settings-page.php';
require_once 'includes/class-key-expiration-handler.php';


add_action( 'plugins_loaded', function () {
	new Settings_Page();
	new Key_Expiration_Handler();
});


add_action(
	'after_setup_theme',
	function () {

		// Check to make sure the theme has a theme.json file.
		if ( wp_theme_has_theme_json() ) {
			$color_palette = new AI_Color_Palette();
			add_filter( 'wp_theme_json_data_theme', array( $color_palette, 'add_color_palette' ) );
		}
	}
);
