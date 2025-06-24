<?php

namespace Dcentrica\Registration\Security;

use SilverStripe\Security\Authenticator;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use Dcentrica\Registration\Security\RegistrationHandler;

class RegistrationAuthenticator extends MemberAuthenticator
{
    /**
     *
     * @return int
     */
    public function supportedServices(): int
    {
        // Bitwise-OR of all the supported services in this Authenticator, to make a bitmask
        return Authenticator::LOGIN | Authenticator::LOGOUT | Authenticator::CHANGE_PASSWORD
        | Authenticator::RESET_PASSWORD | Authenticator::CHECK_PASSWORD | 64 | 128;
    }

    /**
     * @param string $link
     * @return RegistrationHandler
     */
    public function getRegistrationHandler(string $link): RegistrationHandler
    {
        return RegistrationHandler::create($link, $this);
    }
}