<?php

/**
 * OpauthController
 * Wraps around Opauth for handling callbacks.
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class OpauthController extends ContentController {

	private static
		$allowed_actions = array(
			'index',
			'finished',
			'profilecompletion',
			'RegisterForm',
		),
		$url_handlers = array(
			'finished' => 'finished',
		);

	/**
	 * Bitwise indicators to extensions what sort of action is happening
	 */
	const
		/**
		 * LOGIN = already a user with an OAuth ID
		 */
		AUTH_FLAG_LOGIN = 2,
		/**
		 * LINK = already a user, linking a new OAuth ID
		 */
		AUTH_FLAG_LINK = 4,
		/**
		 * REGISTER = new user, linking OAuth ID
		 */
		AUTH_FLAG_REGISTER = 8;

	protected
		$registerForm;

	/**
	 * Fake a Page_Controller by using that class as a failover
	 */
	public function __construct($dataRecord = null) {
		if(class_exists('Page_Controller')) {
			$dataRecord = new Page_Controller($dataRecord);
		}
		parent::__construct($dataRecord);
	}

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
			return Security::permissionFailure($this);
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
	 * This is executed when the Oauth provider redirects back to us
	 * Opauth handles everything sent back in this request.
	 */
	protected function oauthCallback(SS_HTTPRequest $request) {

		// Set up and run opauth with the correct params from the strategy:
		OpauthAuthenticator::opauth(true, array(
			'strategy'	=> $request->param('Strategy'),
			'action'	=> $request->param('StrategyMethod'),
		));

	}

	/**
	 * Equivalent to "callback.php" in the Opauth package.
	 * If there is a problem with the response, we throw an HTTP error.
	 * When done validating, we return back to the Authenticator continue auth.
	 * @throws SS_HTTPResponse_Exception if any validation errors
	 */
	public function finished(SS_HTTPRequest $request) {

		$opauth = OpauthAuthenticator::opauth(false);

		$response = $this->getOpauthResponse();

		if (!$response) {
			$response = array();
		}
		// Clear the response as it is only to be read once (if Session)
		Session::clear('opauth');

		// Handle all Opauth validation in this handy function
		try {
			$this->validateOpauthResponse($opauth, $response);
		}
		catch(OpauthValidationException $e) {
			return $this->handleOpauthException($e);
		}

		$identity = OpauthIdentity::factory($response);

		$member = $identity->findOrCreateMember();

		// If the member exists, associate it with the identity and log in
		if($member->isInDB() && $member->validate()->valid()) {
			if(!$identity->exists()) {
				$identity->write();
				$flag = self::AUTH_FLAG_LINK;
			}
			else {
				$flag = self::AUTH_FLAG_LOGIN;
			}
		}
		else {

			$flag = self::AUTH_FLAG_REGISTER;

			// Write the identity
			$identity->write();

			// Even if written, check validation - we might not have full fields
			$validationResult = $member->validate();
			if(!$validationResult->valid()) {
				// Keep a note of the identity ID
				Session::set('OpauthIdentityID', $identity->ID);
				// Set up the register form before it's output
				$regForm = $this->RegisterForm();
				$regForm->loadDataFrom($member);
				$regForm->setSessionData($member);
				$regForm->validate();
				return $this->redirect($this->Link('profilecompletion'));
			}
			else {
				$member->extend('onBeforeOpauthRegister');
				$member->write();
				$identity->MemberID = $member->ID;
				$identity->write();
			}
		}
		return $this->loginAndRedirect($member, $identity, $flag);
	}

	/**
	 * @param Member
	 * @param OpauthIdentity
	 * @param int $mode One or more AUTH_FLAGs.
	 */
	protected function loginAndRedirect(Member $member, OpauthIdentity $identity, $mode) {
		// Back up the BackURL as Member::logIn regenerates the session
		$backURL = Session::get('BackURL');

		// Check if we can log in:
		$canLogIn = $member->canLogIn();

		if(!$canLogIn->valid()) {
			$extendedURLs = $this->extend('getCantLoginBackURL', $member, $identity, $canLogIn, $mode);
			if(count($extendedURLs)) {
				$redirectURL = array_pop($extendedURLs);
				$this->redirect($redirectURL, 302);
				return;
			}
			Security::permissionFailure($this, $canLogIn->message());
			return;
		}

		// Decide where to go afterwards...
		if(!empty($backURL)) {
			$redirectURL = $backURL;
		}
		else {
			$redirectURL = Security::config()->default_login_dest;
		}

		$extendedURLs = $this->extend('getSuccessBackURL', $member, $identity, $redirectURL, $mode);

		if(count($extendedURLs)) {
			$redirectURL = array_pop($extendedURLs);
		}

		$member->logIn();

		// Clear any identity ID
		Session::clear('OpauthIdentityID');
		
		// Clear the BackURL
		Session::clear('BackURL');

		return $this->redirect($redirectURL);
	}

	public function profilecompletion(SS_HTTPRequest $request = null) {
		if(!Session::get('OpauthIdentityID')) {
			Security::permissionFailure($this);
		}
		// Redirect to complete register step by adding in extra info
		return $this->renderWith(array(
				'OpauthController_profilecompletion',
				'Security_profilecompletion',
				'Page',
			)
		);
	}

	public function RegisterForm(SS_HTTPRequest $request = null, Member $member = null, $result = null) {
		if(!isset($this->registerForm)) {
			$form = new OpauthRegisterForm($this, 'RegisterForm', $result);
			$form->populateFromSources($request, $member, $result);
			// Set manually the form action due to how routing works
			$form->setFormAction(Controller::join_links(
				self::config()->opauth_path,
				'RegisterForm'
			));
			$this->registerForm = $form;
		}
		else {
			$this->registerForm->populateFromSources($request, $member, $result);
		}
		return $this->registerForm;
	}

	public function doCompleteRegister($data, $form, $request) {
		$member = new Member();
		$form->saveInto($member);
		$identityID = Session::get('OpauthIdentityID');
		$identity = DataObject::get_by_id('OpauthIdentity', $identityID);
		$validationResult = $member->validate();
		$existing = Member::get()->filter('Email', $member->Email)->first();
		$emailCollision = $existing && $existing->exists();
		// If not valid then we have to manually transpose errors to the form
		if(!$validationResult->valid() || $emailCollision) {
			$errors = $validationResult->messageList();
			$form->setRequiredFields($errors);
			// Mandatory check on the email address
			if($emailCollision) {
				$form->addErrorMessage('Email', _t(
					'OpauthRegisterForm.ERROREMAILTAKEN',
					'It looks like this email has already been used'
				), 'required');
			}
			return $this->redirect('profilecompletion');
		}
		// If valid then write and redirect
		else {
			$member->extend('onBeforeOpauthRegister');
			$member->write();
			$identity->MemberID = $member->ID;
			$identity->write();
			return $this->loginAndRedirect($member, $identity, self::AUTH_FLAG_REGISTER);
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
			case 'get':
			case 'post':
				return $this->getResponseFromRequest($transportMethod);
			default:
				throw new InvalidArgumentException('Invalid transport method: ' . $transportMethod);
		}
	}

	/**
	 * Validates the Oauth response for Opauth.
	 * @throws InvalidArgumentException
	 */
	protected function validateOpauthResponse($opauth, $response) {
		if(!empty($response['error'])) {
			throw new OpauthValidationException('Oauth provider error', 1, $response['error']);
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

		$invalidReason = '';

		/**
		 * @todo: improve this signature check. it's a bit weak.
		 */
		if(!$opauth->validate(
			sha1(print_r($response['auth'], true)),
			$response['timestamp'],
			$response['signature'],
			$invalidReason
		)) {
			throw new OpauthValidationException('Invalid auth response', 3, $invalidReason);
		}
	}

	/**
	 * Shorthand for quickly finding missing components and complaining about it
	 * @throws InvalidArgumentException
	 */
	protected function requireResponseComponents(array $components, $response) {
		foreach($components as $component) {
			if(empty($response[$component])) {
				throw new OpauthValidationException('Required component missing', 2, $component);
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
	 * @param OpauthValidationException $e
	 */
	protected function handleOpauthException(OpauthValidationException $e) {
		$data = $e->getData();
		$loginFormName = 'OpauthLoginForm_LoginForm';
		$message = '';
		switch($e->getCode()) {
			case 1: // provider error
				$message = _t(
					'OpauthLoginForm.OAUTHFAILURE',
					'There was a problem logging in with {provider}.',
					array(
						'provider' => $data['provider'],
					)
				);
				break;
			case 2: // validation error
			case 3: // invalid auth response
				$message = _t(
					'OpauthLoginForm.RESPONSEVALIDATIONFAILURE',
					'There was a problem logging in - {message}',
					array(
						'message' => $e->getMessage(),
					)
				);
				break;
		}
		// Set form message, redirect to login with permission failure
		Form::messageForForm($loginFormName, $message, 'bad');
		// always redirect to login
		Security::permissionFailure($this, $message);
	}

	/**
	 * Looks at $method (GET, POST, PUT etc) for the response.
	 * @return array Opauth response
	 */
	protected function getResponseFromRequest($method) {
		return unserialize(base64_decode($this->request->{$method.'Var'}('opauth')));
	}

	public function Link($action = null) {
		return Controller::join_links(
			self::config()->opauth_path,
			$action
		);
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

////**** Template variables ****////
	function Title() {
		if($this->action == 'profilecompletion') {
			return _t('OpauthController.PROFILECOMPLETIONTITLE', 'Complete your profile');
		}
		return _t('OpauthController.TITLE', 'Social Login');
	}

	public function Form() {
		return $this->RegisterForm();
	}
////**** END Template variables ****////

}
