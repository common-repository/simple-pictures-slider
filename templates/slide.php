<?php
/**
* Template for Slide
* This template can be overwritten by duplicate the file in your theme /sps/slide.php
*
* @args
*/

?><div class="sps_slide"><?php

	do_action( 'sps_slider_before_img', $post_id, $inc, $id );

	if ($as_background) {

		$attachment_url = wp_get_attachment_image_src($id, $size, false);
		if (is_array($attachment_url)) {
			?><div class="sps_img" style="background-image:url('<?php echo esc_url($attachment_url[0]); ?>')"></div><?php
		}

	} else {

		if (is_array($srcset)) {
			$srcset = array_map(function($i) use ($id) {
				$srcset_img = wp_get_attachment_image_src( ($i['img']>0 ? $i['img'] : $id), $i['size'] );
				
				if ( $srcset_img ) {
					list( $srcset_src, $srcset_width, $srcset_height ) = $srcset_img;
					$srcset_descriptor = $i['descriptor']=='w' ? $srcset_width . $i['descriptor'] : $i['descriptor'];
					return $srcset_src . ' ' . $srcset_descriptor;
				}

				return false;
			}, $srcset);
		} else {
			$srcset = array();
		}

		$srcset = array_filter($srcset);

		$image = wp_get_attachment_image_src( $id, $size, false );

		if ( $image ) {
			list( $src, $width, $height ) = $image;

			$size_class = $size;

			if ( is_array( $size_class ) ) {
				$size_class = implode( 'x', $size_class );
			}

			$default_attr = array(
				'src'      => $src,
				'class'    => "sps_img sps_img_$size_class",
				'alt'      => trim( strip_tags( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ),
				'decoding' => 'async',
			);

			$attr = array(
				'alt' => $alt,
				'srcset' => implode(',', $srcset),
				'width' => $width,
				'height' => $height,
			);

			$attr = wp_parse_args( $attr, $default_attr );

			// Add `loading` attribute.
			if ( wp_lazy_loading_enabled( 'img', 'wp_get_attachment_image' ) ) {
				$attr = array_merge( $attr, wp_get_loading_optimization_attributes( 'img', $attr, 'wp_get_attachment_image' ) );
			}

			// Omit the `decoding` attribute if the value is invalid according to the spec.
			if ( empty( $attr['decoding'] ) || ! in_array( $attr['decoding'], array( 'async', 'sync', 'auto' ), true ) ) {
				unset( $attr['decoding'] );
			}

			// If the default value of `lazy` for the `loading` attribute is overridden
			// to omit the attribute for this image, ensure it is not included.
			if ( array_key_exists( 'loading', $attr ) && ! $attr['loading'] ) {
				unset( $attr['loading'] );
			}

			$attr = array_map( 'esc_attr', $attr );
			$html = rtrim( "<img" );

			foreach ( $attr as $name => $value ) {
				$html .= " $name=" . '"' . $value . '"';
			}

			$html .= ' />';

			echo $html;
		}

	}

	do_action( 'sps_slider_after_img', $post_id, $inc, $id );

?></div><?php