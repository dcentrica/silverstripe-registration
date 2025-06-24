<?php

namespace Dcentrica\Registration\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordForm;
use SilverStripe\Security\Security;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

/**
 * Handle login requests from MemberLoginForm
 */
class RegistrationHandler extends RequestHandler
{
    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * @var array
     */
    private static $url_handlers = [
        '' => 'register',
    ];

    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'register',
        'RegistrationForm',
        'confirm',
    ];

    /**
     * Link to this handler
     *
     * @var string
     */
    protected $link = null;

    /**
     * @param string $link The URL to recreate this request handler
     * @param MemberAuthenticator $authenticator The authenticator to use
     */
    public function __construct($link, MemberAuthenticator $authenticator)
    {
        $this->link = $link;
        $this->authenticator = $authenticator;
        parent::__construct();
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     *
     * @param null|string $action
     * @return string
     */
    public function Link($action = null): string
    {
        $link = Controller::join_links($this->link, $action);
        $this->extend('updateLink', $link, $action);

        return $link;
    }

    /**
     * URL handler for the log-in screen
     *
     * @return array
     */
    public function register(): array
    {
        if (!Environment::getEnv('REGISTRATION_ENABLED')) {
            return $this->httpError(404, 'Registration is not enabled.');
        }

        return [
            'Form' => $this->registrationForm(),
        ];
    }

    /**
     * Return the MemberLoginForm form
     *
     * @skipUpgrade
     * @return RegistrationForm
     */
    public function registrationForm(): RegistrationForm
    {
        return RegistrationForm::create(
            $this,
            get_class($this->authenticator),
            'RegistrationForm'
        );
    }

    /**
     * Undocumented function
     *
     * @param  string[] $data
     * @return boolean
     */
    private function validPassword(array $data): bool
    {
        if (empty($data['Password']) || empty($data['PasswordConfirm'])) {
            return false;
        }

        $password = (string) $data['Password'];
        $confirm = (string) $data['PasswordConfirm'];

        return ($password === $confirm);
    }

    /**
     * @param array $data
     * @return Member
     */
    private function buildMember(array $data): Member
    {
        [$lhs, $rhs] = array_pad(explode('@', $data['Email'] ?? ''), 2, '');

        return Member::create([
            'FirstName' => preg_replace('#[^\w\d]+#', '', $lhs),
        ]);
    }

    /**
     * Login form handler method
     *
     * This method is called when the user finishes the login flow
     *
     * @param array $data Submitted data
     * @param RegistrationForm $form
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws Exception
     */
    public function doRegister($data, RegistrationForm $form, HTTPRequest $request): HTTPResponse
    {
        $this->extend('beforeRegistration');

        $session = $this->getRequest()->getSession();
        $session->set("FormData.{$form->getName()}.data", $data);

        if(!Email::is_valid_address($data['Email'])) {
            $message = _t(
                'SilverStripe\\Security\\Member.INVALIDEMAIL',
                'Email address is invalid',
            );
        } else if(!$this->validPassword($data)) {
            $message = _t(
                'SilverStripe\\Security\\Member.INVALIDPASSWORD',
                'The passwords don\'t match',
            );
        } else {
            $member = $this->buildMember($data);
            $form->saveInto($member);

            if ($member->write()) {
                // Show optional message on registration form
                if ($message = self::config()->get('registration_completion_message')) {
                    $form->sessionMessage($message, 'good');
                }

                $this->performLogin($member, $data, $request);
                $this->extend('afterSuccessfulRegistration', $member);

                return $this->redirectAfterSuccessfulRegistration();
            }
        }

        $this->extend('afterFailedRegistration', $member);
        $form->sessionMessage($message, 'bad');

        // Failure to register redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getReturnReferer(): string
    {
        return $this->Link();
    }

    /**
     * Login in the user and figure out where to redirect the browser.
     *
     * The $data has this format
     * array(
     *   'AuthenticationMethod' => 'MemberAuthenticator',
     *   'Email' => 'sam@silverstripe.com',
     *   'Password' => '1nitialPassword',
     *   'BackURL' => 'test/link',
     *   [Optional: 'Remember' => 1 ]
     * )
     *
     * @return HTTPResponse
     */
    protected function redirectAfterSuccessfulRegistration(): HTTPResponse
    {
        $session = $this->getRequest()->getSession();
        $session->clear("FormData.{$this->registerForm()->getName()}.data");

        $member = Security::getCurrentUser();

        if ($member?->isPasswordExpired()) {
            return $this->redirectToChangePassword();
        }

        // Absolute redirection URLs may cause spoofing
        if ($backURL = $this->getBackURL()) {
            return $this->redirect($backURL);
        }

        // If a default login dest has been set, redirect to that.
        if ($defaultDest = Security::config()->get('default_registration_dest')) {
            return $this->redirect($defaultDest);
        }

        // Redirect the user to the page where they came from
        return $this->redirectBack();
    }

    /**
     * Try to authenticate the user. Contingent upon userland config.
     *
     * @param Member $member
     * @param array $data Submitted data
     * @param HTTPRequest $request
     * @return null|Member Returns the member object on successful authentication
     *                or NULL on failure.
     */
    public function performLogin($member, $data, HTTPRequest $request): ?Member
    {
        if (!$this->config()->get('login_after_register')) {
            return null;
        }

        $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled'));
        /** @var IdentityStore $identityStore */
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($member, $rememberMe, $request);

        return $member;
    }

    /**
     * Invoked if password is expired and must be changed
     *
     * @return HTTPResponse
     */
    protected function redirectToChangePassword(): HTTPResponse
    {
        $cp = ChangePasswordForm::create($this, 'ChangePasswordForm');
        $cp->sessionMessage(
            _t('SilverStripe\\Security\\Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
            'good'
        );
        $changedPasswordLink = Security::singleton()->Link('changepassword');

        return $this->redirect($this->addBackURLParam($changedPasswordLink));
    }
}
