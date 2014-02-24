<?php
class OpauthMemberLoginFormExtensionTest extends SapphireTest {

	public function testForgotPasswordVeto() {
		$memberWithoutPassword = new Member(array(
			'Email' => 'withoutpassword@test.com'
		));
		$memberWithoutPassword->write();

		$memberWithPassword = new Member(array(
			'Email' => 'withpassword@test.com',
			'Password' => 'test'
		));
		$memberWithPassword->write();

		$memberWithIdentity = new Member(array(
			'Email' => 'withidentity@test.com',
		));
		$memberWithIdentity->write();
		$identity = new OpauthIdentity(array(
			'MemberID' => $memberWithIdentity->ID,
			'Provider' => 'Google'
		));
		$identity->write();

		$form = new Form(new Controller(), 'Form', new FieldList(), new FieldList());
		$ext = new OpauthMemberLoginFormExtension();
		$ext->setOwner($form);

		$this->assertNull($ext->forgotPassword($memberWithoutPassword));
		$this->assertNull(Session::get("FormInfo.Form_Form.formError.message"));
		
		$this->assertNull($ext->forgotPassword($memberWithPassword));
		$this->assertNull(Session::get("FormInfo.Form_Form.formError.message"));
		
		$this->assertFalse($ext->forgotPassword($memberWithIdentity));
		$this->assertContains('Google', Session::get("FormInfo.Form_Form.formError.message"));
	}

}