<?php

/**
 * OpauthMemberExtension
 * @author Will Morgan <@willmorgan>
 */
class OpauthMemberExtension extends DataExtension {

	private static
		$has_many = array(
			'OpauthIdentities' => 'OpauthIdentity',
		);

}
