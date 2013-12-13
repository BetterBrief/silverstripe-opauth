# Configuration

As a starting point, please see the [Opauth configuration documentation](https://github.com/opauth/opauth/wiki/Opauth-configuration).

The Opauth configuration system has been adapted to fit into the SilverStripe Config system, so we can configure the settings we require via YAML or Config API calls.

## Registering a strategy
All strategies you want to use will need to be registered with the module. No strategies are included by default and it's completely up to you which you'd like to use. You can even [write your own](https://github.com/opauth/opauth/wiki/Strategy-Contribution-Guide).

To add the FacebookStrategy, first [download it](https://github.com/opauth/facebook#getting-started) from git hub and place it into your opauth strategies folder. We recommentd mysite/code/thirdparty as a good location for them.

Once you've downloaded the stratagies you want, you'll need to register them. This is how you'd do it if you were registering the Facebook Strategy:

###### YAML (_config.yml):
```yml
OpauthAuthenticator:
  enabled_strategies:
    - FacebookStrategy
```

###### PHP (_config.php):
```php
Config::inst()->update('OpauthAuthenticator', 'enabled_strategies', array('FacebookStrategy'));
```

## Configuring a strategy

Assuming you've added the [Facebook Strategy](https://github.com/opauth/facebook), we need to configure the `app_id` and the `app_secret` to allow you to communicate with Facebook and obtain user details.

###### YAML (_config.yml):
```yml
OpauthAuthenticator:
  opauth_strategy_config:
    Facebook:
      app_id: 'YOUR APP ID'
      app_secret: 'YOUR APP SECRET'
	  scope: 'optional, comma, seperated, string'
```
###### PHP (_config.php):
```php
Config::inst()->update('OpauthAuthenticator', 'opauth_strategy_config', array(
	'FacebookStrategy' => array(
		'app_id' => 'YOUR APP ID',
		'app_secret' => 'YOUR APP SECRET'
		'scope' => 'optional, comma, seperated, string'
	)
));
```

## Extensions and hooks (introduced in 1.1)

If you wish to run some special logic at certain points of the authentication process, 1.1 has introduced hooks for the following object events:

#### OpauthIdentity
* `onBeforeCreate`, called before a new record is saved for the first time
* `onAfterCreate`, called after a new record is saved for the first time
* `onMemberLinked`, called when the MemberID attribute of the object changes; after `OpauthIdentity->onBeforeCreate()`

#### Member
* `onBeforeOpauthRegister`, called when member validation passes; before `OpauthIdentity->onMemberLinked()`; before `Member->write()`

#### OpauthController
* `getSuccessBackURL`, called before the Member is logged in and redirected. This is handy for overriding the BackURL if you need to send the user off for further processes.
* `getCantLoginBackURL`, called if the validation logic in `Member->canLogIn` returns false (i.e. you can't login yet, even if you have completed the Opauth process successfully). Handy for overriding the BackURL and doing something more interesting than `Security::permissionFailure` if you need a user to do more things before being fully signed up, like verify their email, etc.

The extension points can be accessed using the standard SilverStripe DataExtension system, and should cover a lot of use cases where special business logic must happen.
Since you can access the full object on the extended event, you can even customise your logic based on the identity provider for this request.
