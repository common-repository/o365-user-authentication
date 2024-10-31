<?php
defined('ABSPATH') OR die('Access denied!');
/**
*authentcate wordpress user on login
**/
class O365_USER_AUTH_ONLINE {
	static $instance = FALSE;
	private $settings = NULL;
	const ANTIFORGERY_ID_KEY = 'antiforgery-id';
	public function __construct( $settings )
	{
		$this->settings = $settings;
		// Set the redirect urls
		$this->settings->redirect_uri = wp_login_url();
		$this->settings->logout_redirect_uri = wp_login_url();
		// If plugin is not configured, we shouldn't proceed.
		if ( ! $this->o365_user_auth_plugin_is_configured() )
		{
			return;
		}

		// Add the hook that starts the SESSION
		add_action( 'init', array($this, 'o365_user_auth_register_session') );
		// The authenticate filter
		add_filter( 'authenticate', array( $this, 'o365_user_auth_authenticate' ), 1, 3 );
		// Add the link to the organization's sign-in page
		add_action( 'login_form', array( $this, 'o365_user_auth_printLoginLink' ) ) ;
		// Clear session variables when logging out
		add_action( 'wp_logout', array( $this, 'o365_user_auth_clearSession' ) );
		add_shortcode( 'o365_azure_login_url', array($this, 'o365_azure_login_url_func') );
		add_shortcode( 'o365_azure_logout_url', array($this, 'o365_azure_logout_url_func') );
	}
	/**
	 * Determine if required plugin settings are stored
	 *
	 * @return bool Whether plugin is configured
	 */
	public function o365_user_auth_plugin_is_configured()
	{
		return 
			isset( $this->settings->client_id, $this->settings->client_secret )
			 && $this->settings->client_id
			 && $this->settings->client_secret;
	}
	public static function getInstance( $settings )
	{
		if ( ! self::$instance ) {
			self::$instance = new self( $settings );
		}
		return self::$instance;
	}
	/** shortcode function to show azure login url if user not login feb-22-2018**/
	function o365_azure_login_url_func( $atts ) 
	{
		if ( ! function_exists( 'is_plugin_active' ) ){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active( 'o365/o365.php' )) 
		{
			$atts = shortcode_atts(
				array(
					'login_text' => '',
					'image_url' => '',
				), $atts, 'o365_azure_login_url' 
			);
			
			if( ! is_user_logged_in() )
			{
				$azure_login_setting_flow = get_option( 'azure_login_setting_flow' ); //get user setting
				$azure_login_setting_flow = json_decode( $azure_login_setting_flow );
				$value = $azure_login_setting_flow->azure_print_login_link;
				if(session_status() === PHP_SESSION_NONE){ session_start(); }
				$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";	
				$currenturl = 	$protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				if( !strstr($currenturl, 'wp-login.php') ){
					unset($_SESSION['currentpageurl']);		 
					$_SESSION['currentpageurl'] = $currenturl;
				}
	
				if( $value == "Yes" || $value == "" )
				{
					$antiforgery_id = o365_com_create_guid ();
					$_SESSION[ self::ANTIFORGERY_ID_KEY ] = $antiforgery_id;
					if( isset($atts["image_url"]) && $atts["image_url"] !="" )
					{
						$login_html = '<img src="'. $atts["image_url"] .'" title="'. $atts["login_text"] .'" alt="'.$atts["login_text"].'" />';
					}
					else if( isset($atts["login_text"]) && $atts["login_text"] !="" )
					{
						$login_html = $atts["login_text"];
					}
					else if( isset( $azure_login_setting_flow->azure_image_login_link ) && $azure_login_setting_flow->azure_image_login_link != "" )
					{
						$login_html = '<img src="'.$azure_login_setting_flow->azure_image_login_link.'" title="Login With Azure Account" alt="Login With Azure Account" />';
					}
					else if( isset($azure_login_setting_flow->azure_login_link_text) && $azure_login_setting_flow->azure_login_link_text != '' )
					{
						$login_html = $azure_login_setting_flow->azure_login_link_text;
					} else {
						$login_html = 'Sign in with your Office 365 Credentials';
					}
					return "<div class='o365_azure_login'><a class='o365_user_auth_login_button' href=".O365_USER_AUTH_ONLINE_AuthorizationHelper::getAuthorizationURL( $this->settings, $antiforgery_id ).">". $login_html ."</a></div>";
				}
			}
		}
	}
	function o365_azure_logout_url_func( $atts )
	{
		if ( ! function_exists( 'is_plugin_active' ) ){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		/*if(isset($_SESSION["o365_user_auth_id"]))
		{*/
			if ( is_plugin_active( 'o365/o365.php' )) 
			{
				$atts = shortcode_atts(
						array(
							'logout_text' => '',
							'image_url' => '',
							'show_welcome_text' => "",
							'welcome_text' => ""
						), $atts, 'o365_azure_login_url'
					);
				$welcome_html = '';

				if( is_user_logged_in() ){
					
					$current_user = wp_get_current_user();
					
					if( isset( $atts['show_welcome_text']) && $atts['show_welcome_text'] == 'yes' ){
					
						if( isset( $atts['welcome_text']) && !empty( $atts['welcome_text'] ) ){
							$welcome_html .= $atts['welcome_text'].' '. $current_user->display_name . ' ';
						}else{
							$welcome_html .= 'Hi '. $current_user->display_name.' ';// . '<a href='.wp_logout_url().'>Logout</a>';
						}
					
					}else{
						$welcome_html = '';
					}
					
					if( isset($atts["logout_text"]) && $atts["logout_text"] !="" )
					{
						$login_html = $atts["logout_text"];
					}
					else
					{
						$login_html = "Logout From Azure Account";
					}
					
					$o365_user_auth_logout_url =  $this->getLogoutUrl();
					$o365_user_auth_logout_url = $o365_user_auth_logout_url."?no_redirect=true";

					return '<div class="o365_azure_logout">'.$welcome_html.'<a href="'.$o365_user_auth_logout_url.'">'. $login_html .'</a></div>';
				}
			}
		/*}
		else if( is_user_logged_in() )
		{
			return '<a href='.wp_logout_url().'>Logout</a>';
		}*/
	}
	function o365_user_auth_register_session()
	{
		if ( ! session_id() )
		{
			session_start();
		}
	}
	function o365_user_auth_authenticate( $user, $username, $password )
	{
		/* Don't re-authenticate if already authenticated */
		if ( is_a( $user, 'WP_User' ) ) {
			return $user;
		}
		if ( isset( $_GET['code'] ) ) {
			/* $antiforgery_id = $_SESSION[ self::ANTIFORGERY_ID_KEY ]; */
			$antiforgery_id = sanitize_text_field($_GET['state']);
			$state_is_missing = !isset( $_GET['state']);
			$state_doesnt_match = sanitize_text_field($_GET['state']) != $antiforgery_id;
			/* Looks like we got an authorization code, let's try to get an access token with it */
			$acc_code = sanitize_text_field($_GET['code']);
			$token = O365_USER_AUTH_ONLINE_AuthorizationHelper::getAccessToken( $acc_code, $this->settings );
			/* Happy path */
			if ( isset( $token->access_token ) ) {
				try {
					$o365_online_JWT = O365_USER_AUTH_ONLINE_AuthorizationHelper::validateIdToken(
						$token->id_token,
						$this->settings,
						$antiforgery_id
					);
				} catch ( Exception $e ) {
					return new WP_Error(
						'invalid_id_token',
						sprintf( 'ERROR: Invalid id_token. %s', $e->getMessage() )
					);
				}
				/* Invoke any configured matching and auto-provisioning strategy and get the user. */
				$user = $this->getWPUserFromAADUser( $o365_online_JWT );
			} elseif ( isset( $token->error ) ) {
				/* Unable to get an access token (although we did get an authorization code) */
				return new WP_Error(
					$token->error,
					sprintf(
						'ERROR: Could not get an access token to Azure Active Directory. %s',
						$token->error_description
					)
				);
			} else {
				/* None of the above, I have no idea what happened. */
				return new WP_Error( 'unknown', 'ERROR: An unknown error occured.' );
			}
		} elseif ( isset( $_GET['error'] ) ) {
			/* The attempt to get an authorization code failed. */
			return new WP_Error(
				sanitize_text_field($_GET['error']),
				sprintf(
					'ERROR: Access denied to Azure Active Directory. %s',
					$_GET['error_description']
				)
			);
		}
		return $user;
	}
	
	function getWPUserFromAADUser($o365_online_JWT)
	{
		global $wpdb;
		$user_email_setting = '';
		// Try to find an existing user in WP where the UPN of the current AAD user is
		// (depending on config) the 'login' or 'email' field
		if( isset($o365_online_JWT->oid) && $o365_online_JWT->oid != "")
		{
			$azure_user_data = o365UserAuthOnlineGraphServiceAccessHelper::getEntry('users', $o365_online_JWT->oid);
			if( isset( $azure_user_data->{'odata.error'} ) )
			{
				 $this->o365_user_auth_clearSession();
				// The user was authenticated, but not found in WP and auto-provisioning is disabled
				return new WP_Error(
					'user_not_registered',
					sprintf(
						'ERROR: '.$azure_user_data->{'odata.error'}->{'message'}->{'value'},
						$o365_online_JWT->upn
					)
				);
			}
			else
			{
				/*get azure login user seeting:start */
				$azure_login_setting_flow = get_option( 'azure_login_setting_flow','' );
				if( $azure_login_setting_flow != '' )
				{
					$azure_login_setting_flow = json_decode( $azure_login_setting_flow );
					$mapped_fields = unserialize( $azure_login_setting_flow->user_auth_mapping_fields );
					if( isset($azure_login_setting_flow->user_email_setting ) ){
						$user_email_setting = $azure_login_setting_flow->user_email_setting;
					}
					
					/*get azure login user seeting:end */
					/*Get Mapped fields and assign azure API data to WordPress user fields Start Here*/
					$sp_fields_val = array();
					$sp_custom_fields_val = array();
					if(!empty($mapped_fields))
					{
						foreach($mapped_fields as $mapped_field_key => $mapped_field)
						{
							$local_field_name = $mapped_fields[$mapped_field_key][0];
							$azure_field_name = $mapped_fields[$mapped_field_key][1];
							$custom_fields_mapped_row_field = explode("custom_field_", $local_field_name);
							$azure_group_array = array();
							/*manage custom azure fields :start*/
							if( count( $custom_fields_mapped_row_field )>1 )
								{
									$sp_custom_fields_val[$custom_fields_mapped_row_field[1]] = $azure_user_data->$azure_field_name;
								}
								else
								{
									$sp_fields_val[$mapped_fields[$mapped_field_key][0]]= $azure_user_data->$azure_field_name;
								}
						}
					}
					/*manage custom azure fields :end*/
					/*Get Mapped fields and assign azure API data to WordPress user fields End Here*/
					if( isset( $azure_user_data->mail ) && $azure_user_data->mail != "" )
					{
						$wp_user_mail = $azure_user_data->mail;
					}
					else if( isset( $azure_user_data->userPrincipalName ) && $azure_user_data->userPrincipalName !="" )
					{
						$wp_user_mail = $azure_user_data->userPrincipalName;
					}
					else if( isset( $azure_user_data->otherMails[0]) && $azure_user_data->otherMails[0] != "" )
					{
						$wp_user_mail = $azure_user_data->otherMails[0];
					}
					else
					{
						 $this->o365_user_auth_clearSession();
						// The user was authenticated, but not found in WP and auto-provisioning is disabled
						return new WP_Error(
							'user_not_registered',
							sprintf(
								'ERROR: User Email address not registered on Azure Account',
								$o365_online_JWT->upn
							)
						);
					}

					$o365_user_auth_redirect_to = '';

					if( $azure_login_setting_flow->after_login_redirect_url == 'current_page' ){
						if( isset($_SESSION['currentpageurl']) && $_SESSION['currentpageurl'] != "" ){
							$o365_user_auth_redirect_to = $_SESSION['currentpageurl'];
						}else{
							$o365_user_auth_redirect_to = get_site_url();
						}
					}
					if(empty($o365_user_auth_redirect_to)){
						$o365_user_auth_redirect_to = get_site_url()."/wp-admin";
					}

					$o365_user_auth_new_assign_role = "subscriber";
					/** Get user by user meta aaduserobjectid: Start**/
					//$azure_user_data->objectId
					$user_by_aaduserid = get_users(
											array(
												'meta_query' => array(
													array(
														'key' => 'aaduserobjectid',
														'value' => $azure_user_data->id,
														'compare' => '=='
													)
												)
											)
										);
					if( isset($user_by_aaduserid) && !empty($user_by_aaduserid) )
					{
						if(isset($user_email_setting) && $user_email_setting == "yes"){
							$wp_user_mail = $user_by_aaduserid[0]->user_email;
						}else if(isset($user_by_aaduserid[0]->user_email) && $wp_user_mail != $user_by_aaduserid[0]->user_email){
							$wp_user_mail_by_aad = $wp_user_mail;
							$wp_user_mail = $user_by_aaduserid[0]->user_email;
						}
					}
					/** Get user by user meta o365objectid: Start**/
					if(isset($wp_user_mail) && ( (email_exists( $wp_user_mail ) || username_exists( $wp_user_mail )) ) ){
						$wp_user_email = $wp_user_mail;
						$user_obj = get_user_by( 'email', $wp_user_email );
						if(!$user_obj){
							$user_obj = get_user_by( 'login', $wp_user_email );
						}
						$user_synced = new WP_User( $user_obj->ID );
						$user_synced->add_role( $o365_user_auth_new_assign_role );
						$sp_fields_val["ID"]=$user_obj->ID;
						$sp_fields_val["user_login"]=$wp_user_email;
						if(isset($wp_user_mail_by_aad) && $wp_user_mail_by_aad != ""){
							$sp_fields_val["user_email"]= $wp_user_mail_by_aad;
						}else{
							$sp_fields_val["user_email"]=$wp_user_email;
						}
						$user_id = wp_insert_user( $sp_fields_val ) ;
						foreach( $sp_custom_fields_val as $key=>$value){
							update_user_meta($user_id, $key, $value);
						}
						update_user_meta($user_id, "aaduserobjectid", $azure_user_data->id);
						update_user_meta($user_id, "nickname", sanitize_text_field($sp_fields_val["user_nicename"]) );
						$_SESSION["o365_user_auth_id"] = $user_id;
						do_action('wp_login', $user_obj->data->user_login, $user_obj);
						wp_set_current_user( $user_obj->ID );
						wp_set_auth_cookie( $user_obj->ID );
						wp_redirect($o365_user_auth_redirect_to);
						exit();
					}else if(isset($wp_user_mail)){
						$wp_user_email = $wp_user_mail;
						$api_password = "API@wp_".mt_rand();
						$sp_fields_val["user_login"]=$wp_user_email;
						$sp_fields_val["user_pass"]=$api_password;
						$sp_fields_val["user_email"]=$wp_user_email;
						$sp_fields_val["role"]= $o365_user_auth_new_assign_role;
						$user_id = wp_insert_user( $sp_fields_val ) ;
						//On success
						if ( ! is_wp_error( $user_id ) ){
							foreach( $sp_custom_fields_val as $key=>$value){
								update_user_meta($user_id, $key, $value);
							}
							
							update_user_meta($user_id, "aaduserobjectid", $azure_user_data->id);
							update_user_meta($user_id, "nickname", sanitize_text_field($sp_fields_val["user_nicename"]) );
							$_SESSION["o365_user_auth_id"] = $user_id;
							$user_synced = new WP_User( $user_id );
							$user_synced->add_role( $o365_user_auth_new_assign_role );
							$user_obj = get_user_by( 'email', $wp_user_email );
							if(!$user_obj){
								$user_obj = get_user_by( 'login', $wp_user_email );
							}
							do_action('wp_login', $user_obj->data->user_login, $user_obj);
							wp_set_current_user( $user_obj->ID );
							wp_set_auth_cookie( $user_obj->ID );
							wp_redirect($o365_user_auth_redirect_to);
							exit();
						}
					}
					/**update data in wordpress: end*/
				}
			}
		}else{
				 $this->o365_user_auth_clearSession();
				// The user was authenticated, but not found in WP and auto-provisioning is disabled
				return new WP_Error(
					'user_not_registered',
					sprintf(
						'ERROR: User not registered',
						$o365_online_JWT->upn
					)
				);
			}
	}
	/**
	  * Sets a WordPress user's role based on their AAD group memberships
	  * 
	  * @param WP_User $user 
	  * @param string $aad_user_id The AAD object id of the user
	  * @param string $aad_tenant_id The AAD directory tenant ID
	  * @return WP_User|WP_Error Return the WP_User with updated rols, or WP_Error if failed.
	  */ 
	function o365_user_auth_clearSession(){
		if(isset($_SESSION["o365_user_auth_id"])){
			$current_o365_sess = $_SESSION["o365_user_auth_id"];
			session_destroy();
			if( $current_o365_sess != ""){
				$o365_user_auth_logout_url =  $this->getLogoutUrl();
				wp_redirect($o365_user_auth_logout_url);
				exit;
			}
		}
	}
	
	function get_login_url(){
		$antiforgery_id = o365_com_create_guid ();
		$_SESSION[ self::ANTIFORGERY_ID_KEY ] = $antiforgery_id;
		return O365_USER_AUTH_ONLINE_AuthorizationHelper::getAuthorizationURL( $this->settings, $antiforgery_id );
	}
	
	function getLogoutUrl(){
		return $this->settings->end_session_endpoint
			. '?' 
			. http_build_query(
				array( 'post_logout_redirect_uri' => $this->settings->logout_redirect_uri )
			);
	}
	/*** View ****/
	/*function printDebug()
	{
		if ( isset( $_SESSION['O365_USER_AUTH_debug'] ) ) {
			echo '<pre>'. print_r( $_SESSION['O365_USER_AUTH_var'], TRUE ) . '</pre>';
		}
		echo '<p>DEBUG</p><pre>' . print_r( $_SESSION, TRUE ) . '</pre>';
		echo '<pre>' . print_r( $_GET, TRUE ) . '</pre>';
	}*/
	
	function o365_user_auth_printLoginLink(){
		if ( ! function_exists( 'is_plugin_active' ) ){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		if ( is_plugin_active( 'o365/o365.php' )) {
			$azure_login_setting_flow = get_option( 'azure_login_setting_flow','' );
			if($azure_login_setting_flow !='' ){
				$azure_login_setting_flow = json_decode( $azure_login_setting_flow );
				$value = $azure_login_setting_flow->azure_print_login_link;
				if(session_status() === PHP_SESSION_NONE){ session_start(); }
				$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
				$currenturl = 	$protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				if( !strstr($currenturl, 'wp-login.php') ){
					unset($_SESSION['currentpageurl']);		 
					$_SESSION['currentpageurl'] = $currenturl;
				}

				if( $value == "Yes" || $value == "" ){
				if( isset( $azure_login_setting_flow->azure_image_login_link ) && $azure_login_setting_flow->azure_image_login_link !="" ){
					$html = <<<EOF
					<style type="text/css">
						$azure_login_setting_flow->azure_login_css_text;
				    </style>
					<p class="o365_user_auth_login_form_text">
						<a href="%s"><img src="$azure_login_setting_flow->azure_image_login_link" title="Login With Azure Account" alt="Login With Azure Account" /></a><br />
					</p>
EOF;
				}else if( $azure_login_setting_flow->azure_login_link_text != "")
				{
				$html = <<<EOF
					<style type="text/css">
						$azure_login_setting_flow->azure_login_css_text;
					</style>
					<p class="o365_user_auth_login_form_text">
						<a href="%s">$azure_login_setting_flow->azure_login_link_text</a><br />
					</p>
EOF;
				}else{
				$html = <<<EOF
					<style type="text/css">
						$azure_login_setting_flow->azure_login_css_text;
					</style>
					<p class="o365_wp_login_form_seperator_or">or</p>
					<p class="o365_user_auth_login_form_text">
						<a href="%s">Sign in with your Azure</a><br />
					</p>
EOF;
				}
					
				printf(
					$html,
					$this->get_login_url(),
					htmlentities( $this->settings->org_display_name ), 
					$this->getLogoutUrl()
				);
			}
			}
		}
	}
}