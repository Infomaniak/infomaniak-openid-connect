<?php
/**
 * OpenID Connect Infomaniak
 *
 * This plugin provides the ability to authenticate users with Identity
 * Providers using the OpenID Connect OAuth2 API with Authorization Code Flow.
 *
 * @package   OpenID_Connect_Infomaniak
 * @category  General
 * @copyright 2025-2030 infomaniak
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/infomaniak
 *
 * @wordpress-plugin
 * Plugin Name:       OpenID Connect Infomaniak
 * Plugin URI:        https://github.com/Infomaniak/openid-connect-infomaniak
 * Description:       Connect to Infomaniak OpenID provider using Authorization Code Flow.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            infomaniak
 * Author URI:        http://www.infomaniak.com
 * Text Domain:       openid-connect-infomaniak
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/Infomaniak/openid-connect-infomaniak
 */

/*
Notes
  Spec Doc - http://openid.net/specs/openid-connect-basic-1_0-32.html

  Filters
  - openid-connect-infomaniak-alter-request       - 3 args: request array, plugin settings, specific request op
  - openid-connect-infomaniak-settings-fields     - modify the fields provided on the settings page
  - openid-connect-infomaniak-login-button-text   - modify the login button text
  - openid-connect-infomaniak-cookie-redirect-url - modify the redirect url stored as a cookie
  - openid-connect-infomaniak-user-login-test     - (bool) should the user be logged in based on their claim
  - openid-connect-infomaniak-user-creation-test  - (bool) should the user be created based on their claim
  - openid-connect-infomaniak-auth-url            - modify the authentication url
  - openid-connect-infomaniak-alter-user-claim    - modify the user_claim before a new user is created
  - openid-connect-infomaniak-alter-user-data     - modify user data before a new user is created
  - openid-connect-modify-token-response-before-validation - modify the token response before validation
  - openid-connect-modify-id-token-claim-before-validation - modify the token claim before validation

  Actions
  - openid-connect-infomaniak-user-create                     - 2 args: fires when a new user is created by this plugin
  - openid-connect-infomaniak-user-update                     - 1 arg: user ID, fires when user is updated by this plugin
  - openid-connect-infomaniak-update-user-using-current-claim - 2 args: fires every time an existing user logs in and the claims are updated.
  - openid-connect-infomaniak-redirect-user-back              - 2 args: $redirect_url, $user. Allows interruption of redirect during login.
  - openid-connect-infomaniak-user-logged-in                  - 1 arg: $user, fires when user is logged in.
  - openid-connect-infomaniak-cron-daily                      - daily cron action
  - openid-connect-infomaniak-state-not-found                 - the given state does not exist in the database, regardless of its expiration.
  - openid-connect-infomaniak-state-expired                   - the given state exists, but expired before this login attempt.

  Callable actions

  User Meta
  - openid-connect-infomaniak-subject-identity    - the identity of the user provided by the idp
  - openid-connect-infomaniak-last-id-token-claim - the user's most recent id_token claim, decoded
  - openid-connect-infomaniak-last-user-claim     - the user's most recent user_claim
  - openid-connect-infomaniak-last-token-response - the user's most recent token response

  Options
  - openid_connect_generic_settings     - plugin settings
  - openid-connect-infomaniak-valid-states - locally stored generated states
*/


/**
 * OpenID_Connect_Infomaniak class.
 *
 * Defines plugin initialization functionality.
 *
 * @package OpenID_Connect_Infomaniak
 * @category  General
 */
class OpenID_Connect_Infomaniak {

	/**
	 * Singleton instance of self
	 *
	 * @var OpenID_Connect_Infomaniak
	 */
	protected static $_instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin settings.
	 *
	 * @var OpenID_Connect_Infomaniak_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin logs.
	 *
	 * @var OpenID_Connect_Infomaniak_Option_Logger
	 */
	private $logger;

