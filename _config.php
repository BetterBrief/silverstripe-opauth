<?php

/**
 * @author Will Morgan <@willmorgan>
 * @author Dan Hensby <@dhensby>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
// Set base constant so devs can put this module wherever
define('OPAUTH_BASE', basename(dirname(__FILE__)));

Authenticator::register_authenticator('OpauthAuthenticator');
