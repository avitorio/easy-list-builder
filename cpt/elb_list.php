<?php

add_action( 'init', 'elb_register_elb_list' );
function elb_register_elb_list() {
	$labels = array(
		"name" => __( 'Lists', 'twentysixteen' ),
		"singular_name" => __( 'List', 'twentysixteen' ),
		);

	$args = array(
		"label" => __( 'Lists', 'twentysixteen' ),
		"labels" => $labels,
		"description" => "",
		"public" => false,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "elb_list", "with_front" => false ),
		"query_var" => true,
				
		"supports" => array( "title" ),				
	);
	register_post_type( "elb_list", $args );

// End of cptui_register_my_cpts_elb_list()
}

if(function_exists("register_field_group"))
{
	register_field_group(array (
		'id' => 'acf_list-settings',
		'title' => 'List Settings',
		'fields' => array (
			array (
				'key' => 'field_57c840a86fd74',
				'label' => 'Enable Reward on Opt-in',
				'name' => 'elb_enable_reward',
				'type' => 'radio',
				'instructions' => 'Do you want to reward subscribers when they sing-up to your list?',
				'choices' => array (
					0 => 'No',
					1 => 'Yes',
				),
				'other_choice' => 0,
				'save_other_choice' => 0,
				'default_value' => 0,
				'layout' => 'vertical',
			),
			array (
				'key' => 'field_57c841046fd75',
				'label' => 'Reward Title',
				'name' => 'elb_reward_title',
				'type' => 'text',
				'required' => 1,
				'conditional_logic' => array (
					'status' => 1,
					'rules' => array (
						array (
							'field' => 'field_57c840a86fd74',
							'operator' => '==',
							'value' => '1',
						),
					),
					'allorany' => 'all',
				),
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'formatting' => 'html',
				'maxlength' => '',
			),
			array (
				'key' => 'field_57c8413f6fd76',
				'label' => 'Reward File',
				'name' => 'elb_reward_file',
				'type' => 'file',
				'required' => 1,
				'conditional_logic' => array (
					'status' => 1,
					'rules' => array (
						array (
							'field' => 'field_57c840a86fd74',
							'operator' => '==',
							'value' => '1',
						),
					),
					'allorany' => 'all',
				),
				'save_format' => 'object',
				'library' => 'all',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'elb_list',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'acf_after_title',
			'layout' => 'default',
			'hide_on_screen' => array (
				0 => 'permalink',
				1 => 'the_content',
				2 => 'excerpt',
				3 => 'custom_fields',
				4 => 'discussion',
				5 => 'comments',
				6 => 'revisions',
				7 => 'slug',
				8 => 'author',
				9 => 'format',
				10 => 'featured_image',
				11 => 'categories',
				12 => 'tags',
				13 => 'send-trackbacks',
			),
		),
		'menu_order' => 0,
	));
}

