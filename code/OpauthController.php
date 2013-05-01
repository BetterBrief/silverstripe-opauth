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
		),
		$url_handlers = array(
			// Some inconsistent strategies use oauth_callback
			'oauth_callback' => 'callback',
		);

	/**
	 * This function only catches the request to pass it straight on.
	 * Opauth uses the last segment of the URL to identify the auth method.
	 * In _routes.yml we enforce a $Strategy request parameter to enforce this.
	 * Equivalent to "index.php" in the Opauth package.
	 * @todo: Validate the strategy works before delegating to Opauth.
	 */
	public function index(SS_HTTPRequest $request) {
		$strategy = $request->param('Strategy');
		// Redirects:
		OpauthAuthenticator::opauth(true);
	}

	/**
	 * Equivalent to "callback.php" in the Opauth package.
	 * If there is a problem with the response, we throw an HTTP error.
	 * When done validating, we return back to the Authenticator continue auth.
	 * @throws SS_HTTPResponse_Exception if any validation errors
	 */
	public function callback(SS_HTTPRequest $request) {
		$opauth = OpauthAuthenticator::opauth(false);
		$response = $this->getOpauthResponse($opauth);

		// Handle all Opauth validation in this handy function
		try {
			$this->validateOpauthResponse($opauth, $response);
		}
		catch(Exception $e) {
			$this->httpError(400, $e->getMessage());
		}

		return 'callback';
	}

	/**
	 * Returns the response from the Oauth callback.
	 * @throws InvalidArugmentException
	 * @return array The response
	 */
	protected function getOpauthResponse(Opauth $opauth) {
		$transportMethod = $opauth->env['callback_transport'];
		switch($transportMethod) {
			case 'session':
				return $this->getResponseFromSession();
			break;
			case 'get':
			case 'post':
				return $this->getResponseFromRequest($transportMethod);
			break;
			default:
				throw new InvalidArgumentException('Invalid transport method: ' . $transportMethod);
			break;
		}
	}

	/**
	 * Validates the Oauth response for Opauth.
	 * @throws InvalidArgumentException
	 */
	protected function validateOpauthResponse($opauth, $response) {
		if(!empty($response['error'])) {
			throw new InvalidArgumentException((string) $response['error']);
		}

		// Required components within the response
		$this->requireResponseComponents(
			array('auth', 'timestamp', 'signature'),
			$response
		);

		// More required components within the auth section...
		$this->requireResponseComponents(
			array('provider', 'uid'),
			$response['auth']
		);

		$invalidReason;

		if(!$opauth->validate(
			sha1(print_r($response['auth'], true)),
			$response['timestamp'],
			$response['signature'],
			$invalidReason
		)) {
			throw new InvalidArgumentException('Invalid auth response: ' . $invalidReason);
		}
	}

	/**
	 * Shorthand for quickly finding missing components and complaining about it
	 * @throws InvalidArgumentException
	 */
	protected function requireResponseComponents(array $components, $response) {
		foreach($components as $component) {
			if(empty($response[$component])) {
				throw new InvalidArgumentException('Required component ' . $component . ' was missing');
			}
		}
	}

	/**
	 * @return array Opauth response from session
	 */
	protected function getResponseFromSession() {
		return Session::get('opauth');
	}

	/**
	 * Looks at $method (GET, POST, PUT etc) for the response.
	 * @return array Opauth response
	 */
	protected function getResponseFromRequest($method) {
		return unserialize(base64_decode($this->request->{$method.'Var'}('opauth')));
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
