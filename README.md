SilverStripe Opauth
-------------------

### Description
Uses the [Opauth library](http://opauth.org) for easy drop-in strategies for social login.

We intend to release it as a full, official SilverStripe module that supports Opauth fully in the next few weeks.

### Current Status
In development.

FAQ
---
### What version of SilverStripe?
Requires: SilverStripe 3.1 (maybe 3.0, but untested so far)

### What does this module include?
It includes:
- the Opauth core (see below);
- OpauthAuthenticator, intended to be comparable with MemberAuthenticator;
- OpauthLoginForm, which offers different ways you can authenticate;
- OpauthController, which acts as a negotiator for the communication that strategies undertake;
- OpauthIdentity, intended to give you a service-agnostic interface with which to save Oauth identities in to the Member object.

*NB: Opauth's maintainers recommend you include strategies as required, rather than bundling them together.*

### Where can I get strategies?
You can find them under the "Available Strategies" heading on the [Opauth homepage](http://opauth.org)

Alternatively, you can find them in the [bundle package](http://opauth.org/download.php).

### Where should I put strategies?
We recommend putting them under mysite/code/thirdparty, but it's up to you. Any root level directory that contains a _config.php (empty or otherwise) is scanned by the manifest builder.

### How do I configure the module and its strategies?
You can put them in your _config.yml file. Additionally, as your strategy API details will likely change per domain and thus per environment, you are able to update these using the Config API. Here's an example to help you:

    OpauthAuthenticator:
      enabled_strategies:
        - FacebookStrategy
        - GoogleStrategy
        - TwitterStrategy
      opauth_security_salt: 'correct horse battery staple'
      opauth_security_iteration: 500
      opauth_security_timeout: '2 minutes'
      opauth_callback_transport: 'session'
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
