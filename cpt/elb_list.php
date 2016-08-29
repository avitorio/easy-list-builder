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
