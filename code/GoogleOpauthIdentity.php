<?php

class GoogleOpauthIdentity extends OpauthIdentity {

	protected static
		$member_mapping = array(
			'FirstName' => 'info.first_name',
			'Surname' => 'info.last_name',
			'Email' => 'info.email',
			'Locale' => array('parseLocale', 'raw.locale'),
		);

	public function parseLocale($locale) {
		return static::get_smart_locale();
	}

}
