jQuery(document).ready( function($) {
	// -------------------------------------------------------------
	//   Prevent ENTER on input from submitting whole Editor feature settings
	// -------------------------------------------------------------
	$(document).on("keydown", ".ppc-add-custom-row-body input[type='text']", function (event) {
        return event.keyCode !== 13;
      });

      // -------------------------------------------------------------
      //   Submit new item for editor feature
      // -------------------------------------------------------------
      $(document).on("click", ".ppc-feature-gutenberg-new-submit, .ppc-feature-classic-new-submit", function (event) {
        event.preventDefault();
        var ajax_action,
          security,
          custom_label,
          custom_element,
          button = $(this);

        $('.ppc-feature-submit-form-error').remove();
        button.attr('disabled', true);
        $(".ppc-feature-post-loader").addClass("is-active");

        if (button.hasClass('ppc-feature-gutenberg-new-submit')) {
          ajax_action = 'ppc_submit_feature_gutenberg_by_ajax';
          custom_label = $('.ppc-feature-gutenberg-new-name').val();
          custom_element = $('.ppc-feature-gutenberg-new-ids').val();
        } else {
          ajax_action = 'ppc_submit_feature_classic_by_ajax';
          custom_label = $('.ppc-feature-classic-new-name').val();
          custom_element = $('.ppc-feature-classic-new-ids').val();
        }

        security = $('.ppc-feature-submit-form-nonce').val();

        var data = {
          'action': ajax_action,
          'security': security,
          'custom_label': custom_label,
          'custom_element': custom_element,
        };

        $.post(ajaxurl, data, function (response) {

          if (response.status == 'error') {
            button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error" style="color:red;">' + response.message + '</div>');
            $(".ppc-feature-submit-form-error").delay(2000).fadeOut('slow');
          }else if (response.status == 'promo') {
            button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error" style="color:red;">' + response.message + '</div>');
          } else {
            var activeTable;

			$('.ppc-editor-features-submit').attr('disabled', false);
			$('.ppc-save-button-warning').remove();
					  
            if (button.hasClass('ppc-feature-gutenberg-new-submit')) {
              activeTable = '.editor-features-gutenberg';
              $('.ppc-feature-gutenberg-new-name').val('');
              $('.ppc-feature-gutenberg-new-ids').val('');
            } else {
              activeTable = '.editor-features-classic';
              $('.ppc-feature-classic-new-name').val('');
              $('.ppc-feature-classic-new-ids').val('');
            }

            button.closest('tr').find('.ppc-post-features-note').html('<div class="ppc-feature-submit-form-error" style="color:green;">' + response.message + '</div>');
            $(".ppc-feature-submit-form-error").delay(5000).fadeOut('slow');
            setTimeout(function () {
              $('.ppc-menu-overlay-item').removeClass('ppc-menu-overlay-item');
            }, 5000);

            $(activeTable).find('tr:last').after(response.content);
          }

          $(".ppc-feature-post-loader").removeClass("is-active");
          button.attr('disabled', false);

        });


      });

      // -------------------------------------------------------------
      //   Delete custom added post features item
      // -------------------------------------------------------------
      $(document).on("click", ".ppc-custom-features-delete", function (event) {
        if (confirm(cmeAdmin.deleteWarning)) {
          var item = $(this);
          var delete_id = item.attr('data-id');
          var delete_parent = item.attr('data-parent');
          var security = $('.ppc-feature-submit-form-nonce').val();

          item.closest('.ppc-menu-row').fadeOut(300);

          var data = {
            'action': 'ppc_delete_custom_post_features_by_ajax',
            'security': security,
            'delete_id': delete_id,
            'delete_parent': delete_parent,
          };

          $.post(ajaxurl, data, function (response) {
            if (response.status == 'error') {
              item.closest('.ppc-menu-row').show();
              alert(response.message);
            }
          });

        }
      });

  	// -------------------------------------------------------------
  	//   Lock Editor Features 'Save changes' button if unsaved custom items exist
  	// -------------------------------------------------------------
  	$(document).on("keyup paste", ".ppc-add-custom-row-body input, .ppc-add-custom-row-body textarea", function (event) {
    	var lock_button = false;
    	$('.ppc-save-button-warning').remove();

    	$('.ppc-add-custom-row-body .left input, .ppc-add-custom-row-body .right textarea').each(function () {
      	if ($(this).val() !== '' && $(this).val().replace(/\s/g, '').length) {
        	lock_button = true;
      	}
    	});

    	if (lock_button) {
      	$('.ppc-editor-features-submit').attr('disabled', true).after('<span class="ppc-save-button-warning">' + cmeAdmin.saveWarning + '</span>');
    	} else {
      	$('.ppc-editor-features-submit').attr('disabled', false);
    	}
    });
    
    if ($('.ppc-feature-gutenberg-new-name').val() || $('.ppc-feature-classic-new-name').val()) {
      $('.ppc-editor-features-submit').attr('disabled', true).after('<span class="ppc-save-button-warning">' + cmeAdmin.saveWarning + '</span>');
    }
  });
