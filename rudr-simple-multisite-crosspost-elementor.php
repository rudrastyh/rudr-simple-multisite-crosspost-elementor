<?php
/**
 * Plugin Name: Simple Multisite Crossposting – Elementor
 * Plugin URI: https://rudrastyh.com/support/elementor-multisite
 * Description: Adds better compatibility with Elementor and Elementor PRO.
 * Network: true
 * Author: Misha Rudrastyh
 * Author URI: https://rudrastyh.com
 * Version: 1.5
 */

class Rudr_SMC_Elementor {

	function __construct() {
		// in elementor we are working with one specific meta key mostly
		add_filter( 'rudr_pre_crosspost_meta', array( $this, 'process' ), 25, 3 );

		// on plugin activation let's add elementor post type to support post types
		register_activation_hook( __FILE__, array( $this, 'add_templates_support' ) );
	}


	private function loop_elements( $elements, $new_blog_id ) {

		foreach( $elements as &$element ) {
			// process our specific elements
			if( 'widget' === $element[ 'elType' ] ) {

				// gallery one
				if( 'gallery' === $element[ 'widgetType' ] && isset( $element[ 'settings' ][ 'gallery' ] ) ) {
					$element = $this->process_gallery_element( $element, $new_blog_id );
					continue;
				}

				// image one
				if( 'image' === $element[ 'widgetType' ] && isset( $element[ 'settings' ][ 'image' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'image' ][ 'id' ] ) ) {
					$element = $this->process_image_element( $element, $new_blog_id );
					continue;
				}

				// icon once
				if( in_array( $element[ 'widgetType' ], array( 'icon', 'icon-box' ) ) && isset( $element[ 'settings' ][ 'selected_icon' ][ 'value' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'selected_icon' ][ 'value' ][ 'id' ] ) ) {
					$element[ 'settings' ][ 'selected_icon' ] = $this->process_icon_in_element( $element[ 'settings' ][ 'selected_icon' ], $new_blog_id );
					continue;
				}

				// flipbox
				if( 'flip-box' === $element[ 'widgetType' ] ) {
					if( isset( $element[ 'settings' ][ 'selected_icon' ][ 'value' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'selected_icon' ][ 'value' ][ 'id' ] ) ) {
						$element[ 'settings' ][ 'selected_icon' ] = $this->process_icon_in_element( $element[ 'settings' ][ 'selected_icon' ], $new_blog_id );
					}
					if( isset( $element[ 'settings' ][ 'background_a_image' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'background_a_image' ][ 'id' ] ) ) {
						$element[ 'settings' ][ 'background_a_image' ] = $this->process_background_image_in_element( $element[ 'settings' ][ 'background_a_image' ], $new_blog_id );
					}
					continue;
				}

				// social icons
				if( 'social-icons' === $element[ 'widgetType' ] ) {
					if( isset( $element[ 'settings' ][ 'social_icon_list' ] ) && is_array( $element[ 'settings' ][ 'social_icon_list' ] ) ) {
						for( $i = 0; $i < count( $element[ 'settings' ][ 'social_icon_list' ] ); $i++ ) {
							// only for custom icons
							if( isset( $element[ 'settings' ][ 'social_icon_list' ][$i][ 'social_icon' ][ 'value' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'social_icon_list' ][$i][ 'social_icon' ][ 'value' ][ 'id' ] ) ) {
								$element[ 'settings' ][ 'social_icon_list' ][$i][ 'social_icon' ] = $this->process_icon_in_element( $element[ 'settings' ][ 'social_icon_list' ][$i][ 'social_icon' ], $new_blog_id );
							}
						}
					}
					continue;
				}

				// template
				if( 'template' === $element[ 'widgetType' ] && isset( $element[ 'settings' ][ 'template_id' ] ) ) {
					// just replace if it is crossposted to a new blog
					if( $crossposted_template_id = Rudr_Simple_Multisite_Crosspost::is_crossposted( $element[ 'settings' ][ 'template_id' ], $new_blog_id ) ) {
						$element[ 'settings' ][ 'template_id' ] = $crossposted_template_id;
					}
					continue;
				}

				// global widget
				if( 'global' === $element[ 'widgetType' ] && isset( $element[ 'templateID' ] ) ) {
					// just replace if it is crossposted to a new blog
					if( $crossposted_template_id = Rudr_Simple_Multisite_Crosspost::is_crossposted( $element[ 'templateID' ], $new_blog_id ) ) {
						$element[ 'templateID' ] = (int) $crossposted_template_id;
					}
					continue;
				}

				/***********************/
				/*   Essential Addons  */
				/***********************/
				if( 'eael-feature-list' === $element[ 'widgetType' ] ) {

					// icons
					if( isset( $element[ 'settings' ][ 'eael_feature_list' ] ) && is_array( $element[ 'settings' ][ 'eael_feature_list' ] ) ) {
						for( $i = 0; $i < count( $element[ 'settings' ][ 'eael_feature_list' ] ); $i++ ) {
							if( isset( $element[ 'settings' ][ 'eael_feature_list' ][$i][ 'eael_feature_list_icon_new' ][ 'value' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'eael_feature_list' ][$i][ 'eael_feature_list_icon_new' ][ 'value' ][ 'id' ] ) ) {
								$element[ 'settings' ][ 'eael_feature_list' ][$i][ 'eael_feature_list_icon_new' ] = $this->process_icon_in_element( $element[ 'settings' ][ 'eael_feature_list' ][$i][ 'eael_feature_list_icon_new' ], $new_blog_id );
							}
						}
					}

					// backgrounds
					if( isset( $element[ 'settings' ][ '_background_image' ][ 'url' ] ) && isset( $element[ 'settings' ][ '_background_image' ][ 'id' ] ) ) {
						$element[ 'settings' ][ '_background_image' ] = $this->process_background_image_in_element( $element[ 'settings' ][ '_background_image' ], $new_blog_id );
					}
					continue;
				}

			}

			// column and section backgrounds
			// or containers in new Elementor versions
			if( in_array( $element[ 'elType' ], array( 'column', 'section', 'container' ) ) ) {
				// background images
				if( isset( $element[ 'settings' ][ 'background_image' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'background_image' ][ 'id' ] ) ) {
					$element[ 'settings' ][ 'background_image' ] = $this->process_background_image_in_element( $element[ 'settings' ][ 'background_image' ], $new_blog_id );
				}
				// background image overlays
				if( isset( $element[ 'settings' ][ 'background_overlay_image' ][ 'url' ] ) && isset( $element[ 'settings' ][ 'background_overlay_image' ][ 'id' ] ) ) {
					$element[ 'settings' ][ 'background_overlay_image' ] = $this->process_background_image_in_element( $element[ 'settings' ][ 'background_overlay_image' ], $new_blog_id );
				}
			}

			// copying global stuff
			if( isset( $element[ 'settings' ][ '__globals__' ] ) && $element[ 'settings' ][ '__globals__' ] ) {
				$element[ 'settings' ] = $this->unglobalize( $element[ 'settings' ] );
			}

			// loop child elements if any
			if( isset( $element[ 'elements' ] ) ) {
				$element[ 'elements' ] = $this->loop_elements( $element[ 'elements' ], $new_blog_id );
			}

		}

		return $elements;

	}


