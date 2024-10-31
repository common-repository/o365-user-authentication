<?php
if(!function_exists('o365_azure_login_setting_flows'))
{
	function o365_azure_login_setting_flows()
	{
		$user_auth_mapping_fields = $role_based_redirect_url = $user_mapping_data = '';
		wp_register_style( 'o365_user_auth_css', O365_USER_AUTH_ONLINE_PLUGIN_URL.'assests/css/o365_user_auth_style.css');
		wp_enqueue_style( 'o365_user_auth_css' );
?>
<div class="TabbedPanelsContent">
  <?php
		$azure_login_client_id = o365_get_setting( "client_id" );
		$azure_login_client_secret = o365_get_setting( "client_secret" );
		$o365_userauth_verify_auth_flow = get_option( 'o365_userauth_verify_auth_flow' , '');
		
			if( $azure_login_client_id != "" && $azure_login_client_secret != "" )
			{
	?>
  <div class="wrap">
    <?php
				// Save if page is submitted
				$fields = array(	
					'azure_login_link_text'     => array( 'Login Link Text', '','text'),
					'azure_print_login_link'    => array( 'Print Login Link', '','radio'),
					'azure_image_login_link'	=>	array('Image Login Link', '', 'file'),
					'azure_login_css_text'      => array( 'Login Page Css', '','textarea'),
				);
				
				$azure_group = array();

				$azure_login_setting_flow = get_option( 'azure_login_setting_flow' );				
				$user_mapping_data = json_decode( $azure_login_setting_flow );
				
				if( isset($user_mapping_data->role_based_redirect_url)){
					$role_based_redirect_url = unserialize( $user_mapping_data->role_based_redirect_url );
				}
				
				if( isset($user_mapping_data->user_auth_mapping_fields)){
					$user_auth_mapping_fields = unserialize( $user_mapping_data->user_auth_mapping_fields );
				}
				
				if( isset( $user_mapping_data->azure_group_mapping_with_wprole )){
					$user_mapping_data = unserialize( $user_mapping_data->azure_group_mapping_with_wprole );
					if(isset($user_mapping_data) && !empty($user_mapping_data)){
						for( $loop_count =0; $loop_count < count($user_mapping_data); $loop_count++){
							$azure_group[$user_mapping_data[$loop_count][0]."-o365-".$loop_count] =$user_mapping_data[$loop_count][1];
						}
					}
				}
				
				$custom_curl = o365UserAuthOnlineGraphServiceAccessHelper::getFeed('groups');
				if( isset( $custom_curl->{'odata.error'} ) ){
					echo "<div class='apps_error'>ERROR: ".$custom_curl->{'odata.error'}->{'message'}->{'value'}."</div>";
				}
				else
				{
					?>
					<form action="<?php echo admin_url('admin.php?page=o365_settings&bt=user'); ?>" method="post" enctype="multipart/form-data" class="second-form-o365-menu-login">
				  <input type="hidden" name="o365_user_auth_settings_nonce" value="<?php echo wp_create_nonce('o365_user_auth_settings_nonce');?>" />
				  <table class="form-table">
					<tr class="settings-area-header">
					  <th>&nbsp;</th>
					  <th>&nbsp;</th>
					</tr>
					<?php
						$azure_login_setting_flow = get_option( 'azure_login_setting_flow' );
						$azure_login_setting_flow = json_decode( $azure_login_setting_flow );
					?>
					<?php 
						foreach( $fields as $name => $label ) : 
						$value = '';
					?>
					<tr class="form-field">
					  <th> <label for="<?php echo $name ?>"><?php echo $label[0]; ?></label>
					  </th>
					  <td>
						<?php 
							if(isset($azure_login_setting_flow->$name) && !empty($azure_login_setting_flow->$name)){
								$value = $azure_login_setting_flow->$name;
							}
						?>
						<?php if( $label[2] == "radio" ) { ?>
							<input type="<?php echo $label[2]; ?>" name="<?php echo $name;?>" value="Yes" checked="checked" />
							Yes
							<?php if( $label[2] == "radio") { ?>
							<input type="<?php echo $label[2]; ?>" name="<?php echo $name;?>" value="No" <?php if( esc_attr( $value ) =="No"){?>checked="checked"<?php } ?>/>
							No
							<?php } ?>
							<br/>
						<?php
							} else if( $label[2] == "file" ){
						?>
						<div class="user_img_wrapper">
						  <?php if($value != "") { ?>
						  <img src="<?php echo $value; ?>" title="" style="height: auto;width: 100%;max-width: 130px;"/> <span id="o365_userauth_login_image_delete" class="corss" title="Delete">X</span>
						  <?php } ?>
						</div>
						<br/>
						<input type="<?php echo $label[2]; ?>" name="<?php echo $name;?>" />
						<br/>
						<?php
									echo $label[1];
								}
								else if( $label[2] == "textarea" )
								{
									?>
						<textarea style="height:150px;" name="<?php echo $name;?>"> <?php echo $value; ?> </textarea>
						<?php
								}
								else
								{
									?>
						<input type="<?php echo $label[2]; ?>" name="<?php echo $name;?>" value="<?php echo esc_attr( $value );?>"/>
						<br/>
						<?php
									echo $label[1];
								}
									?></td>
					</tr>
					<?php 
								endforeach;

					if( is_array( $custom_curl ) && count( $custom_curl )> 0 )
					{
						$value_array = array(
							"save_user_mapping"=>$azure_group,
							"azure_groups"=>$custom_curl,
							"role_based_redirect_url"=>$role_based_redirect_url,
							"user_auth_mapped_fields"=>$user_auth_mapping_fields
						);
						wp_register_script( 'o365_user_auth_script', O365_USER_AUTH_ONLINE_PLUGIN_URL. '/assests/js/o365_user_auth.js' );
						wp_localize_script(  'o365_user_auth_script', 'o365_user_auth_script_obj', $value_array );
						wp_enqueue_script( 'o365_user_auth_script' );					
				?>
                
            
				<tr class="form-field">
					  <th> <label>Redirect after login</label>	</th>
					  <td>
					  	<input type="radio" name="o365_user_auth_redirect_after_login" value="admin_dashboard" checked/> Admin Dashboard <br/>
					  	<input type="radio" name="o365_user_auth_redirect_after_login" value="current_page" <?php if( isset( $azure_login_setting_flow->after_login_redirect_url ) && $azure_login_setting_flow->after_login_redirect_url == 'current_page'){echo 'checked';} ?>/> Current Page
					  </td>
				</tr>        
                
        
		<tr id="main_tr_for_user_auth_mapping">
		  <td colspan="2" class="main_tr_for_user_auth_mapping_td"><table class="table_for_user_auth_mapping_fields" id="table_for_user_auth_mapping_fields_id"  cellpadding="0" cellspacing="0">
			  <tr>
				<td colspan="3"><label for="">Azure User Fields mapped with WP User Fields</label></td>
			  </tr>
			  <tr class="table_for_user_auth_mapping_fields_header_area">
				<th>WordPress Fields</th>
				<th>Azure Fields</th>
				<th></th>
			  </tr>
			  <tr>
				<td colspan="3" class="main_td_for_user_auth_mapping_fields_local_and_azure_fields"><table id="user_auth_mapping_fields_local_and_azure_fields" cellpadding="0" cellspacing="0">
					<!-- tr ( td local + td remote ) -->
					<tbody>
					  <tr id="o365_user_auth_mapping_fields_table_row__0">
						<td><select id="user_auth_wp_user_fields" name="user_auth_fields_mapping[0][]">
							<option value="custom_field_first_name">First Name</option>
						  </select></td>
						<td><select id="user_auth_azure_user_fields" name="user_auth_fields_mapping[0][]" required>
							<option value="">Select Azure User Field</option>
							<?php
								$azure_fields = o365_user_auth_get_azure_user_fields_for_mapping();
								if(isset($azure_fields) && $azure_fields !="")
								{
									if( isset($user_auth_mapping_fields[0][1]) )
									{
										$static_this_field=$user_auth_mapping_fields[0][1];
									}
									else
									{
										$static_this_field = "surname";
									}
									foreach($azure_fields as $azure_field)
									{
										?>
                                        	<option value="<?php echo $azure_field[1]; ?>" <?php selected( $azure_field[1], $static_this_field ); ?>><?php echo $azure_field[0]; ?></option>
                                        <?php
									}	
								}
								?>
						  </select></td>
						<td ><span id="o365_user_auth_mapping_fields_remove_field_button"> </span></td>
					  </tr>
					  <tr id="o365_user_auth_mapping_fields_table_row__1">
						<td><select id="user_auth_wp_user_fields" name="user_auth_fields_mapping[1][]">
							<option value="custom_field_last_name">Last Name</option>
						  </select></td>
						<td><select id="user_auth_azure_user_fields" name="user_auth_fields_mapping[1][]" required>
							<option value="">Select Azure User Field</option>
							<?php
													$azure_fields = o365_user_auth_get_azure_user_fields_for_mapping();
													if(isset($azure_fields) && $azure_fields !="")
													{
														if( isset($user_auth_mapping_fields[1][1]) )
														{
															$static_this_field=$user_auth_mapping_fields[1][1];
														}
														else
														{
															$static_this_field = "givenName";
														}
														foreach($azure_fields as $azure_field)
														{
														?>
							<option value="<?php echo $azure_field[1]; ?>" <?php selected( $azure_field[1],$static_this_field ); ?>><?php echo $azure_field[0]; ?></option>
							<?php 
														}	
													}
														?>
						  </select></td>
						<td ><span id="o365_user_auth_mapping_fields_remove_field_button"> </span></td>
					  </tr>
					  <tr id="o365_user_auth_mapping_fields_table_row__2">
						<td><select id="user_auth_wp_user_fields" name="user_auth_fields_mapping[2][]">
							<option value="user_nicename">User Nickname</option>
						  </select></td>
						<td><select id="user_auth_azure_user_fields" name="user_auth_fields_mapping[2][]" required>
							<option value="">Select Azure User Field</option>
							<?php
													$azure_fields = o365_user_auth_get_azure_user_fields_for_mapping();
													if(isset($azure_fields) && $azure_fields !="")
													{
														if( isset($user_auth_mapping_fields[2][1]) )
														{
															$static_this_field=$user_auth_mapping_fields[2][1];
														}
														else
														{
															$static_this_field = "displayName";
														}
														foreach($azure_fields as $azure_field)
														{
														?>
							<option value="<?php echo $azure_field[1]; ?>" <?php selected( $azure_field[1], $static_this_field); ?>><?php echo $azure_field[0]; ?></option>
							<?php 
														}	
													}
														?>
						  </select></td>
						<td ><span id="o365_user_auth_mapping_fields_remove_field_button"> </span></td>
					  </tr>
					  <tr id="o365_user_auth_mapping_fields_table_row__3">
						<td><select id="user_auth_wp_user_fields" name="user_auth_fields_mapping[3][]" required>
							<option value="display_name">Display Name</option>
						  </select></td>
						<td><select id="user_auth_azure_user_fields" name="user_auth_fields_mapping[3][]">
							<option value="">Select Azure User Field</option>
							<?php
													$azure_fields = o365_user_auth_get_azure_user_fields_for_mapping();
													if(isset($azure_fields) && $azure_fields !="")
													{
														if( isset($user_auth_mapping_fields[3][1]) )
														{
															$static_this_field=$user_auth_mapping_fields[3][1];
														}
														else
														{
															$static_this_field = "displayName";
														}
														foreach($azure_fields as $azure_field)
														{
														?>
							<option value="<?php echo $azure_field[1]; ?>" <?php selected( $azure_field[1], $static_this_field); ?>><?php echo $azure_field[0]; ?></option>
							<?php 
														}	
													}
														?>
						  </select></td>
						<td><span id="o365_user_auth_mapping_fields_remove_field_button"> </span></td>
					  </tr>
					  <?php
						if(isset($user_auth_mapping_fields) && !empty($user_auth_mapping_fields))
						{
							foreach($user_auth_mapping_fields as $key => $mapping_field)
							{
								if($key < 4)
								{
									continue;
								}
								$mapping_field_count = $key;
								?>
					  <tr id="o365_user_auth_mapping_fields_table_row__<?php echo $mapping_field_count; ?>">
						<td><select id="user_auth_wp_user_fields" name="user_auth_fields_mapping[<?php echo $mapping_field_count; ?>][]" >
							<option value="">Select WP User Field</option>
							<?php
								$wp_fields = o365_user_auth_get_wp_user_fields_for_mapping();
								if(isset($wp_fields) && $wp_fields !="")
								{
									foreach($wp_fields as $wp_field)
									{
										?>
							<option value="<?php echo $wp_field; ?>" <?php selected( $wp_field, $user_auth_mapping_fields[$mapping_field_count][0]); ?>><?php echo $wp_field; ?></option>
							<?php
									}	
								}
								?>
						  </select></td>
						<td><select id="user_auth_azure_user_fields" name="user_auth_fields_mapping[<?php echo $mapping_field_count; ?>][]">
							<option value="">Select Azure User Field</option>
							<?php
						$azure_fields = o365_user_auth_get_azure_user_fields_for_mapping();
						if(isset($azure_fields) && $azure_fields !="")
						{
							foreach($azure_fields as $azure_field)
							{
														?>
							<option value="<?php echo $azure_field[1]; ?>" <?php selected( $azure_field[1], $user_auth_mapping_fields[$mapping_field_count][1]); ?>><?php echo $azure_field[0]; ?></option>
							<?php 
							}	
						}
														?>
						  </select></td>
						<td onclick="o365_user_auth_remove_mapping_fields_selected_row(<?php echo $mapping_field_count; ?>)"><span id="o365_user_auth_mapping_fields_remove_field_button"> <font style="vertical-align: inherit;"><font style="vertical-align: inherit;">-</font></font> </span></td>
					  </tr>
					  <?php
								$mapping_field_count++;
							}
						}
											?>
					</tbody>
				  </table></td>
			  </tr>
			</table></td>
		</tr>
		<?php
					}
					?>
				  </table>
				  <p class="submit">
					<input type="submit" class="button button-primary" value="Save settings"/>
				  </p>
				  <table class="form-table form-table-short">
					<tr>
					  <th colspan='2'> Note: The following WordPress User fields will not appear in mapping area dropdown as they are auto mapped.</th>
					</tr>
					<tr>
					  <th>Wordpress</th>
					  <th>Azure</th>
					</tr>
					<tr>
					  <td>user_login</td>
					  <td>Azure Email</td>
					</tr>
					<tr>
					  <td>user_pass</td>
					  <td>Auto Generated Password</td>
					</tr>
					<tr>
					  <td>user_email</td>
					  <td>Azure Email</td>
					</tr>
				  </table>
				  <table class="form-table form-table-short">
					<tr>
					  <th colspan='2'> Short Code For Print Azure/Office 365 Login Hyperlink</th>
					</tr>
					<tr class="form-field">
					  <th> <label for="">Azure/Office 365 Login Hyperlink </label></th>
					  <td>[o365_azure_login_url login_text="" image_url="" ]</td>
					</tr>
					<tr class="form-field">
					  <th> <label for="">Azure/Office 365 Logout Hyperlink </label></th>
					  <td>[o365_azure_logout_url logout_text="Logout" show_welcome_text="yes" welcome_text="Welcome"]</td>
					</tr>
				  </table>
				</form>
					<?php 
				}
				?>
  </div>
  <?php }
			else
			{
			?>
  <div class="wrap">Please fill up the general settings.</div>
  <?php
            }
		
	  ?>
</div>
<?php   
	}  // End of function
}
?>