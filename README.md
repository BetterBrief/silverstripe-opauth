**This module depends on Opauth which is no longer maintained - we don't recommend continuing to use this module as we aren't actively developing it any longer.**

# SilverStripe Opauth Module

[![Build Status](https://secure.travis-ci.org/BetterBrief/silverstripe-opauth.png?branch=master)](http://travis-ci.org/BetterBrief/silverstripe-opauth)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/BetterBrief/silverstripe-opauth/badges/quality-score.png?s=257116d0420a86115addee48affc91a8abb41939)](https://scrutinizer-ci.com/g/BetterBrief/silverstripe-opauth/)
[![Code Coverage](https://scrutinizer-ci.com/g/BetterBrief/silverstripe-opauth/badges/coverage.png?s=3ca06d73fa9aeb2dc7513c4ca6f6cf703a684911)](https://scrutinizer-ci.com/g/BetterBrief/silverstripe-opauth/)

## Introduction
Uses the [Opauth library](http://opauth.org) for easy drop-in strategies for social login. See their [documentation](https://github.com/opauth/opauth/wiki/)

## Current Status
1.1 - stable. No known major issues. Report issues using the [bug tracker](https://github.com/BetterBrief/silverstripe-opauth/issues).

## How does it work?
The module provides an additional login form which the developer has control over, that allows users to instantly sign in to your website with an identity provided by any Oauth provider. The providers are each handled by using an `OpauthStrategy`, many of which are freely available. There are strategies for Facebook, Twitter, Google, and many more.

Based on the identity data from the Oauth provider, the module will find or create a new `Member` object based on the provided email address in the identity. This also means a Member can have many Oauth identities linked to a single account; these are saved in to the `OpauthIdentity` object.

If the resultant `Member` generated from the provider's response doesn't have an email address, or any other piece of data you require, there is functionality built in to handle this. You can enforce required fields, or any other kind of validation, by setting the `OpauthValidator`'s `custom_validator` property to the class name of the validator you require.

Other than that, the user flow is quite simple. Provided all required data is there, the member is logged in with `Member::login` and then redirected to the page they were looking at or the default destination, settable in your config - just like the default `MemberAuthenticator`.

## Requirements

 * SilverStripe 3.1 (maybe 3.0, but untested so far)
 * At least one Opauth strategy
 * Preferably, allow_url_fopen enabled in php.ini. We've written a custom cURL workaround that works with Twitter, Google and Facebook strategies, but it's proprietary.
 * For extended cURL support we rely on an Opauth fork

## FAQ

### What does this module include?
It includes:
 * the Opauth core (see below);
 * `OpauthAuthenticator`: intended to be comparable with `MemberAuthenticator`;
 * `OpauthLoginForm`: which offers different ways you can authenticate;
 * `OpauthRegisterForm`: which, if configured, provides an intermediate step so incomplete `OpauthIdentity`-authenticating members can fill in extra information as required;
 * `OpauthController`: which acts as a negotiator for the communication that strategies undertake;
 * `OpauthIdentity`: which acts as a service-agnostic interface with which to save Oauth identities in to the `Member` object. These are associated with a `Member` upon successful login so that the auth provider's UID and signed response act as a key.

*NB: Opauth's maintainers recommend you include strategies as required, rather than bundling them together.*

### Where can I get strategies?

You can find a list under the "Available Strategies" heading on the [Opauth homepage](http://opauth.org)

Packagist provides a [list of strategies](https://packagist.org/search/?q=opauth/) you can use to install via Composer.

### Where should I put strategies?
Use composer to require your strategies

### How do I map the API responses to a `Member`?
You define the `OpauthIdentity` `member_mapper` block in your `_config.yml`. Simply provide a hash map of member fields to dot notated paths of the Opauth response array for simple fields, or if you need to perform some parsing to retrieve the value you want, an array of class name and function, like `['OpauthResponseHelper', 'get_first_name']`. It takes the auth response array as an argument. See the example config YAML below for more details.

### How do I configure the module and its strategies?
All Opauth-specific configuration variables can be put under `opauth_settings` and are passed directly to `Opauth`.

You can put these settings in your `_config.yml` file. Additionally, as your strategy API details will likely change per domain and thus per environment, you are able to update these using the `Config` API. Please see the [Opauth config documentation](https://github.com/opauth/opauth/wiki/Opauth-configuration#configuration-array). Here's some examples to help you:

###### `_config.yml` example:
```yml
---
Name: silverstripe-opauth
After: 'framework/*','cms/*'
---
# see the Opauth docs for the config settings - https://github.com/opauth/opauth/wiki/Opauth-configuration#configuration-array
OpauthAuthenticator:
  opauth_settings:
    #Register your strategies here
    #Including any extra config
    Strategy:
      Facebook:
        app_id: ''
        app_secret: ''
        scope: email
      Google:
        client_id: ''
        client_secret: ''
      Twitter:
        key: ''
        secret: ''
    security_salt: 'correct horse battery staple'
    security_iteration: 500
    security_timeout: '2 minutes'
    callback_transport: 'session'
#Configuration for the Identity-Member mapping
OpauthIdentity:
  member_mapper:
    Facebook:
      FirstName: 'info.first_name'
      Surname: 'info.last_name'
      Locale: 'raw.locale'
      Email: 'info.email'
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
//Register and configure strategies
Config::inst()->update('OpauthAuthenticator', 'opauth_settings', array(
  'Strategy' => array(
    'Facebook' => array(
      'app_id' => '',
      'app_secret' => '',
      'scope' => 'email',
    ),
    'Google' => array(
      'client_id' => '',
      'client_secret' => ''
    ),
    'Twitter' => array(
      'key' => '',
      'secret' => ''
    ),
  ),
));

//Identity to member mapping settings per strategy
Config::inst()->update('OpauthIdentity', 'member_mapper', array(
  'Facebook' => array(
    'FirstName' => 'info.first_name',
    'Surname' => 'info.last_name',
    'Locale' => 'raw.locale',
    'Email' => 'info.email',
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

*NB: As you can see, sometimes the Strategy configuration settings may have inconsistent namings - we can't help with that, sorry!*

## Documentation
Please read the [Opauth documentation](https://github.com/opauth/opauth/wiki/) and [our own documentation](docs/en/)

## Raising bugs and suggesting enhancements
If you find a bug or have a Really Good Idea™, please [raise an issue](https://github.com/BetterBrief/silverstripe-opauth/issues). Better still, if you can fix the bug, then feel free to send in a pull request with the remedial code that ideally respects the coding conventions used thus far.

## Attribution
 * Opauth available under MIT licence by U-Zyn Chua (http://uzyn.com) Copyright © 2012-2013
