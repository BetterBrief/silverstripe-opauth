<?php

/**
 * OpauthMemberExtension
 * @author Will Morgan <@willmorgan>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class OpauthMemberExtension extends DataExtension {

	private static
		$has_many = array(
			'OpauthIdentities' => 'OpauthIdentity',
		);

}
