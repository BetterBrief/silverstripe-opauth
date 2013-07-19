# SilverStripe Opauth Module

## Introduction
Uses the [Opauth library](http://opauth.org) for easy drop-in strategies for social login.

We intend to release it as a full SilverStripe module that supports Opauth fully in the next few weeks.

## Current Status
Tested, no known major issues. Report issues using the [bug tracker](https://github.com/BetterBrief/silverstripe-opauth/issues).

## Requirements

 * SilverStripe 3.0+
 * At least one Opauth stratagy
 * Preferably, allow_url_fopen enabled in php.ini. We've written a custom cURL workaround that works with Twitter, Google and Facebook strategies, but it's proprietary.

## Documentation
Please read the [Opauth documentation](https://github.com/opauth/opauth/wiki/) and [our own documentation](docs/en/)

## FAQ

### What version of SilverStripe?
Requires: SilverStripe 3.1 (maybe 3.0, but untested so far)

### What does this module include?
It includes:
 * the Opauth core (see below);
 * `OpauthAuthenticator`: intended to be comparable with `MemberAuthenticator`;
 * `OpauthLoginForm`: which offers different ways you can authenticate;
 * `OpauthController`: which acts as a negotiator for the communication that strategies undertake;
 * `OpauthIdentity`: intended to give you a service-agnostic interface with which to save Oauth identities in to the `Member` object.

*NB: Opauth's maintainers recommend you include strategies as required, rather than bundling them together.*

### Where can I get strategies?
You can find them under the "Available Strategies" heading on the [Opauth homepage](http://opauth.org)

Alternatively, you can find them in the [bundle package](http://opauth.org/download.php).

### Where should I put strategies?
We recommend putting them under `mysite/thirdparty`, but it's up to you. Any root level directory that contains a `_config.php` (empty or otherwise) is scanned by the manifest builder.

### Why isn't SilverStripe finding my stratagies in `mysite/thirdparty`?
It could be you're super clever and have a `_manifest_exclude` file in your `thirdparty` folder, preventing it being spidered by SilverStripe's manifest builder. Try moving the stratagies folder to `mysite/code/opauth/` or, if you don't want to do that, you can set the opauth setting `strategy_dir` to be `BASE_PATH . '/mysite/thirdparty'` and Opauth will find them for you.

### How do I map the API responses to a `Member`?
You define the `OpauthIdentity` `member_mapper` block in your `_config.yml`. Simply provide a hash map of member fields to dot notated paths of the Opauth response array for simple fields, or if you need to perform some parsing to retrieve the value you want, an array of class name and function, like `['OpauthResponseHelper', 'get_first_name']`. It takes the auth response array as an argument. See the example config YAML below for more details.

### How do I configure the module and its strategies?
You can put them in your `_config.yml` file. Additionally, as your strategy API details will likely change per domain and thus per environment, you are able to update these using the `Config` API. Please see the [Opauth config documentation](https://github.com/opauth/opauth/wiki/Opauth-configuration#configuration-array). Here's some examples to help you:

###### `_config.yml` example:
```yml
---
Name: silverstripe-opauth
After: 'framework/*','cms/*'
---
# see the Opauth docs for the config settings - https://github.com/opauth/opauth/wiki/Opauth-configuration#configuration-array
OpauthAuthenticator:
  #Register your strategies here
  enabled_strategies:
    - FacebookStrategy
    - GoogleStrategy
    - TwitterStrategy
  opauth_security_salt: 'correct horse battery staple'
  opauth_security_iteration: 500
  opauth_security_timeout: '2 minutes'
  opauth_callback_transport: 'session'
  #Per strategy config
  opauth_strategy_config:
    Facebook:
      app_id: ''
      app_secret: ''
    Twitter:
      key: ''
      secret: ''
    Google:
      client_id: ''
      client_secret: ''
#Configuration for the Identity-Member mapping
OpauthIdentity:
  member_mapper:
    Facebook:
      FirstName: 'info.first_name'
      Surname: 'info.last_name'
      Locale: 'raw.locale'
    Twitter:
      FirstName: ['OpauthResponseHelper', 'get_first_name']
      Surname: ['OpauthResponseHelper', 'get_last_name']
      Locale: ['OpauthResponseHelper', 'get_twitter_locale']
    Google:
      FirstName: 'info.first_name'
      Surname: 'info.last_name'
      Email: 'info.email'
      Locale: ['OpauthResponseHelper', 'get_google_locale']
```

##### `_config.php` example:
```php
//Register strategies
Config::inst()->update('OpauthAuthenticator', 'enabled_strategies', array(
	'FacebookStrategy',
	'GoogleStrategy',
	'TwitterStrategy'
));

//Configure strategies
Config::inst()->update('OpauthAuthenticator', 'opauth_strategy_config', array(
	'Facebook' => array(
		'app_id' => '',
		'app_secret' => ''
	),
	'Twitter' => array(
		'key' => '',
		'secret' => ''
	),
	'Google' => array(
		'client_id' => '',
		'client_secret' => ''
	)
));

//Identity to member mapping settings per strategy
Config::inst()->update('OpauthIdentity', 'member_mapper', array(
	'Facebook' => array(
		'FirstName' => 'info.first_name',
		'Surname' => 'info.last_name',
		'Locale' => 'raw.locale',
	),
	'Twitter' => array(
		'FirstName' => array('OpauthResponseHelper', 'get_first_name'),
		'Surname' => array('OpauthResponseHelper', 'get_last_name'),
		'Locale' => array('OpauthResponseHelper', 'get_twitter_locale'),
	),
	'Google' => array(
		'FirstName' => 'info.first_name',
		'Surname' => 'info.last_name',
		'Email' => 'info.email',
		'Locale' => array('OpauthResponseHelper', 'get_google_locale'),
	),
));
```

## Licence

<a rel="license" href="http://creativecommons.org/licenses/by/2.0/uk/deed.en"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/2.0/uk/88x31.png" /></a><br /><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">SilverStripe Opauth Module</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="http://www.betterbrief.co.uk/" property="cc:attributionName" rel="cc:attributionURL">Better Brief LLP</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by/2.0/uk/deed.en">Creative Commons Attribution 2.0 UK: England &amp; Wales License</a>.<br />Based on a work at <a xmlns:dct="http://purl.org/dc/terms/" href="https://github.com/BetterBrief/silverstripe-opauth" rel="dct:source">https://github.com/BetterBrief/silverstripe-opauth</a>.

## Attribution
 * Opauth available under MIT licence by U-Zyn Chua (http://uzyn.com) Copyright © 2012-2013
