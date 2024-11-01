<?php

declare(strict_types=1);

namespace SPS\Front;

if ( ! defined( 'ABSPATH' ) ) exit;



if (!class_exists('\SPS\Front\SPSFront')) {

	/**
	* 
	*/
	class SPSFront {
		
		/**
		 * @var Singleton
		 * @access private
		 * @static
		 */
		private static $_instance = null;
		
		/**
		 * @var array
		 * @access private
		 */
		private $args = array();

		/**
		* Méthode qui crée l'unique instance de la classe
		* si elle n'existe pas encore puis la retourne.
		*
		* @param void
		* @return Singleton
		*/
		public static function getInstance() {
			if(is_null(self::$_instance)) self::$_instance = new SPSFront();
			return self::$_instance;
		}
		
		/**
		* Constructeur de la classe
		*
		* @param void
		* @return void
		*/
		private function __construct() {
			$this->addHooks();
		}

		public function addHooks() {
			add_action( 'wp_enqueue_scripts', array($this, 'addFrontScripts') );
			add_action( 'init', array($this, 'loadTextDomain'), 1 );
			add_shortcode( 'sps_slider', array($this, 'displaySlider') );

			add_action( 'sps_after_wrapper', array($this, 'addSliderScripts') );
			add_action( 'sps_slides', array($this, 'displaySlides') );
			add_action( 'sps_navigation', array($this, 'displayNavigation') );
			add_action( 'sps_navigation_dots', array($this, 'displayNavigationDots') );
			add_action( 'sps_arrows', array($this, 'displayArrows') );
			add_action( 'sps_arrow', array($this, 'displayArrow') );
		}

		/**
		* Ajoute les scripts au front
		*
		* @param void
		* @return void
		*/
		public function addFrontScripts() {
			wp_register_script( 'sps-keen-slider-script', plugins_url( 'assets/js/keen-slider.js', SIMPLE_PICTURES_SLIDER_FILE ), array(), '6.8.2', 'all' );
			wp_enqueue_style( 'sps-style', plugins_url( 'assets/css/front.css', SIMPLE_PICTURES_SLIDER_FILE ), array(), '1.0.0', 'all' );
			$sps_style = '';

			$sliders = get_posts(array(
				'numberposts'		=> -1,
				'fields'			=> 'ids',
				'post_type'			=> 'sps_slider'
			));

			foreach ($sliders as $slider_id) {
				$args = array(
					'template_id'			=> 'sps_slider_'.$slider_id,
				);
				
				$args['width'] = (metadata_exists('post', $slider_id, 'slider_width')) ? intval(get_post_meta( $slider_id, 'slider_width', true )) : 0;
				$args['height'] = (metadata_exists('post', $slider_id, 'slider_height')) ? intval(get_post_meta( $slider_id, 'slider_height', true )) : 0;
				$args['width_percent'] = (metadata_exists('post', $slider_id, 'slider_width_percent')) ? boolval(get_post_meta( $slider_id, 'slider_width_percent', true )) : false;
				$args['width_ratio'] = (metadata_exists('post', $slider_id, 'slider_width_ratio')) ? boolval(get_post_meta( $slider_id, 'slider_width_ratio', true )) : true;
				$args['transition'] = (metadata_exists('post', $slider_id, 'slider_transition')) ? strval(get_post_meta( $slider_id, 'slider_transition', true )) : 'fade';
				$args['transition_duration'] = (metadata_exists('post', $slider_id, 'slider_transition_duration')) ? intval(get_post_meta( $slider_id, 'slider_transition_duration', true )) : 1000;

				$args = apply_filters('sps_slider_args', $args, $slider_id);

				$sps_style .= '#' . esc_attr($args['template_id']) . '_wrapper {
					width : ' . ($args['width_percent'] ? '100%' : esc_attr(intval($args['width'])).'px') . ';
					max-width : ' . ($args['width_percent'] ? esc_attr(intval($args['width'])).'px' : 'none') . ';
					padding-top : ' . ($args['width_percent']&&$args['width_ratio'] ? esc_attr(intval($args['height'])*100/intval($args['width'])).'%' : esc_attr(intval($args['height'])).'px') . ';
				}
				#' . esc_attr($args['template_id']) . '_wrapper .sps_navigation .sps_dot {
					-webkit-transition-duration : ' . esc_attr(intval($args['transition_duration']/1000)) . 's;
					-o-transition-duration: ' . esc_attr(intval($args['transition_duration']/1000)) . 's;
					transition-duration: ' . esc_attr(intval($args['transition_duration']/1000)) . 's;
				}';

				if ($args['transition']=='fade') {
					$sps_style .= '#' . esc_attr($args['template_id']) . '_wrapper .sps_slide {
						position : absolute;
						left : 0;
						top : 0;
					}';
				}
			}

			wp_add_inline_style( 'sps-style', $sps_style);
			
		}

		/**
		* Active les Traductions du plugin
		*
		* @param void
		* @return void
		*/
		public function loadTextDomain() {
			load_plugin_textdomain( 'simple-pictures-slider', false, basename(dirname(SIMPLE_PICTURES_SLIDER_FILE)) . '/languages' );
		}

		/**
		* Renvoie le slider
		*
		* @param void
		* @return void
		*/
		public function displaySlider($atts) {

			$id = isset($atts['id']) ? $atts['id'] : null;

			if (!isset($id)) :
				return (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? __( 'No Slider ID found.', 'simple-pictures-slider' ) : '';
			elseif (get_post_type($id)!=='sps_slider' || !is_string(get_post_status($id))) :
				return (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? sprintf(__( 'No Slider with ID #%s found.', 'simple-pictures-slider' ), strval($id)) : '';
			elseif (get_post_status($id)!=='publish') :
				return (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? sprintf(__( 'The slider #%s is not published.', 'simple-pictures-slider' ), strval($id)) : '';
			endif;

			$slides = get_post_meta($id, 'slider_slides', true);
			$slides = apply_filters('sps_get_slides', $slides, $id);

			ob_start();

			if (!empty($slides) && is_array($slides) && count($slides)>0) {

				$args = array(
					'id'					=> $id,
					'template_id'			=> 'sps_slider_'.$id,
					'slides'				=> $slides,
				);
				
				$args['width'] = (metadata_exists('post', $id, 'slider_width')) ? intval(get_post_meta( $id, 'slider_width', true )) : 0;
				$args['height'] = (metadata_exists('post', $id, 'slider_height')) ? intval(get_post_meta( $id, 'slider_height', true )) : 0;
				$args['width_percent'] = (metadata_exists('post', $id, 'slider_width_percent')) ? boolval(get_post_meta( $id, 'slider_width_percent', true )) : false;
				$args['width_ratio'] = (metadata_exists('post', $id, 'slider_width_ratio')) ? boolval(get_post_meta( $id, 'slider_width_ratio', true )) : true;
				$args['transition'] = (metadata_exists('post', $id, 'slider_transition')) ? strval(get_post_meta( $id, 'slider_transition', true )) : 'fade';
				$args['transition_duration'] = (metadata_exists('post', $id, 'slider_transition_duration')) ? intval(get_post_meta( $id, 'slider_transition_duration', true )) : 1000;
				$args['arrows'] = (metadata_exists('post', $id, 'slider_arrows')) ? boolval(get_post_meta( $id, 'slider_arrows', true )) : false;
				$args['arrows_position'] = (metadata_exists('post', $id, 'slider_arrows_position')) ? strval(get_post_meta( $id, 'slider_arrows_position', true )) : 'outside_apart_center_left';
				$args['navigation'] = (metadata_exists('post', $id, 'slider_navigation')) ? boolval(get_post_meta( $id, 'slider_navigation', true )) : false;
				$args['navigation_position'] = (metadata_exists('post', $id, 'slider_navigation_position')) ? strval(get_post_meta( $id, 'slider_navigation_position', true )) : 'outside_bottom_middle';
				$args['animation'] = (metadata_exists('post', $id, 'slider_animation')) ? boolval(get_post_meta( $id, 'slider_animation', true )) : true;
				$args['animation_duration'] = (metadata_exists('post', $id, 'slider_animation_duration')) ? intval(get_post_meta( $id, 'slider_animation_duration', true )) : 1000;
				$args['pause_animation'] = (metadata_exists('post', $id, 'slider_pause_animation')) ? boolval(get_post_meta( $id, 'slider_pause_animation', true )) : true;
				$args['infinite'] = (metadata_exists('post', $id, 'slider_infinite')) ? boolval(get_post_meta( $id, 'slider_infinite', true )) : false;
				$args['enable_mouse'] = (metadata_exists('post', $id, 'slider_enable_mouse')) ? boolval(get_post_meta( $id, 'slider_enable_mouse', true )) : false;

				$this->args = $args = apply_filters('sps_slider_args', $args, $id);
			
				do_action( 'sps_before_wrapper' );

				?><div
					id="<?php echo esc_attr($args['template_id']); ?>_wrapper"
					class="sps_slider_wrapper <?php
						echo esc_attr('sps_slider_'.$args['transition']);
						if ($args['width_percent']) echo ' sps_slider_width_percent';
						if ($args['width_ratio']) echo ' sps_slider_width_ratio';
						if ($args['arrows']) echo ' sps_slider_arrows';
						if ($args['navigation']) echo ' sps_slider_navigation';
						if ($args['animation']) echo ' sps_slider_animation';
						if ($args['pause_animation']) echo ' sps_slider_pause_animation';
						if ($args['infinite']) echo ' sps_slider_infinite';
						if ($args['enable_mouse']) echo ' sps_slider_enable_mouse'; ?>
					"
					data-sps-slider="<?php echo esc_attr(json_encode($args)); ?>"
				><?php

					sps_get_template('slider.php', array(
						'id'				=> $id,
						'template_id'		=> $args['template_id'],
						'args'				=> $args,
					));

				?></div><?php

				do_action( 'sps_after_wrapper' );

			} else {
				sps_get_template('slider-empty.php', array(
					'post_id'		=> $id,
				));
			}
			
			return apply_filters( 'sps_slider', ob_get_clean());
		}

		/**
		* Ajoute les scripts à l'admin
		*
		* @param void
		* @return void
		* @static
		*/
		public function addSliderScripts() {
			$args = $this->args;

			$script_args = array(
				'breakpoints'		=> array(),
				'drag'				=> $args['enable_mouse'],
				'initial'			=> 0,
				'loop'				=> $args['infinite'],//||$args['animation'],
				'renderMode'		=> $args['transition']=='fade' ? 'custom' : 'performance',
				'rtl'				=> in_array($args['transition'], array('slideLeft', 'slideUp')),
				'selector'			=> '.sps_slide',
				'vertical'			=> in_array($args['transition'], array('slideDown', 'slideUp')),
				'slides'			=> array(
					'origin'			=> (apply_filters('sps_slider_centered', false, $args['id'])) ? 'center' : 'auto',
					'perView'			=> apply_filters('sps_slider_per_view', 1, $args['id']),
				),
			);
			$script_args = apply_filters('sps_script_args', $script_args, $args['id']);

			$extra_args = array(
				'animated'				=> boolval($args['animation']),
				'animation_duration'	=> intval($args['animation_duration']),
				'transition'			=> strval($args['transition']),
				'transition_duration'	=> intval($args['transition_duration']),
				'pause_on_hover'		=> boolval($args['pause_animation']),
			);

			wp_add_inline_script(
				'sps-keen-slider-script',
				'document.getElementById("'.esc_attr($args['template_id']).'").dataset.slider = JSON.stringify(new SPSSlider("#'.esc_attr($args['template_id']).'", '.json_encode($script_args).', '.json_encode($extra_args).'))',
				'after'
			);
			wp_enqueue_script( 'sps-keen-slider-script' );
		}

		/**
		* Affiche les slides
		*
		* @param void
		* @return void
		*/
		public function displaySlides() {
			$args = $this->args;

			$i = 0;
			while($i < count($args['slides'])) :

				$post_id = $args['slides'][$i];
				$image_id = (metadata_exists('post', $post_id, 'image_id')) ? intval(get_post_meta( $post_id, 'image_id', true )) : 0;
				$image_background = (metadata_exists('post', $post_id, 'background')) ? boolval(get_post_meta( $post_id, 'background', true )) : false;
				$image_alt = (metadata_exists('post', $post_id, 'alt')) ? strval(get_post_meta( $post_id, 'alt', true )) : '';
				$image_srcset = (metadata_exists('post', $post_id, 'srcset')) ? (array) get_post_meta( $post_id, 'srcset', true ) : array();

				if (isset($image_id) && !empty($image_id) && $image_id>0) {
					sps_get_template('slide.php', array(
						'inc'				=> $i,
						'post_id'			=> $post_id,
						'id'				=> $image_id,
						'alt'				=> $image_alt,
						'as_background'		=> $image_background,
						'srcset'			=> $image_srcset,
						'size'				=> apply_filters( 'sps_slider_attachment_size', 'full', $args['id']),
					));
				}
			
				$i++;
			
			endwhile;
		}

		/**
		* Affiche la navigation
		*
		* @param void
		* @return void
		*/
		public function displayNavigation() {
			$args = $this->args;

			if (apply_filters('sps_slider_display_navigation', $args['navigation'], $args)) {
				sps_get_template(
					'navigation.php',
					array(
						'position'			=> apply_filters('sps_slider_navigation_position', $args['navigation_position'], $args),
						'number_of_dots'	=> count($args['slides']),
					),
				);
			}
		}

		/**
		* Affiche les points de navigation
		*
		* @param void
		* @return void
		*/
		public function displayNavigationDots() {
			$args = $this->args;

			$i = 0;
			while($i < count($args['slides'])) :
			
				sps_get_template('navigation-dot.php', array(
					'dot_id'	=> $i
				));
			
				$i++;
			
			endwhile;
		}

		/**
		* Affiche les flèches de direction
		*
		* @param void
		* @return void
		*/
		public function displayArrows() {
			$args = $this->args;

			if (apply_filters('sps_slider_display_arrows', $args['arrows'], $args)) {
				sps_get_template(
					'arrows.php',
					array(
						'position'			=> apply_filters('sps_slider_arrows_position', $args['arrows_position'], $args),
						'number_of_dots'	=> count($args['slides']),
					),
				);
			}
		}

		/**
		* Affiche chaque flèche de direction
		*
		* @param void
		* @return void
		*/
		public function displayArrow() {
			$args = $this->args;
			
			sps_get_template('arrow.php', array(
				'direction'	=> 'previous'
			));
			
			sps_get_template('arrow.php', array(
				'direction'	=> 'next'
			));
		}
	}

	SPSFront::getInstance();
}