	/**
	 * Openid Connect Generic client
	 *
	 * @var OpenID_Connect_Infomaniak_Client
	 */
	private $client;

	/**
	 * Client wrapper.
	 *
	 * @var OpenID_Connect_Infomaniak_Client_Wrapper
	 */
	public $client_wrapper;

	/**
	 * Setup the plugin
	 *
	 * @param OpenID_Connect_Infomaniak_Option_Settings $settings The settings object.
	 * @param OpenID_Connect_Infomaniak_Option_Logger   $logger   The loggin object.
	 *
	 * @return void
	 */
	public function __construct( OpenID_Connect_Infomaniak_Option_Settings $settings, OpenID_Connect_Infomaniak_Option_Logger $logger ) {
		$this->settings = $settings;
		$this->logger = $logger;
		self::$_instance = $this;
	}

	// @codeCoverageIgnoreStart

	/**
	 * WordPress Hook 'init'.
	 *
	 * @return void
	 */
	public function init() {

		$this->client = new OpenID_Connect_Infomaniak_Client(
			$this->settings->client_id,
			$this->settings->client_secret,
			$this->settings->scope,
			$this->settings->endpoint_login,
			$this->settings->endpoint_userinfo,
			$this->settings->endpoint_token,
			$this->get_redirect_uri( $this->settings ),
			$this->settings->acr_values,
			$this->get_state_time_limit( $this->settings ),
			$this->logger
		);

		$this->client_wrapper = OpenID_Connect_Infomaniak_Client_Wrapper::register( $this->client, $this->settings, $this->logger );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		OpenID_Connect_Infomaniak_Login_Form::register( $this->settings, $this->client_wrapper );

		// Add a shortcode to get the auth URL.
		add_shortcode( 'infomaniak_connect_generic_auth_url', array( $this->client_wrapper, 'get_authentication_url' ) );

		// Add actions to our scheduled cron jobs.
		add_action( 'openid-connect-infomaniak-cron-daily', array( $this, 'cron_states_garbage_collection' ) );

		$this->upgrade();

		if ( is_admin() ) {
			OpenID_Connect_Infomaniak_Settings_Page::register( $this->settings, $this->logger );
		}
	}

	/**
	 * Get the default redirect URI.
	 *
	 * @param OpenID_Connect_Infomaniak_Option_Settings $settings The settings object.
	 *
	 * @return string
	 */
	public function get_redirect_uri( OpenID_Connect_Infomaniak_Option_Settings $settings ) {
		$redirect_uri = admin_url( 'admin-ajax.php?action=openid-connect-authorize' );

		if ( $settings->alternate_redirect_uri ) {
			$redirect_uri = site_url( '/openid-connect-authorize' );
		}

		return $redirect_uri;
	}

	/**
	 * Get the default state time limit.
	 *
	 * @param OpenID_Connect_Infomaniak_Option_Settings $settings The settings object.
	 *
	 * @return int
	 */
	public function get_state_time_limit( OpenID_Connect_Infomaniak_Option_Settings $settings ) {
		$state_time_limit = 180;
		// State time limit cannot be zero.
		if ( $settings->state_time_limit ) {
			$state_time_limit = intval( $settings->state_time_limit );
		}

		return $state_time_limit;
	}

