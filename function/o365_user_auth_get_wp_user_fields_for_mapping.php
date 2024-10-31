<?php defined('ABSPATH') OR die('Access denied!');
function o365_user_auth_get_wp_user_fields_for_mapping()
{
	global $wpdb;
	$table_name = 'users';
	$root_multi_prefix = $wpdb->base_prefix;
	$columns = $wpdb->get_results("SELECT * FROM information_schema.columns WHERE table_name = '{$root_multi_prefix}{$table_name}';");
	$match_args =  array(
		'ef_user' => 'all'
	);
	
	if ( ! function_exists( 'is_plugin_active' ) ) 
	{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	/* Checks to see if the acf pro plugin or acf plugin is activated  */
	/**remove not activate condition after user auth changes and merge both condition(pro and acf) feb-23-2018 by k**/
	if ( is_plugin_active('advanced-custom-fields-pro/acf.php') || is_plugin_active('advanced-custom-fields/acf.php') )  
	{
		$groups = apply_filters( 'acf/location/match_field_groups', array(), $match_args );
		if( is_array( $groups )  && count( $groups ) )
		{
			for($group_count = 0; $group_count < count( $groups ); $group_count++)
			{
				$fields = apply_filters( 'acf/field_group/get_fields', array(), $groups[$group_count] );
				for($fields_count = 0; $fields_count < count( $fields ); $fields_count++)
				{
					$temp = array('COLUMN_NAME' => "custom_field_".$fields[$fields_count]["name"]);
					$temp_obj = json_decode(json_encode($temp), FALSE);
					array_push($columns,$temp_obj);
				}
			}
		}
	}
	
	/* Checks to see if the toolset plugin is not activated  */
	if ( is_plugin_active('types/wpcf.php') )
	{
		$wpcf_post_fields = get_option( 'wpcf-usermeta',"");
		if(!empty($wpcf_post_fields))
		{
			foreach( $wpcf_post_fields as $key => $val )
			{
				$temp = array('COLUMN_NAME' => "custom_field_".$val["meta_key"] );
				$temp_obj = json_decode(json_encode($temp), FALSE);
				array_push($columns,$temp_obj);
			}
		}

	}
	/**add buddpress xprofile feb-23-2018 by k**/
	/* Checks to see if the buddypress plugin is activated  */
	if ( is_plugin_active('buddypress/bp-loader.php') )
	{
		$profile_groups = BP_XProfile_Group::get( array( 'fetch_fields' => true	) );
		if ( !empty( $profile_groups ) ) {
			 foreach ( $profile_groups as $profile_group ) {
				if ( !empty( $profile_group->fields ) ) {				
					foreach ( $profile_group->fields as $field ) {
						$temp = array('COLUMN_NAME' => "buddypress_field_". $field->name );
						$temp_obj = json_decode(json_encode($temp), FALSE);
						array_push($columns,$temp_obj);
					}
				}
			}
			$temp = array('COLUMN_NAME' => "buddypress_field_photo" );
			$temp_obj = json_decode(json_encode($temp), FALSE);
			array_push($columns,$temp_obj);
		}
	}
	
	/**get user meta fields**/ 
	$args = array(
		'orderby'      => 'user_login',
		'order'        => 'ASC',
		'offset'       => '',
		'search'       => '',
		'number'       => '',
		'count_total'  => false,
		'fields'       => 'ID',
		'who'          => ''
	);
	
	$wp_users = get_users( $args );
	if( is_array( $wp_users ) && count( $wp_users ) > 0)
	{
		$custom_fields = get_metadata('user', $wp_users[0]);
		foreach ( $custom_fields as $key => $value )
		{
			if( $key != "first_name" && $key != "last_name" && $key != "nickname" )
			$temp = array('COLUMN_NAME' => "custom_field_".$key);
			$temp_obj = json_decode(json_encode($temp), FALSE);
			array_push($columns,$temp_obj);
		}
	}
	
	if( is_array($columns) )
	{
		$success = array();
		for($loop_cout=0;$loop_cout< count($columns); $loop_cout++)
		{
			$column_name = $columns[$loop_cout]->COLUMN_NAME;
			if(is_array($success) && !in_array($column_name, $success))
			{
				$success []=$columns[$loop_cout]->COLUMN_NAME;//COLUMN_NAME	
			}
		}
		sort($success);
		return $success;
	}
	
}