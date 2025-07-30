<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Global functions.
 *
 * @package   OpenID_Connect_Infomaniak
 * @copyright 2025-2030 Infomaniak
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Return a single use authentication URL.
 *
 * @return string
 */
function oidcg_get_authentication_url() {
	return \OpenID_Connect_Infomaniak::instance()->client_wrapper->get_authentication_url();
}

/**
 * Refresh a user claim and update the user metadata.
 *
 * @param WP_User $user             The user object.
 * @param array   $token_response   The token response.
 *
 * @return WP_Error|array
 */
function oidcg_refresh_user_claim( $user, $token_response ) {
	return \OpenID_Connect_Infomaniak::instance()->client_wrapper->refresh_user_claim( $user, $token_response );
}