	public function process( $meta_value, $meta_key, $object_id ) {

		// we do nothing if it is not Elementor JSON
		if( '_elementor_data' !== $meta_key ) {
			return $meta_value;
		}

		// we are currently on a new blog by the way, let's remember it and switch back
		$new_blog_id = get_current_blog_id();
		restore_current_blog();

		// now we convert the meta key json into an array of elements
		$elements = json_decode( $meta_value, true );
		// process the elements
		$elements = $this->loop_elements( $elements, $new_blog_id );
		// go back
		switch_to_blog( $new_blog_id );
		//echo '<pre>';print_r( $elements );exit;
		return json_encode( $elements );

	}


	/* Elements processing functions */
	private function process_gallery_element( $element, $new_blog_id ) {
		// just in case additional check
		if( is_array( $element[ 'settings' ][ 'gallery' ] ) ) {
			$gallery = array();
			foreach( $element[ 'settings' ][ 'gallery' ] as $item ) {

				$attachment_data = Rudr_Simple_Multisite_Crosspost::prepare_attachment_data( $item[ 'id' ] );

				// here we do nothing if we don't have all data
				if( ! $attachment_data ) {
					continue;
				}
				switch_to_blog( $new_blog_id );
				$gallery[] = Rudr_Simple_Multisite_Crosspost::maybe_copy_image( $attachment_data );
				restore_current_blog();
			}
			$element[ 'settings' ][ 'gallery' ] = $gallery;
		}
		//print_r( $gallery );exit;
		return $element;
	}


	private function process_image_element( $element, $new_blog_id ) {

		// our goal here is get an attachment_id
		$attachment_id = $element[ 'settings' ][ 'image' ][ 'id' ];
		// we need some attachment data
		$attachment_data = Rudr_Simple_Multisite_Crosspost::prepare_attachment_data( $attachment_id );

		if( $attachment_data ) {
			switch_to_blog( $new_blog_id );
			$upload = Rudr_Simple_Multisite_Crosspost::maybe_copy_image( $attachment_data );
			restore_current_blog();
			if( $upload ) {
				$element[ 'settings' ][ 'image' ][ 'id' ] = $upload[ 'id' ];
				$element[ 'settings' ][ 'image' ][ 'url' ] = $upload[ 'url' ];
			}
		}

		return $element;

	}


