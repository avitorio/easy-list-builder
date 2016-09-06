<?php

/*
Plugin Name: Easy List Builder
Plugin URI: http://www.altamind.com/
Description: An easy list builder for Wordpress.
Author: Andre Vitorio
Version: 0.1
Author URI: http://www.andrevitorio.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: easy-list-builder
*/

/* TABLE OF CONTENTS */

/* 
	1. HOOKS
		1.1 - register shortcodes
		1.2 - register custom admin column headers
		1.3 - register custom admin column data
		1.4 - register ajax actions
		1.5 - load external scripts and styles
		1.6 - advanced custom fields setting
		1.7 - register custom menus
		1.8 - register admin javascript files
		1.9 - register admin options
		1.10 - 
		1.11

	2. SHORTCODES
		2.1 - elb_register_shortcodes()
		2.2 - elb_form_shortcode()
		2.3 - elb_manage_subscriptions_shortcode()
		2.4 - 
		2.5 - 

	3. FILTERS
		3.1 - elb_subscriber_column_headers()
		3.2 - elb_subscriber_column_data()
		3.2.2 - elb_register_custom_admin_titles()
		3.2.3 - elb_custom_admin_titles()
		3.3 - elb_list_column_headers()
		3.4 - elb_list_column_data()
		3.5 - elb_admin_menus()

	4. EXTERNAL SCRIPTS
		4.1 - include Advanced Custom Fields
		4.2 - elb_public_scripts()
		4.3 - elb_admin_scripts()

	5. ACTIONS
		5.1 - elb_save_subscription()
		5.2 - elb_save_subscriber()
		5.3 - elb_add_subscription()
		5.4 - elb_unsubscribe()
		5.5 - elb_remove_subscription()
		5.6
		5.7
		5.8
		5.9
		5.10
		5.11
		5.12

	6. HELPERS
		6.1 - elb_subscriber_has_subscription()
		6.2 - elb_get_subscriber_id()
		6.3 - elb_get_subscriptions()
		6.4 - elb_return_json()
		6.5 - elb_get_acf_key()
		6.6 - elb_get_subscriber_data()
		6.7 - elb_get_page_select()
		6.8 - elb_get_default_options()
		6.9 - elb_get_option()
		6.10 - elb_get_current_options()
		6.11 - elb_get_manage_subscriptions_html()
		6.12
		6.13
		6.14
		6.15
		6.16
		6.17
		6.18
		6.19 - elb_get_list_reward()
		6.20 -
		6.21 - 
		6.22
		6.23
		6.24
		6.25


	7. CUSTOM POST TYPES
		7.1 - subscribers
		7.2 - lists

	8. ADMIN PAGES
		8.1 - elb_dashboard_admin_page()
		8.2 - elb_import_admin_page()
		8.3 - elb_options_admin_page()

	9. SETTINGS
		9.1 - elb_register_options()

*/

/* 1. HOOKS */

// 1.1
// hint: register our shortcodes on init
add_action('init', 'elb_register_shortcodes');

// 1.2
// hint: register custom admin column headers
add_filter('manage_edit-elb_subscriber_columns', 'elb_subscriber_column_headers');
add_filter('manage_edit-elb_list_columns', 'elb_list_column_headers');

// 1.3
// hint: register custom admin column data
add_filter('manage_elb_subscriber_posts_custom_column', 'elb_subscriber_column_data', 1, 2);
add_action('admin_head-edit.php','elb_register_custom_admin_titles');
add_filter('manage_elb_list_posts_custom_column', 'elb_list_column_data', 1, 2);

//1.4
// hint: register ajax actions
add_action('wp_ajax_nopriv_elb_save_subscription', 'elb_save_subscription'); // regular website visitor
add_action('wp_ajax_elb_save_subscription', 'elb_save_subscription'); // admin user
add_action('wp_ajax_nopriv_elb_unsubscribe', 'elb_unsubscribe'); // regular website visitor
add_action('wp_ajax_elb_unsubscribe', 'elb_unsubscribe'); // admin user
add_action('wp_ajax_elb_download_subscribers_csv', 'elb_download_subscribers_csv'); // admin user
add_action('wp_ajax_elb_parse_import_csv', 'elb_parse_import_csv'); // admin user



//1.5
// hint: load external files to public website
add_action('wp_enqueue_scripts', 'elb_public_scripts');

//1.6
// hint: advanced custom fields setting
add_filter('acf/settings/path', 'elb_acf_settings_path');
add_filter('acf/settings/dir', 'elb_acf_settings_dir');
add_filter('acf/settings/show_admin', 'elb_acf_settings_show_admin');
//if( !defined('ACF_LITE')) define('ACF_LITE',  true); // turn off ACF plugin menu

//1.7
// hint: register custom menus
add_action('admin_menu', 'elb_admin_menus');

//1.8
// hint: register admin javascript files
add_action('admin_enqueue_scripts', 'elb_admin_scripts');

//1.9
// hint: register admin options
add_action('admin_init', 'elb_register_options');

//1.10
// register activate/deactivate/uninstall functions
register_activation_hook(__FILE__, 'elb_activate_plugin');

//1.11
// trigger rewards link
add_action('wp', 'elb_trigger_reward_download');

/* 2. SHORTCODES */

// 2.1
function elb_register_shortcodes() {

	add_shortcode('elb_form', 'elb_form_shortcode');
	add_shortcode('elb_manage_subscriptions', 'elb_manage_subscriptions_shortcode');
	add_shortcode('elb_confirm_subscription', 'elb_confirm_subscription_shortcode');
	add_shortcode('elb_download_reward', 'elb_download_reward_shortcode');
}

// 2.2
function elb_form_shortcode($args, $content="") {

	// grab list id
	$list_id = 0;
	if(isset($args['id'])) {
		$list_id = (int)$args['id'];
	};

	// set title
	$title = '';
	if(isset($args['title'])) {
		$title = (string)$args['title'];
	};

	// setup our output variable - the html form
	$output = '
		<div class="elb">
			<form id="elb_register_form" name="elb_form" class="elb-form" method="post" action="/wp-admin/admin-ajax.php?action=elb_save_subscription"> 

				<input type="hidden" name="elb_list" value="' . $list_id . '">';

				if(strlen($title)) {
					// if title is set, show it
					$output .= '<h3 class="elb-title">' . $title . '</h3>';
				};

				$output .= '
					<p class="elb-input-container">
						<label>Your Name</label>
						<input type="text" name="elb_fname" placeholder="First Name">
						<input type="text" name="elb_lname" placeholder="Last Name">
					</p>
					<p class="elb-input-container">
						<label>Your Email</label>
						<input type="email" name="elb_email" placeholder="Email">
					</p>
				';

				// Include content to our form if content is passed to function
				if ( strlen($content) ):

					$output .= '<div class="elb-content">' . wpautop($content) . '</div>';

				endif;

				// get reward
				$reward = elb_get_list_reward( $list_id );

				// check if reward exists
				if ( $reward !== false ) {

					// include message about reward
					$output .= '
						<div class="elb-content elb-reward-message">
							<p>Get a FREE DOWNLOAD of <strong>' . $reward['title'] . '</strong> when you join this list!</p>
						</div>
					';

				}

				// complete the form
				$output .= ' 

				<p class="elb-input-container">
					<input type="submit" name="elb_submit" value="Sign Me Up!">
				</p>

			</form>
		</div>
	';

	// return output
	return $output;

}

