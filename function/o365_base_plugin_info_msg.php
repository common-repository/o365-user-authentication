<?php defined('ABSPATH') OR die('Direct Access Restricted!');

function o365_base_plugin_info_msg(){
	$class = 'notice notice-error';
	$message = __( 'Click <a style="text-decoration:none;" href="'.admin_url().'admin.php?page=o365_settings">here</a> to configure WordPress + Office 365 User Authentication Plugin. The configuration must - at the very least - provide a valid Application ID, Application Key and Directory (tenant) Name. Please review <a style="text-decoration:none;" href="https://wpintegrate.com/kb-article/base-plugin/">this article</a> for details.', 'o365-user-auth-online' );
	
	if( !empty($message) ){
		printf( '<div style="margin-left:2px;" class="%1$s"><p>%2$s</p></div>', esc_attr( $class ),  $message  );
	}
}