<?php
/**
 * Test case for the authentication functionality.
 *
 * @package Infomaniak_OpenID_Connect
 */

/**
 * Test case for the authentication functionality.
 */
class Test_Authentication extends Infomaniak_OpenID_Connect_TestCase {

    /**
     * @var OpenID_Connect_Infomaniak_Client_Wrapper
     */
    private $client_wrapper;

    /**
     * @var OpenID_Connect_Infomaniak_Client
     */
    private $mock_client;

    /**
     * @var OpenID_Connect_Infomaniak_Option_Settings
     */
    private $mock_settings;

    /**
     * @var OpenID_Connect_Infomaniak_Option_Logger
     */
    private $mock_logger;

    /**
     * Set up the test fixture.
     */
    public function set_up() {
        parent::set_up();

        // Create mock dependencies
        $this->mock_client = $this->createMock('OpenID_Connect_Infomaniak_Client');
        $this->mock_settings = $this->createMock('OpenID_Connect_Infomaniak_Option_Settings');
        $this->mock_logger = $this->createMock('OpenID_Connect_Infomaniak_Option_Logger');

        // Create the client wrapper with mocked dependencies
        $this->client_wrapper = new OpenID_Connect_Infomaniak_Client_Wrapper(
            $this->mock_client,
            $this->mock_settings,
            $this->mock_logger
        );
    }

    /**
     * Test authentication URL generation.
     */
    public function test_authentication_url() {

        $auth_url = $this->client_wrapper->get_authentication_url('test-state');

        $this->assertStringContainsString('response_type=code', $auth_url);
        $this->assertStringContainsString('client_id', $auth_url);
        $this->assertStringContainsString('scope=', $auth_url);
    }

    /**
     * Test validate_user with a valid WP_User
     */
    public function test_validate_user_with_valid_user() {
        // Create a mock WP_User
        $user = $this->createMock('WP_User');
        $user->ID = 1;
        $user->user_login = 'testuser';

        // Mock the exists() method to return true
        $user->method('exists')->willReturn(true);

        // Test the method
        $result = $this->client_wrapper->validate_user($user);

        // Assert the result is true
        $this->assertTrue($result);
    }

    /**
     * Test validate_user with an invalid user (not a WP_User)
     */
    public function test_validate_user_with_invalid_user() {
        // Test with a non-WP_User object
        $invalid_user = new stdClass();
        $invalid_user->ID = 1;

        // Test the method
        $result = $this->client_wrapper->validate_user($invalid_user);

        // Assert the result is a WP_Error with the expected code
        $this->assertWPError($result);
        $this->assertEquals('invalid-user', $result->get_error_code());
    }

    /**
     * Test validate_user with a WP_User that doesn't exist
     */
    public function test_validate_user_with_nonexistent_user() {
        // Create a mock WP_User that doesn't exist
        $user = $this->createMock('WP_User');
        $user->ID = 999; // Non-existent user ID

        // Mock the exists() method to return false
        $user->method('exists')->willReturn(false);

        // Test the method
        $result = $this->client_wrapper->validate_user($user);

        // Assert the result is a WP_Error with the expected code
        $this->assertWPError($result);
        $this->assertEquals('invalid-user', $result->get_error_code());
    }

    /**
     * Test validate_user with a WP_Error
     */
    public function test_validate_user_with_wp_error() {
        // Create a WP_Error
        $error = new WP_Error('test-error', 'Test error message');

        // Test the method
        $result = $this->client_wrapper->validate_user($error);

        // Assert the result is a WP_Error with the expected code
        $this->assertWPError($result);
        $this->assertEquals('invalid-user', $result->get_error_code());
    }

    /**
     * Test validate_user with null
     */
    public function test_validate_user_with_null() {
        // Test with null
        $result = $this->client_wrapper->validate_user(null);

        // Assert the result is a WP_Error with the expected code
        $this->assertWPError($result);
        $this->assertEquals('invalid-user', $result->get_error_code());
    }

    /**
     * Test validate_user with false
     */
    public function test_validate_user_with_false() {
        // Test with false
        $result = $this->client_wrapper->validate_user(false);

        // Assert the result is a WP_Error with the expected code
        $this->assertWPError($result);
        $this->assertEquals('invalid-user', $result->get_error_code());
    }

    /**
     * Test get_redirect_to with default values.
     */
    public function test_get_redirect_to_default() {
        // Test default redirect to home URL
        $this->assertEquals(home_url(), $this->client_wrapper->get_redirect_to());
    }

