<?php
/*!
* WordPress TM Store
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit; 

// --------------------------------------------------------------------

function tms_component_tms_plugin_settings()
{

	tms_admin_welcome_panel();
	
	$assets_setup_base_url = WORDPRESS_TM_STORE_PLUGIN_URL . 'assets/img/';
	$options = get_option( 'tms_settings_data' );
	$options = maybe_unserialize( $options );
?>
<script >

	function toggleproviderkeys(idp)
	{
		if(typeof jQuery=="undefined")
		{
			alert( "Error: TM Store require jQuery to be installed on your wordpress in order to work!" );

			return;
		}

		if(jQuery('#tms_settings_' + idp + '_enabled').val()==1)
		{
			jQuery('.tms_tr_settings_' + idp).show();
		}
		else
		{
			jQuery('.tms_tr_settings_' + idp).hide();
			jQuery('.tms_div_settings_help_' + idp).hide();
		}

		return false;
	}

	function toggleproviderhelp(idp)
	{
		if(typeof jQuery=="undefined")
		{
			alert( "Error: TM Store require jQuery to be installed on your wordpress in order to work!" );

			return false;
		}

		jQuery('.tms_div_settings_help_' + idp).toggle();

		return false;
	}
	
	jQuery(document).ready(function($){
 
	var showFormText='<?php echo $options['email-api'];?>';
	var auto_site_url='<?php echo get_bloginfo('url');?>';
	if(showFormText!="")
	{
		$(".inside").hide();
		$("#aftersubmission").show();
		$("#formtext").hide();
	}else
	{
		$(".inside").show();
		$("#aftersubmission").hide();
		$("#formtext").show();
	}
	
	$("#save-tms-mobile-app").click(function(e){
		
	
	        var username=$('#user-api').val();
			 var emailid=$('#email-api').val();
			 var website_url=$('#website-api').val();
			 if(!username||!emailid||!website_url)
			 {
				return;
			 }else
			 {
				 
			 }
			 e.preventDefault();
			$(this).toggleClass('btn-plus');
			$(".inside").slideToggle();
			if($(this).attr("value")=="Show Details")
			{
				$(this).val("Hide Details");

			}else
			{
				$(this).val("Show Details");

			}
  });
	$("#save-tms-mobile-app_show").click(function(e){
		
			 var username=$('#user-api').val();
			 var emailid=$('#email-api').val();
			// var website_url=$('#website-api').val();
		    var app_url="http://manage.thetmstore.com/auth/set-password?username="+username+'&email_id='+emailid+'&site_url='+auto_site_url;
	        window.open(app_url);
	   });
		$('#tms_setup_form').submit(function(e) {
			
			 var username=$('#user-api').val();
			 var emailid=$('#email-api').val();
			 var website_url=$('#website-api').val();
			 if(!username||!emailid||!website_url)
			 {
				alert("Please Enter all the Details");
				return;
			 }else
			 {
				 
			 }

			var data = $('#tms_setup_form').serialize();
			$.ajax({
				url         :	ajaxurl,
				data        :	data + '&action=save_tms_data',
				type        :	'POST',
				async: false,
				beforeSend: function(){
					$('#save-tms-settings').val('Sending...');
					$('#save-tms-settings').attr('disabled', 'disabled');
				},
				success     : function(data){
					$('#save-tms-settings').val('Submit');
					$('#save-tms-settings').removeAttr('disabled');
					try {
						var returned = jQuery.parseJSON(data);
						if(returned.results == 1){
							alert('Thank You! Click on GETMOBILE APP Button.');
							
							//hide and display elements
							e.preventDefault();
							$(this).toggleClass('btn-plus');
							$(".inside").slideToggle();
							$("#formtext").hide();
							$("#aftersubmission").show();
								$("#save-tms-mobile-app").val("Show Basic Details");
							
							//
						}else if(returned.results == 2)
						{
							alert('Email Id is already registered.');
							return;
						}else if(returned.results == 3)
						{
							alert('Enter valid Email Id');
							return;
						}							
						else {
							alert(returned.error);
						}
					} catch (e) {
						alert('Not able to send the data.');
					}
					return true;
				}
			});
		});
		
	});
	
</script>
<form method="post" id="tms_setup_form" action="">
	<div class="metabox-holder columns-2" id="post-body">
		<table width="100%">
			<tr valign="top">
				<td>
					<div id="post-body-content">
						<div id="namediv" class="stuffbox" style="width:100%;float:left;">
								
								<div id ="headingdiv" style="margin-top:20px; margin-left:5%; width:86%; padding:2%; background-color:#f3f2f2; box-shadow: 2px 2px 5px 0px #424242; text-align:left;float: left;">
								
									<div class="postbox-container" style="width:60%">
										<h3>Mobile App Solution for your eCommerce Website </h3>
										<ol style="font-size:14px;">
											<li>Native Mobile App for <b>Customers</b> and Users (Android and iOS).</li></br>
											<li><b>Vendor</b> Management App (Vendor Registration, Product Upload, Order Reports and Multi-Vendor Support).</li></br>
											<li><b>Marketplace</b> Mobile App (Buyer/Seller inside One App)</li></br>
											<li><b>Admin</b> App (Vendor Approval, Product Upload, Reporting, Vendor Management, Order Management)</li>
										</ol>
										Visit our website for more details <a target="_blank" href="http://www.thetmstore.com">http://www.thetmstore.com</a>
										<br />
									</div>
									<div class="postbox-container" style="width:40%; text-align:center;">
										<h3>Please contact us for more details</h3>
										<a class="button button-secondary" target="_blank" href="http:\\www.thetmstore.com"><b>Live Chat</b></a> &nbsp;&nbsp;
										<a class="button button-secondary" href="mailto:support@twistmobile.in"><b>Email</b></a>
										<p>
										<b>Email : support@twistmobile.in</b><br />
										</p>
									</div>
								
									
								</div>
								
								<div id ="headingdiv" style="margin-top:20px; margin-left:5%; width:90%; padding:10px; padding-right:0px; padding-left:0px; background-color:#FAFAFA; box-shadow: 2px 2px 5px 0px #424242; text-align:center;float: left;">
									<h3 style="padding-top:20px; padding-left:20px; color:#424242" id="head1">Get Demo Mobile App</h3>
									<h4 style="padding-left:20px; color:#424242" id="head2">Please fill form and submit to receive your Demo Mobile App. </h4>
									<br>
									
									<div id="aftersubmission">	
									<!--<form style="text-align:center">-->
											<div style="display:inline-block; width:30%;">
												<input type="button" value="CONFIGURE YOUR WEBSITE" name="formdetails" id="save-tms-mobile-app" style="background-color:#FF4C4D; color:#FAFAFA; height:40px; width:70%; border: 1px solid #FF4C4D; box-shadow: 1px 1px 1px 0px #424242">
											</div>
											<div style="display:inline-block; width:30%;">
											 <a href="http://manage.thetmstore.com/dynamic-layout/v-2/app-custom-layout" target="_blank">
											   <input type="button" value="SAMPLE DYNAMIC LAYOUT" name="sampledynamiclayout" id="sample-dynamic-layout" style="background-color:#00acc1; color:#FAFAFA; width:70%; height:40px; border: 1px solid #00acc1; box-shadow: 1px 1px 1px 0px #424242">
											</a>
											</div>
											<div style="display:inline-block; width:30%;">
											<a href="<?php echo  $app_url;?>" target="_blank">
											   <input type="button" value="GET MOBILE APP" name="getmobileapp" id="save-tms-mobile-app_show" style="background-color:#00C853; color:#FAFAFA; width:70%; height:40px; border: 1px solid #00C853; box-shadow: 1px 1px 1px 0px #424242">
											</a>
											</div>
											</br></br>
											<div style="display:inline-block; width:30%;">
											  <a href="http://www.thetmstore.com/" target="_blank">
											   <input type="button" value="MORE DETAILS" name="moredetails" id="more-details" style="background-color:#00acc1; color:#FAFAFA; width:70%; height:40px; border: 1px solid #00acc1; box-shadow: 1px 1px 1px 0px #424242">
											  </a>
											</div>
										<!--</form>-->
									</div>
									
									<div id="formtext">
									<h2 style="text-align:center; color:#424242 ">Form Details</h2>
									</div>
									
								</div>	
								
							<div class="inside" style="padding-top:0px;float: left;width: 98%;" >
								<table class="form-table editcomment" style="margin-left:5%; padding:20px; padding-right:0px; width:90%; box-shadow:2px 2px 5px 0px #424242; padding:2px; ">
									<tbody>
										<tr class="tms_tr_settings_Facebook" style="background-color:#EEE">
											<td>Username:  <a onclick="toggleproviderhelp('Facebook')" href="#help">(?)</a></td>
											<td><input type="text" id="user-api" name="username" dir="ltr" value="<?php echo isset($options['username']) ?  esc_attr($options['username']) : ''; ?>"></td>
										    <td><p class="description" style="font-size:90%">Enter a unique username.</p></td>
											<td></td>
										</tr>
										<tr class="tms_tr_settings_Facebook" style="background-color:#EEE">
											<td>Email:  <a onclick="toggleproviderhelp('Facebook')" href="#help">(?)</a></td>
											<td><input type="text" id="email-api" name="email-api" dir="ltr" value="<?php echo isset($options['email-api']) ?  esc_attr($options['email-api']) : ''; ?>"></td>
										     <td><p class="description" style="font-size:90%">Select your email for Registeration.</p></td>
											 <td></td>
										</tr>
										<tr class="tms_tr_settings_Facebook" style="background-color:#EEE">
											<td>Website Url:  <a onclick="toggleproviderhelp('Facebook')" href="#help">(?)</a></td>
											<td><input type="text" id="website-api" name="website-api" dir="ltr" value="<?php echo isset($options['website-api']) ? esc_url_raw($options['website-api']) : ''; ?>"></td>
										    <td><p class="description" style="font-size:90%">Enter your shop url to convert into Native Mobile App.</p></td>
											<td></td>
										</tr>
										<tr class="tms_tr_settings_Facebook" style="background-color:#E1E1E1">
											<td>Woocommerce API Consumer Key:  <a onclick="toggleproviderhelp('Facebook')" href="#help">(?)</a></td>
											<td><input type="text" id="wc-api-key" name="wc-api-key" dir="ltr" value="<?php echo isset($options['wc-api-key']) ? esc_attr($options['wc-api-key']) : ''; ?>"></td>
											<td><p class="description" style="font-size:90%">Create a new Woocommerce API Consumer Key.</p></td>
											<td style="background-color:#E1E1E1;"><a onclick="toggleproviderhelp('Facebook')" href="#help">Where do I get this info?</a></td>
										</tr>
										<tr class="tms_tr_settings_Facebook" style="background-color:#E1E1E1">
											<td>Woocommerce API Consumer Secret:  <a onclick="toggleproviderhelp('Facebook')" href="#help">(?)</a></td>
											<td><input type="text" id="wc-api-key" name="wc-api-secret" dir="ltr" value="<?php echo isset($options['wc-api-secret']) ? esc_attr($options['wc-api-secret']) : ''; ?>"></td>
											<td><p class="description" style="font-size:90%">Create a new Woocommerce API Consumer Secret.</p></td>
											<td style="background-color:#E1E1E1;"><a onclick="toggleproviderhelp('Facebook')" href="#help">Where do I get this info?</a></td>
										</tr>
										<tr class="tms_tr_settings_Facebook">
											<input type="hidden" name="site_url" value="<?php echo get_bloginfo('url'); ?>" />
											<input type="hidden" name="reference_site_cms" value="wordpress" />
											<td><input type="submit" value="SUBMIT" name="Save" class="button-primary" id="save-tms-settings" style="width: 100px; background-color:#0097A7; border: 1px solid #0097A7; color:#FFFFFF; box-shadow: 1px 1px 1px 0px #424242"">
											<p class="description" style="font-size:80%">Click "SUBMIT" to get started with your Mobile App.</p></td>		
										</tr>
										
										
									</tbody>
								</table>
							</div>
							
							<div id="help" class="inside" style="float: left;width: 98%;" >
								<h2 style="padding-left:25px; color:#424242">Follow below Steps to receive your application:</h2>
									<h4 style="padding-left:45px; color:#424242"><b>Step 1:</b> Fill form above with unique username, email and website url for which you need mobile app.</b></h4>
									<h4 style="padding-left:45px; color:#424242"><b>Step 2:</b> TM Store plugin requires WooCommerce Consumer/Secert Keys to create application.</h4>
										<p style="padding-left:92px; color:#424242">Simple steps for getting WooCommerce Consumer Key and Secert Key.</p>
										<p style="padding-left:92px; color:#424242">Step 2.1 You enable REST API access in WooCommerce Plugin.</p>
										<p style="padding-left:92px; color:#424242">Step 2.2 You create new KEY in WooCommerce and give access of read/write in it.</p>
										<p style="padding-left:92px; color:#424242">Step 2.3 Then you copy/paste API code in TM Store plugin</p>
									<h4 style="padding-left:125px; color:#424242"><b>Detailed steps to get WooCommerce "CONSUMER KEY" and "CONSUMER SECRET".</b></h4>
										<p style="padding-left:150px; color:#424242">a. Open new window on web browser of Wordpress Admin Panel to follow below steps.</p>
										<p style="padding-left:150px; color:#424242">b. Go to WooCommerce Plugin in new window and find WooCommerce > Settings > API</p>
										<p style="padding-left:150px; color:#424242">c. In "API" Page, find Settings, Keys/Apps, and Webhooks Tabs.</p>
										<p style="padding-left:150px; color:#424242">d. Go to "Settings" tab, Click on "Enable REST API" and then click on "Save Changes".</p>
										<p style="padding-left:150px; color:#424242">e. Go to WooCommerce > Settings > API > Key/Apps > Add Key. Click Button Add Key.</p>
										<p style="padding-left:150px; color:#424242">f. Add Key Name and Set Read/Write permission in permission field.</p>
										<p style="padding-left:150px; color:#424242">g. Press "Generate API Key" button</p>
										<p style="padding-left:150px; color:#424242">h. Nice now you have Consumer Key and Consumer Secert with you.</p>
										<p style="padding-left:150px; color:#424242">i. Just Copy paste them in TM Store Plugin form.</p>
										<p style="padding-left:150px; color:#424242">j. Incase of problem email. support@twistmobile.in</p>
									<h4 style="padding-left:45px; color:#424242"><b>Step 3:</b> Just press Submit and wait for Demo Mobile app.</h4>
									<h4 style="padding-left:45px; color:#424242"><b>Step 4:</b> Once you approve Demo App we will create thetmstore free account for your application.</b></h4>
									<h4 style="padding-left:45px; color:#424242"><b>Step 5:</b> Start publishing your App in Google Play and iOS App Store.</h4>
							</div>
							<br>
							
							<div style="width:100%;float:left;"><p style="padding-left: 20px;">Incase of any issues in understanding the above steps, Please email us at <a href="mailto:support@twistmobile.in">support@twistmobile.in</a><br/>
							Also please note in order to allow the Mobile App APIs to work properly please <span style="color:#CB4B16;">do not</span> set the "<b>Permalink Settings</b>" to "<b>Plain options</b>".</p>
							</div>
						</div>
					</div></td>
			</tr>
		</table>
	</div>
	<?php wp_nonce_field( 'tms_setup_form' ); ?>
</form>
<style>
.button-secondary
{
    background: #e5e5e5 !important;
}
#wpbody-content .metabox-holder {
    padding-top: 0px;
}
h2 {
    margin-bottom: 0;
}
</style>
<?php
}
tms_component_tms_plugin_settings();

// --------------------------------------------------------------------	
