<?php
/**
 * Plugin Name: Office 365 User Authentication
 * Plugin URI:  http://www.wpintegrate.com/
 * Version:     2.6
 * Author:      wpintegrate.com
 * Author URI:  http://www.wpintegrate.com/
 * Date:        April 5, 2020
 * Description: Authenticates a WpUser against Azure Active directory(AAD) for Office 365 and logs that user into WordPress.
 * Text Domain: o365-user-auth-online
 */

defined('ABSPATH') or die("No script kiddies please!");
define('O365_USER_AUTH_ONLINE_PATH', dirname(__FILE__) . '/');
define('O365_USER_AUTH_ONLINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('O365_USER_AUTH_ONLINE_PLUGIN_DIR', plugin_dir_path(__FILE__));

/*inclide this file for register Plugin Activate Hook*/
require O365_USER_AUTH_ONLINE_PATH . '/inc/o365_user_auth_online_register_activation_hook.php';
register_activation_hook(__FILE__, 'o365_user_auth_online_register_activation_hook');

/*inclide this file for register Plugin Deactivate Hook*/
require O365_USER_AUTH_ONLINE_PATH . '/inc/o365_user_auth_online_register_deactivation_hook.php';
register_deactivation_hook(__FILE__, 'o365_user_auth_online_register_deactivation_hook');

/* Require Library files */
require_once O365_USER_AUTH_ONLINE_PLUGIN_DIR . '/libraries/o365-user-auth-online-settings.php';
require_once O365_USER_AUTH_ONLINE_PLUGIN_DIR . '/libraries/o365-user-auth-online-authorization-helper.php';
require_once O365_USER_AUTH_ONLINE_PLUGIN_DIR . 'libraries/o365-user-auth-online-plugin-initializer.php';
require_once O365_USER_AUTH_ONLINE_PLUGIN_DIR . '/libraries/o365-user-auth-online-jwt.php';
require_once O365_USER_AUTH_ONLINE_PATH . '/libraries/o365-user-auth-online-graph-service-access-helper.php';
require_once O365_USER_AUTH_ONLINE_PATH . '/inc/o365_user_auth_online_class.php';

$settings = O365_USER_AUTH_Settings_ONLINE::loadSettingsFromJSON();
$O365_USER_AUTH = O365_USER_AUTH_ONLINE::getInstance($settings);

add_action('o365_user_auth_tab_title', 'o365_user_auth_tab_title_func');
add_action('o365_user_auth_html_content', 'o365_azure_login_setting_flows');

add_action("login_form", "o365_user_auth_online_add_css_for_login_func");
function o365_user_auth_online_add_css_for_login_func()
{
	$azure_login_setting_flow = get_option('azure_login_setting_flow', '');
	if ($azure_login_setting_flow != "") {
		$azure_login_setting_flow = json_decode($azure_login_setting_flow);
		if (isset($azure_login_setting_flow->azure_login_css_text) && $azure_login_setting_flow->azure_login_css_text != "") {
?>
			<style type="text/css">
				<?php echo $azure_login_setting_flow->azure_login_css_text; ?>
			</style>
		<?php
		}
	}
}

/*Added option for restrict plugin*/
if (!function_exists('is_plugin_active')) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
if (is_plugin_active('o365-wp-restrict/o365-wp-restrict.php')) {
	add_filter('o365_wp_restrict_auth_method', 'o365_userauth_online_restrict_auth_method');
}
if (!function_exists('o365_userauth_online_restrict_auth_method')) {
	function o365_userauth_online_restrict_auth_method($opt)
	{
		$adb2c_opt = array('o365_user_auth' => 'Office 365');
		$opt = array_merge($adb2c_opt, $opt);
		return $opt;
	}
}
add_action('admin_notices', 'o365_base_plugin_error_msg');

$azure_online_access_token = get_option('o365_user_auth_online_access_token', '');

if (!isset($azure_online_access_token) || $azure_online_access_token == "") {
	global $pagenow;
	if ( !empty($_GET['page']) && $_GET['page'] == 'o365_settings') {
		$current_page_path = 'admin.php?page=' . $_GET['page'];
		if (admin_url('admin.php?page=o365_settings') == admin_url($current_page_path)) {
			add_action('admin_init', 'o365_user_auth_online_access_token');
		}
	} else if (isset($_GET['state']) && $_GET['state'] == 'o365_user_auth_online_identifier' && isset($_GET['code'])) {
		add_action('admin_init', 'o365_user_auth_online_access_token');
	}
} else {
	$O365_USER_AUTH = O365_USER_AUTH_ONLINE::getInstance($settings);
}

if (empty($azure_online_access_token)) {
	function o365_azure_online_show_error_notice_id_token_not_exist_func()
	{
		$class = 'notice notice-error';
		$message = __('Office 365 User Authentication Plugin doesn\'t have access token. <a href="' . admin_url('admin.php?page=o365_settings') . '" >click here</a> to grab the token.', 'o365');

		printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), ($message));
	}
	add_action('admin_notices', 'o365_azure_online_show_error_notice_id_token_not_exist_func');
}

/*if( !isset($azure_online_access_token) || $azure_online_access_token == "" ){add_action( 'admin_init', 'o365_user_auth_online_access_token' );}
else{$O365_USER_AUTH = O365_USER_AUTH_ONLINE::getInstance($settings);}*/

$expire_time    = time() + 300;
$azure_token_expires = get_option('o365_user_auth_online_token_expires');
if ($expire_time > $azure_token_expires && !empty($azure_online_access_token)) {
	add_action('init', 'o365_user_auth_online_refresh_token');
}
add_action('admin_notices', 'o365_user_auth_online_free_plugin_admin_notice');
function o365_user_auth_online_free_plugin_admin_notice()
{
	$screen = get_current_screen();
	if ($screen->parent_base == "o365_settings") {
		?>
		<div class="notice notice-info" style="margin-left: 2px;">
			<p>Thank you for using <strong>WordPress + Office 365</strong>. I would be incredibly grateful if you could take a couple of minutes to write a quick WordPress review! To submit your review, simply <a target="_blank" href="https://wordpress.org/plugins/o365-user-authentication/#reviews">click this link</a>. Your feedback is highly appreciated and important to me as well as others looking for the right plugin to integrate <strong>WordPress + Office 365</strong>. You can save 10% off the purchase of a <a target="_blank" href="https://wpintegrate.com/product-category/wordpress-plugin/">PROFESSIONAL, PREMIUM or INTRANET edition</a>. To claim your discount, enter <strong>UPGRADE2020</strong> in the discount field on checkout.</p>
			<p>- Christopher Hunt | Downloads by Christopher Hunt | <a href="https://wpintegrate.com/">https://wpintegrate.com/</a></p>
		</div>
<?php
	}
}
