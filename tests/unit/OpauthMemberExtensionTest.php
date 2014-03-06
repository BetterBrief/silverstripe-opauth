<?php
class OpauthMemberExtensionTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $requiredExtensions = array(
		'Member' => array('OpauthMemberExtension')
	);

	public function testDeletesOpauthIdentityOnDelete() {
		$member = new Member(array('Email' => 'test@test.com'));
		$member->write();
		$identity = new OpauthIdentity();
		$identity->write();
		$member->OpauthIdentities()->add($identity);
		
		$member->delete();
		
		$this->assertEquals(0, $member->OpauthIdentities()->Count());
	}

}