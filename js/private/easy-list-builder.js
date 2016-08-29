// wait until the page and jQuery have loaded before running the code below
jQuery(document).ready(function($){
	
	// stop our admin menus from collapsing
	if( $('body[class*=" elb_"]').length || $('body[class*=" post-type-elb_"]').length ) {

		$elb_menu_li = $('#toplevel_page_elb_dashboard_admin_page');
		
		$elb_menu_li
		.removeClass('wp-not-current-submenu')
		.addClass('wp-has-current-submenu')
		.addClass('wp-menu-open');
		
		$('a:first',$elb_menu_li)
		.removeClass('wp-not-current-submenu')
		.addClass('wp-has-submenu')
		.addClass('wp-has-current-submenu')
		.addClass('wp-menu-open');
		
	}
	
});