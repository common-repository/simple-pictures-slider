
/**
* Custom load function to knowing the percentage of loading of an image
**/
Image.prototype.load = function(url){
	var thisImg = this;
	var xmlHTTP = new XMLHttpRequest();
	xmlHTTP.open('GET', url,true);
	xmlHTTP.responseType = 'arraybuffer';
	xmlHTTP.onload = function(e) {
		var blob = new Blob([this.response]);
		thisImg.src = window.URL.createObjectURL(blob);
	};
	xmlHTTP.onprogress = function(e) {
		thisImg.completedPercentage = parseInt((e.loaded / e.total) * 100);
	};
	xmlHTTP.onloadstart = function() {
		thisImg.completedPercentage = 0;
	};
	xmlHTTP.send();
};
Image.prototype.completedPercentage = 0;

// Boolean if "Edit Slide" button is click to control tb_position
var sps_slider_image_edit_clicked = false;

// Slides of the slider
var loading_slides = {};

(function($) {

	/**
	* Confirm than the Thickbox opened is for slide
	**/
	window.sps_slide_tb_loaded = function() {
		$('#TB_window').addClass('tb_slide');
	};

	/**
	* Display the media manager for select 1 media and return the attachment to a function with args
	*
	* @param f : The function to call at the end
	* @param ...args : The args to pass through the function with the attachment
	**/
	window.sps_display_media_manager = function(f, ...args) {

		// Init the media manager iframe
		var image_frame;
		if(image_frame) image_frame.open();
		image_frame = wp.media({
			title: sps_slider.i18n.media_manager_title,
			multiple : false,
			library : {
				type : 'image',
			}
		});

		// When the media manager is closed
		image_frame.on('close',function() {
			let selection =  image_frame.state().get('selection');
				
			selection.each(function(attachment) {
				// Call the function of return with attachment and args
				f(attachment, ...args);
			});
		});

		// Open the media manager iframe
		image_frame.open();
	};

	/**
	* Customize the tb_position function if it conceirns slide to keep bigger size
	**/
	window.sps_tb_position = function() {
		if (sps_slider_image_edit_clicked || $('#TB_window').hasClass('tb_slide')) {
			TB_WIDTH = $(window).width() - 200;
			if (TB_WIDTH>1200) TB_WIDTH = 1200;
			TB_HEIGHT = $(window).height() - 200;
			if (TB_HEIGHT>750) TB_HEIGHT = 750;

			sps_slider_image_edit_clicked = false;

			var isIE6 = typeof document.body.style.maxHeight === "undefined";
			$("#TB_window").css({marginLeft: '-' + parseInt((TB_WIDTH / 2),10) + 'px', width: TB_WIDTH + 'px'});
			$("#TB_iframeContent").css({width: TB_WIDTH + 'px',height: TB_HEIGHT + 'px'});
			if ( ! isIE6 ) {
				$("#TB_window").css({marginTop: '-' + parseInt((TB_HEIGHT / 2),10) + 'px'});
			}
		} else {
			window.old_tb_position();
		}
	};

	/**
	* Open media manager and add and create slide
	**/
	window.sps_add_slide = function() {

		// Init the media manager iframe
		var image_frame;
		if(image_frame) image_frame.open();
		image_frame = wp.media({
			title: sps_slider.i18n.media_manager_title,
			multiple : true,
			library : {
				type : 'image',
			}
		});

		// When the media manager is closed
		image_frame.on('close',function() {
			let selection =  image_frame.state().get('selection');
				
			selection.each(function(attachment) {

				// Add slide placeholder
				$("#sps_slider_images").append('<div class="sps_slider_image ui-sortable-handle loading" data-load="10"><input type="hidden" name="slides[]" value=""/><a href="#" class="sps_slider_image_edit"></a><button class="sps_slider_image_remove"></button><div class="sps_slider_image_thumbnail"></div></div>');

				// Ajax request to create slide post and display correct thumbnail
				$.ajax({
					url: sps_slider.ajax_url,
					method: 'POST',
					dataType: 'json',
					data: {
						action: 'simple_pictures_slider_create_slide_post',
						nonce: sps_slider.nonces.create_slide_post,
						post_id: sps_slider.post_id,
						image_id: attachment['id'],
					},
					cache: false,
				}).done(function(result) {
					if (result.success) {

						// Set slide id to placeholder's id and input
						$("#sps_slider_images").find('.sps_slider_image.loading:not([id])').first().attr('id', 'sps_slider_image_'+result.data.post_id).find('input').first().val(result.data.post_id).nextAll('.sps_slider_image_edit').first().attr('href', result.data.post_edit).addClass('thickbox');

						// Save slide in loading_slides
						loading_slides[result.data.post_id] = {};
						loading_slides[result.data.post_id]['object'] = new Image();
						loading_slides[result.data.post_id]['object'].onload = function(){
							// Fake waiting time between 500ms and 1000ms minimum to let user thinks its worked
							let time_wait = (Math.random() * 500 + 500) - (Date.now() - loading_slides[result.data.post_id]['start']);
							if (time_wait<0) time_wait = 0;

							var src = this.src;
							setTimeout(function() {
								clearInterval(loading_slides[result.data.post_id]['interval']);
								$('#sps_slider_image_'+result.data.post_id).removeClass('loading').find('.sps_slider_image_thumbnail').first().css('background-image', 'url(' + src + ')');

								if (!$('#sps_slider_dimensions input[name="slider_width"]').first().val()) $('#sps_slider_dimensions input[name="slider_width"]').first().val(result.data.image_width);
								if (!$('#sps_slider_dimensions input[name="slider_height"]').first().val()) $('#sps_slider_dimensions input[name="slider_height"]').first().val(result.data.image_height);
							}, time_wait);
						};
						
						// Animate loading bar
						loading_slides[result.data.post_id]['interval'] = setInterval(function() {
							let percentage = 10*Math.floor(loading_slides[result.data.post_id]['object'].completedPercentage/10);
							if (percentage>=100) percentage = 90;
							$('#sps_slider_image_'+result.data.post_id).attr('data-load', percentage);
						}, 50);
						
						loading_slides[result.data.post_id]['start'] = Date.now();
						loading_slides[result.data.post_id]['object'].load(result.data.image_src);

					} else {
						$("#sps_slider_images").find('.sps_slider_image.loading').first().removeClass('loading').addClass('fail');
					}
				}).fail(function(error) {
					$("#sps_slider_images").find('.sps_slider_image.loading').first().removeClass('loading').addClass('fail');
				});
			});
			
			// Resort the slides
			$("#sps_slider_images").sortable( "refresh" );
				
		});
		
		// Open the media manager iframe
		image_frame.open();
	};

	/**
	* Remove slide
	*
	* @param slide : Slide element to remove
	**/
	window.sps_remove_slide = function(slide_id) {
		let slide = $('input[name="slides[]"][value="'+slide_id+'"').first().closest('.sps_slider_image');
		slide.remove();
		$("#sps_slider_images").sortable( "refresh" );
		$.ajax({
			url: sps_slider.ajax_url,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'simple_pictures_slider_remove_slide_post',
				nonce: sps_slider.nonces.remove_slide_post,
				post_id: slide_id,
			},
			cache: false,
		});
	};

	/**
	* Previous dimensions of slider to calculate ratio
	**/
	var sps_width_value = 1;
	var sps_height_value = 1;

	/**
	* Save dimensions during blur to be used for ratio calcul
	**/
	window.sps_save_dimensions = function() {
		sps_width_value = $('input#slider_width').val();
		sps_height_value = $('input#slider_height').val();
	}



	$(document).ready( function($) {

		/**
		* Save the old tb_position function customized on certain page of admin and change the tb_position function to custom one
		**/
		window.old_tb_position = window.tb_position;
		window.tb_position = window.sps_tb_position;
		
		/**
		* On click on "Add slide", open the media manager and add slide
		**/
		$('input#slider_media_manager').click(function(e) {
			e.preventDefault();
			window.sps_add_slide();
			return false;
		});
		
		/* Slides sortables */
		$( "#sps_slider_images" ).sortable({
			containment: "parent",
			cursor: "move",
			delay: 150,
			opacity: 1,
			cursor: "grabbing",
			tolerance: "pointer",
			start: function( event, ui ) {
				$( "#sps_slider_images" ).addClass('sorting');
			},
			stop: function( event, ui ) {
				$( "#sps_slider_images" ).removeClass('sorting');
				/* Save order */
			}
		});

		/* Remove slide */
		$("#sps_slider_images").on('click', '.sps_slider_image_remove', function(e) {
			e.preventDefault();
			window.sps_remove_slide($(this).closest('.sps_slider_image').find('input[name="slides[]"]').val());
			return false;
		});

		/* Edit slide */
		$('body').on('click', 'a.sps_slider_image_edit', function() {
			sps_slider_image_edit_clicked = true;
		});

		$('body').on('click', 'a.sps_slider_image_edit', tb_click);


		sps_width_value += $('input#slider_width').val();
		sps_height_value += $('input#slider_height').val();

		$('body').on('input', 'input#slider_width', function(e) {
			if ($('input#sps-dimensions-related-link').is(':checked')) $('input#slider_height').val(Math.round(sps_height_value * $(this).val() / sps_width_value));
		});

		$('body').on('input', 'input#slider_height', function(e) {
			if ($('input#sps-dimensions-related-link').is(':checked')) $('input#slider_width').val(Math.round(sps_width_value * $(this).val() / sps_height_value));
		});

		$('body').on('change', 'input#slider_width, input#slider_height', sps_save_dimensions);
		
		/**
		* On change toggle switch, display or hide some fields
		**/
		$('body').on('change', '.postbox .post-attributes-label-wrapper > input[type="checkbox"][id]', function(e) {
			if ($(this).is(':checked')) {
				$('.hide_if_' + $(this).attr('id')).hide();
				$('.show_if_' + $(this).attr('id')).show();
			} else {
				$('.hide_if_' + $(this).attr('id')).show();
				$('.show_if_' + $(this).attr('id')).hide();
			}
		});
		$('.postbox .post-attributes-label-wrapper > input[type="checkbox"][id]').each(function(e) {
			if ($(this).is(':checked')) {
				$('.hide_if_' + $(this).attr('id')).hide();
				$('.show_if_' + $(this).attr('id')).show();
			} else {
				$('.hide_if_' + $(this).attr('id')).show();
				$('.show_if_' + $(this).attr('id')).hide();
			}
		});
		
	});
	
	/* Refresh image on edit */
	/*// Ajax request to refresh the image preview
	function Refresh_Image(the_id){
		var data = {
			action: 'myprefix_get_image',
			id: the_id
		};

		$.get(ajaxurl, data, function(response) {
			if(response.success === true) {
				$('#myprefix-preview-image').replaceWith( response.data.image );
			}
		});
	}*/

})(jQuery);
