<?php

/**
 * OpauthController
 * Wraps around Opauth for handling callbacks.
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class OpauthController extends Controller {

	private static
		$allowed_actions = array(
			'index',
			'finished',
		),
		$url_handlers = array(
			'finished' => 'finished',
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
		$method = $request->param('StrategyMethod');

		if(!isset($strategy)) {
			$this->redirect('Security/login');
		}

		// If there is no method then we redirect (not a callback)
		if(!isset($method)) {
			// Redirects:
			OpauthAuthenticator::opauth(true);
		}
		else {
			return $this->oauthCallback($request);
		}
	}

	/**
	 * Equivalent to "callback.php" in the Opauth package.
	 * If there is a problem with the response, we throw an HTTP error.
	 * When done validating, we return back to the Authenticator continue auth.
	 * @throws SS_HTTPResponse_Exception if any validation errors
	 */
	protected function oauthCallback(SS_HTTPRequest $request) {

		// Set up and run opauth with the correct params from the strategy:
		$opauth = OpauthAuthenticator::opauth(true, array(
			'strategy'	=> $request->param('Strategy'),
			'action'	=> $request->param('StrategyMethod'),
		));

	}

	public function finished(SS_HTTPRequest $request) {

		$opauth = OpauthAuthenticator::opauth(false);

		$response = $this->getOpauthResponse();

		// Handle all Opauth validation in this handy function
		try {
			$this->validateOpauthResponse($opauth, $response);
		}
		catch(Exception $e) {
			$this->httpError(400, $e->getMessage());
		}

		$identity = OpauthIdentity::factory($response);

		$member = $identity->findOrCreateMember();

		// If the member exists, associate it with the identity and log in
		if($member->isInDB()) {
			if(!$identity->exists()) {
				$identity->write();
			}
		}
		else {
			// Check that it passes validation - we might not have full fields
			$validationResult = $member->validate();
			if(!$validationResult->valid()) {
				// Redirect to complete register step by adding in extra info
			}

			// Associate the identity with the member
			$member->write();
			$identity->MemberID = $member->ID;
			$identity->write();
		}

		// Back up the BackURL as Member::logIn regenerates the session
		$backURL = Session::get('BackURL');
		$member->logIn();

		// Decide where to go afterwards...
		if(!empty($backURL)) {
			$redirectURL = $backURL;
		}
		else {
			$redirectURL = Security::config()->default_login_dest;
		}

		return $this->redirect($redirectURL);
	}

	/**
	 * Returns the response from the Oauth callback.
	 * @throws InvalidArugmentException
	 * @return array The response
	 */
	protected function getOpauthResponse() {
		$transportMethod = OpauthAuthenticator::config()->get('opauth_callback_transport');
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

		/**
		 * @todo: improve this signature check. it's a bit weak.
		 */
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
				throw new InvalidArgumentException('Required component "'.$component.'" was missing');
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
	 * MUST have trailling slash for Opauth needs
	 * @return string
	 */
	public static function get_path() {
		return Controller::join_links(
			self::config()->opauth_path,
			'strategy/'
		);
	}

	/**
	 * 'callback_url' param for use in Opauth's config
	 * MUST have trailling slash for Opauth needs
	 * @return string
	 */
	public static function get_callback_path() {
		return Controller::join_links(
			self::config()->opauth_path,
			'finished/'
		);
	}
}
