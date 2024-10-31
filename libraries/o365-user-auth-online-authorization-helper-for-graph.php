<?php
// A class that provides authortization token for apps that need to access Azure Active Directory Graph Service.
class O365UserAuthOnlineAuthorizationHelperForAADGraphService
{
    // Post the token generated from the symettric key and other information to STS URL and construct the authentication header
    public static function O365UserAuthgetAuthenticationHeader($application_tenant_domain_name, $appPrincipalId, $password)
	{
        $azure_access_token = get_option('o365_user_auth_online_access_token','');
		return 'Bearer '.$azure_access_token;
        
    }
}
?>