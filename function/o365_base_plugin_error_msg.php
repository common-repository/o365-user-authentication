<?php defined('ABSPATH') OR die('Direct Access Restricted!');

function o365_base_plugin_error_msg(){
	$message = '';
	if ( ! function_exists( 'is_plugin_active' ) ){
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	$o365_base_verify = get_option( 'o365_base_plugin_is_verify','' );
	if ( !is_plugin_active( 'o365/o365.php' ) ){
		$class = 'notice notice-error';
		$message = __( 'Office 365 User Authentication plugin requires o365 base plugin, <a href="https://wpintegrate.com/custom/o365.zip">download from here</a>.', 'o365-user-auth-online' );
	}else if( $o365_base_verify == '0' ){
		$class = 'notice notice-error';
		$message = __( 'Error: Office 365 base plugin needs to be configured on the <a style="text-decoration:none;" href="'.admin_url().'admin.php?page=o365_settings">settings</a> page, before proceeding.', 'o365-user-auth-online' );
	}
	
	if( !empty($message) ){
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ),  $message  );
	}
	 
}
