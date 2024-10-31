<?php defined('ABSPATH') OR die('Access denied!');
/**
 * Called by ajax.
 * @return string SP users group lists where data will be synchronized
 */
function o365_user_auth_action_wp_ajax_o365_userauth_login_image_delete(){
	$azure_login_setting_flow = get_option( 'azure_login_setting_flow','' );
	if( $azure_login_setting_flow != '' )
	{
		$azure_login_setting_flow = json_decode( $azure_login_setting_flow );
		$azure_login_setting_flow->azure_image_login_link = "";
		$azure_newJsonString = json_encode($azure_login_setting_flow);
		update_option( 'azure_login_setting_flow', $azure_newJsonString );
		wp_send_json_success();
	}
}