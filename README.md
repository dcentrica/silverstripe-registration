# Silverstripe Member Registration

## About

It [does exactly what it says on the tin](https://www.youtube.com/watch?v=f8v_RqanM74): Provides a route `/Security/register` to a basic membership form for users to register with your Silverstripe application. It also provides a profile form, for your users to maintain their profiles.

## Requirements

* `PHP ^8`
* `silverstripe/framework ^5`
* Does **not require** `silverstripe/cms`.

## Configuration

An environment variable `REGISTRATION_ENABLED` needs to be set in your server/hosting environment(s) in order for the registration form to be be displayed at the designated route.

The correct route overrides need to be set:

```yml
SilverStripe\Control\Director:
  rules:
    'Security//$Action/$ID/$OtherID': Dcentrica\Registration\Security\Security
```

There's also an optional extension for augmenting `Member` records with a field, whose value tells userland systems that a user was indeed created via registration.

```yml
SilverStripe\Security\Member:
  extensions:
    - Dcentrica\Registration\Extension\MemberRegistrationExtension
```

By default, newly registered users are automatically logged-in. However, this behaviour can be configured as follows:

```yml
Dcentrica\Registration\Security\RegisterHandler:
  # Prevent Silverstripe from auto-logging-in post registration
  login_after_register: false
```

You can add an optional "success" message when registration is complete. This is useful to show if you do not want registered
users to be automatically logged-in:

```yml
Dcentrica\Registration\Security\RegisterHandler:
  registration_completion_message: 'Well done, you filled in a form. Who's a good boy?'
```

## Caveats

* The module also includes a profile management form, but this hasn't yet been tested as working 100%.
* The module does **not** include any form of spam protection.

## History

Forked from [tony13tv/silverstripe-registration](https://github.com/tony13tv/silverstripe-registration) to work with Silverstripe 5+ and finish off a few rough edges.

## License

This module retains the original module's `MIT` license "claim" (the original module didn't come with a `LICENSE` file, nor did it stipulate its licensing in any file headers).