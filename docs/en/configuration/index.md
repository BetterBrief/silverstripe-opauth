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

## Configuring Opauth

As you can see, Opauth takes its own config options, these are set by...

Some of the settings are redundant, such as `strategy_dir` as SilverStripe will auto load the strategies for you.
