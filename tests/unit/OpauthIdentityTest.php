<?php
class OpauthIdentityTest extends SapphireTest {

	protected $usesDatabase = true;

	public function setUp() {
		parent::setUp();

		Config::inst()->update('OpauthIdentity', 'member_mapper', array(
			'Facebook' => array(
				'FirstName' => 'info.first_name',
				'Surname' => 'info.last_name',
				'Email' => 'info.email',
			)
		));
	}

	public function testFindOrCreateMemberLinkOnMatch() {
		$member = new Member(array('Email' => 'existing@test.com'));
		$member->write();

		$identity = OpauthIdentity::factory(array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => 999,
				'info' => array('email' => 'existing@test.com')
			)
		));
		$identity->findOrCreateMember(array('linkOnMatch' => false));
		$this->assertEquals(0, $identity->MemberID, 'Does not link unless requested');

		$identity = OpauthIdentity::factory(array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => 999,
				'info' => array('email' => 'existing@test.com')
			)
		));
		$identity->findOrCreateMember(array('linkOnMatch' => true));
		$this->assertEquals(
			$member->ID, 
			$identity->MemberID, 
			'Links if requested and email matches'
		);

		$identity = OpauthIdentity::factory(array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => 999,
				'info' => array('email' => 'new@test.com')
			)
		));
		$identity->findOrCreateMember(array('linkOnMatch' => true));
		$this->assertEquals(0, $identity->MemberID, 'Does not link if requested but no member found');
	}

	public function testFindOrCreateMemberOverwriteExistingFields() {
		$member = new Member(array(
			'Email' => 'existing@test.com',
			'FirstName' => 'Existing',
			'Surname' => 'Existing',
		));
		$member->write();

		$identity = OpauthIdentity::factory(array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => 999,
				'info' => array(
					'email' => 'existing@test.com',
					'first_name' => 'New',
					'last_name' => 'New'
				)
			)
		));
		$member = $identity->findOrCreateMember(array('overwriteExistingFields' => false));
		$this->assertEquals(
			'Existing', 
			$member->FirstName, 
			'Does not overwrite unless requested'
		);

		$identity = OpauthIdentity::factory(array(
			'auth' => array(
				'provider' => 'Facebook',
				'uid' => 999,
				'info' => array(
					'email' => 'existing@test.com',
					'first_name' => 'New',
					'last_name' => 'New'
				)
			)
		));
		$member = $identity->findOrCreateMember(array('overwriteExistingFields' => array(
			'FirstName'
		)));
		$this->assertEquals(
			'New', 
			$member->FirstName, 
			'Overwrites existing fields if requested'
		);
		$this->assertEquals(
			'Existing', 
			$member->Surname, 
			'Does not overwrite fields if not present in whitelist'
		);
	}

}