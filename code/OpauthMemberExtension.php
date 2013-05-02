<?php

class OpauthMemberExtension extends DataExtension {

	public function extraStatics($class = null, $extension = null) {
		return array(
			'has_many' => array(
				'OpauthIdentities' => 'OpauthIdentity',
			),
		);
	}

}
