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

	2. SHORTCODES
		2.1 - elb_register_shortcodes()
		2.2 - elb_form_shortcode()

	3. FILTERS

	4. EXTERNAL SCRIPS

	5. ACTIONS

	6. HELPERS

	7. CUSTOM POST TYPES

	8. ADMIN PAGES

	9. SETTINGS

*/

/* 1. HOOKS */

// 1.1
// Register our shortcodes
add_action('init', 'elb_register_shortcodes');

/* 2. SHORTCODES */

// 2.1
function elb_register_shortcodes () {

	add_shortcode('elb_form', 'elb_form_shortcode');
}

// 2.2
function elb_form_shortcode($args, $content="") {

	// setup our output variable - the html form
	$output = '
		<div class="elb">
			<form id="elb_form" name="elb_form" class="elb-form" method="post"> 
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