//2.3
// hint: displays a form for managing the users list subscriptions
// example: [elb_manage_subscriptions]
function elb_manage_subscriptions_shortcode() {

	// setup our return string
	$output = '<div class="elb elb_manage_subscriptions">';

	try {

		// get the email address from the URL
		$email = ( isset($_GET['email'])) ? esc_attr( $_GET['email'] ) : '';

		// get the subscriber id from the email address
		$subscriber_id = elb_get_subscriber_id($email);

		// get subscriber data
		$subscriber_data = elb_get_subscriber_data($subscriber_id);

		// if subscriber exists
		if ($subscriber_id) {

			// get subscriptions html
			$output = elb_get_manage_subscriptions_html( $subscriber_id );

		} else {

			// invalid link
			$output .= '<p>This link is invalid</p>';
		}

	} catch (Exception $e) {
		// php error
	}

	// close our div tag
	$output .= '</div>';

	return $output;
}

//2.4
// displays subscription opt-in confirmation text and link to manage subscriptions
// example: [elb_confirm_subscription]
function elb_confirm_subscription_shortcode( $args, $content="") {

	// setup output variable
	$output = '';

	// setup email and list_id variables and handle if they are not defined in GET scope
	$email = ( isset( $_GET['email'] )) ? esc_attr($_GET['email']) : '';
	$list_id = ( isset( $_GET['list'] )) ? esc_attr($_GET['list']) : 0;

	// get subscriber id from email
	$subscriber_id = elb_get_subscriber_id( $email );
	$subscriber = get_post( $subscriber_id );

	// if we find a subscriber matching that email address
	if ( $subscriber_id && elb_validate_subscriber( $subscriber )) {

		// get list object
		$list = get_post( $list_id );

		// if list and subscriber are valid
		if ( elb_validate_list( $list )) {

			// if subscription has not yet been added
			if ( !elb_subscriber_has_subscription( $subscriber_id, $list_id)) {

				// complete opt-in
				$optin_complete = elb_confirm_subscription( $subscriber_id, $list_id );

				if ( !$optin_complete ) {

					$output .= elb_get_message_html('Unfortunately we were unable to confirm your subscription', 'error');

					$output .= '</div>';

					return $output;
				}
			}

			// get confirmation message html and append it to output
			$output .= elb_get_message_html('Your subscription to ' . $list->post_title . ' has now been confirmed.', 'confirmation');

			// get manage subscriptions link
			$manage_subscriptions_link = elb_get_manage_subscriptions_link( $email );

			// append link to output
			$output .= '<p><a href="' . $manage_subscriptions_link . '">Click here to manage your subscriptions.</a></p>';

		} else {

			$output .= elb_get_message_html('This link is invalid.', 'error');

		}
	} else {

		$output .= elb_get_message_html('This link is invalid. Invalid subscriber' . $email . '.', 'error');

	}

	// close elb div
	$output .= '</div>';

	// return output
	return $output;
}

//2.5
// [elb_download_reward]
// shortcode for the download page
function elb_download_reward_shortcode( $args, $content='' ) {

	$output = '';

	$uid = (isset($_GET['reward'])) ? (string)$_GET['reward'] : 0;

	// get reward link uid
	$reward = elb_get_reward( $uid );

	// if reward not found
	if ( $reward === false ) {

		if ($reward['downloads'] >= elb_get_option('elb_download_limit')) { 

			$output .= elb_get_message_html( 'This link has reached it\'s download limit', 'warning');

		}

	} else { 

		$output .= elb_get_message_html( 'This link is invalid', 'error');

	}

	return $output;

}


/* 3. FILTERS */

//3.1
function elb_subscriber_column_headers($columns) {

	// creating custom column header data
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __('Subscriber Name'),
		'email' => __('Email Address'),
	);

	// return new columns
	return $columns;

}

//3.2
function elb_subscriber_column_data($column, $post_id) {

	// setup our return text
	$output = '';

	switch($column) {

		case 'title':
			// get the custom name data
			$fname = get_field('elb_fname', $post_id);
			$lname = get_field('elb_lname', $post_id);
			$output .= $fname . ' ' . $lname;
			break;
		case 'email':
			// get the custom email data
			$email = get_field('elb_email', $post_id);
			$output .= $email;
			break;
	}

	echo $output;
}

//3.2.2
function elb_register_custom_admin_titles() {
	add_filter(
		'the_title',
		'elb_custom_admin_titles',
		99,
		2
	);
}

//3.2.3
function elb_custom_admin_titles($title, $post_id) {
	global $post;

	$output = $title;

	if (isset($post->post_type)):
		switch($post->post_type) {
			case 'elb_subscriber':
				$fname = get_field('elb_fname', $post_id);
				$lname = get_field('elb_lname', $post_id);
				$output = $fname . ' ' . $lname;
				break;
		}
	endif;

	return $output;
}

//3.3
function elb_list_column_headers($columns) {

	// creating custom column header data
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __('List Name'),
		'reward' => __('Opt-in Reward'),
		'subscribers' => __('Subscribers'),
		'shortcode' => __('Shortcode')
	);

	// return new columns
	return $columns;

}

//3.4
function elb_list_column_data($column, $post_id) {

	// setup our return text
	$output = '';

	switch($column) {

		case 'reward':
			$reward = elb_get_list_reward( $post_id );

			if ( $reward !== false ) {

				$output .= '<a href="' . $reward['file']['url'] . '" download="' . $reward['title'] . '">' . $reward['title'] . '</a>';

			}

			break;

		case 'subscribers':
			// get the count of current subscribers
			$subscriber_count = elb_get_list_subscriber_count( $post_id );

			// get our unique export link
			$export_link = elb_get_export_link( $post_id );

			// append subscriber count to our $output
			$output .= $subscriber_count;

			// if we have more than one subscriber add new export link to output
			if ( $subscriber_count ) $output .= '<a href="' . $export_link . '"> Export</a>';
			break;

		case 'shortcode':
			$output .= '[slb_form id="'. $post_id .'"]';
			break;

	}

	echo $output;
}

//3.5
// hint: register custom plugin admin menus
function elb_admin_menus() {

	// main Menu
		$top_menu_item = 'elb_dashboard_admin_page';

		add_menu_page('', 'List Builder', 'manage_options', 'elb_dashboard_admin_page', 'elb_dashboard_admin_page', 'dashicons-email-alt');

	// submenu Items

		// dashboard
		add_submenu_page($top_menu_item, '', 'Dashboard', 'manage_options', $top_menu_item, $top_menu_item);

		// email lists
		add_submenu_page($top_menu_item, '', 'Email Lists', 'manage_options', 'edit.php?post_type=elb_list');

		// subscribers
		add_submenu_page($top_menu_item, '', 'Subscribers', 'manage_options', 'edit.php?post_type=elb_subscriber');

		// import subscribers
		add_submenu_page($top_menu_item, '', 'Import Subscribers', 'manage_options', 'elb_import_admin_page', 'elb_import_admin_page');

		// plugin options
		add_submenu_page($top_menu_item, '', 'Plugin Options', 'manage_options', 'elb_options_admin_page', 'elb_options_admin_page');

}

/* 4. EXTERNAL SCRIPTS */

//4.1 Include Advanced Custom Fields
include_once( plugin_dir_path(__FILE__) . 'lib/advanced-custom-fields/acf.php');

