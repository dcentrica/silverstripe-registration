<?php

namespace Dcentrica\Registration\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Security\Authenticator;
use SilverStripe\View\TemplateGlobalProvider;
use SilverStripe\Security\Security as CoreSecurity;

class Security extends CoreSecurity implements TemplateGlobalProvider
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
     * The default profile URL
     *
     * @config
     *
     * @var string $profile_url
     */
    private static $profile_url = 'Security/profile';

    /**
     * Undocumented variable
     *
     * @var string[]
     */
    private static $allowed_actions = [
        'register',
        'profile',
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
                $auth = $auth->getRegisterHandler(Controller::join_links($link, $name));
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
     * Show the "register" page
     *
     * For multiple authenticators, Security_MultiAuthenticatorLogin is used.
     * See getTemplatesFor and getIncludeTemplate for how to override template logic
     *
     * @param HTTPRequest $request
     * @param int $service
     * @return HTTPResponse|string Returns the "profile" page as HTML code.
     * @throws HTTPResponse_Exception
     */
    public function profile(HTTPRequest $request, $service = 64)
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
        $authName = null;

        $handlers = $this->getServiceAuthenticatorsFromRequest($service, $request);

        $link = $this->Link('profile');
        array_walk(
            $handlers,
            function (Authenticator &$auth, $name) use ($link) {
                $auth = $auth->getProfileHandler(Controller::join_links($link, $name));
            }
        );

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t(__CLASS__ . '.REGISTER', 'Profile'),
            $this->getTemplatesFor('profile'),
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

    /**
     * Get the URL of the profile page.
     *
     * To update the profile url use the "Security.profile_url" config setting.
     *
     * @return string
     */
    public static function profile_url(): string
    {
        return Controller::join_links(Director::baseURL(), self::config()->get('profile_url'));
    }

    /**
     * @return array
     */
    public static function get_template_global_variables(): array
    {
        $tgv = parent::get_template_global_variables();
        $tgv['RegisterURL'] = 'register_url';
        $tgv['ProfileURL'] = 'profile_url';
        return $tgv;
    }
}