    /**
     * Test get_redirect_to from login form redirects to admin.
     */
    public function test_get_redirect_to_from_login_form() {
        // Set global pagenow to simulate wp-login.php
        global $pagenow;
        $pagenow = 'wp-login.php';

        // Test redirect to admin when coming from login form
        $this->assertEquals(admin_url(), $this->client_wrapper->get_redirect_to());
    }

    /**
     * Test get_redirect_to with redirect_to parameter.
     */
    public function test_get_redirect_to_with_redirect_parameter() {
        $test_url = 'https://example.com/custom-page';

        // Set the redirect_to parameter
        $_REQUEST['redirect_to'] = $test_url;

        // Test that the redirect_to parameter is respected
        $this->assertEquals($test_url, $this->client_wrapper->get_redirect_to());

        // Clean up
        unset($_REQUEST['redirect_to']);
    }

    /**
     * Test get_redirect_to with redirect_user_back enabled.
     */
    public function test_get_redirect_to_with_redirect_user_back() {
        global $wp;

        // Enable redirect_user_back
        $this->mock_settings->method('__get')->with('redirect_user_back')->willReturn(true);
        // Set up test query string
        $test_query = 'test=value&another=test';
        $wp->query_string = $test_query;

        // Test with query string
        $this->assertEquals(home_url('?' . $test_query), $this->client_wrapper->get_redirect_to());

        // Test with pretty permalinks
        $wp->query_string = '';
        $wp->request = 'test-page';
        $wp->did_permalink = true;

        $_GET = array('param' => 'value');

        $expected = home_url(add_query_arg($_GET, trailingslashit($wp->request)));
        $this->assertEquals($expected, $this->client_wrapper->get_redirect_to());
    }

    /**
     * Test get_redirect_to during logout.
     */
    public function test_get_redirect_to_during_logout() {
        global $pagenow;

        // Simulate logout action
        $pagenow = 'wp-login.php';
        $_GET['action'] = 'logout';

        // Should return empty string during logout
        $this->assertEquals('', $this->client_wrapper->get_redirect_to());

        // Clean up
        unset($_GET['action']);
    }

    /**
     * Test get_redirect_to with filters.
     */
    public function test_get_redirect_to_with_filters() {
        // Test with the new filter
        add_filter('infomaniak-connect-openid-client-redirect-to', function($url) {
            return 'https://example.com/new-filter';
        });

        $this->assertEquals('https://example.com/new-filter', $this->client_wrapper->get_redirect_to());
    }