//4.2
// hint: loads external file into public website
function elb_public_scripts() {

	// register script with WordPress's internal library
	wp_register_script('easy-list-builder-js-public', plugins_url('/js/public/easy-list-builder.js', __FILE__), array('jquery'), '', true);

	// register styles with WordPress's internal library
	wp_register_style('easy-list-builder-css-public', plugins_url('/css/public/easy-list-builder.css', __FILE__));

	// add it to queue of scripts that get loaded on each page
	wp_enqueue_script('easy-list-builder-js-public'); 
	wp_enqueue_style('easy-list-builder-css-public'); 
}

//4.3
// hint: loads external file into admin area
function elb_admin_scripts() {

	// register script with WordPress's internal library
	wp_register_script('easy-list-builder-js-private', plugins_url('/js/private/easy-list-builder.js', __FILE__), array('jquery'), '', true);

	// add it to queue of scripts that get loaded on each page
	wp_enqueue_script('easy-list-builder-js-private'); 
}

/* 5. ACTIONS */

//5.1
function elb_save_subscription() {

	// setup default result data
	$result = array(
		'status' => 0,
		'message' => 'Subscription was not saved',
		'error' => '',
		'errors' => array(),
	);

	try {

		// get list id
		$list_id = (int)$_POST['elb_list'];

		// prepare subscriber data
		$subscriber_data = array (
			'fname' => esc_attr($_POST['elb_fname']),
			'lname' => esc_attr($_POST['elb_lname']),
			'email' => esc_attr($_POST['elb_email']),
		);

		// set up errors array
		$errors = array();

		// validate fields
		if ( !strlen($subscriber_data['fname'])) $errors['fname'] = 'First name field is required.';
		if ( !strlen($subscriber_data['email'])) $errors['email'] = 'Email address field is required.';
		if ( strlen($subscriber_data['email']) && !is_email($subscriber_data['email'])) $errors['email'] = 'Email address must be valid.';

		// check for errors
		if ( count($errors)) {

			// append errors to result structure for later use
			$result['error'] = 'Some fields are still required.';
			$result['errors'] = $errors;
		} 

		// if there are no errors proceed
		else {

			// attempt to create/save subscriber
			$subscriber_id = elb_save_subscriber($subscriber_data);

			// if subscriber was saved successfully $subscriber_id will be greater than 0
			if ($subscriber_id) {

				// if subscriber already has a subscription
				if ( elb_subscriber_has_subscription( $subscriber_id, $list_id)) {

					// get list object
					$list = get_post( $list_id);

					// return detailed error
					$result['error'] = esc_attr( $subscriber_data['email'] . ' is already subscribed to ' . $list->post_title . '.');

				} else {

					// send new subscriber a confirmation email, returns true if we are successful
					$email_sent = elb_send_subscriber_email($subscriber_id, 'new_subscription', $list_id);

					// if email was sent
					if ( !$email_sent ) {

						// email could not be sent
						$result['error'] = 'Email could not be sent';

					} else {

						// subscription saved
						$result['status'] = 1;
						$result['message'] = 'Success! A confirmation email has been sent to ' . $subscriber_data['email'];

						// clean up: remove our empty error
						unset( $result['error']);
						
					}
				}
			}
		}

		
	} catch (Exception $e) {

		// a php error occurred
		$result['error'] = 'Caught exception: ' . $e->getMessage();
	}

	// return result as json
	elb_return_json($result);
}

//5.2
function elb_save_subscriber($subscriber_data) {

	// setup default subscriber id
	// 0 means the subscriber was not saved
	$subscriber_id = 0;

	try {

		$subscriber_id = elb_get_subscriber_id($subscriber_data['email']);

		// if the subscriber does not already exist
		if (!$subscriber_id) {

			// add new subscriber to database
			$subscriber_id = wp_insert_post(
				array(
					'post_type' => 'elb_subscriber',
					'post_title' => $subscriber_data['fname'] . ' ' . $subscriber_data['lname'],
					'post_status' => 'publish',
				), 
				true
			);

		}

		// add/update custom meta data
		update_field(elb_get_acf_key('elb_fname'), $subscriber_data['fname'], $subscriber_id);
		update_field(elb_get_acf_key('elb_lname'), $subscriber_data['lname'], $subscriber_id);
		update_field(elb_get_acf_key('elb_email'), $subscriber_data['email'], $subscriber_id);

	} catch (Exception $e) {

		// a php error occurred
	}

	// return subscriber id
	return $subscriber_id;
}

//5.3
function elb_add_subscription($subscriber_id, $list_id) {

	// setup default return value
	$subscription_saved = false;

	// if subscriber doesn't have the current list subscription
	if( !elb_subscriber_has_subscription($subscriber_id, $list_id)) {

		// get subscription and append new $list_id
		$subscriptions = elb_get_subscriptions($subscriber_id);
		array_push($subscriptions, $list_id);

		// update elb_subscriptions
		update_field(elb_get_acf_key('elb_subscriptions'), $subscriptions, $subscriber_id);

		// subscriptions updated!
		$subscription_saved = true;

	}

	// return result
	return $subscription_saved;
	
}

//5.4
// unsubscribes user from one or more lists
function elb_unsubscribe() {

	// setup default results data
	$result = array(
		'status' => 0,
		'message' => 'Subscriptions were NOT Updated.',
		'error' => '',
		'errors' => array()
	);

	$subscriber_id = ( isset($_POST['subscriber_id'])) ? esc_attr( (int)$_POST['subscriber_id']) : 0;
	$list_ids = (isset($_POST['list_ids'])) ? $_POST['list_ids'] : 0;

	try {

		// if there are lists to emove
		if( is_array($list_ids)) {

			// loop over lists to remove
			foreach ($list_ids as &$list_id) {

				// remove this subscription
				elb_remove_subscription( $subscriber_id, $list_id);
			}
		}

		// setup success status and message
		$result['status'] = 1;
		$result['message'] = 'Subscriptions updated.';

		// get the udapted list of subscriptions as html
		$result['html'] = elb_get_manage_subscriptions_html( $subscriber_id);

	} catch (Exception $e) {

		// php error
	}

	// return result as json
	elb_return_json($result);

}

//5.5
// remove a single subscription from a subscriber
function elb_remove_subscription($subscriber_id, $list_id) {

	// setup default return value
	$subscription_saved = false;

	// if the subscriber has the current list subscription
	if ( elb_subscriber_has_subscription($subscriber_id, $list_id)) {

		// get current subscriptions
		$subscriptions = elb_get_subscriptions($subscriber_id);

		// get the position of the $list_id to remove
		$needle = array_search($list_id, $subscriptions);

		// remove $list_id from $subscription array
		unset($subscriptions[$needle]);

		// update elb subscriptions
		update_field(elb_get_acf_key('elb_subscriptions'), $subscriptions, $subscriber_id);

		// subscriptions updated
		$subscription_saved = true;


	}

}

//5.6
// hint: sends a unique cutomized email to a subscriber
function elb_send_subscriber_email( $subscriber_id, $email_template_name, $list_id ) {

	// return variable
	$email_sent = false;

	// get email template data
	$email_template_object = elb_get_email_template( $subscriber_id, $email_template_name, $list_id);

	// if email template data was found
	if ( !empty( $email_template_object )) {

		// get subscriber data
		$subscriber_data = elb_get_subscriber_data( $subscriber_id );

		// set wp_mail headers
		$wp_mail_headers = array('Content-Type: text/html; charset=UTF-8');

		// use wp_mail to send email
		$email_sent = wp_mail( array( $subscriber_data['email']), $email_template_object['subject'], $email_template_object['body'], $wp_mail_headers);

	}

	return $email_sent;
}

