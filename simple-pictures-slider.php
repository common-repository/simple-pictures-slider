<?php
/**
 * Plugin Name: Simple Pictures Slider
 * Plugin URI: https://simpleplugins.fr/sps/
 * Description: Just a simple slider of pictures
 * Version: 1.5.1
 * Author: Tom Baumgarten
 * Author URI: https://www.tombgtn.fr/
 * Text Domain: simple-pictures-slider
 * Domain Path: /languages
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'SIMPLE_PICTURES_SLIDER_FILE' ) ) define( 'SIMPLE_PICTURES_SLIDER_FILE', __FILE__ );

if (is_admin()) {
	require_once( dirname(SIMPLE_PICTURES_SLIDER_FILE) . '/includes/admin/admin.php' );
} else {
	require_once( dirname(SIMPLE_PICTURES_SLIDER_FILE) . '/includes/front/front.php' );
	require_once( dirname(SIMPLE_PICTURES_SLIDER_FILE) . '/includes/front/helpers.php' );
}
