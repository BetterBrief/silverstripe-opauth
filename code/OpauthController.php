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
			'RegisterForm',
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

		// Clear the response as it is only to be read once (if Session)
		Debug::dump('Skipping opauth session clearing');
		//Session::clear('opauth');

		$response = array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => '547752916',
				'info' => array(
					'name' => 'Will Morgan',
					'image' => 'https://graph.facebook.com/547752916/picture?type=square',
					'nickname' => 'willm0rgan',
					'first_name' => 'Will',
					'last_name' => 'Morgan 2',
					'urls' => array(
						'facebook' => 'http://www.facebook.com/willm0rgan',
					),
				),
				'credentials' => array(
					'token' => 'BAAHUME7HW0gBAKgGzHvcTiA8J2xIfmfZAZA4AwYQt2r3n2d3XzWiPxMAu33iLbTOpVctTC9pUUy3IeIMNktiYna4kxopuX2EOW60kadzBG7JVpWGECRHOZCsTZBVRuxnMZA4M8grMd7HOjVV9Bm351SRv3eHXZBQbeK5zVMmEzOiH4QFzjYKggtlNYd53Guzg7joZAFn99o9mLK4JPjXmIgzjsEre5ZCgnQZD',
					'expires' => '2013-06-30T15:21:35+00:00',
				),
				'raw' => array(
					'id' => '547752916',
					'name' => 'Will Morgan',
					'first_name' => 'Will',
					'last_name' => 'Morgan',
					'link' => 'http://www.facebook.com/willm0rgan',
					'username' => 'willm0rgan',
					'gender' => 'male',
					'timezone' => 1,
					'locale' => 'en_GB',
					'verified' => 1,
					'updated_time' => '2013-02-04T23:06:37+0000',
				),
			),
			'timestamp' => '2013-05-01T16:13:45+00:00',
			'signature' => '6aajcs7w3ycksggwok84c80wcoko0ws',
		);

		// Handle all Opauth validation in this handy function
		try {
			Debug::dump('Skipping validating');
			//$this->validateOpauthResponse($opauth, $response);
		}
		catch(Exception $e) {
			$this->httpError(400, $e->getMessage());
		}

		$identity = OpauthIdentity::factory($response);

		$member = $identity->findOrCreateMember();

		// If the member exists, associate it with the identity and log in
		if($member->isInDB() && $member->validate()->valid()) {
			if(!$identity->exists()) {
				$identity->write();
			}
		}
		else {
			// Write the identity
			$identity->write();

			// Even if written, check validation - we might not have full fields
			$validationResult = $member->validate();
			if(!$validationResult->valid()) {
				// Keep a note of the identity ID
				Session::set('OpauthIdentityID', $identity->ID);
				// Redirect to complete register step by adding in extra info
				return $this->renderWith(array(
						'OpauthController_register',
						'Security_register',
						'Page',
					),
					array(
						'Form' => $this->RegisterForm(null, $member, $validationResult)
					)
				);
			}
			else {
				$member->write();
				$identity->MemberID = $member->ID;
				$identity->write();
			}
		}

		$this->loginAndRedirect($member);
	}

	protected function loginAndRedirect(Member $member) {
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

	public function RegisterForm(SS_HTTPRequest $request = null, Member $member = null, ValidationResult $result = null) {
		$form = new OpauthRegisterForm($this, 'RegisterForm', $result);

		if(isset($member)) {
			$form->loadDataFrom($member);
		}
		else if(isset($request)) {
			$form->loadDataFrom($request->postVars());
		}

		// Hacky!!!
		$form->setFormAction(Controller::join_links(
			self::config()->opauth_path,
			'RegisterForm'
		));

		return $form;
	}

	public function doCompleteRegister($data, $form, $request) {
		$member = new Member();
		$form->saveInto($member);
		if($member->validate()->valid()) {
			$identityID = Session::get('OpauthIdentityID');
			$identity = DataObject::get_by_id('OpauthIdentity', $identityID);
			$member->write();
			$identity = $member->ID;
			$identity->write();
			$this->loginAndRedirect($member);
		}
		else {
			Debug::dump('not valid, whoops.');
			$this->redirectBack();
		}
	}

	/**
	 * Returns the response from the Oauth callback.
	 * @throws InvalidArugmentException
	 * @return array The response
	 */
	protected function getOpauthResponse() {
		$config = OpauthAuthenticator::get_opauth_config();
		$transportMethod = $config['callback_transport'];
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