//5.7
// adds subscription to database and send confirmation email to the subscriber
function elb_confirm_subscription( $subscriber_id, $list_id ) {

	// setup return value
	$optin_complete = false;

	// add new subscription
	$subscription_saved = elb_add_subscription( $subscriber_id, $list_id );

	// if subscription was saved
	if ( $subscription_saved ) {

		// send email
		$email_sent = elb_send_subscriber_email( $subscriber_id, 'subscription_confirmed', $list_id );

		// if email sent
		if ($email_sent) {

			// return true
			$optin_complete = true;

		}
	}

	return $optin_complete;
}

//5.8
// create custom tables for plugin
function elb_create_plugin_tables() {

	global $wpdb;

	// setup return variable
	$return_value = false;

	try {

		$table_name = $wpdb->prefix . 'elb_reward_links';
		$charset_collate = $wpdb->get_charset_collate();
		
		// sql for our table creation
		$sql = "CREATE TABLE $table_name (
			id mediumint(11) NOT NULL AUTO_INCREMENT,
			uid varchar(128) NOT NULL,
			subscriber_id mediumint(11) NOT NULL,
			list_id mediumint(11) NOT NULL,
			attachment_id mediumint(11) NOT NULL,
			downloads mediumint(11) DEFAULT 0 NOT NULL,
			UNIQUE KEY id (id)
			) $charset_collate;";

		// make sure we include wordpress functions for dbDelta
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// dbDelta will create a new table if none exists or update an existing one
		dbDelta($sql);

		// return value
		$return_value = true;

	} catch (Exception $e) {

		var_dump($e->getMessage());
		die();
	}

	return $return_value;
}

//5.9
// runs on plugin activation
function elb_activate_plugin() {

	// setup custom database tables
	elb_create_plugin_tables();

}

//5.10
// this function adds new reward links to the database
function elb_add_reward_link( $uid, $subscriber_id, $list_id, $attachment_id ){

	global $wpdb;

	// setup return value
	$return_value = false;

	try {

		$table_name = $wpdb->prefix . 'elb_reward_links';

		$wpdb->insert(
			$table_name,
			array(
				'uid' => $uid,
				'subscriber_id' => $subscriber_id,
				'$list_id' => $list_id,
				'$attachment_id' => $attachment_id
			),
			array(
				'%s',
				'%d',
				'%d',
				'%d',
			)
		);

		$return_value = true;

	} catch (Exception $e) {

		//php error

	}

	return $return_value;
}

//5.11
// triggers download of a reward file
function elb_trigger_reward_download() {

	global $post;

	if ( $post->ID = elb_get_option( 'elb_reward_page_id') && isset($_GET['reward'])) {

		$uid = ($_GET['reward']) ? (string)$_GET['reward'] : 0;

		// get reward from link uid
		$reward = elb_get_reward( $uid );

		// if reward was found
		if ( $reward !== false && $reward['downloads'] < elb_get_option('elb_download_limit') ) {

			elb_update_reward_links_downloads( $uid );

			// trigger browser download
			header('Content-Type: application/' . $reward['file']['mime_type'], true, 200 );
			header('Content-Disposition: attachment; filename='. $reward['title']);
			header('Pragma: no-cache');
			header('Expires: 0');
			readfile($reward['file']['url']);
			exit();

		}

	}
}

//5.12
// this function increases te download link count by one
function elb_update_reward_link_downloads( $uid ) {

	global $wpdb;

	// setup our return value
	$return_value = false;

	try {

		$table_name = $wpdb->prefix . 'elb_reward_links';

		// get current download count
		$current_count = $wpdb->get_var( 
			$wpdb->prepare(
				'SELECT downloads
				FROM $table_name
				WHERE uid = %s',
				$uid 
			)
		);

		$current_count = (int)$current_count + 1;

		$wpdb->query( 
			$wpdb->prepare(
				'UPDATE $table_name
				SET downloads = $current_count
				WHERE uid = %s',
				$uid 
			)
		);

		$return_value = true;

	} catch (Exception $e) {

		// php error

	}

	return $return_value;

}

//5.13
// this function generates a csv file for our subscriber data
// it expects $_GET['list_id'] to be set in the URL
function elb_download_subscribers_csv() {

	// get the list id from the URL scope
	$list_id = ( isset($_GET['list_id'])) ? (int)$_GET['list_id'] : 0;

	// setup our return data
	$csv = '';

	// get the list object
	$list = get_post( $list_id );

	// get the list's subscribers or get all subscribers if no list is given
	$subscribers = elb_get_list_subscribers( $list_id );

	// if we have confirmed subscribers
	if ( $subscribers !== false ) {

		// get the current date
		$now = new DateTime();

		// setup a unique filename for the generated export file
		$fn1 = 'easy-list-builder-export-list-id-' . $list_id . '-date-' . $now->format('Y-m-d') . '.csv';
		$fn2 = plugin_dir_path( __FILE__ ) . 'exports/' . $fn1;

		// open new file in write mode
		$fp = fopen($fn2, 'w');

		// get the first subscriber's data
		$subscriber_data = elb_get_subscriber_data( $subscribers[0] );

		// remove the subscriptions and name column for the data
		unset($subscriber_data['subscriptions']);
		unset($subscriber_data['name']);

		// build our csv header array from $subscriber_data's data keys
		$csv_headers = array();

		foreach ( $subscriber_data as $key => $value ) {
			array_push($csv_headers, $key);
		}

		// append csv headers to our csv file
		fputcsv($fp, $csv_headers);

		// loop over all subscribers
		foreach ( $subscribers as &$subscriber_id) {

			// get the subscriber data of the current subscriber
			$subscriber_data = elb_get_subscriber_data( $subscriber_id );

			// remove the subscriptions and name column for the data
			unset($subscriber_data['subscriptions']);
			unset($subscriber_data['name']);

			// append this subscriber data to our csv file
			fputcsv($fp, $subscriber_data);

		}

		// read open our file in read mode
		$fp = fopen( $fn2, 'r' );

		// read our csv file and store its content in $fc
		$fc = fread( $fp, filesize($fn2) );

		//close our open file pointer
		fclose($fp);

		// setup file headers
		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename=" . $fn1);

		// echo the contents of our file and return it to the browser
		echo($fc);

		// exit php processes
		exit;
	} 

	// return false if unable to fetch subscribers
	return false;
}

//5.14
// retrieve csv file from server and parse it to a php array
// it then returns that object as a json formatted object
// this function is an ajax form handler 
// it expects $_POST['elb_import_file_id']
function elb_parse_import_csv() {

	// setup return array
	$result = array(
		'status' => 0,
		'message' => 'Error, could not parse import csv.',
		'error' => '',
		'data' => array(
		)
	);

	try {

		// get the attachment id from $_POST['elb_import_file_id']
		$attachment_id = ( isset($_POST['elb_import_file_id']) ) ? esc_attr( $_POST['elb_import_file_id'] ) : 0;

		// get the filename using wp's get attached_file
		$filename = get_attached_file( $attachment_id );

		// if we got a filename
		if ( $filename !== false ) {


			// parse the data to a php array using elb_csv_to_array
			$csv_data = elb_csv_to_array( $filename, ',' );

			// if we were able to parse the file and there is data in it
			if ( $csv_data !== false && count( $csv_data ) ) {

				// append the data to our result array and return success
				$result = array(
					'status' => 1,
					'message' => 'CSV import data parsed successfully.',
					'error' => '',
					'data' => $csv_data
				);			

			}

		} else {

			$result['error'] = 'The import file does not exist.';

		}

	} catch ( Exception $e ) {

		// php error

	}

	elb_return_json( $result );

}


