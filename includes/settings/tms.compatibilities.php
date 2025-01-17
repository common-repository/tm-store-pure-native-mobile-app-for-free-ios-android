<?php
/*!
* WordPress TM Store
*

*/

/**
* Check and upgrade compatibilities from old TMS versions
*
* Here we attempt to:
*	- set to default all settings when TMS is installed
*	- make TMS compatible when updating from older versions, by registering new options
*
* Side note: Over time, the number of options have become too long, and as you can notice
*            things are not optimal. If you have any better idea on how to tackle this issue,
*            please don't hesitate to share it.
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// --------------------------------------------------------------------

/**
* Check and upgrade compatibilities from old TMS versions
*/
function tms_update_compatibilities()
{

	
	delete_option( 'tms_settings_development_mode_enabled' );
	delete_option( 'tms_settings_debug_mode_enabled' );

	update_option( 'tms_settings_welcome_panel_enabled', 1 );

	if( ! get_option( 'tms_settings_redirect_url' ) )
	{
		update_option( 'tms_settings_redirect_url', home_url() );
	}

	if( ! get_option( 'tms_settings_force_redirect_url' ) )
	{
		update_option( 'tms_settings_force_redirect_url', 2 );
	}

	if( ! get_option( 'tms_settings_connect_with_label' ) )
	{
		update_option( 'tms_settings_connect_with_label', _tms__("Connect with:", 'wordpress-tm-store') );
	}

	if( ! get_option( 'tms_settings_users_avatars' ) )
	{
		update_option( 'tms_settings_users_avatars', 1 );
	}

	if( ! get_option( 'tms_settings_use_popup' ) )
	{
		update_option( 'tms_settings_use_popup', 2 );
	}

	if( ! get_option( 'tms_settings_widget_display' ) )
	{
		update_option( 'tms_settings_widget_display', 1 );
	}

	if( ! get_option( 'tms_settings_authentication_widget_css' ) )
	{
		update_option( 'tms_settings_authentication_widget_css', ".wp-tm-store-connect-with {}\n.wp-tm-store-provider-list {}\n.wp-tm-store-provider-list a {}\n.wp-tm-store-provider-list img {}\n.tms_connect_with_provider {}" );
	}

	# bouncer settings
	if( ! get_option( 'tms_settings_bouncer_registration_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_registration_enabled', 1 );
	}

	if( ! get_option( 'tms_settings_bouncer_authentication_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_authentication_enabled', 1 );
	}

	if( ! get_option( 'tms_settings_bouncer_accounts_linking_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_accounts_linking_enabled', 1 );
	}

	if( ! get_option( 'tms_settings_bouncer_profile_completion_require_email' ) )
	{
		update_option( 'tms_settings_bouncer_profile_completion_require_email', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_profile_completion_change_username' ) )
	{
		update_option( 'tms_settings_bouncer_profile_completion_change_username', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_profile_completion_hook_extra_fields' ) )
	{
		update_option( 'tms_settings_bouncer_profile_completion_hook_extra_fields', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_moderation_level' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_moderation_level', 1 );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_membership_default_role' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_membership_default_role', "default" );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_domain_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_domain_enabled', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_domain_text_bounce' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_domain_text_bounce', _tms__("<strong>This website is restricted to invited readers only.</strong><p>It doesn't look like you have been invited to access this site. If you think this is a mistake, you might want to contact the website owner and request an invitation.<p>", 'wordpress-tm-store') );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_email_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_email_enabled', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_email_text_bounce' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_email_text_bounce', _tms__("<strong>This website is restricted to invited readers only.</strong><p>It doesn't look like you have been invited to access this site. If you think this is a mistake, you might want to contact the website owner and request an invitation.<p>", 'wordpress-tm-store') );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_profile_enabled' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_profile_enabled', 2 );
	}

	if( ! get_option( 'tms_settings_bouncer_new_users_restrict_profile_text_bounce' ) )
	{
		update_option( 'tms_settings_bouncer_new_users_restrict_profile_text_bounce', _tms__("<strong>This website is restricted to invited readers only.</strong><p>It doesn't look like you have been invited to access this site. If you think this is a mistake, you might want to contact the website owner and request an invitation.<p>", 'wordpress-tm-store') );
	}

	# contacts import
	if( ! get_option( 'tms_settings_contacts_import_facebook' ) )
	{
		update_option( 'tms_settings_contacts_import_facebook', 2 );
	}

	if( ! get_option( 'tms_settings_contacts_import_google' ) )
	{
		update_option( 'tms_settings_contacts_import_google', 2 );
	}

	if( ! get_option( 'tms_settings_contacts_import_twitter' ) )
	{
		update_option( 'tms_settings_contacts_import_twitter', 2 );
	}

	if( ! get_option( 'tms_settings_contacts_import_live' ) )
	{
		update_option( 'tms_settings_contacts_import_live', 2 );
	}

	if( ! get_option( 'tms_settings_contacts_import_linkedin' ) )
	{
		update_option( 'tms_settings_contacts_import_linkedin', 2 );
	}

	if( ! get_option( 'tms_settings_buddypress_enable_mapping' ) )
	{
		update_option( 'tms_settings_buddypress_enable_mapping', 2 );
	}

	# buddypress profile mapping
	if( ! get_option( 'tms_settings_buddypress_xprofile_map' ) )
	{
		update_option( 'tms_settings_buddypress_xprofile_map', '' );
	}

	# if no idp is enabled then we enable the default providers (facebook, google, twitter)
	global $WORDPRESS_TM_STORE_PROVIDERS_CONFIG;
	$nok = true;
	foreach( $WORDPRESS_TM_STORE_PROVIDERS_CONFIG AS $item )
	{
		$provider_id = $item["provider_id"];

		if( get_option( 'tms_settings_' . $provider_id . '_enabled' ) )
		{
			$nok = false;
		}
	}

	if( $nok )
	{
		foreach( $WORDPRESS_TM_STORE_PROVIDERS_CONFIG AS $item )
		{
			$provider_id = $item["provider_id"];

			if( isset( $item["default_network"] ) && $item["default_network"] ){
				update_option( 'tms_settings_' . $provider_id . '_enabled', 1 );
			}
		}
	}

	global $wpdb;

	# migrate steam users id to id64. Prior to 2.2
	$sql = "UPDATE {$wpdb->prefix}tmsusersprofiles
		SET identifier = REPLACE( identifier, 'http://steamcommunity.com/openid/id/', '' )
		WHERE provider = 'Steam' AND identifier like 'http://steamcommunity.com/openid/id/%' ";
	$wpdb->query( $sql );
}

// --------------------------------------------------------------------

/**
* Old junk
*
* Seems like some people are using TMS _internal_ functions for some reason...
*
* Here we keep few of those old/depreciated/undocumented/internal functions, so their websites
* doesn't break when updating to newer versions.
*
* TO BE REMOVED AS OF TMS 3.0
**
* Ref: developer-api-migrating-2.2.html
*/

// 2.1.6
function tms_render_login_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); return tms_render_auth_widget(); }
function tms_render_comment_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_render_login_form_login_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_render_login_form_login_on_register_and_login(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_render_login_form_login(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_shortcode_handler(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); return tms_shortcode_wordpress_tm_login(); }

// 2.2.2
function tms_render_tms_widget_in_comment_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_render_tms_widget_in_wp_login_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_render_tms_widget_in_wp_register_form(){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); tms_action_wordpress_tm_login(); }
function tms_user_custom_avatar($avatar, $mixed, $size, $default, $alt){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); return tms_get_wp_user_custom_avatar($html, $mixed, $size, $default, $alt); }
function tms_bp_user_custom_avatar($html, $args){ tms_deprecated_function( __FUNCTION__, '2.2.3' ); return tms_get_bp_user_custom_avatar($html, $args); }

// nag about it
function tms_deprecated_function( $function, $version )
{
	// user should be admin and logged in
	if( current_user_can('manage_options') )
	{
		trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since TM Store %2$s! For more information, check TMS Developer API - Migration.'), $function, $version ), E_USER_NOTICE );
	}
}

// --------------------------------------------------------------------