	/**
	 * Check if privacy enforcement is enabled, and redirect users that aren't
	 * logged in.
	 *
	 * @return void
	 */
	public function enforce_privacy_redirect() {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			// The client endpoint relies on the wp-admin ajax endpoint.
			if (
				! defined( 'DOING_AJAX' ) ||
				! boolval( constant( 'DOING_AJAX' ) ) ||
				! isset( $_GET['action'] ) ||
				'openid-connect-authorize' != $_GET['action'] ) {
				auth_redirect();
			}
		}
	}

	/**
	 * Enforce privacy settings for rss feeds.
	 *
	 * @param string $content The content.
	 *
	 * @return mixed
	 */
	public function enforce_privacy_feeds( $content ) {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			$content = __( 'Private site', 'infomaniak-openid-connect' );
		}
		return $content;
	}

	/**
	 * Handle plugin upgrades
	 *
	 * @return void
	 */
	public function upgrade() {
		$last_version = get_option( 'openid-connect-infomaniak-plugin-version', 0 );
		$settings = $this->settings;

		if ( version_compare( self::VERSION, $last_version, '>' ) ) {
			// An upgrade is required.
			self::setup_cron_jobs();

			// @todo move this to another file for upgrade scripts
			if ( isset( $settings->ep_login ) ) {
				$settings->endpoint_login = $settings->ep_login;
				$settings->endpoint_token = $settings->ep_token;
				$settings->endpoint_userinfo = $settings->ep_userinfo;

				unset( $settings->ep_login, $settings->ep_token, $settings->ep_userinfo );
				$settings->save();
			}

			// Update the stored version number.
			update_option( 'openid-connect-infomaniak-plugin-version', self::VERSION );
		}
	}

	/**
	 * Expire state transients by attempting to access them and allowing the
	 * transient's own mechanisms to delete any that have expired.
	 *
	 * @return void
	 */
	public function cron_states_garbage_collection() {
		global $wpdb;
		$states = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_openid-connect-infomaniak-state--%'" );

		if ( ! empty( $states ) ) {
			foreach ( $states as $state ) {
				$transient = str_replace( '_transient_', '', $state );
				get_transient( $transient );
			}
		}
	}

	/**
	 * Ensure cron jobs are added to the schedule.
	 *
	 * @return void
	 */
	public static function setup_cron_jobs() {
		if ( ! wp_next_scheduled( 'openid-connect-infomaniak-cron-daily' ) ) {
			wp_schedule_event( time(), 'daily', 'openid-connect-infomaniak-cron-daily' );
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activation() {
		self::setup_cron_jobs();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'openid-connect-infomaniak-cron-daily' );
	}

	/**
	 * Simple autoloader.
	 *
	 * @param string $class The class name.
	 *
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'OpenID_Connect_Infomaniak_';

		if ( stripos( $class, $prefix ) !== 0 ) {
			return;
		}

		$filename = $class . '.php';

		// Internal files are all lowercase and use dashes in filenames.
		if ( false === strpos( $filename, '\\' ) ) {
			$filename = strtolower( str_replace( '_', '-', $filename ) );
		} else {
			$filename  = str_replace( '\\', DIRECTORY_SEPARATOR, $filename );
		}

		$filepath = __DIR__ . '/includes/' . $filename;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}

	/**
	 * Instantiate the plugin and hook into WordPress.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		/**
		 * This is a documented valid call for spl_autoload_register.
		 *
		 * @link https://www.php.net/manual/en/function.spl-autoload-register.php#71155
		 */
		spl_autoload_register( array( 'OpenID_Connect_Infomaniak', 'autoload' ) );

		$settings = new OpenID_Connect_Infomaniak_Option_Settings(
			// Default settings values.
			array(
				// OAuth client settings.
				'login_type'           => defined( 'INFOMANIAK_OIDC_LOGIN_TYPE' ) ? INFOMANIAK_OIDC_LOGIN_TYPE : 'button',
				'client_id'            => defined( 'INFOMANIAK_OIDC_CLIENT_ID' ) ? INFOMANIAK_OIDC_CLIENT_ID : '',
				'client_secret'        => defined( 'INFOMANIAK_OIDC_CLIENT_SECRET' ) ? INFOMANIAK_OIDC_CLIENT_SECRET : '',
				'scope'                => defined( 'INFOMANIAK_OIDC_CLIENT_SCOPE' ) ? INFOMANIAK_OIDC_CLIENT_SCOPE : 'email profile openid',
				'endpoint_login'       => defined( 'INFOMANIAK_OIDC_ENDPOINT_LOGIN_URL' ) ? INFOMANIAK_OIDC_ENDPOINT_LOGIN_URL : '',
				'endpoint_userinfo'    => defined( 'INFOMANIAK_OIDC_ENDPOINT_USERINFO_URL' ) ? INFOMANIAK_OIDC_ENDPOINT_USERINFO_URL : '',
				'endpoint_token'       => defined( 'INFOMANIAK_OIDC_ENDPOINT_TOKEN_URL' ) ? INFOMANIAK_OIDC_ENDPOINT_TOKEN_URL : '',
				'endpoint_end_session' => defined( 'INFOMANIAK_OIDC_ENDPOINT_LOGOUT_URL' ) ? INFOMANIAK_OIDC_ENDPOINT_LOGOUT_URL : '',
				'acr_values'           => defined( 'INFOMANIAK_OIDC_ACR_VALUES' ) ? INFOMANIAK_OIDC_ACR_VALUES : '',

				// Non-standard settings.
				'no_sslverify'           => 0,
				'http_request_timeout'   => 5,
				'identity_key'           => 'sub',
				'nickname_key'           => 'name',
				'email_format'           => '{email}',
				'displayname_format'     => '',
				'identify_with_username' => false,
				'state_time_limit'       => 180,

				// Plugin settings.
				'enforce_privacy'          => defined( 'OIDC_ENFORCE_PRIVACY' ) ? intval( OIDC_ENFORCE_PRIVACY ) : 0,
				'alternate_redirect_uri'   => 1,
				'token_refresh_enable'     => 1,
				'link_existing_users'      => defined( 'OIDC_LINK_EXISTING_USERS' ) ? intval( OIDC_LINK_EXISTING_USERS ) : 1,
				'create_if_does_not_exist' => defined( 'OIDC_CREATE_IF_DOES_NOT_EXIST' ) ? intval( OIDC_CREATE_IF_DOES_NOT_EXIST ) : 1,
				'redirect_user_back'       => defined( 'OIDC_REDIRECT_USER_BACK' ) ? intval( OIDC_REDIRECT_USER_BACK ) : 0,
				'redirect_on_logout'       => defined( 'OIDC_REDIRECT_ON_LOGOUT' ) ? intval( OIDC_REDIRECT_ON_LOGOUT ) : 1,
				'enable_logging'           => defined( 'OIDC_ENABLE_LOGGING' ) ? intval( OIDC_ENABLE_LOGGING ) : 0,
				'log_limit'                => defined( 'OIDC_LOG_LIMIT' ) ? intval( OIDC_LOG_LIMIT ) : 1000,
			)
		);

		$logger = new OpenID_Connect_Infomaniak_Option_Logger( 'error', $settings->enable_logging, $settings->log_limit );

		$plugin = new self( $settings, $logger );

		add_action( 'init', array( $plugin, 'init' ) );

		// Privacy hooks.
		add_action( 'template_redirect', array( $plugin, 'enforce_privacy_redirect' ), 0 );
		add_filter( 'the_content_feed', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'the_excerpt_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'comment_text_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
	}

	/**
	 * Create (if needed) and return a singleton of self.
	 *
	 * @return OpenID_Connect_Infomaniak
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::bootstrap();
		}
		return self::$_instance;
	}
}

// endpoint_login
define('INFOMANIAK_OIDC_ENDPOINT_LOGIN_URL', 'https://login.infomaniak.com/authorize' );
// endpoint_userinfo
define('INFOMANIAK_OIDC_ENDPOINT_USERINFO_URL', 'https://login.infomaniak.com/oauth2/userinfo');
// endpoint_token
define('INFOMANIAK_OIDC_ENDPOINT_TOKEN_URL', 'https://login.infomaniak.com/token');

OpenID_Connect_Infomaniak::instance();

register_activation_hook( __FILE__, array( 'OpenID_Connect_Infomaniak', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'OpenID_Connect_Infomaniak', 'deactivation' ) );

// Provide publicly accessible plugin helper functions.
require_once 'includes/functions.php';