/* 6. HELPERS */

//6.1
function elb_subscriber_has_subscription($subscriber_id, $list_id) {

	// setup default return value
	$has_subscription = false;

	// get subscriber
	$subscriber = get_post($subscriber_id);

	// get subscriptions
	$subscriptions = elb_get_subscriptions($subscriber_id);

	// check if list id is in subscriptions
	if ( in_array($list_id, $subscriptions)) {

		// found the $list_id in $subscriptions
		// this subscriber is already subscribed to the list
		$has_subscription = true;

	} else {

		// subscriber not in list
	};

	return $has_subscription;
}

//6.2 
function elb_get_subscriber_id($email) {

	// default return value
	$subscriber_id = 0;

	try {

		// check if subscriber already exists
		$subscriber_query = new WP_Query(
			array(
				'post_type' => 'elb_subscriber',
				'posts_per_page' => 1,
				'meta_key' => 'elb_email',
				'meta_query' => array(
					array (
						'key' => 'elb_email',
						'value' => $email,
						'compare' => '=',
					)
				),
			)
		);

		// if the subscriber exists
		if ($subscriber_query->have_posts()) {

			// get the subscriber id
			$subscriber_query->the_post();
			$subscriber_id = get_the_ID();
		}

	} catch (Exception $e) {
		// an error occurred
	}

	// reset the WordPress post object
	wp_reset_query();

	return (int)$subscriber_id;

}

//6.3
function elb_get_subscriptions($subscriber_id) {

	$subscriptions = array();

	// get subscriptions (returns array of list objects)
	$lists = get_field(elb_get_acf_key('elb_subscriptions'), $subscriber_id);



	// check if $lists returns something
	if ($lists) {

		// if $lists is an array and there is one or more items in it
		if (is_array($lists) && count($lists)) {

			// build subscriptions: array of list id's
			foreach ($lists as &$list) {
				array_push($subscriptions, $list->ID);
			}

		}

		else if ( is_numeric($lists)) {
			// single result returned
			array_push($subscriptions, $lists);

		}
	}

	return (array)$subscriptions;
}

//6.4
function elb_return_json($array) {

	// encode result as json string
	$json_result = json_encode($array);

	// return result
	die($json_result);

	// stop all other processing
	exit;
}

//6.5
function elb_get_acf_key($field_name) {

	$field_key = $field_name;

	switch ($field_name) {
		case 'elb_fname':
			$field_key = 'field_57bc051fb1d32';
			break;
		case 'elb_lname':
			$field_key = 'field_57bc0548b1d33';
			break;
		case 'elb_email':
			$field_key = 'field_57bc0562b1d34';
			break;
		
		case 'elb_subscriptions':
			$field_key = 'field_57bc0583b1d35';
			break;

		case 'elb_enable_reward':
			$field_key = 'field_57c840a86fd74';
			break;

		case 'elb_reward_title':
			$field_key = 'field_57c841046fd75';
			break;

		case 'elb_reward_file':
			$field_key = 'field_57c8413f6fd76';
			break;

	}

	return $field_key;

}

// 6.6
function elb_get_subscriber_data($subscriber_id) {

	// setup subscriber data
	$subscriber_data = array();

	// get subscriber object
	$subscriber = get_post($subscriber_id);

	// if subscriber is valid
	if ( isset($subscriber->post_type) && $subscriber->post_type == 'elb_subscriber') {

		$fname = get_field(elb_get_acf_key('elb_fname'), $subscriber_id);
		$lname = get_field(elb_get_acf_key('elb_lname'), $subscriber_id);

		// build subscriber data for return
		$subscriber_data = array(
			'name' =>  $fname . ' ' . $lname,
			'fname' => $fname,
			'lname' => $lname,
			'email' => get_field(elb_get_acf_key('elb_email'), $subscriber_id),
			'subscriptions' => elb_get_subscriptions($subscriber_id)
		);
	}

	return $subscriber_data;

}

// 6.7
// hint: returns html for a page selector
function elb_get_page_select( $input_name="elb_page", $input_id="", $parent=-1, $value_field="id", $selected_value="" ) {
	
	// get WP pages
	$pages = get_pages( 
		array(
			'sort_order' => 'asc',
			'sort_column' => 'post_title',
			'post_type' => 'page',
			'parent' => $parent,
			'status'=>array('draft','publish'),	
		)
	);
	
	// setup our select html
	$select = '<select name="'. $input_name .'" ';
	
	// IF $input_id was passed in
	if( strlen($input_id) ):
	
		// add an input id to our select html
		$select .= 'id="'. $input_id .'" ';
	
	endif;
	
	// setup our first select option
	$select .= '><option value="">- Select One -</option>';
	
	// loop over all the pages
	foreach ( $pages as &$page ): 
	
		// get the page id as our default option value
		$value = $page->ID;
		
		// determine which page attribute is the desired value field
		switch( $value_field ) {
			case 'slug':
				$value = $page->post_name;
				break;
			case 'url':
				$value = get_page_link( $page->ID );
				break;
			default:
				$value = $page->ID;
		}
		
		// check if this option is the currently selected option
		$selected = '';
		if( $selected_value == $value ):
			$selected = ' selected="selected" ';
		endif;
	
		// build our option html
		$option = '<option value="' . $value . '" '. $selected .'>';
		$option .= $page->post_title;
		$option .= '</option>';
		
		// append our option to the select html
		$select .= $option;
		
	endforeach;
	
	// close our select html tag
	$select .= '</select>';
	
	// return our new select 
	return $select;
	
}

//6.8
// hint: returns default options 
function elb_get_default_options() {

	$defaults = array();

	try {

		// get front page id
		$front_page_id = get_option('page_on_front');

		// setup default email footer
		$default_email_footer = '
			<p>Sincerely, <br /><br />
			The ' . get_bloginfo('name') . ' Team <br />
			<a href="' . get_bloginfo('url') . '">' . get_bloginfo('url') . '</a>
			</p>
		';

		// setup defaults array
		$defaults = array(
			'elb_manage_subscription_page_id' => $front_page_id,
			'elb_confirmation_page_id' => $front_page_id,
			'elb_reward_page_id' => $front_page_id,
			'elb_default_email_footer' =>  $default_email_footer,
			'elb_download_limit' => 3,
		);
	} catch (Exception $e) {

		// php error
	}

	// return defaults
	return $defaults;
}

//6.9
// hint: returns the requested page option value or its default
function elb_get_option( $option_name ) {

	// setup return variable
	$option_value = '';

	try {

		// get default option values
		$defaults = elb_get_default_options();

		// get the requested option
		switch( $option_name ) {

			case 'elb_manage_subscription_page_id':

				// subscription page id
				$option_value = (get_option('elb_manage_subscription_page_id')) ? get_option('elb_manage_subscription_page_id') : $defaults['elb_manage_subscription_page_id'];
				break;

			case 'elb_confirmation_page_id':

				// confirmation page id
				$option_value = (get_option('elb_confirmation_page_id')) ? get_option('elb_confirmation_page_id') : $defaults['elb_confirmation_page_id'];
				break;

			case 'elb_reward_page_id':

				// reward page id
				$option_value = (get_option('elb_reward_page_id')) ? get_option('elb_reward_page_id') : $defaults['elb_reward_page_id'];
				break;

			case 'elb_default_email_footer':

				// email footer
				$option_value = (get_option('elb_default_email_footer')) ? get_option('elb_default_email_footer') : $defaults['elb_default_email_footer'];
				break;

			case 'elb_download_limit':

				// reward download limit
				$option_value = (get_option('elb_download_limit')) ? get_option('elb_download_limit') : $defaults['elb_download_limit'];
				break;
		}

	} catch (Exception $e) {
			// php error
	}

	// return option value or its default
	return $option_value;
}

