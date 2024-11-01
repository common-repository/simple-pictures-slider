<?php

declare(strict_types=1);

namespace SPS\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;



if (!class_exists('\SPS\Admin\SPSAdmin')) {

	/**
	* 
	*/
	class SPSAdmin {
		
		/**
		 * @var Singleton
		 * @access private
		 * @static
		 */
		private static $_instance = null;
		
		/**
		 * @var mixed : Enregistrement temporaires des meta-box normal
		 * @access private
		 * @static
		 */
		private static $_temp_meta_boxes_normal = null;

		/**
		* Méthode qui crée l'unique instance de la classe
		* si elle n'existe pas encore puis la retourne.
		*
		* @param void
		* @return Singleton
		*/
		public static function getInstance() {
			if(is_null(self::$_instance)) self::$_instance = new SPSAdmin();
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
		
		/**
		* Crochets Wordpress
		*
		* @param void
		* @return void
		*/
		public function addHooks() {

			register_activation_hook( SIMPLE_PICTURES_SLIDER_FILE, array(__CLASS__, 'addActivationHooks') );
			register_uninstall_hook( SIMPLE_PICTURES_SLIDER_FILE, array(__CLASS__, 'addUninstallHooks') );

			add_action( 'init', array($this, 'addCustomPostTypes') );
			add_action( 'init', array($this, 'loadTextDomain'), 1 );
			add_action( 'page-links-to-post-types', array($this, 'removeSliderOfPageLinksTo') );
			add_action( 'admin_enqueue_scripts', array($this, 'addAdminScripts') );
			add_action( 'admin_head', array($this, 'addAdminLink') );
			add_filter( 'update_footer', array($this, 'addMentionsFooter'), 20 );

			/* List of posts */
			add_filter( 'manage_sps_slider_posts_columns', array($this, 'addCustomColumns') );
			add_action( 'manage_sps_slider_posts_custom_column', array($this, 'addCustomColumnsContent'), 10, 2 );

			/* Metabox */
			add_filter( 'postbox_classes_sps_slider_sps_slider_dimensions', array($this, 'blockMetaBoxes') );
			add_filter( 'postbox_classes_sps_slider_sps_slider_transitions', array($this, 'blockMetaBoxes') );
			add_filter( 'postbox_classes_sps_slider_sps_slider_arrows', array($this, 'blockMetaBoxes') );
			add_filter( 'postbox_classes_sps_slider_sps_slider_navigation', array($this, 'blockMetaBoxes') );
			add_filter( 'postbox_classes_sps_slider_sps_slider_animation', array($this, 'blockMetaBoxes') );
			add_filter( 'postbox_classes_sps_slider_sps_slider_advanced', array($this, 'blockMetaBoxes') );

			add_action( 'submitpost_box', array($this, 'saveNormalMetabox') );
			add_action( 'edit_form_advanced', array($this, 'displayContent') );
			add_filter( 'enter_title_here', array($this, 'changeTitlePlaceholder'), 10, 2 );

			/* Enregistrement */
			add_action( 'save_post', array($this, 'saveSliderContent'), 9 );
			add_action( 'save_post', array($this, 'saveSliderDimensions') );
			add_action( 'save_post', array($this, 'saveSliderTransitions') );
			add_action( 'save_post', array($this, 'saveSliderArrows') );
			add_action( 'save_post', array($this, 'saveSliderNavigation') );
			add_action( 'save_post', array($this, 'saveSliderAnimation') );
			add_action( 'save_post', array($this, 'saveSliderAdvanced') );
			add_action( 'delete_post', array($this, 'removeSlidePosts'), 10, 2 );

			/* Slide Iframe */
			add_action( 'current_screen', array($this, 'slideSetIframe') );
			add_filter( 'admin_footer_text', array($this, 'slideIframeFooter') );
			add_filter( 'update_footer', array($this, 'slideIframeFooter'), 20 );
			add_filter( 'screen_options_show_screen', array($this, 'slideIframeHideScreenOptions'), 10, 2 );
			add_action( 'edit_form_top', array($this, 'slideIframeCheckboxAdvanced') );
			add_action( 'submitpost_box', array($this, 'slideIframePositionACFSide') );
			add_action( 'edit_form_advanced', array($this, 'slideIframeAdvancedPanel') );
			add_action( 'dbx_post_sidebar', array($this, 'slideIframeCloseAdvancedPanel') );
			add_action( 'edit_form_after_editor', array($this, 'slideIframeContentPicture') );
			add_action( 'in_admin_header', array($this, 'slideIframeRemoveNotices'), 0 );
			add_action( 'save_post', array($this, 'saveSlideContent'), 9 );
			add_action( 'save_post', array($this, 'saveSlideBackground') );
			add_action( 'save_post', array($this, 'saveSlideSrcset') );
			add_action( 'save_post', array($this, 'saveSlideAlt') );

			/* Ajax */
			add_action( 'wp_ajax_simple_pictures_slider_create_slide_post', array($this, 'createSlidePostAjax') );
			add_action( 'wp_ajax_simple_pictures_slider_remove_slide_post', array($this, 'removeSlidePostAjax') );
			add_action( 'wp_ajax_simple_pictures_slider_get_attachment_image', array($this, 'getAttachmentImageAjax') );

			/* ACF */
			add_action( 'acf/init', array($this, 'addRulesToACF') );
			add_filter( 'acf/get_object_type', array($this, 'changeIconTypeToACF'), 10, 3 );

			/* Media */
			add_image_size( 'sps_half', 1, 1, false );
			add_image_size( 'sps_quarter', 1, 1, false );
			add_filter( 'intermediate_image_sizes_advanced', array($this, 'resizeImageHalfAndQuarter'), 10, 3 );
			add_filter( 'image_size_names_choose', array($this, 'labelizeImageHalfAndQuarter') );
			add_filter( 'wp_prepare_attachment_for_js', array($this, 'setImageHalfAndQuarterDimensionsForJS'), 9999, 3 );

		}
		







		/**
		* Crochets Wordpress à l'activation
		*
		* @param void
		* @return void
		* @static
		*/
		public static function addActivationHooks() {
			$instance = self::getInstance();
			$instance->addCapabilitiesToRoles();
		}
		
		/**
		* Crochets Wordpress à la desinstallation
		*
		* @param void
		* @return void
		* @static
		*/
		public static function addUninstallHooks() {
			add_action( 'init', array(self::getInstance(), 'removeCapabilitiesToRoles') );

			$all_posts = get_posts(array(
				'numberposts'	=> -1,
				'post_type'		=> array('sps_slider','sps_slide'),
				'post_status'	=> 'any',
				'fields'		=> 'ids',
			));
			foreach ( $all_posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
		







		/**
		* Ajoute les capacités d'éditions de slider au roles
		*
		* @param void
		* @return void
		*/
		public function addCapabilitiesToRoles() {

			$roles = wp_roles();

			if ( isset($roles) && is_a($roles, 'WP_Roles') ) {

				foreach (get_editable_roles() as $name => $info) {
					$role = $roles->get_role($name);

					if ( isset($role) && is_a($role, 'WP_Role') ) {
						if ($role->has_cap('edit_page')) $role->add_cap('edit_sps_slider');
						if ($role->has_cap('read_page')) $role->add_cap('read_sps_slider');
						if ($role->has_cap('delete_page')) $role->add_cap('delete_sps_slider');
						if ($role->has_cap('edit_pages')) $role->add_cap('edit_sps_sliders');
						if ($role->has_cap('edit_others_pages')) $role->add_cap('edit_others_sps_sliders');
						if ($role->has_cap('edit_private_pages')) $role->add_cap('edit_private_sps_sliders');
						if ($role->has_cap('edit_published_pages')) $role->add_cap('edit_published_sps_sliders');
						if ($role->has_cap('publish_pages')) $role->add_cap('publish_sps_sliders');
						if ($role->has_cap('read_private_pages')) $role->add_cap('read_private_sps_sliders');
						if ($role->has_cap('delete_pages')) $role->add_cap('delete_sps_sliders');
						if ($role->has_cap('delete_others_pages')) $role->add_cap('delete_others_sps_sliders');
						if ($role->has_cap('delete_private_pages')) $role->add_cap('delete_private_sps_sliders');
						if ($role->has_cap('delete_published_pages')) $role->add_cap('delete_published_sps_sliders');
					}
				}
			}
		}

		/**
		* Supprime les capacités d'éditions de slider au roles
		*
		* @param void
		* @return void
		*/
		public function removeCapabilitiesToRoles() {

			global $wp_roles;

			if ( isset($wp_roles) && is_a($wp_roles, 'WP_Roles') ) {

				foreach ($wp_roles->get_names() as $name) {
					$role = $wp_roles->get_role($name);

					if ( isset($role) && is_a($role, 'WP_Role') ) {
						if ($role->has_cap('edit_sps_slider')) $role->remove_cap('edit_sps_slider');
						if ($role->has_cap('read_sps_slider')) $role->remove_cap('read_sps_slider');
						if ($role->has_cap('delete_sps_slider')) $role->remove_cap('delete_sps_slider');
						if ($role->has_cap('edit_sps_sliders')) $role->remove_cap('edit_sps_sliders');
						if ($role->has_cap('edit_others_sps_sliders')) $role->remove_cap('edit_others_sps_sliders');
						if ($role->has_cap('edit_private_sps_sliders')) $role->remove_cap('edit_private_sps_sliders');
						if ($role->has_cap('edit_published_sps_sliders')) $role->remove_cap('edit_published_sps_sliders');
						if ($role->has_cap('publish_sps_sliders')) $role->remove_cap('publish_sps_sliders');
						if ($role->has_cap('read_private_sps_sliders')) $role->remove_cap('read_private_sps_sliders');
						if ($role->has_cap('delete_sps_sliders')) $role->remove_cap('delete_sps_sliders');
						if ($role->has_cap('delete_others_sps_sliders')) $role->remove_cap('delete_others_sps_sliders');
						if ($role->has_cap('delete_private_sps_sliders')) $role->remove_cap('delete_private_sps_sliders');
						if ($role->has_cap('delete_published_sps_sliders')) $role->remove_cap('delete_published_sps_sliders');
					}
				}
			}
		}
		







		/**
		* Ajoute les Custom Post Types des sliders et des slides
		*
		* @param void
		* @return void
		*/
		public function addCustomPostTypes() {
			$labels = array(
				'name'                     => __( 'Sliders', 'simple-pictures-slider' ),
				'singular_name'            => __( 'Slider', 'simple-pictures-slider' ),
				'add_new_item'             => __( 'Add New Slider', 'simple-pictures-slider' ),
				'edit_item'                => __( 'Edit Slider', 'simple-pictures-slider' ),
				'new_item'                 => __( 'New Slider', 'simple-pictures-slider' ),
				'view_item'                => __( 'View Slider', 'simple-pictures-slider' ),
				'view_items'               => __( 'View Sliders', 'simple-pictures-slider' ),
				'search_items'             => __( 'Search Slider', 'simple-pictures-slider' ),
				'not_found'                => __( 'No sliders found', 'simple-pictures-slider' ),
				'not_found_in_trash'       => __( 'No sliders found in Trash', 'simple-pictures-slider' ),
				'parent_item_colon'        => __( 'Parent Slider:', 'simple-pictures-slider' ),
				'all_items'                => __( 'All Sliders', 'simple-pictures-slider' ),
				'archives'                 => __( 'Slider Archives', 'simple-pictures-slider' ),
				'attributes'               => __( 'Slider Attributes', 'simple-pictures-slider' ),
				'insert_into_item'         => __( 'Insert into slider', 'simple-pictures-slider' ),
				'uploaded_to_this_item'    => __( 'Uploaded to this slider', 'simple-pictures-slider' ),
				'menu_name'                => __( 'Sliders', 'simple-pictures-slider' ),
				'filter_items_list'        => __( 'Filter sliders list', 'simple-pictures-slider' ),
				'items_list_navigation'    => __( 'Sliders list navigation', 'simple-pictures-slider' ),
				'items_list'               => __( 'Sliders list', 'simple-pictures-slider' ),
				'item_published'           => __( 'Slider published.', 'simple-pictures-slider' ),
				'item_published_privately' => __( 'Slider published privately.', 'simple-pictures-slider' ),
				'item_reverted_to_draft'   => __( 'Slider reverted to draft.', 'simple-pictures-slider' ),
				'item_scheduled'           => __( 'Slider scheduled.', 'simple-pictures-slider' ),
				'item_updated'             => __( 'Slider updated.', 'simple-pictures-slider' ),
				'item_link'                => __( 'Slider Link.', 'simple-pictures-slider' ),
				'item_link_description'    => __( 'A link to a slider.', 'simple-pictures-slider' ),
			);
			$args = array(
				'label'                 => __( 'Slider', 'simple-pictures-slider' ),
				'labels'                => $labels,
				'description'           => __( 'Simple sliders of pictures', 'simple-pictures-slider' ),
				'public'                => false,
				'hierarchical'          => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'show_in_nav_menus'     => false,
				'show_in_admin_bar'     => true,
				'show_in_rest'          => false,
				'menu_position'         => 30,
				'menu_icon'             => 'dashicons-images-alt2',
				'capability_type'       => array('sps_slider', 'sps_sliders'),
				'map_meta_cap'          => true,
				'supports'              => array( 'title' ),
				'register_meta_box_cb'  => array($this, 'setMetaBoxes'),
				'taxonomies'            => array(),
				'has_archive'           => false,
				'rewrite'               => false,
				'query_var'             => false,
				'can_export'            => true,
			);
			register_post_type( 'sps_slider', $args );

			$labels = array(
				'name'                     => __( 'Slides', 'simple-pictures-slider' ),
				'singular_name'            => __( 'Slide', 'simple-pictures-slider' ),
				'add_new_item'             => __( 'Add New Slide', 'simple-pictures-slider' ),
				'edit_item'                => __( 'Edit Slide', 'simple-pictures-slider' ),
				'new_item'                 => __( 'New Slide', 'simple-pictures-slider' ),
				'view_item'                => __( 'View Slide', 'simple-pictures-slider' ),
				'view_items'               => __( 'View Slides', 'simple-pictures-slider' ),
				'search_items'             => __( 'Search Slide', 'simple-pictures-slider' ),
				'not_found'                => __( 'No slides found', 'simple-pictures-slider' ),
				'not_found_in_trash'       => __( 'No slides found in Trash', 'simple-pictures-slider' ),
				'parent_item_colon'        => __( 'Parent Slide:', 'simple-pictures-slider' ),
				'all_items'                => __( 'All Slides', 'simple-pictures-slider' ),
				'archives'                 => __( 'Slide Archives', 'simple-pictures-slider' ),
				'attributes'               => __( 'Slide Attributes', 'simple-pictures-slider' ),
				'insert_into_item'         => __( 'Insert into slide', 'simple-pictures-slider' ),
				'uploaded_to_this_item'    => __( 'Uploaded to this slide', 'simple-pictures-slider' ),
				'menu_name'                => __( 'Slides', 'simple-pictures-slider' ),
				'filter_items_list'        => __( 'Filter slides list', 'simple-pictures-slider' ),
				'items_list_navigation'    => __( 'Slides list navigation', 'simple-pictures-slider' ),
				'items_list'               => __( 'Slides list', 'simple-pictures-slider' ),
				'item_published'           => __( 'Slide published.', 'simple-pictures-slider' ),
				'item_published_privately' => __( 'Slide published privately.', 'simple-pictures-slider' ),
				'item_reverted_to_draft'   => __( 'Slide reverted to draft.', 'simple-pictures-slider' ),
				'item_scheduled'           => __( 'Slide scheduled.', 'simple-pictures-slider' ),
				'item_updated'             => __( 'Slide updated.', 'simple-pictures-slider' ),
				'item_link'                => __( 'Slide Link.', 'simple-pictures-slider' ),
				'item_link_description'    => __( 'A link to a slide.', 'simple-pictures-slider' ),
			);
			$args = array(
				'label'                 => __( 'Slide', 'simple-pictures-slider' ),
				'labels'                => $labels,
				'description'           => __( 'Simple slides for simple sliders', 'simple-pictures-slider' ),
				'public'                => false,
				'hierarchical'          => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'show_in_admin_bar'     => false,
				'show_in_rest'          => false,
				'menu_icon'             => 'dashicons-format-image',
				'capability_type'       => array('sps_slider', 'sps_sliders'),
				'map_meta_cap'          => true,
				'supports'              => false,
				'register_meta_box_cb'  => array($this, 'setMetaBoxes'),
				'taxonomies'            => array(),
				'has_archive'           => false,
				'rewrite'               => false,
				'query_var'             => false,
				'can_export'            => false,
			);
			register_post_type( 'sps_slide', $args );

		}

		/**
		* Active les Traductions du plugin
		*
		* @param void
		* @return void
		*/
		public function loadTextDomain() {
			load_plugin_textdomain( 'simple-pictures-slider', false, dirname( plugin_basename(SIMPLE_PICTURES_SLIDER_FILE)) . '/languages' );

			/* Plugin meta translations */
			__('Just a simple slider of pictures', 'simple-pictures-slider');
			__('A simple plugin to display a picture slider. Light, efficient and compatible with ACF. Best for developers.', 'simple-pictures-slider');
			__('SPS is a simple plugin to create a slider of pictures and display it with a shortcode or PHP code. Few options to keep it simple, but fast and efficient. Some hooks to improve the slider or to configure it more specifically. Good plugin for developers, with possibility to control what your client can edit or not in the advanced options.', 'simple-pictures-slider');
			__('Compatible with ACF, you can put field groups for every slide. It\'s possible to override template files for displaying the fields.', 'simple-pictures-slider');
		}

		/**
		* Enlève la possibilité de modifier l'url avec Page Links To
		*
		* @param void
		* @return void
		*/
		public function removeSliderOfPageLinksTo($post_types) {
			if (($key = array_search('sps_slider', $post_types)) !== false) unset($post_types[$key]);
			if (($key = array_search('sps_slide', $post_types)) !== false) unset($post_types[$key]);
			return $post_types;
		}

		/**
		* Ajoute les scripts à l'admin
		*
		* @param void
		* @return void
		*/
		public function addAdminScripts($hook_suffix) {
			if( in_array($hook_suffix, array('post.php', 'post-new.php', 'edit.php') ) ) {
				$screen = get_current_screen();
				global $post;
				if( is_object( $screen ) && 'sps_slider' == $screen->post_type ) {
					wp_enqueue_style( 'simple-pictures-slider-style', plugins_url( 'assets/css/admin.css', SIMPLE_PICTURES_SLIDER_FILE ) );
					wp_add_inline_style( 'simple-pictures-slider-style', 'body.post-php.post-type-sps_slider #TB_window.thickbox-loading:before { background-image: url("'.get_admin_url(null, 'images/spinner.gif').'"); }' );
					if( in_array($hook_suffix, array('post.php', 'post-new.php') ) ) {
						add_thickbox();
						wp_enqueue_media();
						wp_enqueue_script( 'simple-pictures-slider-script', plugins_url( 'assets/js/admin.js', SIMPLE_PICTURES_SLIDER_FILE ), array( 'jquery-ui-sortable', 'jquery-ui-core' ) );
						wp_add_inline_script( 'simple-pictures-slider-script', 'const sps_slider = {
							i18n : {
								media_manager_title : '.json_encode( __( 'Select Media', 'simple-pictures-slider' ) ).'
							},
							ajax_url : '.json_encode( admin_url( 'admin-ajax.php' ) ).',
							post_id : '.json_encode( $post->ID ).',
							nonces : {
								create_slide_post : '.json_encode( wp_create_nonce('sps_create_slide_post_nonce_value') ).',
								remove_slide_post : '.json_encode( wp_create_nonce('sps_remove_slide_post_nonce_value') ).',
							}
						}' );

						add_action( 'admin_print_footer_scripts', function() {
							?><script type="text/javascript">/* <![CDATA[ */
								jQuery(function($) {
									$(".meta-box-sortables").sortable('option', 'cancel', '.not-sortable .hndle, :input, button').sortable('refresh');
								});
							/* ]]> */</script><?php
						}, 99 );
					}
				} else if( is_object( $screen ) && 'sps_slide' == $screen->post_type ) {
					$sizes = apply_filters(
						'image_size_names_choose',
						array(
							'thumbnail'	=> __( 'Thumbnail' ),
							'medium'	=> __( 'Medium' ),
							'large'		=> __( 'Large' ),
							'full'		=> __( 'Full Size' ),
						)
					);
					$default_sizes = array();

					$image_id = get_post_meta(intval($post->ID), 'image_id', true);
					if (isset($image_id) && !empty($image_id) && intval($image_id)>0) {
						$current_image_meta_data = wp_get_attachment_metadata(intval($image_id));

						if (isset($current_image_meta_data['width']) && isset($current_image_meta_data['height'])) {
							$default_sizes['full'] = ((isset($sizes['full'])) ? $sizes['full'] : 'full') . sprintf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($current_image_meta_data['width']), esc_html($current_image_meta_data['height']));
						}
											
						if (isset($current_image_meta_data['sizes']) && is_array($current_image_meta_data['sizes']) && count($current_image_meta_data['sizes'])>0) {
							foreach ($current_image_meta_data['sizes'] as $image_size_key => $image_size_data) {
								$default_sizes[esc_attr($image_size_key)] = ((isset($sizes[$image_size_key])) ? $sizes[$image_size_key] : $image_size_key) . sprintf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($image_size_data['width']), esc_html($image_size_data['height']));
							}
						}
					}

					wp_enqueue_style( 'simple-pictures-slider-style', plugins_url( 'assets/css/admin.css', SIMPLE_PICTURES_SLIDER_FILE ) );
					wp_enqueue_script( 'simple-pictures-slide-script', plugins_url( 'assets/js/admin-slide.js', SIMPLE_PICTURES_SLIDER_FILE ), array( 'jquery' ) );
					wp_add_inline_script( 'simple-pictures-slide-script', 'const sps_slide = {
						i18n : {
							media_manager_title : '.json_encode( __( 'Select Media', 'simple-pictures-slider' ) ).',
							alert_close_on_change : '.json_encode( __( 'Select Media', 'simple-pictures-slider' ) ).',
							size_dimensions : '.json_encode( __(' (%s &times; %s px)', 'simple-pictures-slider') ).',
							sizes : '.json_encode($sizes).',
						},
						post_id : '.json_encode( $post->ID ).',
						ajax_url : '.json_encode( admin_url( 'admin-ajax.php' ) ).',
						default_sizes : '.json_encode($default_sizes).',
						nonces : {
							get_attachment_image : '.json_encode( wp_create_nonce('sps_get_attachment_image_nonce_value') ).',
						}
					}' );
					if ( isset($_GET['message']) && in_array(absint($_GET['message']), array(1,4,6,7,8,9,10)) ) wp_add_inline_script( 'simple-pictures-slide-script', 'window.sps_slide_close();' );
				}
			}
		}

		/**
		* Ajoute les link rel="preload" à l'admin
		*
		* @param void
		* @return void
		*/
		public function addAdminLink() {
			$screen = get_current_screen();
			if( is_object( $screen ) && 'sps_slider' == $screen->post_type && 'edit' == $screen->parent_base ){
				global $post;
				$id = $post->ID;
				$slides = get_post_meta($post->ID, 'slider_slides', true);
				if (!empty($slides) && is_array($slides) && count($slides)>0) {
					foreach ($slides as $slide) echo '<link rel="prerender" href="'.esc_url(get_edit_post_link(intval($slide))).'" pr="1" as="document">';
				}

				echo '<link rel="preload" href="'.esc_url(get_admin_url(null, 'images/spinner.gif')).'" as="image">';
			}
		}

		/**
		* Ajoute les mentions en pied de page du slider
		*
		* @param void
		* @return void
		*/
		public function addMentionsFooter($text) {
			$screen = get_current_screen();
			if( is_object( $screen ) && 'sps_slider' == $screen->post_type ) return 'Made with <img src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDUwIDUwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MCA1MDsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI1MTJweCI+CjxwYXRoIHN0eWxlPSJmaWxsOiNDMDNBMkI7IiBkPSJNMjQuODUsMTAuMTI2YzIuMDE4LTQuNzgzLDYuNjI4LTguMTI1LDExLjk5LTguMTI1YzcuMjIzLDAsMTIuNDI1LDYuMTc5LDEzLjA3OSwxMy41NDMgIGMwLDAsMC4zNTMsMS44MjgtMC40MjQsNS4xMTljLTEuMDU4LDQuNDgyLTMuNTQ1LDguNDY0LTYuODk4LDExLjUwM0wyNC44NSw0OEw3LjQwMiwzMi4xNjVjLTMuMzUzLTMuMDM4LTUuODQtNy4wMjEtNi44OTgtMTEuNTAzICBjLTAuNzc3LTMuMjkxLTAuNDI0LTUuMTE5LTAuNDI0LTUuMTE5QzAuNzM0LDguMTc5LDUuOTM2LDIsMTMuMTU5LDJDMTguNTIyLDIsMjIuODMyLDUuMzQzLDI0Ljg1LDEwLjEyNnoiLz4KPHBhdGggc3R5bGU9ImZpbGw6I0VENzE2MTsiIGQ9Ik02LDE4LjA3OGMtMC41NTMsMC0xLTAuNDQ3LTEtMWMwLTUuNTE0LDQuNDg2LTEwLDEwLTEwYzAuNTUzLDAsMSwwLjQ0NywxLDFzLTAuNDQ3LDEtMSwxICBjLTQuNDExLDAtOCwzLjU4OS04LDhDNywxNy42MzEsNi41NTMsMTguMDc4LDYsMTguMDc4eiIvPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" alt="love" style="vertical-align:middle" width="11"> and <img src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMiA1MTI7IiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iNTEycHgiIGhlaWdodD0iNTEycHgiPgo8cGF0aCBzdHlsZT0iZmlsbDojOTlBQUI1OyIgZD0iTTUxMiwzNjkuNzc3YzAsNzguNTUtMTE0LjYxNiwxNDIuMjIyLTI1NiwxNDIuMjIyUzAsNDQ4LjMyNywwLDM2OS43NzdzMTE0LjYxNi0xNDIuMjIyLDI1Ni0xNDIuMjIyICBTNTEyLDI5MS4yMjgsNTEyLDM2OS43NzciLz4KPHBhdGggc3R5bGU9ImZpbGw6I0NDRDZERDsiIGQ9Ik01MTIsMzQxLjMzM2MwLDc4LjU1LTExNC42MTYsMTQyLjIyMi0yNTYsMTQyLjIyMlMwLDQxOS44ODIsMCwzNDEuMzMzczExNC42MTYtMTQyLjIyMiwyNTYtMTQyLjIyMiAgUzUxMiwyNjIuNzgzLDUxMiwzNDEuMzMzIi8+CjxwYXRoIHN0eWxlPSJmaWxsOiNGNUY4RkE7IiBkPSJNMjU2LDQ0MC44ODhjLTIxMi43MzYsMC0yNDEuNzc4LTIxMy4zMzMtMjQxLjc3OC0yNzAuMjIyaDQ4My41NTYgIEM0OTcuNzc4LDE5OS4xMTEsNDY5LjkzLDQ0MC44ODgsMjU2LDQ0MC44ODgiLz4KPHBhdGggc3R5bGU9ImZpbGw6I0NDRDZERDsiIGQ9Ik00NTMuNDMsOTguODY4Yy0xOS4yOTktMTMuMzQxLTQ0LjEzMi0yNC4wOTItNzIuODMyLTMyQzQwNy4yNjQsNTguNjc3LDQ0NS4zMjQsNjIuNDc0LDQ1My40Myw5OC44NjggICBNNDg5LjAyOCwxMzcuMTU1QzUyMy43NTktNi44MDMsMzQ5LjkyMSwyNy41NTgsMzE1LjQ2LDU0LjY5NGMtMTkuMDU4LTIuMTE5LTM4LjkxMi0zLjMyOC01OS40NjQtMy4zMjggIGMtMTMzLjUzMiwwLTI0MS43NzgsNDUuOTEtMjQxLjc3OCwxMjAuMDkyczEwOC4yNDYsMTM0LjMxNCwyNDEuNzc4LDEzNC4zMTRzMjQxLjc3OC02MC4xMzIsMjQxLjc3OC0xMzQuMzE0ICBDNDk3Ljc3NCwxNTkuMTk5LDQ5NC41NzQsMTQ3Ljc5Miw0ODkuMDI4LDEzNy4xNTUiLz4KPHBhdGggc3R5bGU9ImZpbGw6IzhBNEIzODsiIGQ9Ik00NjkuMzMzLDE4NC44ODhjMCw1NC45ODQtOTUuNTE3LDk5LjU1Ni0yMTMuMzMzLDk5LjU1NlM0Mi42NjcsMjM5Ljg3Miw0Mi42NjcsMTg0Ljg4OCAgUzEzOC4xODQsODUuMzMzLDI1Niw4NS4zMzNTNDY5LjMzMywxMjkuOTA1LDQ2OS4zMzMsMTg0Ljg4OCIvPgo8Zz4KCTxwYXRoIHN0eWxlPSJmaWxsOiNEOTlFODI7IiBkPSJNMjg0LjQ0NCwyNDEuNzc3Yy0zLjY0MSwwLTcuMjgyLTEuMzk0LTEwLjA1NS00LjE2N2MtMzMuMjM3LTMzLjIzNy0zMy43OTItNjkuNDc2LTEuNzc4LTExNy41MDQgICBjMTAuNTEtMTUuNzcyLDEyLjgtMzEuOTI5LDYuNzk4LTQ4LjAyOGMtNi41NTctMTcuNTY0LTIwLjQ1Mi0yOC4zODgtMjQuNjE5LTI5LjUyNmMtNy44NjUsMC0xMy42MjUtNi4zMTQtMTMuNjI1LTE0LjE2NiAgIGMwLTcuODY1LDYuOTg0LTE0LjE2NiwxNC44MzQtMTQuMTY2YzE0LjE3OSwwLDM0LjA2MiwxNi4zOTgsNDUuMjcsMzcuMzMzYzE0LjcwNSwyNy40OTIsMTIuOTQyLDU3LjQ0My00Ljk5Miw4NC4zMzggICBjLTI3Ljg5LDQxLjg0Mi0yMS43NzUsNjEuNjEtMS43NzgsODEuNjA3YzUuNTYxLDUuNTYxLDUuNTYxLDE0LjU1LDAsMjAuMTFDMjkxLjcyNiwyNDAuMzgzLDI4OC4wODYsMjQxLjc3NywyODQuNDQ0LDI0MS43NzciLz4KCTxwYXRoIHN0eWxlPSJmaWxsOiNEOTlFODI7IiBkPSJNMTk5LjExMSwyMTMuMzMzYy0zLjY0MSwwLTcuMjgyLTEuMzk0LTEwLjA1NS00LjE2N2MtMzMuMjM3LTMzLjIzNy0zMy43OTItNjkuNDc2LTEuNzc4LTExNy41MDQgICBjMTAuMzM5LTE1LjUxNywxMi43MDEtMjkuNjEsNy4wMjUtNDEuOTEzYy02LjMxNC0xMy42NjgtMjAuMzUyLTIwLjg5Mi0yMy45NS0yMS4zMTljLTcuODUsMC0xNC4wNjUtNi4zNTctMTQuMDY1LTE0LjIwOCAgIGMwLTcuODY1LDYuNTI4LTE0LjIyMiwxNC4zNzktMTQuMjIyYzE0LjE3OSwwLDM2Ljc2NCwxMy44NTIsNDcuNzg3LDM0LjQ2MWM2Ljg0MSwxMi43ODUsMTQuOTc2LDM5LjI2OC03LjUxLDcyLjk4OCAgIGMtMjcuODksNDEuODQyLTIxLjc3NSw2MS42MS0xLjc3OCw4MS42MDdjNS41NjEsNS41NjEsNS41NjEsMTQuNTUsMCwyMC4xMUMyMDYuMzkzLDIxMS45MzksMjAyLjc1MiwyMTMuMzMzLDE5OS4xMTEsMjEzLjMzMyIvPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+Cjwvc3ZnPgo=" alt="cafeine" style="vertical-align:middle" width="11"> '.$text;
			return $text;
		}
		







		/**
		* Ajoute les colonnes à la liste des sliders
		*
		* @param void
		* @return void
		*/
		public function addCustomColumns($columns) {
			$new = array();
			foreach($columns as $key => $title) {
				if ($key=='date') {
					$new['shortcode'] = esc_html__( 'Shortcode', 'simple-pictures-slider' );
					$new['php_code'] = esc_html__( 'PHP Code', 'simple-pictures-slider' );
				}
				$new[$key] = $title;
			}
			return $new;
		}

		/**
		* Ajoute le contenu des colonnes à la liste des sliders
		*
		* @param void
		* @return void
		*/
		public function addCustomColumnsContent($column, $post_id) {
			switch ( $column ) {
				case 'shortcode' :
					?><input type="text" class="large-text" value="[sps_slider id='<?php echo esc_attr($post_id); ?>']" onclick="this.select()" id="input_shortcode_<?php echo esc_attr($post_id); ?>" readonly="readonly"/><label for="input_shortcode_<?php echo esc_attr($post_id); ?>" onclick="copied=typeof copied==='undefined'?true:copied;if(copied) {event.preventDefault();var that=this;navigator.clipboard.writeText('[sps_slider id=\'<?php echo esc_attr($post_id); ?>\']').then(function() {that.classList.add('success');setTimeout(function() {that.classList.remove('success');}, 1500);}, function() {copied = false;that.trigger('click');});} else {copied = true;}"><span class="dashicons-sps-copy"></span><span class="copied_success"><?php _e('Copied!', 'simple-pictures-slider'); ?></span></label><?php
				break;

				case 'php_code' :
					?><input type="text" class="large-text" value="<?php echo '<?php if (function_exists(\'sps_slider\')) { sps_slider('.esc_attr($post_id).'); } ?>'; ?>" id="input_php_code_<?php echo esc_attr($post_id); ?>" onclick="this.select()" readonly="readonly"/><label for="input_php_code_<?php echo esc_attr($post_id); ?>" onclick="copied=typeof copied==='undefined'?true:copied;if(copied) {event.preventDefault();var that=this;navigator.clipboard.writeText('<?php echo '<?php if (function_exists(\\\'sps_slider\\\')) { sps_slider('.esc_attr($post_id).'); } ?>'; ?>').then(function() {that.classList.add('success');setTimeout(function() {that.classList.remove('success');}, 1500);}, function() {copied = false;that.trigger('click');});} else {copied = true;}"><span class="dashicons-sps-copy"></span><span class="copied_success"><?php _e('Copied!', 'simple-pictures-slider'); ?></span></label><?php
				break;
			}
		}
		







		/**
		* Empeche les metabox du slider d'étre déplacable
		*
		* @param string $classes : Les classes de la metabox
		* @return string $classes : Les nouvelles classes de la metabox
		*/
		public function blockMetaBoxes($classes) {
			$classes[] = 'not-sortable';
			return $classes;
		}

		/**
		* Verifie si l'utilisateur a le droit de modifier la metabox
		*
		* @param string $metabox : Le slug de la metabox
		* @return string $capability : La capacité par défaut pour modifier la metabox
		*/
		public function checkMetaboxCap($metabox, $capability = 'manage_options') {
			return current_user_can(apply_filters('sps_admin_options_cap', apply_filters('sps_admin_options_cap_'.$metabox, $capability)));
		}

		/**
		* Ajoute et supprime les metabox au slider 
		*
		* @param void
		* @return void
		*/
		public function setMetaBoxes() {
			remove_meta_box('submitdiv', 'sps_slider', 'side');
			remove_meta_box('submitdiv', 'sps_slide', 'side');
			
			add_meta_box('sps_slider_publish', __( 'Publish', 'simple-pictures-slider' ), array( $this, 'metaBoxPublish' ), 'sps_slider', 'side', 'core', array( '__back_compat_meta_box' => true ));
			
			if ($this->checkMetaboxCap('dimensions')) add_meta_box('sps_slider_dimensions', __( 'Dimensions', 'simple-pictures-slider' ), array( $this, 'metaBoxDimensions' ), 'sps_slider', 'side', 'low');
			if ($this->checkMetaboxCap('transitions')) add_meta_box('sps_slider_transitions', __( 'Transitions', 'simple-pictures-slider' ), array( $this, 'metaBoxTransitions' ), 'sps_slider', 'side', 'low');
			if ($this->checkMetaboxCap('arrows')) add_meta_box('sps_slider_arrows', __( 'Next & Previous Arrows', 'simple-pictures-slider' ), array( $this, 'metaBoxArrows' ), 'sps_slider', 'side', 'low');
			if ($this->checkMetaboxCap('navigation')) add_meta_box('sps_slider_navigation', __( 'Navigation Dots', 'simple-pictures-slider' ), array( $this, 'metaBoxNavigation' ), 'sps_slider', 'side', 'low');
			if ($this->checkMetaboxCap('animation')) add_meta_box('sps_slider_animation', __( 'Animation', 'simple-pictures-slider' ), array( $this, 'metaBoxAnimation' ), 'sps_slider', 'side', 'low');
			if ($this->checkMetaboxCap('advanced')) add_meta_box('sps_slider_advanced', __( 'Advanced', 'simple-pictures-slider' ), array( $this, 'metaBoxAdvanced' ), 'sps_slider', 'side', 'low');
		}

		/**
		* Contenu de la metabox "Publier"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxPublish($post, $args = array()) {
			global $action;

			$post_id          = (int) $post->ID;
			$post_type        = $post->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish      = current_user_can( 'publish_sps_sliders', $post_id );

			?><div class="submitbox" id="submitpost">
				<div id="minor-publishing">

					<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key. ?>
					<div style="display:none;">
						<?php submit_button( __( 'Save' ), '', 'save' ); ?>
					</div>

					<div id="minor-publishing-actions">
						<div id="add-pictures-to-slider">
							<input type='button' class="button-primary" value="<?php esc_attr_e( 'Add slides', 'simple-pictures-slider' ); ?>" id="slider_media_manager"/>
						</div>
						<div id="code-slider-integration">
							<div>
								<input type="text" class="large-text" value="[sps_slider id='<?php echo esc_attr($post_id); ?>']" onclick="this.select()" id="input_shortcode_<?php echo esc_attr($post_id); ?>" readonly="readonly"/><label for="input_shortcode_<?php echo esc_attr($post_id); ?>" onclick="copied=typeof copied==='undefined'?true:copied;if(copied) {event.preventDefault();var that=this;navigator.clipboard.writeText('[sps_slider id=\'<?php echo esc_attr($post_id); ?>\']').then(function() {that.classList.add('success');setTimeout(function() {that.classList.remove('success');}, 1500);}, function() {copied = false;that.trigger('click');});} else {copied = true;}"><span class="dashicons-sps-copy"></span><span class="copied_success"><?php _e('Copied!', 'simple-pictures-slider'); ?></span></label>
							</div>
							<div>
								<input type="text" class="large-text" value="<?php echo '<?php if (function_exists(\'sps_slider\')) { sps_slider('.esc_attr($post_id).'); } ?>'; ?>" id="input_php_code_<?php echo esc_attr($post_id); ?>" onclick="this.select()" readonly="readonly"/><label for="input_php_code_<?php echo esc_attr($post_id); ?>" onclick="copied=typeof copied==='undefined'?true:copied;if(copied) {event.preventDefault();var that=this;navigator.clipboard.writeText('<?php echo '<?php if (function_exists(\\\'sps_slider\\\')) { sps_slider('.esc_attr($post_id).'); } ?>'; ?>').then(function() {that.classList.add('success');setTimeout(function() {that.classList.remove('success');}, 1500);}, function() {copied = false;that.trigger('click');});} else {copied = true;}"><span class="dashicons-sps-copy"></span><span class="copied_success"><?php _e('Copied!', 'simple-pictures-slider'); ?></span></label>
							</div>
						</div>
						<?php


						/**
						 * Fires after the Save Draft (or Save as Pending) and Preview (or Preview Changes) buttons
						 * in the Publish meta box.
						 *
						 * @since 4.4.0
						 *
						 * @param WP_Post $post WP_Post object for the current post.
						 */
						do_action( 'post_submitbox_minor_actions', $post );
						?>
						<div class="clear"></div>
					</div>

					<div id="misc-publishing-actions">
						<?php
						/**
						 * Fires after the post time/date setting in the Publish meta box.
						 *
						 * @since 2.9.0
						 * @since 4.4.0 Added the `$post` parameter.
						 *
						 * @param WP_Post $post WP_Post object for the current post.
						 */
						do_action( 'post_submitbox_misc_actions', $post );
						?>
					</div>
					<div class="clear"></div>
				</div>

				<div id="major-publishing-actions">
					<?php
					/**
					 * Fires at the beginning of the publishing actions section of the Publish meta box.
					 *
					 * @since 2.7.0
					 * @since 4.9.0 Added the `$post` parameter.
					 *
					 * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen,
					 *                           null on Edit Link screen.
					 */
					do_action( 'post_submitbox_start', $post );
					?>
					<div id="delete-action">
						<?php
						if ( current_user_can( 'delete_sps_slider', $post_id ) ) {
							if ( ! EMPTY_TRASH_DAYS ) {
								$delete_text = __( 'Delete permanently' );
							} else {
								$delete_text = __( 'Move to Trash' );
							}
							?>
							<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post_id ); ?>"><?php echo $delete_text; ?></a>
							<?php
						}
						?>
					</div>

					<div id="publishing-action">
						<span class="spinner"></span>
						<?php
						if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ), true ) || 0 === $post_id ) {
							if ( $can_publish ) :
								if ( ! empty( $post->post_date_gmt ) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) :
									?>
									<input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr_x( 'Schedule', 'post action/button label' ); ?>" />
									<?php submit_button( _x( 'Schedule', 'post action/button label' ), 'primary large', 'publish', false ); ?>
									<?php
								else :
									?>
									<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ); ?>" />
									<?php submit_button( __( 'Publish' ), 'primary large', 'publish', false ); ?>
									<?php
								endif;
							else :
								?>
								<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ); ?>" />
								<?php submit_button( __( 'Submit for Review' ), 'primary large', 'publish', false ); ?>
								<?php
							endif;
						} else {
							?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>" />
							<?php submit_button( __( 'Update' ), 'primary large', 'save', false, array( 'id' => 'publish' ) ); ?>
							<?php
						}
						?>
					</div>
					<div class="clear"></div>
				</div>
			</div><?php
		}

		/**
		* Contenu de la metabox "Dimensions"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxDimensions($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_dimensions_nonce', 'sps_metabox_dimensions_nonce_value' );

			$slider_width = intval(get_post_meta( $post->ID, 'slider_width', true ));
			$slider_height = intval(get_post_meta( $post->ID, 'slider_height', true ));
			$slider_dimensions_related = (metadata_exists('post', $post->ID, 'slider_dimensions_related')) ? boolval(get_post_meta( $post->ID, 'slider_dimensions_related', true )) : true;
			$slider_width_percent = (metadata_exists('post', $post->ID, 'slider_width_percent')) ? boolval(get_post_meta( $post->ID, 'slider_width_percent', true )) : false;
			$slider_width_ratio = (metadata_exists('post', $post->ID, 'slider_width_ratio')) ? boolval(get_post_meta( $post->ID, 'slider_width_ratio', true )) : true;

			?><div class="sps-column-dimension">
				<p class="post-attributes-label-wrapper slider-width-label-wrapper"><label class="post-attributes-label" for="slider_width"><?php esc_html_e('Width', 'simple-pictures-slider'); ?></label></p>
				<input type="number" name="slider_width" id="slider_width" class="large-text" value="<?php echo esc_attr($slider_width); ?>"/>
			</div><!--
			--><div class="sps-column-link">
				<label><input type="checkbox" name="slider_dimensions_related" value="1" id="sps-dimensions-related-link" hidden="hidden" <?php checked($slider_dimensions_related); ?>/><span class="dashicons-sps-related"></span><span class="dashicons-sps-unrelated"></span></label>
			</div><!--
			--><div class="sps-column-dimension">
				<p class="post-attributes-label-wrapper slider-height-label-wrapper"><label class="post-attributes-label" for="slider_height"><?php esc_html_e('Height', 'simple-pictures-slider'); ?></label></p>
				<input type="number" name="slider_height" id="slider_height" class="large-text" value="<?php echo esc_attr($slider_height); ?>"/>
			</div>
			<p class="post-attributes-label-wrapper slider-width_percent-label-wrapper">
				<input type="checkbox" name="slider_width_percent" id="slider_width_percent" value="1" <?php checked($slider_width_percent); ?>/>
				<label class="post-attributes-label" for="slider_width_percent"><span></span><?php esc_html_e('Set maximum width at 100%', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="post-attributes-label-wrapper slider-width_ratio-label-wrapper show_if_slider_width_percent" <?php if(!$slider_width_percent) { echo 'style="display:none;"'; } ?>>
				<input type="checkbox" name="slider_width_ratio" id="slider_width_ratio" value="1" <?php checked($slider_width_ratio); ?>/>
				<label class="post-attributes-label" for="slider_width_ratio"><span></span><?php esc_html_e('Keep ratio', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="description show_if_slider_width_percent" <?php if(!$slider_width_percent) { echo 'style="display:none;"'; } ?>><?php esc_html_e('Keep the ratio width/height of the slider (checked) or leave the height fixed (unchecked).', 'simple-pictures-slider'); ?></p><?php
		}

		/**
		* Contenu de la metabox "Transitions"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxTransitions($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_transitions_nonce', 'sps_metabox_transitions_nonce_value' );

			$slider_transition = (metadata_exists('post', $post->ID, 'slider_transition')) ? strval(get_post_meta( $post->ID, 'slider_transition', true )) : 'fade';
			$slider_transition_duration = (metadata_exists('post', $post->ID, 'slider_transition_duration')) ? intval(get_post_meta( $post->ID, 'slider_transition_duration', true )) : 1000;

			$transitions = apply_filters('sps_transitions', array(
				'fade' => __('Fade', 'simple-pictures-slider'),
				'slideRight' => __('Slide Right to Left', 'simple-pictures-slider'),
				'slideDown' => __('Slide Bottom to Top', 'simple-pictures-slider'),
				'slideLeft' => __('Slide Left to Right', 'simple-pictures-slider'),
				'slideUp' => __('Slide Top to Bottom', 'simple-pictures-slider'),
			));
			if (!in_array($slider_transition, array_keys($transitions))) $slider_transition = 'fade';

			?><p class="post-attributes-label-wrapper slider-transition-label-wrapper"><label class="post-attributes-label" for="slider_transition"><?php esc_html_e('Transition Effect', 'simple-pictures-slider'); ?></label></p>
			<select onchange="let e=document.getElementById('transition_example');e.className='';e.classList.add('transition_'+this.value);" name="slider_transition" id="slider_transition" class="large-text"><?php
				foreach ($transitions as $transition_value => $transition_label) {
					?><option value="<?php echo esc_attr($transition_value); ?>" <?php selected($slider_transition, $transition_value); ?>><?php echo esc_html($transition_label); ?></option><?php
				}
			?></select>
			<span onclick="let e=document.getElementById('transition_example');e.className='';e.classList.add('transition_'+document.getElementById('slider_transition').value);this.classList.toggle('dashicons-controls-play');this.classList.toggle('dashicons-controls-pause');return false;" class="dashicons dashicons-controls-play"></span>
			<span id="transition_example"></span>
			<p class="post-attributes-label-wrapper slider-transition_duration-label-wrapper"><label class="post-attributes-label" for="slider_transition_duration"><?php esc_html_e('Duration', 'simple-pictures-slider'); ?></label></p>
			<input type="number" name="slider_transition_duration" id="slider_transition_duration" class="large-text" value="<?php echo esc_attr($slider_transition_duration); ?>"/>
			<p class="description"><?php esc_html_e('Sets the duration (in milliseconds) for the slideshow transition.', 'simple-pictures-slider'); ?></p><?php
		}

		/**
		* Contenu de la metabox "Flèches"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxArrows($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_arrows_nonce', 'sps_metabox_arrows_nonce_value' );

			$slider_arrows = (metadata_exists('post', $post->ID, 'slider_arrows')) ? boolval(get_post_meta( $post->ID, 'slider_arrows', true )) : false;
			$slider_arrows_position = (metadata_exists('post', $post->ID, 'slider_arrows_position')) ? strval(get_post_meta( $post->ID, 'slider_arrows_position', true )) : 'outside_apart_center_left';
			$slider_arrows_position_array = explode('_', $slider_arrows_position);
			$slider_arrows_position_inside = in_array('inside', $slider_arrows_position_array);
			
			?><p class="post-attributes-label-wrapper slider-arrows-label-wrapper">
				<input type="checkbox" name="slider_arrows" id="slider_arrows" value="1" <?php checked($slider_arrows); ?>/>
				<label class="post-attributes-label" for="slider_arrows"><span></span><?php esc_html_e('Enable Arrows', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="post-attributes-label-wrapper slider-arrows_position_inside-label-wrapper show_if_slider_arrows" <?php if(!$slider_arrows) { echo 'style="display:none;"'; } ?>>
				<input type="checkbox" name="slider_arrows_position_inside" id="slider_arrows_position_inside" value="1" <?php checked($slider_arrows_position_inside); ?>/>
				<label class="post-attributes-label" for="slider_arrows_position_inside"><span></span><?php esc_html_e('Positioned Inside', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="description show_if_slider_arrows" <?php if(!$slider_arrows) { echo 'style="display:none;"'; } ?>><?php esc_html_e('Sets the position of the arrows : inside the slider (checked) or outside (unchecked).', 'simple-pictures-slider'); ?></p><?php
		}

		/**
		* Contenu de la metabox "Navigation"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxNavigation($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_navigation_nonce', 'sps_metabox_navigation_nonce_value' );

			$slider_navigation = (metadata_exists('post', $post->ID, 'slider_navigation')) ? boolval(get_post_meta( $post->ID, 'slider_navigation', true )) : false;
			$slider_navigation_position = (metadata_exists('post', $post->ID, 'slider_navigation_position')) ? strval(get_post_meta( $post->ID, 'slider_navigation_position', true )) : 'outside_bottom_middle';
			$slider_navigation_position_array = explode('_', $slider_navigation_position);
			$slider_navigation_position_inside = in_array('inside', $slider_navigation_position_array);
			
			?><p class="post-attributes-label-wrapper slider-navigation-label-wrapper">
				<input type="checkbox" name="slider_navigation" id="slider_navigation" value="1" <?php checked($slider_navigation); ?>/>
				<label class="post-attributes-label" for="slider_navigation"><span></span><?php esc_html_e('Enable Navigation Dots', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="post-attributes-label-wrapper slider-navigation_position_inside-label-wrapper show_if_slider_navigation" <?php if(!$slider_navigation) { echo 'style="display:none;"'; } ?>>
				<input type="checkbox" name="slider_navigation_position_inside" id="slider_navigation_position_inside" value="1" <?php checked($slider_navigation_position_inside); ?>/>
				<label class="post-attributes-label" for="slider_navigation_position_inside"><span></span><?php esc_html_e('Positioned Inside', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="description show_if_slider_navigation" <?php if(!$slider_navigation) { echo 'style="display:none;"'; } ?>><?php esc_html_e('Sets the position of the navigation dots : centered inside the slider (checked) or outside (unchecked).', 'simple-pictures-slider'); ?></p><?php
		}

		/**
		* Contenu de la metabox "Animation"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxAnimation($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_animation_nonce', 'sps_metabox_animation_nonce_value' );

			$slider_animation = (metadata_exists('post', $post->ID, 'slider_animation')) ? boolval(get_post_meta( $post->ID, 'slider_animation', true )) : true;
			$slider_animation_duration = (metadata_exists('post', $post->ID, 'slider_animation_duration')) ? intval(get_post_meta( $post->ID, 'slider_animation_duration', true )) : 1000;
			$slider_pause_animation = (metadata_exists('post', $post->ID, 'slider_pause_animation')) ? boolval(get_post_meta( $post->ID, 'slider_pause_animation', true )) : true;

			?><p class="post-attributes-label-wrapper slider-animation-label-wrapper">
				<input type="checkbox" name="slider_animation" id="slider_animation" value="1" <?php checked($slider_animation); ?>/>
				<label class="post-attributes-label" for="slider_animation"><span></span><?php esc_html_e('Enable Animation', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="post-attributes-label-wrapper slider-animation_duration-label-wrapper show_if_slider_animation" <?php if(!$slider_animation) { echo 'style="display:none;"'; } ?>><label class="post-attributes-label" for="slider_animation_duration"><?php esc_html_e('Duration of the slides', 'simple-pictures-slider'); ?></label></p>
			<input type="number" name="slider_animation_duration" id="slider_animation_duration" class="show_if_slider_animation large-text" value="<?php echo esc_attr($slider_animation_duration); ?>"/>
			<p class="description show_if_slider_animation" <?php if(!$slider_animation) { echo 'style="display:none;"'; } ?>><?php esc_html_e('Sets the duration (in milliseconds) of the slides display.', 'simple-pictures-slider'); ?></p>
			<p class="post-attributes-label-wrapper slider-pause_animation-label-wrapper show_if_slider_animation" <?php if(!$slider_animation) { echo 'style="display:none;"'; } ?>>
				<input type="checkbox" name="slider_pause_animation" id="slider_pause_animation" value="1" <?php checked($slider_pause_animation); ?>/>
				<label class="post-attributes-label" for="slider_pause_animation"><span></span><?php esc_html_e('Pause Animation on Hover', 'simple-pictures-slider'); ?></label>
			</p><?php
		}

		/**
		* Contenu de la metabox "Avancé"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function metaBoxAdvanced($post, $args = array()) {
			wp_nonce_field( 'sps_metabox_advanced_nonce', 'sps_metabox_advanced_nonce_value' );

			$slider_infinite = (metadata_exists('post', $post->ID, 'slider_infinite')) ? boolval(get_post_meta( $post->ID, 'slider_infinite', true )) : false;
			$slider_enable_mouse = (metadata_exists('post', $post->ID, 'slider_enable_mouse')) ? boolval(get_post_meta( $post->ID, 'slider_enable_mouse', true )) : false;

			?><p class="post-attributes-label-wrapper slider-infinite-label-wrapper">
				<input type="checkbox" name="slider_infinite" id="slider_infinite" value="1" <?php checked($slider_infinite); ?>/>
				<label class="post-attributes-label" for="slider_infinite"><span></span><?php esc_html_e('Infinite Slider', 'simple-pictures-slider'); ?></label>
			</p>
			<p class="post-attributes-label-wrapper slider-enable_mouse-label-wrapper">
				<input type="checkbox" name="slider_enable_mouse" id="slider_enable_mouse" value="1" <?php checked($slider_enable_mouse); ?>/>
				<label class="post-attributes-label" for="slider_enable_mouse"><span></span><?php esc_html_e('Enable Mouse & Touch Control', 'simple-pictures-slider'); ?></label>
			</p><?php
		}

		/**
		* Affichage du contenu du slider
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function saveNormalMetabox($post) {
			if (is_a($post, 'WP_Post') && $post->post_type === 'sps_slider') {
				global $wp_meta_boxes;
				
				/* Enregistrement temporaire des meta-box pour affichage plus tard */
				$screen = get_current_screen();
				$page = $screen->id;
				self::$_temp_meta_boxes_normal = $wp_meta_boxes[ $page ][ 'normal' ];
				$wp_meta_boxes[ $page ][ 'normal' ] = null;
			}
		}

		/**
		* Affichage du contenu du slider
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function displayContent($post) {
			if (is_a($post, 'WP_Post') && $post->post_type === 'sps_slider') {
				wp_nonce_field( 'sps_content_slider_nonce', 'sps_content_slider_nonce_value' );

				$slides = get_post_meta($post->ID, 'slider_slides', true);
				
				?><div id="postbox-container-3">
					<div id="sps_slider_images"><?php
						if (!empty($slides) && is_array($slides) && count($slides)>0) {
							foreach ($slides as $slide) {
								$img_id = get_post_meta(intval($slide), 'image_id', true);
								$background = (isset($img_id) && !empty($img_id)) ? wp_get_attachment_image_src(intval($img_id), 'full') : false;
								if (isset($img_id) && !empty($img_id) && isset($background) && is_array($background)) {
									?><div class="sps_slider_image">
										<input type="hidden" name="slides[]" value="<?php echo esc_attr(intval($slide)); ?>"/>
										<a href="<?php echo esc_url(add_query_arg(array('TB_iframe'=>true, 'width'=>1900, 'height'=>1000), get_edit_post_link(intval($slide)))); ?>" class="sps_slider_image_edit"></a>
										<button class="sps_slider_image_remove"></button>
										<div class="sps_slider_image_thumbnail" style="background-image:url('<?php echo esc_url($background[0]); ?>')"></div>
									</div><?php
								} else {
									?><div class="sps_slider_image fail">
										<input type="hidden" name="slides[]" value="<?php echo esc_attr(intval($slide)); ?>"/>
										<a href="<?php echo esc_url(add_query_arg(array('TB_iframe'=>true, 'width'=>1900, 'height'=>1000), get_edit_post_link(intval($slide)))); ?>" class="sps_slider_image_edit"></a>
										<button class="sps_slider_image_remove"></button>
										<div class="sps_slider_image_thumbnail"></div>
									</div><?php
								}
							}
						}
					?></div>
				</div><?php
			
				/* Recuperation des meta-box pour affichage */
				if (isset(self::$_temp_meta_boxes_normal)) {
					global $wp_meta_boxes;

					$screen = get_current_screen();
					$page = $screen->id;

					$wp_meta_boxes[ $page ][ 'normal' ] = self::$_temp_meta_boxes_normal;
					self::$_temp_meta_boxes_normal = null;
				}

				do_meta_boxes( null, 'normal', $post );
			}
		}

		/**
		* Change le placeholder du champ titre du slider
		*
		* @param string $title : Le placeholder
		* @param WP_Post $post : Le post en question
		* @return string $title : Le nouveau placeholder
		*/
		public function changeTitlePlaceholder($title, $post) {
			if ($post->post_type == 'sps_slider') return __( 'Enter slider name here', 'simple-pictures-slider' );
			return $title;
		}
		







		/**
		* Enregistrement du contenu du slider
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderContent($post_id) {
			if (!isset($_POST['sps_content_slider_nonce_value']) || !wp_verify_nonce($_POST['sps_content_slider_nonce_value'], 'sps_content_slider_nonce')) return;
			if (!current_user_can( 'edit_sps_slider', $post_id )) return;
			if (get_post_type($post_id)!='sps_slider') return;

			if ( isset($_POST['slides']) && is_array($_POST['slides']) ) {
				$slides = array_map(function($item) {
					return intval(sanitize_text_field($item));
				}, $_POST['slides']);
				update_post_meta($post_id, 'slider_slides', $slides);

				foreach ($slides as $slide_id) {
					$error = wp_update_post(array(
						'ID' => $slide_id,
						'post_status' => 'publish',
					), true);

					if (is_a($error, 'WP_Error')) error_log($error->get_error_message());
				}
			} else {
				update_post_meta($post_id, 'slider_slides', array());
			}
		}

		/**
		* Enregistrement des informations de la metabox "Dimensions"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderDimensions($post_id) {
			if (!$this->checkMetaboxCap('dimensions'))  return;
			if (!isset($_POST['sps_metabox_dimensions_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_dimensions_nonce_value'], 'sps_metabox_dimensions_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			$slides = get_post_meta($post_id, 'slider_slides', true);
			$slider_width = 0;
			$slider_height = 0;

			// Get first image width and height by default
			if (!empty($slides) && is_array($slides) && count($slides)>0) {
				foreach ($slides as $slide) {
					$img_id = get_post_meta(intval($slide), 'image_id', true);
					$img_metadata = (isset($img_id) && !empty($img_id)) ? wp_get_attachment_metadata(intval($img_id)) : false;
					if (is_array($img_metadata)) {
						if (isset($img_metadata['width'])) $slider_width = intval($img_metadata['width']);
						if (isset($img_metadata['height'])) $slider_height = intval($img_metadata['height']);
					}
				}
			}

			if ( isset($_POST['slider_width']) ) $slider_width = intval(sanitize_text_field($_POST['slider_width']));
			update_post_meta($post_id, 'slider_width', $slider_width);

			if ( isset($_POST['slider_height']) ) $slider_height = intval(sanitize_text_field($_POST['slider_height']));
			update_post_meta($post_id, 'slider_height', $slider_height);

			if ( isset($_POST['slider_dimensions_related']) && intval($_POST['slider_dimensions_related'])===1 ) {
				update_post_meta($post_id, 'slider_dimensions_related', true);
			} else {
				update_post_meta($post_id, 'slider_dimensions_related', false);
			}

			if ( isset($_POST['slider_width_percent']) && intval($_POST['slider_width_percent'])===1 ) {
				update_post_meta($post_id, 'slider_width_percent', true);
				
				$slider_width_ratio = false;

				if ( isset($_POST['slider_width_ratio']) && intval($_POST['slider_width_ratio'])===1 ) $slider_width_ratio = true;
				update_post_meta($post_id, 'slider_width_ratio', $slider_width_ratio);
			} else {
				update_post_meta($post_id, 'slider_width_percent', false);
			}
		}

		/**
		* Enregistrement des informations de la metabox "Transitions"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderTransitions($post_id) {
			if (!$this->checkMetaboxCap('transitions')) return;
			if (!isset($_POST['sps_metabox_transitions_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_transitions_nonce_value'], 'sps_metabox_transitions_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			$slider_transition = 'fade';
			$slider_transition_duration = 1000;
			$transitions = apply_filters('sps_transitions', array(
				'fade' => __('Fade', 'simple-pictures-slider'),
				'slideRight' => __('Slide Right to Left', 'simple-pictures-slider'),
				'slideDown' => __('Slide Bottom to Top', 'simple-pictures-slider'),
				'slideLeft' => __('Slide Left to Right', 'simple-pictures-slider'),
				'slideUp' => __('Slide Top to Bottom', 'simple-pictures-slider'),
			));

			if ( isset($_POST['slider_transition']) && in_array($_POST['slider_transition'], array_keys($transitions)) ) $slider_transition = strval(sanitize_text_field($_POST['slider_transition']));
			update_post_meta($post_id, 'slider_transition', $slider_transition);

			if ( isset($_POST['slider_transition_duration']) ) $slider_transition_duration = intval(sanitize_text_field($_POST['slider_transition_duration']));
			update_post_meta($post_id, 'slider_transition_duration', $slider_transition_duration);
		}

		/**
		* Enregistrement des informations de la metabox "Arrows"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderArrows($post_id) {
			if (!$this->checkMetaboxCap('arrows')) return;
			if (!isset($_POST['sps_metabox_arrows_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_arrows_nonce_value'], 'sps_metabox_arrows_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			if ( isset($_POST['slider_arrows']) && intval($_POST['slider_arrows'])===1 ) {
				update_post_meta($post_id, 'slider_arrows', true);
				
				$slider_arrows_position = 'outside_apart_center_left';

				if ( isset($_POST['slider_arrows_position_inside']) && intval($_POST['slider_arrows_position_inside'])===1 ) $slider_arrows_position = 'inside_apart_center_left';
				update_post_meta($post_id, 'slider_arrows_position', $slider_arrows_position);
			} else {
				update_post_meta($post_id, 'slider_arrows', false);
			}
		}

		/**
		* Enregistrement des informations de la metabox "Navigation Dots"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderNavigation($post_id) {
			if (!$this->checkMetaboxCap('navigation')) return;
			if (!isset($_POST['sps_metabox_navigation_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_navigation_nonce_value'], 'sps_metabox_navigation_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			if ( isset($_POST['slider_navigation']) && intval($_POST['slider_navigation'])===1 ) {
				update_post_meta($post_id, 'slider_navigation', true);
				
				$slider_navigation_position = 'outside_bottom_middle';

				if ( isset($_POST['slider_navigation_position_inside']) && intval($_POST['slider_navigation_position_inside'])===1 ) $slider_navigation_position = 'inside_bottom_middle';
				update_post_meta($post_id, 'slider_navigation_position', $slider_navigation_position);
			} else {
				update_post_meta($post_id, 'slider_navigation', false);
			}
		}

		/**
		* Enregistrement des informations de la metabox "Animation"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderAnimation($post_id) {
			if (!$this->checkMetaboxCap('animation')) return;
			if (!isset($_POST['sps_metabox_animation_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_animation_nonce_value'], 'sps_metabox_animation_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			if ( isset($_POST['slider_animation']) && intval($_POST['slider_animation'])===1 ) {
				update_post_meta($post_id, 'slider_animation', true);

				$slider_animation_duration = 4000;
				
				if ( isset($_POST['slider_animation_duration']) ) $slider_animation_duration = intval(sanitize_text_field($_POST['slider_animation_duration']));
				update_post_meta($post_id, 'slider_animation_duration', $slider_animation_duration);

				$slider_pause_animation = false;
				if ( isset($_POST['slider_pause_animation']) && intval($_POST['slider_pause_animation'])===1 ) $slider_pause_animation = true;
				update_post_meta($post_id, 'slider_pause_animation', $slider_pause_animation);
			} else {
				update_post_meta($post_id, 'slider_animation', false);
			}
		}

		/**
		* Enregistrement des informations de la metabox "Transitions"
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSliderAdvanced($post_id) {
			if (!$this->checkMetaboxCap('advanced')) return;
			if (!isset($_POST['sps_metabox_advanced_nonce_value']) || !wp_verify_nonce($_POST['sps_metabox_advanced_nonce_value'], 'sps_metabox_advanced_nonce')) return;
			if (get_post_type($post_id)!='sps_slider') return;

			if ( isset($_POST['slider_infinite']) && intval($_POST['slider_infinite'])===1 ) {
				update_post_meta($post_id, 'slider_infinite', true);
			} else {
				update_post_meta($post_id, 'slider_infinite', false);
			}

			if ( isset($_POST['slider_enable_mouse']) && intval($_POST['slider_enable_mouse'])===1 ) {
				update_post_meta($post_id, 'slider_enable_mouse', true);
			} else {
				update_post_meta($post_id, 'slider_enable_mouse', false);
			}
		}









		/**
		* À la suppression d'un slider, supprime les slides correspondantes
		*
		* @param int $postid : L'ID du post en question
		* @return void
		*/
		public function removeSlidePosts(int $postid, $post) {
			if ($post->post_type=='sps_slider') {
				$slides = get_posts(array(
					'post_type' => 'sps_slide',
					'numberposts' => -1,
					'meta_key' => 'slider_id',
					'meta_value' => $postid,
					'meta_compare' => '=',
					'meta_compare_key' => '=',
					'fields' => 'ids'
				));

				if (is_array($slides) && count($slides)>0) {
					foreach ($slides as $slide) {
						wp_delete_post($slide, true);
					}
				}
			}
		}
		







		/**
		* Lorsqu'une slide est demandé, active la constante IFRAME_REQUEST de WP et masque ainsi la barre d'admin
		*
		* @param WP_Screen $screen : L'ecran d'admin sur lequel on est
		* @return void
		*/
		public function slideSetIframe($screen) {
			if( is_object( $screen ) && 'sps_slide' == $screen->post_type && !defined('IFRAME_REQUEST') ) define( 'IFRAME_REQUEST', true );
		}

		/**
		* Lorsqu'une slide est demandé, masque le pied de l'admin
		*
		* @param string $text : Le texte affiché dans le pied de page de l'admin
		* @return string $text|void : Vide si l'on est dans l'affichage d'une slide
		*/
		public function slideIframeFooter($text) {
			$screen = get_current_screen();
			if( is_object( $screen ) && 'sps_slide' == $screen->post_type ) return '';
			return $text;
		}

		/**
		* Lorsqu'une slide est demandé, masque les options de l'ecran
		*
		* @param bool $show_screen : Si les options de l'écran doivent être affichées
		* @param WP_Screen $screen : L'ecran d'admin sur lequel on est
		* @return bool $show_screen|false : Faux si on est dans l'ecran d'une slide
		*/
		public function slideIframeHideScreenOptions($show_screen, $screen) {
			if( is_object( $screen ) && 'sps_slide' == $screen->post_type ) return false;
			return $show_screen;
		}

		/**
		* Lorsqu'une slide est demandé, affiche dans la colonne du milieu les metaboxes "normal"
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframeRemoveNotices() {
			$screen = get_current_screen();
			if ($screen->id == 'sps_slide') {
				remove_all_actions('admin_notices');
				remove_all_actions('all_admin_notices');
			}
		}

		/**
		* Lorsqu'une slide est demandé, affiche une checkbox pour passer en mode avancé
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframeCheckboxAdvanced($post) {
			if( is_object( $post ) && 'sps_slide' == $post->post_type ) {
				?><input type="checkbox" id="sps_slide_advanced_mode" hidden="hidden" style="display:none" autocomplete="off"/><?php
			}
		}

		/**
		* Lorsqu'une slide est demandé, affiche l'image de la slide en contenu principal
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframeContentPicture($post) {
			if( is_object( $post ) && 'sps_slide' == $post->post_type ) {
				wp_nonce_field( 'sps_slide_image_id_nonce', 'sps_slide_image_id_nonce_value' );

				$image_id = (metadata_exists('post', $post->ID, 'image_id')) ? intval(get_post_meta( $post->ID, 'image_id', true )) : 0;

				?><input type="hidden" name="image_id" id="sps_slide_image_id" value="<?php echo esc_attr($image_id); ?>"/><?php

				if (isset($image_id) && !empty($image_id) && $image_id>0) {
					?><div id="sps_slide_image_display"><?php
						echo wp_get_attachment_image($image_id, 'full');
					?></div><?php
				} else {
					?><div id="sps_slide_image_display" class="empty"><?php
						esc_html_e('No pictures found', 'simple-pictures-slider');
						?><button type="button" class="button button-hero button-large sps-slide-edit-picture"><?php esc_html_e('Add picture', 'simple-pictures-slider'); ?></button>
					</div><?php
				}
			}
		}

		/**
		* Lorsqu'une slide est demandé, affiche dans la colonne de droite les boutons de modification, les metaboxes "side" et un bouton d'enregistrement
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframePositionACFSide($post) {
			if( is_object( $post ) && 'sps_slide' == $post->post_type ) {
				global $wp_meta_boxes, $post_type;

				?><div class="sps-slide-primary-actions">
					<div>
						<button type="button" class="button button-secondary button-large sps-slide-edit-picture"><?php esc_html_e('Change picture', 'simple-pictures-slider'); ?></button>
						<input type="submit" name="save" id="publish" class="button button-primary button-large alignright" value="<?php esc_html_e('Save slide', 'simple-pictures-slider'); ?>">
					</div>
					<button type="submit" name="action" value="trash" id="sps-remove-slide" class="button-link button-link-delete"><?php esc_html_e('Remove slide', 'simple-pictures-slider'); ?></button>
				</div><?php
				
				do_meta_boxes( $post_type, 'side', $post );

				$side_screen = convert_to_screen( $post_type );
				$side_page = $side_screen->id;
				
				if (is_array($wp_meta_boxes[ $side_page ][ 'side' ])) {
					foreach ($wp_meta_boxes[ $side_page ][ 'side' ] as $priority) {
						foreach ($priority as $box) {
							if ( false !== $box && $box['title'] ) echo '<input type="submit" name="save" id="publish" class="button button-primary button-large alignright" value="'.esc_html__('Save slide', 'simple-pictures-slider').'">';
						}
					}
				}
				
				$wp_meta_boxes[ $side_page ][ 'side' ] = null;

				/* Enregistrement temporaire des meta-box pour affichage plus tard */
				$screen = get_current_screen();
				$page = $screen->id;
				self::$_temp_meta_boxes_normal = $wp_meta_boxes[ $page ][ 'normal' ];
				$wp_meta_boxes[ $page ][ 'normal' ] = null;

				ob_start();
			}
		}

		/**
		* Lorsqu'une slide est demandé, affiche un panneau avancé
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframeAdvancedPanel($post) {
			if( is_object( $post ) && 'sps_slide' == $post->post_type ) {
				ob_clean();

				if ($this->checkMetaboxCap('slide_advanced')) {
					?></div>
					<div id="postbox-container-2" class="postbox-container">
						<div id="screen-meta-links"><label for="sps_slide_advanced_mode" class="button show-settings"><?php esc_html_e('Advanced Mode', 'simple-pictures-slider'); ?></label></div>
						<div id="postbox-container-advanced-panel"><?php
						
							/* Recuperation des meta-box pour affichage */
							if (isset(self::$_temp_meta_boxes_normal)) {
								global $wp_meta_boxes;

								$screen = get_current_screen();
								$page = $screen->id;

								$wp_meta_boxes[ $page ][ 'normal' ] = self::$_temp_meta_boxes_normal;
								self::$_temp_meta_boxes_normal = null;
							}

							do_meta_boxes( null, 'normal', $post );

							?><div id="advanced-options"><?php
								$image_id = get_post_meta(intval($post->ID), 'image_id', true);
								if (isset($image_id) && !empty($image_id) && intval($image_id)>0) {
									$this->slideIframeAdvancedOptionBackground($post, $image_id);
									$this->slideIframeAdvancedOptionSrcset($post, $image_id);
									$this->slideIframeAdvancedOptionAlt($post, $image_id);
								}
							?></div><?php
				}
			}
		}

		/**
		* Lorsqu'une slide est demandé, affiche un label pour passer en mode avancé
		*
		* @param WP_Post $post : Le post demandé
		* @return void
		*/
		public function slideIframeCloseAdvancedPanel($post) {
			if( is_object( $post ) && 'sps_slide' == $post->post_type && $this->checkMetaboxCap('slide_advanced') ) {
				?></div><?php
			}
		}

		/**
		* Contenu de la metabox "Animation"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function slideIframeAdvancedOptionBackground($post, $img_id) {
			wp_nonce_field( 'sps_slide_advanced_background_nonce', 'sps_slide_advanced_background_nonce_value' );

			$background = (metadata_exists('post', $post->ID, 'background')) ? boolval(get_post_meta( $post->ID, 'background', true )) : false;

			?><p class="post-attributes-label-wrapper slide-background-label-wrapper">
				<input type="checkbox" name="background" id="background" value="1" <?php checked($background); ?>/>
				<label class="post-attributes-label" for="background"><span></span><?php esc_html_e('Set as Background Image', 'simple-pictures-slider'); ?></label>
			</p><?php
		}

		/**
		* Contenu de l'option "srcset"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function slideIframeAdvancedOptionSrcset($post, $img_id) {

			wp_nonce_field( 'sps_slide_advanced_srcset_nonce', 'sps_slide_advanced_srcset_nonce_value' );

			$background = (metadata_exists('post', $post->ID, 'background')) ? boolval(get_post_meta( $post->ID, 'background', true )) : false;

			?><p class="post-attributes-label-wrapper slide-alt-label-wrapper hide_if_background" <?php if($background) { echo 'style="display:none;"'; } ?>><span class="post-attributes-label"><?php esc_html_e('Picture sizes', 'simple-pictures-slider'); ?></span></p>
			<div class="hide_if_background" id="sps-srcset-sizes" <?php if($background) { echo 'style="display:none;"'; } ?>>
				<input type="checkbox" hidden="hidden" id="sps-srcset-new-size-displayed" style="display:none" autocomplete="off"/><?php
				
				$srcsets = (metadata_exists('post', $post->ID, 'srcset')) ? (array) get_post_meta( $post->ID, 'srcset', true ) : array();
				if (count($srcsets)<=0) {
					$srcsets[0] = apply_filters(
						'sps_srcset_default',
						array(
							'img'			=> 0,
							'size'			=> 'full',
							'descriptor'	=> '1x'
						)
					);
				}
				$srcsets = array_values($srcsets);

				$sizes = apply_filters(
					'image_size_names_choose',
					array(
						'thumbnail'	=> __( 'Thumbnail' ),
						'medium'	=> __( 'Medium' ),
						'large'		=> __( 'Large' ),
						'full'		=> __( 'Full Size' ),
					)
				);

				$descriptors = apply_filters(
					'sps_srcset_descriptors',
					array(
						'1x'	=> __( '1&times;', 'simple-pictures-slider' ),
						'2x'	=> __( '2&times;', 'simple-pictures-slider' ),
						'3x'	=> __( '3&times;', 'simple-pictures-slider' ),
						'4x'	=> __( '4&times;', 'simple-pictures-slider' ),
						'w'		=> __( 'w', 'simple-pictures-slider' ),
					)
				);

				$current_image_meta_data = wp_get_attachment_metadata(intval($img_id));

				foreach ($srcsets as $srcset_id => $srcset) {
					$current_image = (intval($srcset['img'])>0) ? !wp_attachment_is_image(intval($srcset['img'])) : true;
					?><div class="postbox sps-row-size" data-id-row="<?php echo esc_attr($srcset_id); ?>">
						<div class="sps-row-handle"><span>&bull;<br/>&bull;<br/>&bull;</span></div>
						<div class="sps-row-field inside">
							<input type="hidden" name="srcset[<?php echo esc_attr($srcset_id); ?>][img]" value="<?php echo esc_attr($srcset['img']); ?>"/>
							<p>
								<button type="submit" name="remove_srcset" value="<?php echo esc_attr($srcset_id); ?>" class="sps-srcset-remove-size button-link button-link-delete hide-if-no-js alignright"><?php esc_html_e('Delete', 'simple-pictures-slider'); ?></button>
								<span class="sps-srcset-custom-picture hide-if-no-js" <?php if ($current_image) echo 'style="display:none;"'; ?>>
									<span class="sps-srcset-thumbnail loading"></span>
									<strong><?php esc_html_e('Picture #', 'simple-pictures-slider'); ?><span class="sps-srcset-custom-picture-id"><?php echo esc_html($srcset['img']); ?></span></strong><br/>
									<button type="button" class="button-link sps-srcset-set-media"><?php esc_html_e('Edit Picture', 'simple-pictures-slider'); ?></button> (<button type="button" class="button-link button-link-delete sps-srcset-unset-media"><?php esc_html_e('Remove', 'simple-pictures-slider'); ?></button>)
								</span>
								<span class="sps-srcset-current-picture" <?php if (!$current_image) echo 'style="display:none;"'; ?>>
									<strong><?php esc_html_e('Current Picture', 'simple-pictures-slider'); ?></strong><br/>
									<button type="button" class="button-link sps-srcset-set-media hide-if-no-js"><?php esc_html_e('Edit Picture', 'simple-pictures-slider'); ?></button>
								</span>
							</p>
							<div class="sps-row-selects">
								<select name="srcset[<?php echo esc_attr($srcset_id); ?>][size]" class="sps-srcset-select-size"><?php
									$image_meta_data = ($current_image) ? $current_image_meta_data : wp_get_attachment_metadata(intval($srcset['img']));

									if (isset($image_meta_data['width']) && isset($image_meta_data['height'])) {
										?><option value="full" <?php selected($srcset['size'], 'full'); ?>><?php
											echo (isset($sizes['full'])) ? esc_html($sizes['full']) : 'full' ;
											printf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($image_meta_data['width']), esc_html($image_meta_data['height']));
										?></option><?php
									}
									
									if (isset($image_meta_data['sizes']) && is_array($image_meta_data['sizes']) && count($image_meta_data['sizes'])>0) {
										foreach ($image_meta_data['sizes'] as $image_size_key => $image_size_data) {
											?><option value="<?php echo esc_attr($image_size_key); ?>" <?php selected($srcset['size'], $image_size_key); ?>><?php
												echo (isset($sizes[$image_size_key])) ? esc_html($sizes[$image_size_key]) : esc_html($image_size_key) ;
												printf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($image_size_data['width']), esc_html($image_size_data['height']));
											?></option><?php
										}
									}
								?></select><?php

								if (isset($descriptors) && is_array($descriptors) && count($descriptors)>0) {
									?><select name="srcset[<?php echo esc_attr($srcset_id); ?>][descriptor]" class="sps-srcset-select-descriptor"><?php
									foreach ($descriptors as $descriptor_key => $descriptor_label) {
										?><option value="<?php echo esc_attr($descriptor_key); ?>" <?php selected($srcset['descriptor'], $descriptor_key); ?>><?php
											echo esc_html($descriptor_label);
										?></option><?php
									}
									?></select><?php
								} else {
									?><input type="hidden" name="srcset[<?php echo esc_attr($srcset_id); ?>][descriptor]" value="1x"/><?php
								}

							?></div>
							<label for="sps-srcset-new-size-displayed" class="button-secondary hide-if-js"><?php esc_html_e('Cancel', 'simple-pictures-slider'); ?></label>
							<button type="submit" class="button-primary hide-if-js"><?php esc_html_e( 'Save' ); ?></button>
						</div>
					</div><?php
				}

				$new_srcset_id = count($srcsets);

				$srcset_new_default = apply_filters(
					'sps_srcset_new_default',
					array(
						'img'			=> 0,
						'size'			=> 'full',
						'descriptor'	=> '1x'
					)
				);
				$current_new_image = (intval($srcset_new_default['img'])>0) ? !wp_attachment_is_image(intval($srcset_new_default['img'])) : true;

				?><div class="postbox sps-row-size" id="sps-srcset-new-size" data-id-row="<?php echo esc_attr($new_srcset_id); ?>">
					<div class="sps-row-handle"><span>&bull;<br/>&bull;<br/>&bull;</span></div>
					<div class="sps-row-field inside">
						<input type="hidden" name="srcset[<?php echo esc_attr($new_srcset_id); ?>][img]" value="<?php echo esc_attr($srcset_new_default['img']); ?>" disabled="disabled"/>
						<p>
							<button type="submit" name="remove_srcset" value="<?php echo esc_attr($new_srcset_id); ?>" class="sps-srcset-remove-size button-link button-link-delete hide-if-no-js alignright"><?php esc_html_e('Delete', 'simple-pictures-slider'); ?></button>
							<span class="sps-srcset-custom-picture hide-if-no-js" <?php if ($current_image) echo 'style="display:none;"'; ?>>
								<span class="sps-srcset-thumbnail loading"></span>
								<strong><?php esc_html_e('Picture #', 'simple-pictures-slider'); ?><span class="sps-srcset-custom-picture-id"><?php echo esc_html($srcset_new_default['img']); ?></span></strong><br/>
								<button type="button" class="button-link sps-srcset-set-media"><?php esc_html_e('Edit Picture', 'simple-pictures-slider'); ?></button> (<button type="button" class="button-link button-link-delete sps-srcset-unset-media"><?php esc_html_e('Remove', 'simple-pictures-slider'); ?></button>)
							</span>
							<span class="sps-srcset-current-picture" <?php if (!$current_image) echo 'style="display:none;"'; ?>>
								<strong><?php esc_html_e('Current Picture', 'simple-pictures-slider'); ?></strong><br/>
								<button type="button" class="button-link sps-srcset-set-media hide-if-no-js"><?php esc_html_e('Edit Picture', 'simple-pictures-slider'); ?></button>
							</span>
						</p>
						<div class="sps-row-selects">
							<select name="srcset[<?php echo esc_attr($new_srcset_id); ?>][size]" class="sps-srcset-select-size" disabled="disabled"><?php
								$image_meta_data = ($current_image) ? $current_image_meta_data : wp_get_attachment_metadata(intval($srcset['img']));

								if (isset($image_meta_data['width']) && isset($image_meta_data['height'])) {
									?><option value="full" <?php selected($srcset_new_default['size'], 'full'); ?>><?php
										echo (isset($sizes['full'])) ? esc_html($sizes['full']) : 'full' ;
										printf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($image_meta_data['width']), esc_html($image_meta_data['height']));
									?></option><?php
								}
								
								if (isset($image_meta_data['sizes']) && is_array($image_meta_data['sizes']) && count($image_meta_data['sizes'])>0) {
									foreach ($image_meta_data['sizes'] as $image_size_key => $image_size_data) {
										?><option value="<?php echo esc_attr($image_size_key); ?>" <?php selected($srcset_new_default['size'], $image_size_key); ?>><?php
											echo (isset($sizes[$image_size_key])) ? esc_html($sizes[$image_size_key]) : esc_html($image_size_key) ;
											printf(__(' (%s &times; %s px)', 'simple-pictures-slider'), esc_html($image_size_data['width']), esc_html($image_size_data['height']));
										?></option><?php
									}
								}
							?></select><?php

							if (isset($descriptors) && is_array($descriptors) && count($descriptors)>0) {
								?><select name="srcset[<?php echo esc_attr($new_srcset_id); ?>][descriptor]" class="sps-srcset-select-descriptor" disabled="disabled"><?php
								foreach ($descriptors as $descriptor_key => $descriptor_label) {
									?><option value="<?php echo esc_attr($descriptor_key); ?>" <?php selected($srcset_new_default['descriptor'], $descriptor_key); ?>><?php
										echo esc_html($descriptor_label);
									?></option><?php
								}
								?></select><?php
							} else {
								?><input type="hidden" name="srcset[<?php echo esc_attr($new_srcset_id); ?>][descriptor]" value="1x" disabled="disabled"/><?php
							}

						?></div>
						<label for="sps-srcset-new-size-displayed" class="button-secondary hide-if-js"><?php esc_html_e('Cancel', 'simple-pictures-slider'); ?></label>
						<button type="submit" class="button-primary hide-if-js"><?php esc_html_e( 'Save' ); ?></button>
					</div>
				</div>

				<label for="sps-srcset-new-size-displayed" id="sps-srcset-add-size" class="button-secondary"><?php esc_html_e('Add a new picture size', 'simple-pictures-slider'); ?></label>
				<label class="button-secondary hide-if-js" disabled="disabled"><?php esc_html_e('Add a new picture size', 'simple-pictures-slider'); ?></label>
			</div><?php
		}

		/**
		* Contenu de la metabox "Animation"
		*
		* @param WP_Post $post : Le post en question
		* @return void
		*/
		public function slideIframeAdvancedOptionAlt($post, $img_id) {
			wp_nonce_field( 'sps_slide_advanced_alt_nonce', 'sps_slide_advanced_alt_nonce_value' );

			$alt = (metadata_exists('post', $post->ID, 'alt')) ? strval(get_post_meta( $post->ID, 'alt', true )) : '';

			?><p class="post-attributes-label-wrapper slide-alt-label-wrapper"><label class="post-attributes-label" for="slide_alt"><?php esc_html_e('Alternative text', 'simple-pictures-slider'); ?></label></p>
			<input type="text" name="alt" id="slide_alt" class="large-text" value="<?php echo esc_attr($alt); ?>"/><?php
		}
		







		/**
		* Enregistrement du contenu d'une slide
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSlideContent($post_id) {
			if (!isset($_POST['sps_slide_image_id_nonce_value']) || !wp_verify_nonce($_POST['sps_slide_image_id_nonce_value'], 'sps_slide_image_id_nonce')) return;
			if (!current_user_can( 'edit_sps_slider', $post_id )) return;
			if (get_post_type($post_id)!='sps_slide') return;

			$image_id = 0;

			if ( isset($_POST['image_id']) ) $image_id = intval(sanitize_text_field($_POST['image_id']));
			update_post_meta($post_id, 'image_id', $image_id);
		}

		/**
		* Enregistrement des informations du background-image de la slide
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSlideBackground($post_id) {
			if (!$this->checkMetaboxCap('slide_advanced')) return;
			if (!isset($_POST['sps_slide_advanced_background_nonce_value']) || !wp_verify_nonce($_POST['sps_slide_advanced_background_nonce_value'], 'sps_slide_advanced_background_nonce')) return;
			if (get_post_type($post_id)!='sps_slide') return;

			if ( isset($_POST['background']) && intval($_POST['background'])===1 ) {
				update_post_meta($post_id, 'background', true);
			} else {
				update_post_meta($post_id, 'background', false);
			}
		}

		/**
		* Enregistrement des informations des srcset de la slide
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSlideSrcset($post_id) {
			if (!$this->checkMetaboxCap('slide_advanced')) return;
			if (!isset($_POST['sps_slide_advanced_srcset_nonce_value']) || !wp_verify_nonce($_POST['sps_slide_advanced_srcset_nonce_value'], 'sps_slide_advanced_srcset_nonce')) return;
			if (get_post_type($post_id)!='sps_slide') return;
			if ( isset($_POST['background']) && intval($_POST['background'])==1 ) return;

			$slide_srcset = array();

			$descriptors = apply_filters(
				'sps_srcset_descriptors',
				array(
					'1x'	=> __( '1&times;', 'simple-pictures-slider' ),
					'2x'	=> __( '2&times;', 'simple-pictures-slider' ),
					'3x'	=> __( '3&times;', 'simple-pictures-slider' ),
					'4x'	=> __( '4&times;', 'simple-pictures-slider' ),
					'w'		=> __( 'w', 'simple-pictures-slider' ),
				)
			);

			if ( isset($_POST['srcset']) && is_array($_POST['srcset']) ) {
				$slide_srcset = array_map(function($item) use ($post_id, $descriptors) {

					/* ID de l'image du srcset */
					if (isset($item['img']) && intval(sanitize_text_field($item['img']))>0 && wp_attachment_is_image(intval(sanitize_text_field($item['img'])))) {
						$img_id = intval(sanitize_text_field($item['img']));
					} else if (metadata_exists('post', $post_id, 'image_id') && wp_attachment_is_image(intval(get_post_meta( $post_id, 'image_id', true )))) {
						$img_id = intval(get_post_meta( $post_id, 'image_id', true ));
					} else {
						$img_id = 0;
					}

					if ($img_id<=0) return false;

					/* ID de l'image enregistré (0 si l'image est l'image courante) */
					$item_img = (metadata_exists('post', $post_id, 'image_id') && intval(get_post_meta( $post_id, 'image_id', true ))==$img_id) ? 0 : $img_id;

					/* Tailles disponible pour l'image */
					$item_meta_data = wp_get_attachment_metadata($img_id);
					$item_sizes = array();
					if (isset($item_meta_data['width']) && isset($item_meta_data['height'])) $item_sizes[] = 'full';
					if (isset($item_meta_data['sizes']) && is_array($item_meta_data['sizes']) && count($item_meta_data['sizes'])>0) {
						foreach ($item_meta_data['sizes'] as $image_size_key => $image_size_data) $item_sizes[] = esc_attr($image_size_key);
					}

					if (isset($item['size']) && in_array(strval(sanitize_text_field($item['size'])), $item_sizes)) {
						$item_size = strval(sanitize_text_field($item['size']));
					} else if (in_array('full', $item_sizes)) {
						$item_size = 'full';
					} else if (reset($item_sizes)!==false) {
						$item_size = reset($item_sizes);
					} else {
						$item_size = 'full';
					}
					
					if (isset($item['descriptor']) && in_array(strval(sanitize_text_field($item['descriptor'])), array_keys($descriptors))) {
						$item_descriptor = strval(sanitize_text_field($item['descriptor']));
					} else if (reset(array_keys($descriptors))!==false) {
						$item_descriptor = reset(array_keys($descriptors));
					} else {
						$item_descriptor = '1x';
					}

					if ($item_descriptor=='4x') return false;

					return array(
						'img'			=> $item_img,
						'size'			=> $item_size,
						'descriptor'	=> $item_descriptor,
					);
				}, $_POST['srcset']);
			}

			$slide_srcset = array_filter($slide_srcset);
			
			update_post_meta($post_id, 'srcset', $slide_srcset);
		}

		/**
		* Enregistrement des informations du texte alternatif de la slide
		*
		* @param int $post_id : L'ID du post en question
		* @return void
		*/
		public function saveSlideAlt($post_id) {
			if (!$this->checkMetaboxCap('slide_advanced')) return;
			if (!isset($_POST['sps_slide_advanced_alt_nonce_value']) || !wp_verify_nonce($_POST['sps_slide_advanced_alt_nonce_value'], 'sps_slide_advanced_alt_nonce')) return;
			if (get_post_type($post_id)!='sps_slide') return;

			$slide_alt = '';

			if ( isset($_POST['alt']) ) $slide_alt = strval(sanitize_text_field($_POST['alt']));
			update_post_meta($post_id, 'alt', $slide_alt);
		}
		







		/**
		* Créer un post slide lors de l'ajout d'une slide dans un slider et renvoie l'image et l'id du slide
		*
		* @param void
		* @return void
		*/
		public function createSlidePostAjax() {
			if ( ! wp_verify_nonce( $_POST['nonce'], 'sps_create_slide_post_nonce_value' ) ) wp_send_json_error();

			if (isset($_POST['post_id']) && isset($_POST['image_id'])) {
				$slider_id = intval(sanitize_text_field($_POST['post_id']));
				$image_id = intval(sanitize_text_field($_POST['image_id']));
				$image_meta = wp_get_attachment_metadata($image_id);
				$image_src = wp_get_attachment_image_src($image_id, 'full');
				if (is_array($image_meta) && is_int($slider_id) && $slider_id>0 && is_int($image_id) && $image_id>0) {
					$post_id = wp_insert_post(array(
						'post_type' => 'sps_slide',
						'post_status' => 'draft',
						'post_content' => 'slide',
						'post_title' => 'Slide #'.$image_id.' of slider #'.$slider_id,
						'post_excerpt' => 'slide',
						'meta_input' => array(
							'slider_id' => $slider_id,
							'image_id' => $image_id
						)
					), true);

					if (is_int($post_id) && $post_id>0) {
						$upload_dir = wp_upload_dir();
						if (is_ssl()) { $upload_dir = str_replace( 'http://', 'https://', $upload_dir ); }

						$return = array(
							'image_src' => esc_url($upload_dir['baseurl'] . '/' . $image_meta['file'] ),
							'image_width' => $image_meta['width'],
							'image_height' => $image_meta['height'],
							'post_id' => $post_id,
							'post_edit' => add_query_arg(array('TB_iframe'=>true, 'width'=>600, 'height'=>550), get_edit_post_link($post_id)),
						);

						wp_send_json_success( $return );
					}
				} else {
					wp_send_json_error();
				}
			} else {
				wp_send_json_error();
			}
		}

		/**
		* Supprime un post slide lors de la suppression d'une slide dans un slider et renvoie un succes ou une erreur
		*
		* @param void
		* @return void
		*/
		public function removeSlidePostAjax() {
			if ( ! wp_verify_nonce( $_POST['nonce'], 'sps_remove_slide_post_nonce_value' ) ) wp_send_json_error();

			if (isset($_POST['post_id'])) {
				$deleted = wp_delete_post(intval(sanitize_text_field($_POST['post_id'])), true);
				if (isset($deleted) && $deleted) {
					wp_send_json_success();
				} else {
					wp_send_json_error();
				}
			} else {
				wp_send_json_error();
			}
		}

		/**
		* Execute la fonction wp_get_attachment_image pour JS
		*
		* @param void
		* @return void
		*/
		public function getAttachmentImageAjax() {
			if ( ! wp_verify_nonce( $_POST['nonce'], 'sps_get_attachment_image_nonce_value' ) ) wp_send_json_error();

			if (isset($_POST['attachment_id'])) {
				wp_send_json_success(wp_get_attachment_image(intval(sanitize_text_field($_POST['attachment_id'])), 'full'));
			} else {
				wp_send_json_error();
			}
		}
		







		/**
		* Ajoute des nouvelles règle à ACF pour permettre l'ajout de champs aux slides d'un slider
		*
		* @param void
		* @return void
		*/
		public function addRulesToACF() {
			if (class_exists('\ACF_Location') && function_exists('acf_register_location_type')) acf_register_location_type( '\SPS\Admin\SPSLocationACF' );
		}

		/**
		* Change l'icone dans la liste des groupe de champs concernant les slides d'un slider
		*
		* @param void
		* @return void
		*/
		public function changeIconTypeToACF($object, $object_type, $object_subtype) {
			if ($object_subtype == 'sps_slider') return (object) array('type' => $object_type, 'subtype' => $object_subtype, 'name' => $object_type.'/'.$object_subtype, 'label' => __( 'Sliders', 'simple-pictures-slider' ), 'icon' => 'dashicons-images-alt2');
			return $object;
		}
		







		/**
		* Modifie les tailles quart et demi à générer
		*
		* @param void
		* @return void
		*/
		public function resizeImageHalfAndQuarter($sizes, $meta, $id) {

			$sizes['sps_half'] = array(
				'width' => ceil( $meta['width'] / 2 ),
				'height' => ceil( $meta['height'] / 2 ),
				'crop' => false
			);
			
			$sizes['sps_quarter'] = array(
				'width' => ceil( $meta['width'] / 4 ),
				'height' => ceil( $meta['height'] / 4 ),
				'crop' => false
			);

		    return $sizes;
		}

		/**
		* Nomme les tailles d'images demi et quart
		*
		* @param void
		* @return void
		*/
		public function labelizeImageHalfAndQuarter($sizes) {
			return array_merge(
				$sizes,
				array(
					'sps_half' => __('Half size', 'simple-pictures-slider'),
					'sps_quarter' => __('Quarter size', 'simple-pictures-slider')
				)
			);
		}

		/**
		* Indique les tailles d'images demi et quart lors du renvoi au JS
		*
		* @param void
		* @return void
		*/
		public function setImageHalfAndQuarterDimensionsForJS($response, $attachment, $meta) {
			if (isset($response['sizes']['sps_half'])) {
				$response['sizes']['sps_half']['width'] = $meta['sizes']['sps_half']['width'];
				$response['sizes']['sps_half']['height'] = $meta['sizes']['sps_half']['height'];
			}
			if (isset($response['sizes']['sps_quarter'])) {
				$response['sizes']['sps_quarter']['width'] = $meta['sizes']['sps_quarter']['width'];
				$response['sizes']['sps_quarter']['height'] = $meta['sizes']['sps_quarter']['height'];
			}

			return $response;
		}

	}

	SPSAdmin::getInstance();
}


if (class_exists('\ACF_Location') && !class_exists('\SPS\Admin\SPSLocationACF')) {
	class SPSLocationACF extends \ACF_Location {
		function initialize() {
			$this->name = 'sps_slider';
			$this->label = __( 'Slider', 'simple-pictures-slider' );
			$this->category = 'forms';
			$this->object_type = 'comment';
		}

		function match($rule, $screen, $field_group) {
			return (isset($screen['post_type']) && $screen['post_type']=='sps_slide' && $rule['param']=='sps_slider' && ( (intval(get_post_meta($screen['post_id'], 'slider_id', true))==intval($rule['value']) && $rule['operator']=='==') || (intval(get_post_meta($screen['post_id'], 'slider_id', true))!=intval($rule['value']) && $rule['operator']=='!=') ));
		}

		function get_values($rule) {
			$posts = get_posts(array(
				'numberposts' => -1,
				'post_type' => 'sps_slider'
			));
			$out = array();
			foreach ($posts as $post) $out[$post->ID] = $post->post_title;

			return $out;
		}

		function get_object_subtype( $rule ) {
			return 'sps_slider';
		}
	}
}
