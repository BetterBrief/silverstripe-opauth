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
		$enabled_strategies = array(),
		/**
		 * @config string
		 */
		$opauth_security_salt,
		/**
		 * @var Opauth Persistent Opauth instance.
		 */
		$opauth,
		/**
		 * @var boolean debug
		 */
		$debug = false;

	/**
	 * get_enabled_strategies
	 * @return array Enabled strategies set in _config
	 */
	public static function get_enabled_strategies() {
		return self::config()->enabled_strategies;
	}

	/**
	 * get_opauth_config
	 * @return array Config for use with Opauth
	 */
	public static function get_opauth_config() {
		$config = self::config();
		return array(
			'path' => 'set this myself',
			'security_salt' => $config->opauth_security_salt,
			'security_iteration' => $config->opauth_security_iteration,
			'security_timeout' => $config->opauth_security_timeout,
			'callback_transport' => $config->opauth_callback_transport,
			'debug' => self::is_debug(),
			'Strategy' => $config->opauth_strategy_config,
		);
	}

	/**
	 * opauth
	 * @param boolean $autoRun Should Opauth auto run? Default: false
	 * @return Opauth The Opauth instance. Isn't it easy to typo this as Opeth?
	 */
	public static function opauth($autoRun = false) {
		if(!isset(self::$opauth)) {
			self::$opauth = new Opauth(self::get_opauth_config(), $autoRun);
		}
		return self::$opauth;
	}

	/**
	 * @return OpauthLoginForm
	 */
	public static function get_login_form(Controller $controller) {
		return new OpauthLoginForm($controller, 'LoginForm');
	}

	/**
	 * Get the name of the authentication method
	 *
	 * @return string Returns the name of the authentication method.
	 */
	public static function get_name() {
		return _t('OpauthAuthenticator.TITLE', 'Social Login');
	}

	/**
	 * Set debug
	 * @param boolean $debug
	 * @return boolean Is it debug time?
	 */
	public static function is_debug($debug = null) {
		if(isset($debug)) {
			self::$debug = $debug;
		}
		return self::$debug;
	}

}