//6.10
// hint: get the current options and return values in associative array
function elb_get_current_options() {

	// setup our return variable
	$current_options = array();

	try {

		// build our current options associative array
		$current_options = array(
			'elb_manage_subscription_page_id' => elb_get_option('elb_manage_subscription_page_id'),
			'elb_confirmation_page_id' => elb_get_option('elb_confirmation_page_id'),
			'elb_reward_page_id' => elb_get_option('elb_reward_page_id'),
			'elb_default_email_footer' => elb_get_option('elb_default_email_footer'),
			'elb_download_limit' => elb_get_option('elb_download_limit')
		);

	} catch (Exception $e) {
		// php error
	}

	return $current_options;
}

//6.11
// hint: creates html form to manage subscriptions
function elb_get_manage_subscriptions_html($subscriber_id) {

	$output = '';

	try {

		// get array of list_ids for this subscriber
		$lists = elb_get_subscriptions($subscriber_id);

		// get subscriber data
		$subscriber_data = elb_get_subscriber_data($subscriber_id);

		// set the title
		$title = $subscriber_data['fname'] . '\'s Subscriptions';

		// build output html

		$output = '
			<form id="elb_manage_subscriptions_form" class="elb elb_form" method="post" action="/wp-admin/admin-ajax.php?action=elb_unsubscribe">

				<input type="hidden" name="subscriber_id" value="' . $subscriber_id . '">

				<h3 class="elb-title">' . $title . '</h3>';

				if (!count($lists)) {

					$output .= '<p>There are no active subscriptions</p>';

				} else {
					$output .= '
						<table>
							<tbody>';

					// loop over lists
					foreach ($lists as &$list_id) {


						$list_object = get_post( $list_id );

						$output .= '
							<tr>
								<td>' . $list_object->post_title . '</td>
								<td> 
									<label>
										<input type="checkbox" name="list_ids[]" value="' . $list_object->ID .'"/> UNSUBSCRIBE
									</label>
								</td>
							</tr>';

					}

					$output .= '		
							</tbody>
						</table>

						<p><input type="submit" value="Save Changes" /></p>';

				}

		$output .= '
			</form>
		';

	} catch (Exception $e) {

		// php error

	}

	return $output;

}

//6.12
// return an array of email template data if the template exists
function elb_get_email_template( $subscriber_id, $email_template_name, $list_id) {

	// setup return variable
	$template_data = array();

	// create new array to store email templates
	$email_templates = array();

	// get list object
	$list = get_post( $list_id );

	// get subscriber object
	$subscriber = get_post( $subscriber_id );

	if ( !elb_validate_list( $list ) || !elb_validate_subscriber( $subscriber ) ) {

		// the list or the subscriber are not valid
	} else {

		// get subscriber data
		$subscriber_data = elb_get_subscriber_data( $subscriber_id );

		// get unique manage subscription link
		$manage_subscriptions_link = elb_get_manage_subscriptions_link( $subscriber_data['email'], $list_id );

		// get default email header
		$default_email_header = '<p>Hello, ' . $subscriber_data['fname'] . '!</p>';

		// get default email footer
		$default_email_footer = elb_get_option('elb_default_email_footer');

		// setup unsubscribe text
		$unsubscribe_text = '
			<br /><br />
			<hr />
			<p><a href="'. $manage_subscriptions_link . '">Click here to unsubscribe</a> from this or any other email lists.</p>';

		// get reward
		$reward = elb_get_list_reward( $list_id );

		// setup reward text
		$reward_text = '';

		// if reward exists
		if ( $reward !== false ) {

			// setup the appropriate reward text
			switch ( $email_template_name ) {

				case 'new_subscription':

					// set reward text
					$reward_text = '<p>After confirming your subscription, we will send you a link for a FREE DOWNLOAD of ' . $reward['title'] . '</p>';
					break;

				case 'subscription_confirmed':

					// get download limit
					$download_limit = elb_get_option( 'elb_download_limit ');

					// generate new download link
					$download_link = elb_get_reward_link( $subscriber_id, $list_id );

					// set reward text
					$reward_text = '<p>Here is your <a href="' . $download_link . '">UNIQUE DOWNLOAD LINK</a> for ' . $reward['title'] . '. This link will expire after'. $download_limit . '.</p>';

					break;

			}

		}

		// setup email templates


			// get unique opt-in link
			$optin_link = elb_get_option_link( $subscriber_data['email'], $list_id);

			// template: new_subscription
			$email_templates['new_subscription'] = array(
				'subject' => 'Thank you for subscribing to' . $list->post_title . '! Please confirm your subscription.',
				'body' => '' . $default_email_header . '
					<p>Thank you for subscribing to ' . $list->post_title . '!</p>
					<p>Please <a href="' . $optin_link . '">click here to confirm your subscription.</a></p>'
					. $reward_text . $default_email_footer . $unsubscribe_text,
			);

			// template: subscription_confirmed
			$email_templates['subscription_confirmed'] = array(
				'subject' => 'You are now subscribed to' . $list->post_title . '!',
				'body' => '' . $default_email_header . '
					<p>You are now subscribed to ' . $list->post_title . '!</p>
					<p>Thank you for confirming your subscription!</p>'
					. $reward_text . $default_email_footer . $unsubscribe_text,
			);
	}

	// if the email template exists
	if ( isset( $email_templates[ $email_template_name ])) {

		// add template data to return variable
		$template_data = $email_templates[ $email_template_name ];

	}

	// return template data
	return $template_data;

}

//6.13
// validates whether the post object exists and if it is a valid post type
function elb_validate_list( $list_object ) {

	// setup return variable
	$list_valid = false;

	if ( isset($list_object->post_type) && $list_object->post_type == 'elb_list') {
		$list_valid = true;
	}

	return $list_valid;

}

//6.14
// validates whether the post object exists and if it is a valid post type
function elb_validate_subscriber( $subscriber_object ) {

	// setup return variable
	$subscriber_valid = false;

	if ( isset($subscriber_object->post_type) && $subscriber_object->post_type == 'elb_subscriber') {

		$subscriber_valid = true;
	}

	return $subscriber_valid;

}

//6.15
// returns a unique link for managing a user's subscription
function elb_get_manage_subscriptions_link( $email, $list_id=0 ) {

	$link_href = '';

	try {

		$page = get_post( elb_get_option('elb_manage_subscription_page_id'));
		$slug = $page->post_name;

		$permalink = get_permalink($page);

		// get character to start querystring
		$startquery = elb_get_querystring_start( $permalink );

		$link_href = $permalink . $startquery . 'email=' . urlencode($email) . '&list=' . $list_id;

	} catch (Exception $e) {

		//$link_href = $e->getMessage();

	}

	return esc_url($link_href);
}

// 6.16
// returns the appropriate character for the beginning of a query string
function elb_get_querystring_start( $permalink ) {

	// setup our default return variable
	$querystring_start = '&';

	// if ? is not found in the permalink
	if ( strpos($permalink, '? ') === false ) {

		$querystring_start = '?';

	}

	return $querystring_start;
}

