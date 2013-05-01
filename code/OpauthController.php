<?php

/**
 * OpauthController
 * Wraps around Opauth for handling callbacks.
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <will@betterbrief>
 */
class OpauthController extends Controller {

	private static
		$allowed_actions = array(
			'index',
			'callback',
		);

	/**
	 * Opauth uses the last segment of the URL to identify the auth method.
	 * In _routes.yml we enforce a $Strategy request parameter to enforce this.
	 * Equivalent to "index.php" in the Opauth package.
	 * @todo: Validate the strategy works before delegating to Opauth.
	 */
	public function index(SS_HTTPRequest $request) {
		$strategy = $request->param('Strategy');
		$opauth = OpauthAuthenticator::opauth(true);
	}

	/**
	 * Equivalent to "callback.php" in the Opauth package.
	 * When done validating, we return back to the Authenticator continue auth.
	 */
	public function callback(SS_HTTPRequest $request) {
		return 'callback';
	}

	/**
	 * 'path' param for use in Opauth's config
	 * @return string
	 */
	public static function get_path() {
		return self::config()->opauth_path;
	}

	/**
	 * 'callback_url' param for use in Opauth's config
	 * @return string
	 */
	public static function get_callback_path() {
		return self::get_path() . 'callback/';
	}
}
