<?php

/**
 * OpauthController
 * Wraps around Opauth for handling callbacks.
 * @author Will Morgan <will@betterbrief>
 */

class OpauthController extends Controller {

	private static
		$allowed_actions = array(
			'index',
			'callback',
		);

	public function index(SS_HTTPRequest $request) {
		$strategy = $request->param('Strategy');
		$opauth = OpauthAuthenticator::opauth(true);
	}

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
