<?php
class OpauthMemberExtension extends DataExtension {

	private static $has_many = array(
		"OpauthIdentities" => "OpauthIdentity"
	);

	public function onBeforeDelete() {
		$this->owner->OpauthIdentities()->removeAll();
	}

}