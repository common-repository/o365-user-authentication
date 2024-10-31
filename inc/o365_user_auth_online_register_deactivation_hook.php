<?php defined('ABSPATH') OR die('Direct Access Restricted!');
/**
 * Keep or delete all associated data including tables based on settings.
 */
function o365_user_auth_online_register_deactivation_hook($sitewide,$args = array())
{
	$method = 'o365_user_auth_online_register_deactivation_hook_func';
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
function o365_user_auth_online_register_deactivation_hook_func()
{
    if( is_multisite() ) {
        delete_site_option('o365_user_auth_online_access_token' );
        delete_site_option('o365_user_auth_online_token_expires' );
        delete_site_option('o365_user_auth_online_refresh_token' );
    }
    delete_option('o365_user_auth_online_access_token' );
    delete_option('o365_user_auth_online_token_expires' );
    delete_option('o365_user_auth_online_refresh_token' );
}