//6.17
// returns unique link for opting to an email list
function elb_get_optin_link( $email, $list_id=0) {

	$link_href = '';

	try {

		$page = get_post( elb_get_option('elb_confirmation_page_id'));
		$slug = $page->post_name;

		$permalink = get_permalink($page);

		// get character to start querystring
		$startquery = elb_get_querystring_start( $permalink );

		$link_href = $permalink . $startquery . 'email=' . urlencode($email) . '&list=' . $list_id;

	} catch (Exception $e) {

		//$link_href = $e->getMessage();

	}

	return esc_url($link_href);
}

//6.18
// return html messages
function elb_get_message_html( $message, $message_type ) {

	$output = '';

	try {

		$message_class = 'confirmation';

		switch ( $message_type ) {

			case 'warning' :

				$message_class = 'elb-warning';
				break;

			case 'error' :

				$message_class = 'elb-error';
				break;

			case 'confirmation' :

				$message_class = 'elb-confirmation';
				break;
			 
		}

		$output .= '
			<div class="elb-message-container">
				<div class="elb-message ' . $message_class . '">
					<p>' . $message . '</p>
				</div>
			</div>
		';


	} catch (Exception $e) {

	}

	return $output;


}

// 6.19
// return false if list has no reward or returns the object containing file and title if it does
function elb_get_list_reward( $list_id ) {

	// setup return data
	$reward_data = false;

	// get enable_reward alue
	$enable_reward = ( get_field( elb_get_acf_key('elb_enable_reward'), $list_id) ) ? true : false;

	// if reward is enabled
	if ( $enable_reward) {

		// get reward file
		$reward_file = ( get_field(elb_get_acf_key('elb_reward_file'), $list_id) ) ? get_field( elb_get_acf_key('elb_reward_file'), $list_id) : false;

		// get reward title
		$reward_title = ( get_field (elb_get_acf_key('elb_reward_title'), $list_id) ) ? get_field( elb_get_acf_key('elb_reward_title'), $list_id) : 'Reward';

		// if reward_file is a valid array
		if ( is_array($reward_file) ) {


			// setup return data
			$reward_data = array(
				'file' => $reward_file,
				'title' => $reward_title,
			);
		}	
	}

	return $reward_data;
}

//6.20
// returns unique link for downloading a reward file
function elb_get_reward_link( $subscriber_id, $list_id ) {

	$link_href = '';

	try {

		$page = get_post( elb_get_option( 'elb_reward_page_id') );
		$slug = $page->post_name;
		$permalink = get_permalink($page);

		// generate unique uid for reward link
		$uid = elb_gererate_reward_uid( $subscriber_id, $list_id );

		// get list reward
		$reward = elb_get_list_reward( $list_id );

		// if an attachment id was returned
		if ( $uid && $reward !== false ) {

			// add reward link to database
			$link_added = elb_add_reward_link( $uid, $subscriber_id, $list_id, $reward['list']['file']);

			// if link was added successfuly
			if ( $link_added == true ) {

				// get character to start querystring
				$startquery = elb_get_querystring_start( $permalink );

				// build reward link
				$link_href = $permalink . $startquery . 'reward=' . urlencode($uid);

			}

		}

	} catch (Exeption $e) {

		//php error

	}

	// return reward link
	return esc_url( $link_href );
}

//6.21
// generate unique uid
function elb_generate_reward_uid( $subscriber_id, $list_id ) {

	// setup return value
	$uid = '';

	// get subscriber post object
	$subscriber = get_post( $subscriber_id );

	// get list post object
	$list = get_post( $list_id );

	// if the subscriber and list are valid
	if ( elb_validate_subscriber( $subscriber ) && elb_validate_list( $list ) ){

		// get list reward
		$reward = elb_get_list_reward( $list_id );

		// if reward is not equal to false
		if ( $reward !== false ) {

			// generate an unique id
			$uid = uniqid( 'elb', true );

		}



	}

	return $uid;

}

//6.22
// return false if list has no reward or return associative array if reward exists
function elb_get_reward( $uid ) {

	global $wpdb;

	// setup return data
	$reward_data = false;

	// reward links download table name
	$table_name = $wpdb->prefix . 'elb_reward_links';

	// get list id from reward link
	$list_id = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT list_id
			FROM $table_name
			WHERE uid = %s',
			$uid
		)
	);

	// get download from reward link
	$downloads = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT downloads
			FROM $table_name
			WHERE uid = %s',
			$uid
		)
	);

	// get reward data
	$reward = elb_get_list_reward( $list_id );

	// if reward was found
	if ( $reward !== false ) {

		// set reward data
		$reward_data = $reward;

		// add downloads to reward data
		$reward_data['downloads'] = $downloads;

	}

	return $reward_data;
}

//6.23
// return array of subscribers ids
function elb_get_list_subscribers( $list_id ) {

	// setup return variable
	$subscribers = false;

	// get list object
	$list = get_post( $list_id );

	if ( elb_validate_list( $list ) ) {
		// query all subscribers from this list only
		$subscribers_query = new WP_Query(
			array(
				'post_type' => 'elb_subscriber',
				'published' => true,
				'posts_per_page' => -1,
				'order_by' => 'post_date',
				'order' => 'DESC',
				'status' => 'publish',
				'meta_query' => array(
					array(
						'key' => 'elb_subscriptions',
						'value' => ':"' . $list->ID . '"',
						'compare' => 'LIKE'
					)
				)
			)
		);

	} elseif ( $list_id === 0 ) {
		// query all subscribers from all lists
		$subscribers_query = new WP_Query(
			array(
				'post_type' => 'elb_subscriber',
				'published' => true,
				'posts_per_page' => -1,
				'order_by' => 'post_date',
				'order' => 'DESC'
			)
		);
	}

	// if $subscribers_query is set and returns results
	if ( isset( $subscribers_query ) && $subscribers_query->have_posts() ) {
		// set subscribers array
		$subscribers = array();

		// loop over results
		while ( $subscribers_query->have_posts() ) {

			// get the post object
			$subscribers_query->the_post();

			$post_id = get_the_ID();

			// append result to subscribers array
			array_push( $subscribers, $post_id );

		}
	}

	// reset wp query/postdata
	wp_reset_query();
	wp_reset_postdata();

	// return results
	return $subscribers;

}

//6.24
// returns the amount of subscribers in the list
function elb_get_list_subscriber_count( $list_id = 0 ) {

	// setup return variable
	$count = 0;

	// get array of subscribers ids
	$subscribers = elb_get_list_subscribers( $list_id );

	// if array was returned
	if ( $subscribers !== false ) {

		// update count
		$count = count($subscribers);

	}

	return $count;
}

//6.25
// returns unique link for downloading subscribers csv
function elb_get_export_link( $list_id = 0 ) {

	$link_href = 'admin-ajax.php?action=elb_download_subscribers_csv&list_id=' . $list_id;

	// return reward link
	return esc_url($link_href);

}

//6.26
// reads a csv file and converts to php array
function elb_csv_to_array( $filename='', $delimiter=',') {

	// this is an important setting
	ini_set('auto_detect_line_endings', true);

	// check if file exists and is readable
	if ( !file_exists( $filename ) || !is_readable( $filename ) ) {

		return FALSE;

	}

	// setup our return data
	$return_data = array();

	// if we can open and read the file
	if ( ( $handle = fopen( $filename, 'r' ) ) !== FALSE ) {

		$row = 0;

		// while data exists loop over data
		while ( ( $data = fgetcsv($handle, 1000, ',')) !== FALSE ) {

			// count the number of items in the data
			$num = count($data);

			// increment our row variable
			$row++;

			// setup our row data array
			$row_data = array();

			// loop over all items and append them to our row data
			for ( $c = 0; $c < $num; $c++ ) {

				// if this is the first row set it up as our header
				if ( $row == 1 ) {

					$header[] = $data[$c];

				} else {

					// all rows greater than 1
					// add row data item
					$return_data[$row-2][$header[$c]] = $data[$c]; 

				}
			}
		}

		// close our file
		fclose( $handle );

	}

	return $return_data;

}


