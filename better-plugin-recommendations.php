<?php
/**
 * Plugin Name:     Better Plugin Recommendations
 * Plugin URI:      https://secretpizza.party
 * Description:     Better Plugin Recommendations
 * Author:          jkudish, secretpizzaparty
 * Author URI:      https://secretpizza.party
 * Text Domain:     spp-bpr
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Better_Plugin_Recommendations
 */

if ( ! defined('SPP_BPR_API_HOST') || ! SPP_BPR_API_HOST ) {
	define( 'SPP_BPR_API_HOST', 'betterpluginrecommendations.now.sh' );
}

add_filter( 'install_plugins_tabs', 'spp_bpr_install_plugins_tabs' );
function spp_bpr_install_plugins_tabs( $tabs ) {
	// remove Featured, change order on the others so that Recommended is first
	unset( $tabs['featured'] );
	unset( $tabs['popular'] );
	unset( $tabs['favorites'] );
	$tabs['popular']   = _x( 'Popular', 'Plugin Installer' );
	$tabs['favorites'] = _x( 'Favorites', 'Plugin Installer' );

	return $tabs;
}


add_filter( 'plugins_api', 'spp_bpr_hijack_recommended_tab', 10, 3 );
function spp_bpr_hijack_recommended_tab( $res, $action, $args ) {
	// proceed with hijack?
	if ( ! isset( $args->browse ) || $args->browse !== 'recommended' ) {
		return $res;
	}

	$url = $http_url = 'http://' . SPP_BPR_API_HOST . '/api/plugin-recommendations';
	if ( $ssl = wp_http_supports( array( 'ssl' ) ) && strpos( SPP_BPR_API_HOST, 'localhost' ) === false ) {
		$url = set_url_scheme( $url, 'https' );
	}

	$request   = wp_remote_get( $url, array( 'timeout' => 15 ) );

	if ( $ssl && is_wp_error( $request ) ) {
		trigger_error(
			__( 'An unexpected error occurred. Something may be wrong with the Better Plugin Recommendations Server or your site&#8217;s server&#8217;s configuration.', 'spp-bpr' ) . ' ' . __( '(WordPress could not establish a secure connection to the Better Plugin Recommendations Server. Please contact your server administrator.)', 'spp-bpr' ),
			headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);

		$request = wp_remote_get( $http_url, array( 'timeout' => 15 ) );
	}

	if ( is_wp_error( $request ) ) {
		$res = new WP_Error( 'plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with the Better Plugin Recommendations Server or your site&#8217;s server&#8217;s configuration.', 'spp-bpr' ),
			$request->get_error_message()
		);
	} else {
		$res = json_decode( wp_remote_retrieve_body( $request ) );
		$res->info = (array) $res->info; // WP wants this as an array...
		if ( ! is_object( $res ) && ! is_array( $res ) ) {
			$res = new WP_Error( 'plugins_api_failed',
				__( 'An unexpected error occurred. Something may be wrong with the Better Plugin Recommendations Server or your site&#8217;s server&#8217;s configuration.', 'spp-bpr' ),
				wp_remote_retrieve_body( $request )
			);
		}
	}

	return $res;
}
