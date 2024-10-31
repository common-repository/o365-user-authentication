<?php
/**
*run curl and send result
**/
defined( 'ABSPATH') OR die('Access denied!' );
if(!function_exists('o365_user_auth_online_curl'))
{
	function o365_user_auth_online_curl( $url, $post_data = false, $header = false, $cipher = true ){
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_RETURNTRANSFER  => true,
			//CURLOPT_FOLLOWLOCATION  => true,
			CURLOPT_FOLLOWLOCATION  => false,
			CURLOPT_SSL_VERIFYPEER  => false,
			CURLOPT_HEADER          => false,
			CURLOPT_URL             => $url,       
		));

		if( $post_data ){
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $post_data);
		}else{
			curl_setopt( $curl, CURLOPT_HTTPGET, true );
		}

		if( $header ){
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
		}
		$result = curl_exec( $curl );
		$return = array();
		if( $result === false ){
			$return['error'] = curl_error( $curl );
		}else{
			$return['success'] = $result;
		}
		curl_close( $curl );
		return $return;
	}
}