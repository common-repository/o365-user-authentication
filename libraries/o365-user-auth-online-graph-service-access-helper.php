<?php
//Require other files.
require_once 'o365-user-auth-online-settings-api-version.php';
require_once 'o365-user-auth-online-authorization-helper-for-graph.php';
class o365UserAuthOnlineGraphServiceAccessHelper
{
	// Constructs a Http GET request to a feed passed in as paremeter.
	// Returns the json decoded respone as the objects that were recieved in feed.
	public static function getFeed($feedName)
	{
		
		$az_tanent_name = o365_get_setting( 'application_tenant_domain_name' );
		$az_client_id = o365_get_setting( 'client_id' );
		$az_client_secret = o365_get_setting( 'client_secret' );
		
		$authHeader = O365UserAuthOnlineAuthorizationHelperForAADGraphService::O365UserAuthgetAuthenticationHeader($az_tanent_name, $az_client_id, $az_client_secret);
		
		$az_tanent_name = o365_get_setting( 'application_tenant_domain_name' );
		
		$feedURL = "https://graph.microsoft.com/v1.0/".$feedName;
		//$feedURL = $feedURL."?".o365SettingsOnline::$apiVersion;
		
		$args = array(
			'headers'	=> array(
						'Authorization' => $authHeader,  
						'Accept'=>'application/json;',
						'Content-Type'=>'application/json;', 
						'Prefer'=>'return-content'
					)
		);
		
		$output = wp_remote_get( $feedURL, $args );
		
		$jsonOutput = json_decode($output['body']);
				
		if( isset( $jsonOutput->{'odata.error'} ) ){
			return  $jsonOutput;
			//return $jsonOutput->{'odata.error'}->{'message'}->{'value'};
		}
		else
		{
			/* There is a field for odata metadata that we ignore and just consume the value */
			return $jsonOutput->{'value'};
		}
	}
	
	public static function getEntry($feedName, $keyValue){
		
		$az_tanent_name = o365_get_setting( 'application_tenant_domain_name' );
		
		$az_tanent_name = o365_get_setting( 'application_tenant_domain_name' );
		$az_client_id = o365_get_setting( 'client_id' );
		$az_client_secret = o365_get_setting( 'client_secret' );
		
		$authHeader = O365UserAuthOnlineAuthorizationHelperForAADGraphService::O365UserAuthgetAuthenticationHeader($az_tanent_name, $az_client_id, $az_client_secret);
		
		/* Create url for the entry based on the feedname and the key value */
		if( isset( $keyValue ) && $keyValue != "" ){
			$feedURL = 'https://graph.microsoft.com/v1.0/'.$feedName.'(\''. $keyValue .'\')';
		}else{
			$feedURL = 'https://graph.microsoft.com/v1.0/'.$feedName;
		}
		//$feedURL = $feedURL."?".o365SettingsOnline::$apiVersion;
		if( $feedName == "users" )
		{
			$feedURL = $feedURL.'?$select=city,country,department,displayName,givenName,jobTitle,mail,mailNickname,mobilePhone,id,otherMails,postalCode,preferredLanguage,state,streetAddress,surname,businessPhones,usageLocation,userPrincipalName,userType';
		}
		
		$args = array(
			'headers'	=> array(
						'Authorization' => $authHeader,
						'Accept'=>'application/json;',
						'Content-Type'=>'application/json;',
						'Prefer'=>'return-content'
					)
		);
		
		$output = wp_remote_get( $feedURL, $args );
		
		$jsonOutput = json_decode($output['body']);
		
		return $jsonOutput;
	}

}