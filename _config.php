<?php

/**
 * @author willm
 */

// Set base constant so devs can put this module wherever
define('OPAUTH_BASE', basename(dirname(__FILE__)));

Authenticator::register_authenticator('OpauthAuthenticator');
