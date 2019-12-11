<?php

namespace Registration\Security\MemberAuthenticator;

use Profile\Security\MemberAuthenticator\MemberProfileForm;
use Registration\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;

/**
 * Handle login requests from MemberLoginForm
 */
class ProfileHandler extends RequestHandler
{
    /**
     * @var array
     */
    private static $url_handlers = [
        '' => 'profile',
    ];
    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'profile',
        'ProfileForm',
        'confirm',
    ];
    /**
     * @var Authenticator
     */
    protected $authenticator;
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
     * URL handler for the log-in screen
     *
     * @return array
     */
    public function profile()
    {
        if (!Security::getCurrentUser()) return $this->redirect(Security::login_url());
        return [
            'Form' => $this->profileForm(),
        ];
    }

    /**
     * Return the MemberLoginForm form
     *
     * @skipUpgrade
     * @return MemberProfileForm
     */
    public function profileForm()
    {
        $form = MemberProfileForm::create(
            $this,
            get_class($this->authenticator),
            'ProfileForm'
        );
        $validator = $form->getValidator();
        $form
            ->setSessionValidationResult($validator->getResult(), false)
            ->loadDataFrom(Security::getCurrentUser() ?? [])
            ->setEncType(Form::ENC_TYPE_MULTIPART);
        return $form;
    }

    /**
     * Login form handler method
     *
     * This method is called when the user finishes the login flow
     *
     * @param array $data Submitted data
     * @param MemberProfileForm $form
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function doSave($data, MemberProfileForm $form, HTTPRequest $request)
    {
        $failureMessage = null;

        $this->extend('beforeSaveProfile');
        // Successful login
        $member = Security::getCurrentUser();
        if ($data['ProfileImage']['error'] == 4) unset($data['ProfileImage']);
        $form->saveInto($member, array_keys($data));
//        var_dump(json_encode($data));
//        die;
        /** @var ValidationResult $result */
        $result = $member->validate();
        if ($member->write()) {
            // Allow operations on the member after successful login
            $this->extend('afterSaveProfile', $member);

//            return $this->redirectAfterSuccessfulRegistration();
        }

        $this->extend('failedSaveProfile');

        $message = implode("; ", array_map(
            function ($message) {
                return $message['message'];
            },
            $result->getMessages()
        ));

        $form->sessionMessage($message, 'bad');

        // Failed login

        /** @skipUpgrade */
        if (array_key_exists('Email', $data)) {
            $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled') === true);
            $this
                ->getRequest()
                ->getSession()
                ->set('SessionForms.MemberProfileForm.Email', $data['Email'])
                ->set('SessionForms.MemberProfileForm.Remember', $rememberMe);
        }


        // Fail to login redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }

    public function getReturnReferer()
    {
        return $this->Link();
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     *
     * @param null|string $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = Controller::join_links($this->link, $action);
        $this->extend('updateLink', $link, $action);
        return $link;
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
    protected function redirectAfterSuccessfulRegistration()
    {
        $this
            ->getRequest()
            ->getSession()
            ->clear('SessionForms.MemberLoginForm.Email')
            ->clear('SessionForms.MemberLoginForm.Remember');

        $member = Security::getCurrentUser();
        if ($member->isPasswordExpired()) {
            return $this->redirectToChangePassword();
        }

        // Absolute redirection URLs may cause spoofing
        $backURL = $this->getBackURL();
        if ($backURL) {
            return $this->redirect($backURL);
        }

        // If a default login dest has been set, redirect to that.
        $defaultLoginDest = Security::config()->get('default_login_dest');
        if ($defaultLoginDest) {
            return $this->redirect($defaultLoginDest);
        }

        // Redirect the user to the page where they came from
        if ($member) {
            // Welcome message
            $message = _t(
                'SilverStripe\\Security\\Member.WELCOMEBACK',
                'Welcome Back, {firstname}',
                ['firstname' => $member->FirstName]
            );
            Security::singleton()->setSessionMessage($message, ValidationResult::TYPE_GOOD);
        }

        // Redirect back
        return $this->redirectBack();
    }
}
