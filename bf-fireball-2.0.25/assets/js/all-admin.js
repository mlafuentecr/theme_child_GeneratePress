jQuery( document ).ready(function( $ ) {

	$( ".toplevel_page_blueflamingo_settings.wp-not-current-submenu" ).hover(
		function() {
			$( this ).find( ".wp-menu-image img" ).attr( 'src', blueflamingoALL.imgurl + '/bf-logo-hover.svg');
		}, function() {
			$( this ).find( ".wp-menu-image img" ).attr( 'src', blueflamingoALL.imgurl + '/bf-logo.svg');
		}
	);

	$( ".toplevel_page_blueflamingo_settings ul.wp-submenu.wp-submenu-wrap" ).hover(
		function() {
			$( this ).parent().find( ".wp-menu-image img" ).attr( 'src', blueflamingoALL.imgurl + '/bf-logo-hover.svg');
		}, function() {
			$( this ).parent().find( ".wp-menu-image img" ).attr( 'src', blueflamingoALL.imgurl + '/bf-logo.svg');
		}
	);

	$('tr.user-rich-editing-wrap, tr.user-description-wrap').parent().parent().addClass('custom-personal-options-class');
	$('tr.user-rich-editing-wrap, tr.user-description-wrap').parent().parent().prev().addClass('custom-personal-options-class');

	if( pagenow == 'edit-blue-flamingo-notes' || pagenow == 'blue-flamingo-notes' || pagenow == 'admin_page_blue_flamingo_view_notes'){
		$('#toplevel_page_blueflamingo_settings').addClass('wp-has-current-submenu');
		$('a.toplevel_page_blueflamingo_settings').addClass('wp-has-current-submenu');
		$('#toplevel_page_blueflamingo_settings').removeClass('wp-not-current-submenu');
		$('#toplevel_page_blueflamingo_settings .wp-submenu li:nth-child(4)').addClass('current');
		$('#toplevel_page_blueflamingo_settings .wp-submenu li:nth-child(4) a').addClass('current');
	}

	if( pagenow == 'edit-bf-shortcodes' || pagenow == 'bf-shortcodes' ){
		$('#toplevel_page_blueflamingo_settings').addClass('wp-has-current-submenu');
		$('a.toplevel_page_blueflamingo_settings').addClass('wp-has-current-submenu');
		$('#toplevel_page_blueflamingo_settings').removeClass('wp-not-current-submenu');
		$('#toplevel_page_blueflamingo_settings .wp-submenu li:nth-child(5)').addClass('current');
		$('#toplevel_page_blueflamingo_settings .wp-submenu li:nth-child(5) a').addClass('current');
	}

});