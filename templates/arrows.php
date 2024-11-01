<?php
/**
* Template for Arrows
* This template can be overwritten by duplicate the file in your theme /sps/arrows.php
*
* @args
*/

?><div class="sps_arrows <?php echo esc_attr(implode(' ', explode('_', $position))); ?>"><?php
	
	do_action( 'sps_arrow' );

?></div><?php
