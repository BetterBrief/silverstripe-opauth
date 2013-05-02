<?php

/**
 * OpauthIdentity
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 */
class OpauthIdentity {

	protected
		$authProvider,
		$authSource,
		$authUID;

	/**
	 * @param string $provider The auth provider, e.g. Google
	 * @param string $uid The UID specific to the provider, e.g. 55555555
	 * @param array $auth The full auth source array
	 */
	public function __construct($provider, $uid, $auth) {
		$this->authProvider = $provider;
		$this->authUID = $uid;
		$this->authSource = $auth;
		$this->setupFromAuthSource();
	}

	/**
	 * factory
	 * @param array $oaResponse The response object from Opauth.
	 * @return OpauthIdentity instance based on $oaResponse.
	 */
	public static function factory(array $oaResponse) {
		if(empty($oaResponse['auth'])) {
			throw new InvalidArgumentException('The auth key is required to continue.');
		}
		if(empty($oaResponse['auth']['provider'])) {
			throw new InvalidArgumentException('Unable to determine provider.');
		}
		$auth = $oaResponse['auth'];
		$provider = $auth['provider'];
		$uid = $auth['uid'];
		return new OpauthIdentity($provider, $uid, $auth);
	}

	public function setAuthSource($auth) {
		$this->authSource = $auth;
		return $this;
	}

	public function getMemberMapper() {
		$mapper = Config::inst()->get(__CLASS__, 'member_mapper');
		return $mapper[$this->authProvider];
	}

	/**
	 * Use dot notation and/or a parser to retrieve information from a provider.
	 * Examples of simple dot notation:
	 * - 'FirstName' => 'info.first_name'
	 * - 'Surname' => 'info.surname'
	 * Examples of a parser, for example when only a "name" param is present:
	 * - 'FirstName' => array('OpauthResponseHelper', 'get_first_name')
	 * - 'Surname' => array('OpauthResponseHelper', 'get_last_name')
	 * @see OpauthResponseHelper
	 * @return array The data record to add to a member
	 */
	public function setupFromAuthSource() {
		$record = array();
		foreach($this->getMemberMapper() as $memberField => $sourcePath) {
			if(is_array($sourcePath)) {
				$record[$memberField] = call_user_func($sourcePath, $this->authSource);
			}
			else if(is_string($sourcePath)) {
				$record[$memberField] = OpauthResponseHelper::parse_source_path($sourcePath, $this->authSource);
			}
		}
		return $record;
	}

}
