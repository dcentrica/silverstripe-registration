<?php

namespace Dcentrica\Registration\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\Security;

class RegistrationSecurity extends Security
{
    /**
     * The default registration URL
     *
     * @config
     *
     * @var string $register_url
     */
    private static $register_url = 'Security/register';

    /**
     *
     * @var array<string, string>
     */
    private static $allowed_actions = [
        'register',
    ];

    /**
     * Show the "register" page
     *
     * For multiple authenticators, Security_MultiAuthenticatorLogin is used.
     * See getTemplatesFor and getIncludeTemplate for how to override template logic
     *
     * @param HTTPRequest $request
     * @param int $service
     * @return HTTPResponse|string Returns the "register" page as HTML code.
     * @throws HTTPResponse_Exception
     */
    public function register(HTTPRequest $request, $service = 64)
    {
        if ($request) {
            $this->setRequest($request);
        } elseif ($this->getRequest()) {
            $request = $this->getRequest();
        } else {
            throw new HTTPResponse_Exception("No request available", 500);
        }

        // Check pre-login process
        if ($response = $this->preLogin()) {
            return $response;
        }

        $handlers = $this->getServiceAuthenticatorsFromRequest($service, $request);

        $link = $this->Link('register');
        array_walk(
            $handlers,
            function (Authenticator &$auth, $name) use ($link) {
                $auth = $auth->getRegistrationHandler(Controller::join_links($link, $name));
            }
        );

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t(__CLASS__ . '.REGISTER', 'Register'),
            $this->getTemplatesFor('register'),
            [$this, 'aggregateTabbedForms']
        );
    }

    /**
     * Get the URL of the register page.
     *
     * To update the register url use the "Security.register_url" config setting.
     *
     * @return string
     */
    public static function register_url(): string
    {
        return Controller::join_links(Director::baseURL(), self::config()->get('register_url'));
    }
}