<?php defined('ABSPATH') OR die('Access denied!');
function o365_user_auth_filter_o365wp_refresh_tokens_content( $licance_li )
{
	/**
	 * Javascript click(o365/js/setting.js) and ajax is in the base plugins of the all plugins
	 * We need to send only access token, refresh token, expiry time key to the js
	 * Button class should be "o365_base_plugin_refresh_token_button"
	 */
	$user_auth_access_token_li_result = '';
	ob_start();
	$o365_user_auth_access_token = get_option( 'o365_user_auth_online_access_token' , '');
	?>
	<li>
		<div class="wrapper">
			<div class="contant_box">
				<form id="o365_user_auth_active_form" name="o365_user_auth_active_form" method="post">
                <div class="col-md-12 license_li">
					<div class="col-md-4">
						<p class="frist_test">Office 365 User Auth Free</p>
					</div>
					<div class="col-md-1">
						<input 
							type="button" 
							name="o365_base_plugin_refresh_token_button" 
							class="o365_base_plugin_refresh_token_button btn button-primary" 
							value="<?php echo ($o365_user_auth_access_token != '') ? 'Revoke Token' : 'Get Access Token' ?>" 
							data-access_token="o365_user_auth_online_access_token"
							data-refresh_token="o365_user_auth_online_refresh_token"
							data-expiry_time="o365_user_auth_online_token_expires"
							<?php echo ($o365_user_auth_access_token == '')? 'disabled': '';?>
						/>
					</div>
                </div>
				</form>
			</div>
		</div>
	</li>
	<?php
	$user_auth_access_token_li_result = ob_get_clean();
	return $licance_li . $user_auth_access_token_li_result;
}