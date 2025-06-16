<?php

namespace Dcentrica\Registration\Security;

use SilverStripe\Security\Authenticator;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator as CoreAuthenticator;
use Dcentrica\Registration\Security\RegisterHandler;
use Dcentrica\Registration\Security\ProfileHandler;

class MemberAuthenticator extends CoreAuthenticator
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
     * @return RegisterHandler
     */
    public function getRegisterHandler(string $link): RegisterHandler
    {
        return RegisterHandler::create($link, $this);
    }

    /**
     * @param string $link
     * @return ProfileHandler
     */
    public function getProfileHandler(string $link): ProfileHandler
    {
        return ProfileHandler::create($link, $this);
    }
}