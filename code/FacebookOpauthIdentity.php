<?php

class FacebookOpauthIdentity extends OpauthIdentity {

	protected static
		$member_mapping = array(
			'FirstName' => 'info.first_name',
			'Surname' => 'info.last_name',
			'Locale' => 'raw.locale'
		);

}
