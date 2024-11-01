window.parent.sps_slide_tb_loaded();

/**
* Close the slide on escape
*
* @param alert : If an alert must be shown if the slide has been edited
**/
window.sps_slide_close = function() {
	window.parent.tb_remove();
};

/**
* Remove the slide
**/
window.sps_slide_remove = function() {
	window.sps_slide_close();
	window.parent.sps_remove_slide(sps_slide.post_id);
};

(function($) {

	var sps_slide_size_id = null;

	/**
	* Edit the media for the slide
	*
	* @param attachment : The media from wp.media
	**/
	window.sps_slide_edit_media = function(attachment) {
		$.ajax({
			url: sps_slide.ajax_url,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'simple_pictures_slider_get_attachment_image',
				nonce: sps_slide.nonces.get_attachment_image,
				attachment_id: attachment.id,
			},
			cache: true,
		}).done(function(result) {
			if (result.success) {
				$('#sps_slide_image_display').removeClass( "empty" ).html(result.data);
				$('#sps_slide_image_id').val(attachment.id);

				// Modifie les valeurs des tailles par celle disponible pour l'attachment
				let new_default_sizes = {};
				$.each(attachment.attributes.sizes, function(key, value) {
					new_default_sizes[key] = (sps_slide.i18n.sizes[key]?sps_slide.i18n.sizes[key]:key) + sps_slide.i18n.size_dimensions.replace('%s', value.width).replace('%s', value.height);
				});
				sps_slide.default_sizes = new_default_sizes;

				$('#sps-srcset-sizes .sps-row-size').each( function(e) {
					let row = $(this);
					if (row.find('input[type="hidden"][name^="srcset"][name$="[img]"]').val()==0) {
						let size = row.find('.sps-srcset-select-size').val();
						window.sps_srcset_unset_custom_media(row);
						row.find('.sps-srcset-select-size').val(size);
					}
				});

				// Warn parent window of the change to reload thubnails
			}
		});
	};

	/**
	* Add a new size to srcset option
	**/
	window.sps_srcset_add_new_size = function() {
		if (!sps_slide_size_id) sps_slide_size_id = $('#sps-srcset-new-size').data('id-row');
		$('#sps-srcset-new-size').clone().removeAttr('id').attr('data-id-row', sps_slide_size_id).html(function(i, old) { return old.replace(/srcset\[([0-9]+)\]/ig, 'srcset['+(sps_slide_size_id++)+']'); }).each(function(){ $(this).find('[disabled]').removeAttr('disabled'); }).insertBefore('#sps-srcset-new-size');
	}

	/**
	* Remove size to srcset option
	*
	* @param row : The size's row to remove
	**/
	window.sps_srcset_remove_size = function(row) {
		row.remove();
	}

	/**
	* Edit the media for the srcset size
	*
	* @param attachment : The media from wp.media
	* @param row : The size's row to edit
	**/
	window.sps_srcset_set_custom_media = function(attachment, row) {
		// ID de l'image dans l'input
		row.find('input[type="hidden"][name^="srcset"][name$="[img]"]').val(attachment['id']);

		// ID de l'image dans le nom
		row.find('.sps-srcset-custom-picture-id').text(attachment['id']);

		// Miniature de l'image à loading
		row.find('.sps-srcset-thumbnail').addClass('loading');

		// Modifie les valeurs des tailles par celle disponible pour l'attachment
		let select = row.find('.sps-srcset-select-size');
		select.empty();
		console.log(attachment);
		$.each(attachment.attributes.sizes, function(key, value) {
			select.append(
				$('<option '+((key=='full') ? 'selected="selected"' : '')+'></option>').attr('value', key).html((sps_slide.i18n.sizes[key]?sps_slide.i18n.sizes[key]:key) + sps_slide.i18n.size_dimensions.replace('%s', value.width).replace('%s', value.height))
			);
		});


		// Masque le media courant et affiche le media custom
		row.find('.sps-srcset-current-picture').hide();
		row.find('.sps-srcset-custom-picture').show();

		// Charge la miniature de l'image
		let thumbnail = new Image;
		thumbnail.onload = function() {
			row.find('.sps-srcset-thumbnail').css("background-image", "url("+this.src+")").removeClass('loading');
		}
		thumbnail.src = attachment.get('sizes').thumbnail.url;
	}

	/**
	* Unset the media for the srcset size and get back to the current media
	*
	* @param row : The size's row to edit
	**/
	window.sps_srcset_unset_custom_media = function(row) {
		// ID de l'image dans l'input à 0
		row.find('input[type="hidden"][name^="srcset"][name$="[img]"]').val(0);

		// Modifie les valeurs des tailles par celle du media courant
		let select = row.find('.sps-srcset-select-size');
		select.empty();
		$.each(sps_slide.default_sizes, function(key, value) {
			select.append(
				$('<option></option>').attr('value', key).html(value)
			);
		});

		// Masque le media custom et affiche le media courrant
		row.find('.sps-srcset-custom-picture').hide();
		row.find('.sps-srcset-current-picture').show();

		// ID de l'image dans le nom à vide
		row.find('.sps-srcset-custom-picture-id').text('');

		// Miniature de l'image à loading et suppresion de la thumbnail
		row.find('.sps-srcset-thumbnail').addClass('loading').css("background-image", "");
	}



	$(document).ready( function($) {

		/* Au clic sur le bouton de suppression de la slide, fermeture de la thickbox et delete */
		$('body').on('click', '#sps-remove-slide', function(e) {
			e.preventDefault();
			window.sps_slide_remove();
			return false;
		});

		/* Au clic sur le bouton d'édition de la slide, affichage du media manager */
		$('body').on('click', '.sps-slide-edit-picture', function(e) {
			e.preventDefault();
			window.parent.sps_display_media_manager(window.sps_slide_edit_media);
			return false;
		});

		/* Au clic sur le bouton d'édition du srcset, affichage du media manager */
		$('body').on('click', '#sps-srcset-sizes .sps-row-size .sps-srcset-set-media', function(e) {
			e.preventDefault();
			window.parent.sps_display_media_manager(window.sps_srcset_set_custom_media, $(this).closest('.sps-row-size'));
			return false;
		});

		/* Au clic sur le bouton de suppression de l'image, efface le media custom et remet le media courant */
		$('body').on('click', '#sps-srcset-sizes .sps-row-size .sps-srcset-unset-media', function(e) {
			e.preventDefault();
			window.sps_srcset_unset_custom_media($(this).closest('.sps-row-size'));
			return false;
		});

		/* Au clic sur le bouton d'ajout d'un srcset */
		$('body').on('click', '#sps-srcset-sizes #sps-srcset-add-size', function(e) {
			e.preventDefault();
			window.sps_srcset_add_new_size();
			return false;
		});

		/* Au clic sur le bouton de suppression d'un srcset */
		$('body').on('click', '#sps-srcset-sizes .sps-row-size .sps-srcset-remove-size', function(e) {
			e.preventDefault();
			window.sps_srcset_remove_size($(this).closest('.sps-row-size'));
			return false;
		});

		/* Au clic sur la touche echap */
		$(document).on('keydown.thickbox', function(e){
			if ( e.which == 27 ){
				window.sps_slide_close();
				return false;
			}
		});

		/**
		* On change toggle switch, display or hide some fields
		**/
		$('body').on('change', '#advanced-options .post-attributes-label-wrapper > input[type="checkbox"][id]', function(e) {
			if ($(this).is(':checked')) {
				$('.hide_if_' + $(this).attr('id')).hide();
				$('.show_if_' + $(this).attr('id')).show();
				console.log('input checked');
			} else {
				$('.hide_if_' + $(this).attr('id')).show();
				$('.show_if_' + $(this).attr('id')).hide();
				console.log('input unchecked');
			}
		});
		$('#advanced-options .post-attributes-label-wrapper > input[type="checkbox"][id]').each(function(e) {
			if ($(this).is(':checked')) {
				$('.hide_if_' + $(this).attr('id')).hide();
				$('.show_if_' + $(this).attr('id')).show();
			} else {
				$('.hide_if_' + $(this).attr('id')).show();
				$('.show_if_' + $(this).attr('id')).hide();
			}
		});

	});

})(jQuery);
