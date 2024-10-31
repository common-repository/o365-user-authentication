<?php defined('ABSPATH') OR die('Direct Access Restricted!');
function o365_user_auth_get_azure_user_fields_for_mapping()
{
	$fields = array();
	$azure_users = o365UserAuthOnlineGraphServiceAccessHelper::getEntry('users', "");
	if( isset($azure_users->value) && count($azure_users->value )> 0 )
	{
		foreach ($azure_users->value[0] as $key => $value)
		{
			if( $key == 'id' || $key == 'city' || $key == 'country' || $key == 'department' || $key == 'displayName' || $key == 'givenName' || $key == 'jobTitle' || $key == 'mail' || $key == 'mailNickname' || $key == 'mobilePhone' || $key == 'postalCode' || $key == 'preferredLanguage' || $key == 'state' || $key == 'streetAddress' || $key == 'surname' || $key == 'businessPhones' || $key == 'usageLocation' || $key == 'userPrincipalName' || $key == 'userType' || $key == 'otherMails' )
			{
				array_push($fields,array($key,$key));
			}
		}
	}
	array_multisort( array_column($fields, 0), SORT_ASC, $fields );
	return $fields;
}