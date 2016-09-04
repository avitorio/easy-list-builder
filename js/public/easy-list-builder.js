// Wait until the page and jQuery have loaded
jQuery(document).ready(function($) {

	// setup wp ajax url
	var wpajax_url = document.location.protocol + '//' + document.location.host + '/wp-admin/admin-ajax.php';

	// email capture url
	var email_capture_url = wpajax_url + '?action=elb_save_subscription';


	$('form#elb_register_form').bind('submit', function() {

		// get the jquery form object
		$form = $(this);

		// setup form data for ajax post
		var form_data = $form.serialize();

		// submit form data with ajax
		$.ajax({
			'method': 'post',
			'url': email_capture_url,
			'data': form_data,
			'dataType': 'json',
			'cache': false,
			'success': function(data, textStatus){

				if ( data.status == 1) {
					// success
					// reset the form
					$form[0].reset();

					// notify user of success
					alert(data.message);

				} else {

					// error
					// begin building error message text
					var msg = data.message + '\r' + data.error + '\r';

					// loop through errors
					$.each(data.errors, function(index, val){
						// append each error on a new line
						msg += '\r';
						msg += '-' + val;


					});

					// notify user of the error
					alert(msg);

				}
			},
			'error': function(jqXHR, textStatus, errorThrown) {
				// ajax didn't work
			}


		});

		// stop the form from submitting normally
		return false;

	});

	// unsubscribe url
	var unsubscribe_url = wpajax_url + '?action=elb_unsubscribe';


	$(document).on('submit', 'form#elb_manage_subscriptions_form', function() {

		// get the jquery form object
		$form = $(this);

		// setup form data for ajax post
		var form_data = $form.serialize();

		// submit form data with ajax
		$.ajax({
			'method': 'post',
			'url': unsubscribe_url,
			'data': form_data,
			'dataType': 'json',
			'cache': false,
			'success': function(data, textStatus){

				if ( data.status == 1) {
					// success
					// update html form
					$form.replaceWith(data.html);

					// notify user of success
					alert(data.message);

				} else {

					// error
					// begin building error message text
					var msg = data.message + '\r' + data.error + '\r';

					// notify user of the error
					alert(msg);

				}
			},
			'error': function(jqXHR, textStatus, errorThrown) {
				// ajax didn't work
			}


		});

		// stop the form from submitting normally
		return false;

	});

});