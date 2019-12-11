<?php

namespace Registration\Security\MemberAuthenticator;

use SilverStripe\Security\Authenticator;

class MemberAuthenticator extends \SilverStripe\Security\MemberAuthenticator\MemberAuthenticator
{
    public function supportedServices()
    {
        // Bitwise-OR of all the supported services in this Authenticator, to make a bitmask
        return Authenticator::LOGIN | Authenticator::LOGOUT | Authenticator::CHANGE_PASSWORD
        | Authenticator::RESET_PASSWORD | Authenticator::CHECK_PASSWORD | 64 | 128;
    }


    /**
     * @param string $link
     * @return RegisterHandler
     */
    public function getRegisterHandler($link)
    {
        return RegisterHandler::create($link, $this);
    }


    /**
     * @param string $link
     * @return ProfileHandler
     */
    public function getProfileHandler($link)
    {
        return ProfileHandler::create($link, $this);
    }
}