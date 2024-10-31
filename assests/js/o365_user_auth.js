/* 
 * Version: 1.0
 */
jQuery(window).load(function(){
	
	jQuery(document).on("click", "#o365_userauth_login_image_delete",function(e)
	{
		jQuery("#please-wait-bg").show();
		var data = { 'action':'o365_userauth_login_image_delete'};
		jQuery.post( ajaxurl, data , function( response, txtStatus, jqXHR ){
			if(response.success)
			{
				jQuery(".user_img_wrapper").html('');
			}
			jQuery("#please-wait-bg").hide();
		}).fail(function(){
			console.log( "Fail" );
			jQuery("#please-wait-bg").hide();
		});
	});
});