<?php
/**
* Template for Navigation
* This template can be overwritten by duplicate the file in your theme /sps/navigation.php
*
* @args
*/

?><ul class="sps_navigation <?php echo esc_attr(implode(' ', explode('_', $position))); ?>"><?php
	
	do_action( 'sps_navigation_dots' );

?></ul><?php
