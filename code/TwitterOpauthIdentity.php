<?php

class TwitterOpauthIdentity extends OpauthIdentity {

	protected static
		$member_mapping = array(
			'FirstName' => array('parseFirstName', 'info.name'),
			'Surname' => array('parseLastName', 'info.name'),
			'Locale' => array('parseLocale', 'raw.time_zone', 'raw.lang'),
		);

	/**
	 * Take the first part of the name
	 * @return string
	 */
	public function parseFirstName($name) {
		$name = explode(' ', $name);
		return array_shift($name);
	}

	/**
	 * Take all but the first part of the name
	 * @return string
	 */
	public function parseLastName($name) {
		$name = explode(' ', $name);
		array_shift($name);
		return join(' ', $name);
	}

	/**
	 * Uses Zend_Locale to get a decent idea of where the locale is.
	 * If this fails, we go back to using the language set in Twitter with i18n.
	 * @todo: Use PECL Locale class to harden this up
	 * @return string The locale in aa_BB format.
	 */
	public function parseLocale($timeZone, $language) {

		require_once FRAMEWORK_PATH . '/thirdparty/Zend/Locale.php';

		$locale = Zend_Locale::getBrowser();

		if(!$locale) {
			return i18n::get_locale_from_lang($language);
		}

		$locale = array_keys($locale);

		$firstPref = array_shift($locale);

		if(strpos($firstPref, '_') === false) {
			return i18n::get_locale_from_lang($language);
		}

		return $firstPref;

	}

}
