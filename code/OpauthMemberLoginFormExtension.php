<?php
class OpauthMemberLoginFormExtension extends Extension {

	/**
	 * @config
	 * @var boolean
	 */
	private static $allow_password_reset = true;

	/**
	 * Deny password resets
	 * 
	 * @param Member $member
	 * @return boolean
	 */
	public function forgotPassword($member) {
		if(Config::inst()->get('OpauthMemberLoginFormExtension', 'allow_password_reset')) {
			return null;
		}

		$identity = OpauthIdentity::get()->find('MemberID', $member->ID);
		if(!$member->Password && $identity) {
			$this->owner->sessionMessage(
				_t(
					'OpauthMemberLoginFormExtension.NoResetPassword',
					'Can\'t reset password for accounts registered through {provider}',
					array('provider' => $identity->Provider)
				),
				'bad'
			);
			return false;
		} else {
			return null;
		}
	}

}