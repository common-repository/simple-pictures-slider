<?php
/**
* Template for Slider
* This template can be overwritten by duplicate the file in your theme /sps/slider.php
*
* @args
*/

?><div id="<?php echo esc_attr($template_id); ?>" class="sps_slider"><?php

	do_action( 'sps_slides' );

?></div><?php

do_action( 'sps_navigation' );

do_action( 'sps_arrows' );
