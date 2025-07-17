<?php
/**
 * Base test case class for Infomaniak OpenID Connect tests.
 *
 * @package Infomaniak_OpenID_Connect
 */

/**
 * Abstract base test case class.
 */
abstract class Infomaniak_OpenID_Connect_TestCase extends WP_UnitTestCase {

    /**
     * The plugin instance.
     *
     * @var OpenID_Connect_Infomaniak
     */
    protected $plugin;

    /**
     * Set up the test fixture.
     */
    public function set_up() {
        parent::set_up();
        $this->plugin = OpenID_Connect_Infomaniak::instance();
    }

    /**
     * Tear down the test fixture.
     */
    public function tear_down() {
        parent::tear_down();
        unset($this->plugin);
    }

    /**
     * Helper to set a protected/private property value.
     *
     * @param object $object     The object instance.
     * @param string $property   The property name.
     * @param mixed  $value      The value to set.
     * @return void
     */
    protected function set_private_property($object, $property, $value) {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Helper to get a protected/private property value.
     *
     * @param object $object     The object instance.
     * @param string $property   The property name.
     * @return mixed
     */
    protected function get_private_property($object, $property) {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * Helper to invoke a protected/private method.
     *
     * @param object $object     The object instance.
     * @param string $method     The method name.
     * @param array  $parameters The method parameters.
     * @return mixed
     */
    protected function invoke_private_method($object, $method, array $parameters = []) {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
