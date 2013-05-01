<?php

/**
 * OpauthController
 * An
 * The SS equivalent of "index.php" and "callback.php" in the Opauth package.
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 */
abstract class OpauthIdentity {

	protected static
		$member_mapping = array();

	public function factory(array $oaResponse) {
		if(empty($oaResponse['auth'])) {
			throw new InvalidArgumentException('The auth key is required to continue.');
		}
		if(empty($oaResponse['auth']['provider'])) {
			throw new InvalidArgumentException('Unable to determine provider.');
		}
		$auth = $oaResponse['auth'];
		$provider = $auth['provider'];
		$newClassName = $provider.'OpauthIdentity';
		return new $newClassName($auth);
	}

}
