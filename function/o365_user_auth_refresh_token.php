<?php
defined('ABSPATH') OR die('Access denied!' );
/**
*get new token
**/
if(!function_exists('o365_user_auth_online_refresh_token'))
{
	function o365_user_auth_online_refresh_token()
	{
		$expire_time    = time() + 300;
		$outlook_search_token_expires = get_option('o365_user_auth_online_token_expires');
		if($expire_time > $outlook_search_token_expires){
			$o365_settings = get_option('o365_settings');
			if(isset($o365_settings['client_id']))
			{
				$client_id = $o365_settings['client_id'];
			}
			if(isset($o365_settings['client_secret']))
			{
				$client_secret = $o365_settings['client_secret'];
			}
			if(isset($o365_settings['tenant_name']))
			{
				$tenant_name = $o365_settings['tenant_name'];
			}
			$redirect_uri   = admin_url();//."admin.php";
			$outlook_search_refresh_token = get_option( 'o365_user_auth_online_refresh_token','' );
			if( !empty( $outlook_search_refresh_token )){
				$redirect_uri = admin_url();//."admin.php?page=o365_settings";
				$authority = "https://login.microsoftonline.com";
				$tokenUrl = "/common/oauth2/token";
				$token_request_data = array(
					"grant_type" => "refresh_token",
					"refresh_token" => $outlook_search_refresh_token,
					"resource" => "https://graph.microsoft.com",
					"client_id" => $client_id,
					"client_secret" => $client_secret
			  );
				$token_request_body = http_build_query($token_request_data);
				$token_url = "https://login.microsoftonline.com/common/oauth2/token";
				$token_header = array("Content-type: application/x-www-form-urlencoded");
				$response = o365_user_auth_online_curl( $token_url, $token_request_body, $token_header );
				$info = json_decode( isset($response['success']) ? $response['success'] : $response['error'] );
				if( is_object( $info ) && isset($info->expires_on)){
					update_option('o365_user_auth_online_access_token', $info->access_token);
					update_option('o365_user_auth_online_refresh_token', $info->refresh_token);
					update_option('o365_user_auth_online_token_expires', $info->expires_on);
					return true;
				}
			}
		}
	}
}