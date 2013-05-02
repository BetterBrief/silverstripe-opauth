<?php

/**
 * OpauthController
 * An
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 */
abstract class OpauthIdentity {

	protected
		$authSource;

	protected static
		$member_mapping = array();

	public static function factory(array $oaResponse) {
		if(empty($oaResponse['auth'])) {
			throw new InvalidArgumentException('The auth key is required to continue.');
		}
		if(empty($oaResponse['auth']['provider'])) {
			throw new InvalidArgumentException('Unable to determine provider.');
		}
		$auth = $oaResponse['auth'];
		$provider = $auth['provider'];
		$newClassName = $provider.'OpauthIdentity';
		return new $newClassName($auth);
	}

	public function __construct($auth) {
		$this->authSource = $auth;
		$this->setupFromAuthSource();
	}

	public function setAuthSource($auth) {
		$this->authSource = $auth;
		return $this;
	}

	/**
	 * Use dot notation and/or a parser to retrieve information from a provider.
	 * Examples of simple dot notation:
	 * - 'FirstName' => 'info.first_name'
	 * - 'Surname' => 'info.surname'
	 * Examples of a parser, for example when only a "name" param is present:
	 * - 'FirstName' => array('parseFirstName', 'info.name')
	 * - 'Surname' => array('parseSurname', 'info.name')
	 * You can also pass in multiple arguments in to a parser. Just add them:
	 * - 'Locale' => array('parseLocale', 'raw.time_zone', 'raw.lang')
	 * @return array The data record to add to a member
	 */
	public function setupFromAuthSource() {
		$record = array();
		foreach(static::$member_mapping as $memberField => $sourcePath) {
			if(is_string($sourcePath)) {
				$record[$memberField] = static::parse_source_path($sourcePath, $this->authSource);
			}
			else if(is_array($sourcePath)) {
				$parserMethod = array_shift($sourcePath);
				foreach($sourcePath as &$value) {
					$value = static::parse_source_path($value, $this->authSource);
				}
				$record[$memberField] = call_user_func_array(array($this, $parserMethod), $sourcePath);
			}
		}
		Debug::dump($record);
		return $record;
	}

	/**
	 * Dot notation parser. Looks for an index or fails gracefully if not found.
	 * @param string $path The path, dot notated.
	 * @param array $source The source in which to search.
	 * @return string|null
	 */
	public function parse_source_path($path, $source) {
		$fragments = explode('.', $path);
		$currentFrame = $source;
		foreach($fragments as $fragment) {
			if(!isset($currentFrame[$fragment])) {
				return null;
			}
			$currentFrame = $currentFrame[$fragment];
		}
		return $currentFrame;
	}

	protected static function get_smart_locale($language = null, $timeZone = null) {

		require_once FRAMEWORK_PATH . '/thirdparty/Zend/Locale.php';
		$locale = Zend_Locale::getBrowser();

		if(!$locale) {
			if($language) {
				return i18n::get_locale_from_lang($language);
			}
			else {
				return i18n::get_locale();
			}
		}

		$locale = array_keys($locale);
		$firstPref = array_shift($locale);

		if(strpos($firstPref, '_') === false) {
			return i18n::get_locale_from_lang($language);
		}

		return $firstPref;

	}

}