	private function process_background_image_in_element( $element_bg_image, $new_blog_id ) {

		// our goal here is get an attachment_id
		$attachment_id = $element_bg_image[ 'id' ];
		// we need some attachment data
		$attachment_data = Rudr_Simple_Multisite_Crosspost::prepare_attachment_data( $attachment_id );

		if( $attachment_data ) {
			switch_to_blog( $new_blog_id );
			$upload = Rudr_Simple_Multisite_Crosspost::maybe_copy_image( $attachment_data );
			restore_current_blog();
			if( $upload ) {
				$element_bg_image[ 'id' ] = $upload[ 'id' ];
				$element_bg_image[ 'url' ] = $upload[ 'url' ];
			}
		}
		return $element_bg_image;

	}


	private function process_icon_in_element( $element_icon, $new_blog_id ) {

		// our goal here is get an attachment_id
		$attachment_id = $element_icon[ 'value' ][ 'id' ];
		// we need some attachment data
		$attachment_data = Rudr_Simple_Multisite_Crosspost::prepare_attachment_data( $attachment_id );

		if( $attachment_data ) {
			switch_to_blog( $new_blog_id );
			$upload = Rudr_Simple_Multisite_Crosspost::maybe_copy_image( $attachment_data );
			restore_current_blog();
			if( $upload ) {
				$element_icon[ 'value' ][ 'id' ] = $upload[ 'id' ];
				$element_icon[ 'value' ][ 'url' ] = $upload[ 'url' ];
			}
		}

		return $element_icon;

	}


	public function add_templates_support() {

		$post_type_name = 'elementor_library';

		$allowed_post_types = get_site_option( 'rudr_smc_post_types', array() );
		// if this array is set but it doesn't include our elementor library
		if( $allowed_post_types && ! in_array( $post_type_name, $allowed_post_types ) ) {
			$allowed_post_types[] = $post_type_name;
			update_site_option( 'rudr_smc_post_types', $allowed_post_types );
		}

	}


	private function unglobalize( $element_settings ) {
		// double check globals
		if( empty( $element_settings[ '__globals__' ] ) || ! is_array( $element_settings[ '__globals__' ] ) ) {
			return $element_settings;
		}

		// kit
		$kit_active_id = get_option( 'elementor_active_kit' );
		$kit = get_post_meta( $kit_active_id, '_elementor_page_settings', true );
		$kit_system_colors = $kit[ 'system_colors' ] ? wp_list_pluck( $kit[ 'system_colors' ], '_id' ) : array();
		$kit_custom_colors = $kit[ 'custom_colors' ] ? wp_list_pluck( $kit[ 'custom_colors' ], 'color', '_id' ) : array();
		$kit_system_typography = $kit[ 'system_typography' ] ? wp_list_pluck( $kit[ 'system_typography' ], '_id' ) : array();
		$kit_custom_typography = $kit[ 'custom_typography' ] ? array_combine( array_column( $kit[ 'custom_typography' ], '_id' ), $kit[ 'custom_typography' ] ) : array();

		$global_styles = $element_settings[ '__globals__' ];

		// loop through global styles
		foreach( $global_styles as $key => $global_style ) {

			$is_color = ( false !== strpos( $global_style, 'colors?' ) ) ? true : false;
			$is_typography = ( false !== strpos( $global_style, 'typography?' ) ) ? true : false;
			$id = explode( '?id=', $global_style ); $id = $id[1];

			if( $is_color ) {
				// skip default color styles
				if( in_array( $id, $kit_system_colors ) ) {
					continue;
				}
				// unglobalize custom colors
				if( isset( $kit_custom_colors[ $id ] ) && $kit_custom_colors[ $id ] ) {
					unset( $element_settings[ '__globals__' ][ $key ] );
					$element_settings[ $key ] = $kit_custom_colors[ $id ];
				}
			}

			if( $is_typography ) {
				// skip default typography styles
				if( in_array( $id, $kit_system_typography ) ) {
					continue;
				}
				// unglobalize custom typography
				if( isset( $kit_custom_typography[ $id ] ) && $kit_custom_typography[ $id ] ) {
					unset( $element_settings[ '__globals__' ][ 'typography_typography' ] );
					$element_settings[ 'typography_typography' ] = 'custom';
					foreach( $kit_custom_typography[ $id ] as $typography_key => $typography_value ) {
						if( in_array( $typography_key, array( '_id', 'title' ) ) ) {
							continue;
						}
						$element_settings[ $typography_key ] = $typography_value;
					}
				}
			}
		}
		return $element_settings;

	}

}

new Rudr_SMC_Elementor();