    /**
     * Test update_allowed_redirect_hosts with a valid end_session URL.
     */
    public function test_update_allowed_redirect_hosts_with_valid_url() {
        // Configure the mock to return a test URL
        $test_url = 'https://example.com/end_session';
        $this->mock_settings->method('__get')->with('endpoint_end_session')->willReturn($test_url);

        // Initial allowed hosts
        $allowed_hosts = array('wordpress.org');

        // Expected result should include the host from the end_session URL
        $expected = array('wordpress.org', 'example.com');

        // Test the method
        $result = $this->client_wrapper->update_allowed_redirect_hosts($allowed_hosts);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test update_allowed_redirect_hosts with an invalid end_session URL.
     */
    public function test_update_allowed_redirect_hosts_with_invalid_url() {
        // Configure the mock to return an invalid URL
        $this->mock_settings->method('__get')->with('endpoint_end_session')->willReturn('not-a-valid-url');

        // Initial allowed hosts
        $allowed_hosts = array('wordpress.org');

        // Test the method with an invalid URL should return false
        $result = $this->client_wrapper->update_allowed_redirect_hosts($allowed_hosts);

        $this->assertFalse($result);
    }

    /**
     * Test update_allowed_redirect_hosts with an empty end_session URL.
     */
    public function test_update_allowed_redirect_hosts_with_empty_url() {
        // Configure the mock to return an empty URL
        $this->mock_settings->method('__get')->with('endpoint_end_session')->willReturn('');

        // Initial allowed hosts
        $allowed_hosts = array('wordpress.org');

        // Test the method with an empty URL should return false
        $result = $this->client_wrapper->update_allowed_redirect_hosts($allowed_hosts);

        $this->assertFalse($result);
    }


    /**
     * Test get_end_session_logout_redirect_url with auto login type and WP logout URL.
     */
    public function test_get_end_session_logout_redirect_url_with_auto_login_and_wp_logout() {
        // Create a mock user
        $user = $this->factory()->user->create_and_get();

        // Set up the token response and claim
        $token_response = array('id_token' => 'test-id-token');
        $claim = array('iss' => 'https://idp.example.com');

         // Set user meta for the test
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-token-response', $token_response);
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-id-token-claim', $claim);

        // Configure the mock settings
        $this->mock_settings->method('__get')
            ->will($this->returnValueMap([
                ['endpoint_end_session', 'https://example.com/end_session'],
                ['login_type', 'auto']
            ]));

        // Test with WP logout URL
        $result = $this->client_wrapper->get_end_session_logout_redirect_url(
            site_url('wp-login.php?loggedout=true'),
            '',
            $user
        );

        $expected = 'https://example.com/end_session?id_token_hint=test-id-token&post_logout_redirect_uri=' .
                   urlencode('http://example.org');

        // Should return the home URL for auto login type with WP logout
        $this->assertEquals($expected, $result);
    }

    /**
     * Test get_end_session_logout_redirect_url with Google as IDP.
     */
    public function test_get_end_session_logout_redirect_url_with_google_idp() {
        // Create a mock user
        $user = $this->factory()->user->create_and_get();

        // Set up the token response and claim for Google
        $token_response = array('id_token' => 'test-google-id-token');
        $claim = array('iss' => 'https://accounts.google.com');

        // Set user meta for the test
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-token-response', $token_response);
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-id-token-claim', $claim);

        // Configure the mock settings
        $this->mock_settings->method('__get')
            ->will($this->returnValueMap([
                ['endpoint_end_session', 'https://example.com/end_session'],
                ['login_type', 'auto']
            ]));

        // Test the method
        $result = $this->client_wrapper->get_end_session_logout_redirect_url(
            'https://example.com/redirect',
            'https://example.com/requested-redirect',
            $user
        );

        // Should return the original redirect URL for Google
        $this->assertEquals('https://example.com/redirect', $result);
    }

    /**
     * Test get_end_session_logout_redirect_url with standard IDP.
     */
    public function test_get_end_session_logout_redirect_url_with_standard_idp() {
        // Create a mock user
        $user = $this->factory()->user->create_and_get();

        // Set up the token response and claim for a standard IDP
        $token_response = array('id_token' => 'test-id-token');
        $claim = array('iss' => 'https://idp.example.com');

        // Set user meta for the test
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-token-response', $token_response);
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-id-token-claim', $claim);

        // Configure the mock settings
        $this->mock_settings->method('__get')
            ->will($this->returnValueMap([
                ['endpoint_end_session', 'https://example.com/end_session'],
                ['login_type', 'button']
            ]));

        // Test the method
        $result = $this->client_wrapper->get_end_session_logout_redirect_url(
            'https://example.com/redirect',
            'https://example.com/requested-redirect',
            $user
        );

        // Should return the end session URL with the id_token and redirect URL
        $expected = 'https://example.com/end_session?id_token_hint=test-id-token&post_logout_redirect_uri=' .
                   urlencode('https://example.com/redirect');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test get_end_session_logout_redirect_url with relative redirect URL.
     */
    public function test_get_end_session_logout_redirect_url_with_relative_url() {
        // Create a mock user
        $user = $this->factory()->user->create_and_get();

        // Set up the token response and claim
        $token_response = array('id_token' => 'test-id-token');
        $claim = array('iss' => 'https://idp.example.com');

        // Set user meta for the test
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-token-response', $token_response);
        update_user_meta($user->ID, 'infomaniak-connect-openid-last-id-token-claim', $claim);

        // Configure the mock settings
        $this->mock_settings->method('__get')
            ->will($this->returnValueMap([
                ['endpoint_end_session', 'https://example.com/end_session'],
                ['login_type', 'button']
            ]));

        // Test with relative URL
        $relative_url = '/wp-admin';
        $result = $this->client_wrapper->get_end_session_logout_redirect_url(
            $relative_url,
            '',
            $user
        );

        // Should convert relative URL to absolute
        $expected = 'https://example.com/end_session?id_token_hint=test-id-token&post_logout_redirect_uri=' .
                   urlencode(site_url($relative_url));
        $this->assertEquals($expected, $result);
    }

    /**
     * Test get_end_session_logout_redirect_url without token response.
     */
    public function test_get_end_session_logout_redirect_url_without_token_response() {
        // Create a mock user without token response
        $user = $this->factory()->user->create_and_get();

        // Configure the mock settings
        $this->mock_settings->method('__get')
            ->will($this->returnValueMap([
                ['endpoint_end_session', 'https://example.com/end_session'],
                ['login_type', 'button']
            ]));

        // Test the method
        $result = $this->client_wrapper->get_end_session_logout_redirect_url(
            'https://example.com/redirect',
            '',
            $user
        );

        // Should return the original redirect URL when no token response is found
        $this->assertEquals('https://example.com/redirect', $result);
    }
}
