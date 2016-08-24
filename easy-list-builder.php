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

	6. HELPERS
		6.1 - elb_subscriber_has_subscription()
		6.2 - elb_get_subscriber_id()
		6.3 - elb_get_subscriptions()

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
					<input type="text" name="elb_lname" placeholder="First Name">
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
		'title' => __('List Name')
	);

	// return new columns
	return $columns;

}

//3.4
function elb_list_column_data($column, $post_id) {

	// setup our return text
	$output = '';

	switch($column) {

		case 'example':
			// get the custom name data
		/*
			$fname = get_field('elb_fname', $post_id);
			$lname = get_field('elb_lname', $post_id);
			$output .= $fname . ' ' . $lname;
			break;
		*/
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
		$list_id = (int)$__POST['elb_list'];

		// prepare subscriber data
		$subscriber_data = array (
			'fname' => esc_attr($__POST['elb_fname']),
			'lname' => esc_attr($__POST['elb_lname']),
			'email' => esc_attr($__POST['elb_email']),
		);

		// attempt to create/save subscriber
		$subscriber_id = elb_save_subscriber( $subscriber_data);

		// if subscriber was saved successfully $subscriber_id will be greater than 0
		if ( $subscriber_id) {

			// if subscriber already has a subscription
			if ( elb_subscriber_has_subscription( $subscriber_id, $list_id)) {

				// get list object
				$list = get_post( $list_id);

				// return detailed error
				$result['message'] .= esc_attr( $subscriber_data['email'] . 'is already subscribed to ' . $list->post_title . '.');

			} else {

				// save new subnscription
				$subscripton_saved = elb_add_subscription( $subscriber_id, $list_id);

				// if subscription was saved successfully
				if ( $subscription_saved) {

					// subscription saved
					$result['status'] = 1;
					$result['message'] .= 'Subscription saved.';
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
function elb_save_subscriber( $subscriber_data) {

	// setup default subscriber id
	// 0 means the subscriber was not saved
	$subscriber_id = 0;

	try {
		$subscriber_id = elb_get_subscriber_id( $subscriber_data['email']);

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

	// reset the WordPress post object
	wp_reset_query();

	// return subscriber id
	return $subscriber_id;
}

/* 6. HELPERS */
//6.1
function elb_subscriber_has_subscription( $subscriber_id, $list_id) {

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
	}

	return $has_subscription;
}

//6.2 
function elb_get_subscriber_id( $email); {

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
	$lists = get_field( elb_get_acf_key('elb_subscriptions'), $subscriber_id);

	// check if $lists returns something
	if ($lists) {

		// if $lists is an array and there is one or more items in it
		if (is_rray($lists) && count($lists)) {

			// build subscriptions: array of list id's
			foreach ($list as &$lists) {
				$subscriptions[] => (int)$list->ID;
			}
		}

		else if ( is_numeric($lists)) {
			// single result returned
			$subscriptions[] => $lists;

		}

	}
}








