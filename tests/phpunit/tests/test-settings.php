<?php
/**
 * Test case for the settings class.
 *
 * @package Infomaniak_OpenID_Connect
 */

/**
 * Test case for the settings class.
 */
class Test_Settings extends Infomaniak_OpenID_Connect_TestCase {

    /**
     * Test settings fields.
     */
    public function test_settings_fields() {
        $settings = $this->get_private_property($this->plugin, 'settings');
        $fields = $settings->get_values();
        
        // Test that required fields exist
        $this->assertArrayHasKey('client_id', $fields);
        $this->assertArrayHasKey('client_secret', $fields);
        $this->assertArrayHasKey('scope', $fields);
        $this->assertArrayHasKey('endpoint_login', $fields);
        $this->assertArrayHasKey('endpoint_userinfo', $fields);
        $this->assertArrayHasKey('endpoint_token', $fields);
        $this->assertArrayHasKey('endpoint_end_session', $fields);
    }

    /**
     * Test default settings values.
     */
    public function test_default_settings() {
        $settings = $this->get_private_property($this->plugin, 'settings');
        $defaults = $settings->get_values();
        
        // Test default values
        $this->assertEquals('', $defaults['client_id']);
        $this->assertEquals('', $defaults['client_secret']);
        $this->assertEquals('email profile openid', $defaults['scope']);
        $this->assertEquals(INFOMANIAK_OIDC_ENDPOINT_LOGIN_URL, $defaults['endpoint_login']);
        $this->assertEquals(INFOMANIAK_OIDC_ENDPOINT_USERINFO_URL, $defaults['endpoint_userinfo']);
    }
}
