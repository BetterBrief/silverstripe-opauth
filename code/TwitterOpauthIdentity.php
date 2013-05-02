<?php

class TwitterOpauthIdentity extends OpauthIdentity {

	protected static
		$member_mapping = array(
			'FirstName' => array('parseFirstName', 'info.name'),
			'Surname' => array('parseLastName', 'info.name'),
			'Locale' => array('parseLocale', 'raw.lang', 'raw.time_zone'),
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
	public function parseLocale($language, $timeZone) {
		return static::get_smart_locale($language, $timeZone);
	}

}
