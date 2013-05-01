<?php
/**
 * Base authenticator for SilverStripe Opauth module.
 *
 * @author Will Morgan <will.morgan@betterbrief.co.uk>
 */
class OpauthAuthenticator extends MemberAuthenticator {

	private static
		/**
		 * @config array The enabled strategy classes for Opauth
		 */
		$enabled_strategies = array();

	/**
	 * get_enabled_strategies
	 * @return array Enabled strategies set in _config
	 */
	public static function get_enabled_strategies() {
		return self::config()->enabled_strategies;
	}

	public static function get_login_form(Controller $controller) {
		return Object::create("OpauthLoginForm", $controller, "LoginForm");
	}

	/**
	 * Get the name of the authentication method
	 *
	 * @return string Returns the name of the authentication method.
	 */
	public static function get_name() {
		return _t('OpauthAuthenticator.TITLE', 'Social Login');
	}

}