/* 7. CUSTOM POST TYPES */

//7.1
// Subscribers
include_once( plugin_dir_path(__FILE__) . 'cpt/elb_subscriber.php');

//7.2
// Lists
include_once( plugin_dir_path(__FILE__) . 'cpt/elb_list.php');


/* 8. ADMIN PAGES */

// 8.1
// hint: dashboard admin page
function elb_dashboard_admin_page() {
	
	// get subscribers export link
	$export_link = elb_get_export_link();


	$output = '
		<div class="wrap">
			
			<h2>Easy List Builder</h2>
			
			<p>The ultimate email list building plugin for WordPress. Capture new subscribers. Reward subscribers with a custom download upon opt-in. Build unlimited lists. Import and export subscribers easily with .csv</p>

			<p><a href="' .$export_link . '" class="button button-primary">Export All Subscriber Data</a></p>
		
		</div>
	';
	
	echo $output;
	
}

// 8.2
// hint: import subscribers admin page
function elb_import_admin_page() {
	
	// enque special scripts required for the file import field
	wp_enqueue_media();

	echo('<div class="wrap" id="import_subscribers">
			
			<h2>Import Subscribers</h2>
						
			<form id="import_form_1">
			
				<table class="form-table">
				
					<tbody>
				
						<tr>
							<th scope="row"><label for="elb_import_file">Import CSV</label></th>
							<td>
								
								<div class="wp-uploader">
								    <input type="text" name="elb_import_file_url" class="file-url regular-text" accept="csv">
								    <input type="hidden" name="elb_import_file_id" class="file-id" value="0" />
								    <input type="button" name="upload-btn" class="upload-btn button-secondary" value="Upload">
								</div>
								
								
								<p class="description" id="elb_import_file-description">This is the page where Easy List Builder will send subscribers to manage their subscriptions. <br />
									IMPORTANT: In order to work, the page you select must contain the shortcode: <strong>[elb_manage_subscriptions]</strong>.</p>
							</td>
						</tr>
						
					</tbody>
					
				</table>
				
			</form>
			
			<form id="import_form_2">
				
				<table class="form-table">
				
					<tbody class="elb-dynamic-content">
						
					</tbody>
					
					<tbody class="form-table show-only-on-valid" style="display: none">
						
						<tr>
							<th scope="row"><label>Import To List</label></th>
							<td>
								<select name="elb_import_list_id">');
									
									
										// get all our email lists
										$lists = get_posts(
											array(
												'post_type'			=>'elb_list',
												'status'			=>'publish',
												'posts_per_page'   	=> -1,
												'orderby'         	=> 'post_title',
												'order'            	=> 'ASC',
											)
										);
										
										// loop over each email list
										foreach( $lists as &$list ):
										
											// create the select option for that list
											$option = '
												<option value="'. $list->ID .'">
													'. $list->post_title .'
												</option>';
											
											// echo the new option	
											echo $option;
											
										
										endforeach;
										
								echo('</select>
								<p class="description"></p>
							</td>
						</tr>
						
					</tbody>
					
				</table>
				
				<p class="submit show-only-on-valid" style="display:none"><input type="submit" name="submit" id="submit" class="button button-primary" value="Import"></p>
				
			</form>
			
	</div>
	
	');
	
}

// 8.3
// hint: plugin options admin page
function elb_options_admin_page() {

	// get the default values for our options
	$options = elb_get_current_options();
	
	echo('<div class="wrap">
		
		<h2>Easy List Builder Options</h2>
		
		<form action="options.php" method="post">');

			// outputs a unique nounce for our plugin options
			settings_fields('elb_plugin_options');

			// generates a unique hidden field with our form handling url
			@do_settings_fields('elb_plugin_options');

			echo ('<table class="form-table">
			
				<tbody>
			
					<tr>
						<th scope="row"><label for="elb_manage_subscription_page_id">Manage Subscriptions Page</label></th>
						<td>
							'. elb_get_page_select( 'elb_manage_subscription_page_id', 'elb_manage_subscription_page_id', 0, 'id', $options['elb_manage_subscription_page_id']) .'
							<p class="description" id="elb_manage_subscription_page_id-description">This is the page where Easy List Builder will send subscribers to manage their subscriptions. <br />
								IMPORTANT: In order to work, the page you select must contain the shortcode: <strong>[elb_manage_subscriptions]</strong>.</p>
						</td>
					</tr>
					
			
					<tr>
						<th scope="row"><label for="elb_confirmation_page_id">Opt-In Page</label></th>
						<td>
							'. elb_get_page_select( 'elb_confirmation_page_id', 'elb_confirmation_page_id', 0, 'id', $options['elb_confirmation_page_id'] ) .'
							<p class="description" id="elb_confirmation_page_id-description">This is the page where Easy List Builder will send subscribers to confirm their subscriptions. <br />
								IMPORTANT: In order to work, the page you select must contain the shortcode: <strong>[elb_confirm_subscription]</strong>.</p>
						</td>
					</tr>
					
			
					<tr>
						<th scope="row"><label for="elb_reward_page_id">Download Reward Page</label></th>
						<td>
							'. elb_get_page_select( 'elb_reward_page_id', 'elb_reward_page_id', 0, 'id', $options['elb_reward_page_id'] ) .'
							<p class="description" id="elb_reward_page_id-description">This is the page where Easy List Builder will send subscribers to retrieve their reward downloads. <br />
								IMPORTANT: In order to work, the page you select must contain the shortcode: <strong>[elb_download_reward]</strong>.</p>
						</td>
					</tr>
			
					<tr>
						<th scope="row"><label for="elb_default_email_footer">Email Footer</label></th>
						<td>');
						
							
							// wp_editor will act funny if it's stored in a string so we run it like this...
							wp_editor( $options["elb_default_email_footer"], 'elb_default_email_footer', array( 'textarea_rows'=>8 ) );
							
							
							echo('<p class="description" id="elb_default_email_footer-description">The default text that appears at the end of emails generated by this plugin.</p>
						</td>
					</tr>
			
					<tr>
						<th scope="row"><label for="elb_download_limit">Reward Download Limit</label></th>
						<td>
							<input type="number" name="elb_download_limit" value="'. $options['elb_download_limit'] .'" class="" />
							<p class="description" id="elb_download_limit-description">The amount of downloads a reward link will allow before expiring.</p>
						</td>
					</tr>
			
				</tbody>
				
			</table>');
		
			// outputs the WP submit button html;
			@submit_button();
		
		echo('
		</form>
	
	</div>');
	
}

/* 9. SETTINGS */
//9.1
// register plugin options
function elb_register_options() {

	// plugin options
	register_setting('elb_plugin_options', 'elb_manage_subscription_page_id');
	register_setting('elb_plugin_options', 'elb_confirmation_page_id');
	register_setting('elb_plugin_options', 'elb_reward_page_id');
	register_setting('elb_plugin_options', 'elb_default_email_footer');
	register_setting('elb_plugin_options', 'elb_download_limit');

}



