# Silverstripe Member Registration

## About

It [does exactly what it says on the tin](https://www.youtube.com/watch?v=f8v_RqanM74): Provides a route `/Security/register` to a basic membership form for users to register with your Silverstripe application. It also provides a profile form, for your users to maintain their profiles.

## Requirements

* `PHP ^8`
* `silverstripe/framework ^5`
* Does **not require** `silverstripe/cms`.

## Configuration

An environment variable `REGISTRATION_ENABLED` needs to be set in your server/hosting environment(s) in order for the registration form to be be displayed at the designated route.

There's also an optional extension for augmenting `Member` records with a flag which tells consuming systems that users were indeed created via registration.

```yml
SilverStripe\Security\Member:
  extensions:
    - Dcentrica\Registration\Extension\MemberRegistrationExtension
```

## History

Forked from [tony13tv/silverstripe-registration](https://github.com/tony13tv/silverstripe-registration) to work with Silverstripe 5+ and finish off a few rough edges.

## License

This module retains the original module's `MIT` license "claim" (the original module didn't come with a `LICENSE` file, nor did it stipulate its licensing in any file headers).