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

	2. SHORTCODES
		2.1 - elb_register_shortcodes()
		2.2 - elb_form_shortcode()

	3. FILTERS
		3.1 - elb_subscriber_column_headers()
		3.2 - elb_subscriber_column_data()
		3.2.2 - elb_register_custom_admin_titles()
		3.2.3 - elb_custom_admin_titles()
		3.3 - elb_list_column_headers()
		3.4 - elb_list_column_data()

	4. EXTERNAL SCRIPS

	5. ACTIONS
		5.1 - elb_save_subscription()
		5.2 - elb_save_subscriber()
		5.3 - elb_add_subscription()

	6. HELPERS
		6.1 - elb_subscriber_has_subscription()
		6.2 - elb_get_subscriber_id()
		6.3 - elb_get_subscriptions()
		6.4 - elb_return_json()
		6.5 - elb_get_acf_key()
		6.6 - elb_get_subscriber_data()

	7. CUSTOM POST TYPES

	8. ADMIN PAGES

	9. SETTINGS

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

/* 2. SHORTCODES */

// 2.1
function elb_register_shortcodes () {

	add_shortcode('elb_form', 'elb_form_shortcode');
}

// 2.2
function elb_form_shortcode($args, $content="") {

	// grab list id
	$list_id = 0;
	if(isset($args['id'])) {
		$list_id = (int)$args['id'];
	};

	// setup our output variable - the html form
	$output = '
		<div class="elb">
			<form id="elb_form" name="elb_form" class="elb-form" method="post" action="/wp-admin/admin-ajax.php?action=elb_save_subscription"> 

				<input type="hidden" name="elb_list" value="' . $list_id . '">
				<p class="elb-input-container">
					<label>Your Name</label>
					<input type="text" name="elb_fname" placeholder="First Name">
					<input type="text" name="elb_lname" placeholder="Last Name">
				</p>
				<p class="elb-input-container">
					<label>Your Email</label>
					<input type="text" name="elb_email" placeholder="Email">
				</p>';

				// Include content to our form if content is passed to function
				if ( strlen($content) ):

					$output .= '<div class="elb-content">' . wpautop($content) . '</div>';

				endif;

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

		case 'shortcode':
			$output .= '[elb_form id='. $post_id . ']';
			break;

	}

	echo $output;
}

//4

/* 5. Actions */

//5.1
function elb_save_subscription() {

	// setup default result data
	$result = array(
		'status' => 0,
		'message' => 'Subscription was not saved',
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

		// attempt to create/save subscriber
		$subscriber_id = elb_save_subscriber($subscriber_data);

		// if subscriber was saved successfully $subscriber_id will be greater than 0
		if ($subscriber_id) {

			// if subscriber already has a subscription
			if ( elb_subscriber_has_subscription( $subscriber_id, $list_id)) {

				// get list object
				$list = get_post( $list_id);

				// return detailed error
				$result['message'] = esc_attr( $subscriber_data['email'] . 'is already subscribed to ' . $list->post_title . '.');

			} else {

				// save new subnscription
				$subscription_saved = elb_add_subscription( $subscriber_id, $list_id);

				// if subscription was saved successfully
				if ($subscription_saved) {

					// subscription saved
					$result['status'] = 1;
					$result['message'] = 'Subscription saved.';
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