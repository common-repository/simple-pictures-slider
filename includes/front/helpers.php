<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;



if (!function_exists('sps_slider')) {
	function sps_slider($id) {
		echo do_shortcode('[sps_slider id="' . $id . '"]');
	}
}

/**
* Templating Function from Woocommerce
*/
if (!function_exists('sps_get_template')) {
	function sps_get_template( $template_name, $args = array() ) {

		$template_path = apply_filters( 'sps_template_path', 'sps/' );
		$default_path = untrailingslashit( plugin_dir_path( SIMPLE_PICTURES_SLIDER_FILE ) ) . '/templates/';

		if ( empty( $template ) ) {
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				)
			);
		}

		if ( ! $template ) { $template = $default_path . $template_name; }

		$template = apply_filters( 'sps_locate_template', $template, $template_name, $template_path );
		$filter_template = apply_filters( 'sps_get_template', $template, $template_name, $args );

		if ( $filter_template !== $template ) {
			if ( ! file_exists( $filter_template ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'simple-pictures-slider' ), '<code>' . $filter_template . '</code>' ).' Backtrace: ' . wp_debug_backtrace_summary(), '1.0' );
				return;
			}
			$template = $filter_template;
		}

		$action_args = array(
			'template_name' => $template_name,
			'located'       => $template,
			'args'          => $args,
		);

		if ( ! empty( $args ) && is_array( $args ) ) {
			if ( isset( $args['action_args'] ) ) {
				_doing_it_wrong( __FUNCTION__, __( 'action_args should not be overwritten when calling sps_get_template.', 'simple-pictures-slider' ), '1.0' );
				unset( $args['action_args'] );
			}
			extract( $args );
		}

		do_action( 'sps_before_template', $action_args['template_name'], $action_args['located'], $action_args['args'] );
		include $action_args['located'];
		do_action( 'sps_after_template', $action_args['template_name'], $action_args['located'], $action_args['args'] );
	}
}