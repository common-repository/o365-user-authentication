<?php
defined('ABSPATH') OR die('Access denied!' );
/**
*get new token
**/
if(!function_exists('o365_user_auth_online_access_token'))
{
	function o365_user_auth_online_access_token()
	{
		$o365_settings = get_option('o365_settings');
		$client_id = "";
		$client_secret = "";
		$tenant_name = "";
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
		$redirect_uri = admin_url();//."admin.php";
		$outlook_view_tokens_data = get_option( 'o365_user_auth_online_access_token','' );
		$o365_base_plugin_is_verify = get_option( 'o365_base_plugin_is_verify','' );
		if( $client_id != "" && $client_secret != "" && $tenant_name != "" )
		{
			if( isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] == 'o365_user_auth_online_identifier' )
			{
				
				$authCode= $_GET["code"];
				$token_request_data = array(
					"grant_type" => "authorization_code",
					"code" => $authCode,
					"redirect_uri" => $redirect_uri,
					"resource" => "https://graph.microsoft.com",
					"client_id" => $client_id,
					"client_secret" => $client_secret
				  );
				$token_request_body = http_build_query($token_request_data);
				$token_url = "https://login.microsoftonline.com/common/oauth2/token";
				$token_header = array("Content-type: application/x-www-form-urlencoded");
				$result = o365_user_auth_online_curl( $token_url, $token_request_body, $token_header );
				$result = isset( $result['success'] ) ? $result['success'] : $result['error'] ;
				$info = json_decode( $result );
				
				if( isset( $info->access_token ))
				{
					update_option('o365_user_auth_online_access_token',$info->access_token);
					update_option('o365_user_auth_online_token_expires',$info->expires_on);
					update_option('o365_user_auth_online_refresh_token',$info->refresh_token);
					$redirect_uri = admin_url('admin.php?page=o365_settings')."&dcrm_msg=Successfully get Azure access token&msg_type=success";
					header("Location:".$redirect_uri);
					exit();
				}
				else
				{
					$redirect_uri = admin_url('admin.php?page=o365_settings')."&dcrm_msg=Error while getting access token&msg_type=error";
					header("Location:".$redirect_uri);
					exit();
				}
			}
			if( $outlook_view_tokens_data == '' &&( !isset($_GET['state']) || $_GET['state'] != 'o365_user_auth_identifier') && !empty($tenant_name) && $o365_base_plugin_is_verify == '1' )
			{
				
				$code_url = "https://login.microsoftonline.com/common/oauth2/authorize?response_type=code&client_id=".$client_id."&redirect_uri=".$redirect_uri."&state=o365_user_auth_online_identifier";
				
				header("Location:".$code_url);
			}
		}
	}
}