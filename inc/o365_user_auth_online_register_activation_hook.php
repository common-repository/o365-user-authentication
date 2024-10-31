<?php defined('ABSPATH') OR die('Direct Access Restricted!');
/**
* IMPORTENT CAUTION: WP has its own way to create/update tables and options data
*/
function o365_user_auth_online_register_activation_hook($sitewide,$args = array())
{
	$method = 'o365_user_auth_register_online_activation_hook_func';
	if ( is_multisite() && $sitewide )
	{
        global $wpdb, $blog_id;
        $dbquery = 'SELECT blog_id FROM '.$wpdb->blogs;
        $ids = $wpdb->get_col( $dbquery );
        foreach ( $ids as $id )
		{
            switch_to_blog( $id );
            call_user_func_array( $method, array( $args ) );
        }
        switch_to_blog( $blog_id );
    }
	else call_user_func_array( $method, array( $args ) );
}

function o365_user_auth_register_online_activation_hook_func()
{
	if ( ! function_exists( 'is_plugin_active' ) ){
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	if ( is_plugin_active( 'o365-user-auth/o365-user-auth.php' ) ){
		$value = admin_url()."plugins.php";
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'ERROR: This plugin not needs to be activate because User Authentication PRO plugin already activated. <a style="text-decoration:none;" href="'.$value.'">Go to Plugin Page</a>' );
	}
	
	$top_slug_value = get_option('365_plugin_top_slug','');
	
	$new_value = get_option( 'azure_login_setting_flow','default-value' );
	if( $new_value == "default-value" )
	{
		$new_value = '{"azure_login_link_text":"Azure Account Login","azure_print_login_link":"Yes","azure_image_login_link":"","azure_email_note":"","client_id":"","client_secret":"","field_to_match_to_upn":"email","enable_auto_provisioning":true,"enable_aad_group_to_wp_role":true,"default_wp_role":"subscriber","aad_group_to_wp_role_map":{"5d1915c4-2373-42ba-9796-7c092fa1dfc6":"administrator","21c0f87b-4b65-48c1-9231-2f9295ef601c":"editor","f5784693-11e5-4812-87db-8c6e51a18ffd":"author","780e055f-7e64-4e34-9ff3-012910b7e5ad":"contributor","f1be9515-0aeb-458a-8c0a-30a03c1afb67":"subscriber"},"azure_group_mapping_with_wprole":"","role_based_redirect_url":"","default_role_redirect_url":""}';
		update_option( 'azure_login_setting_flow', $new_value );
	}
	
}