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

}
