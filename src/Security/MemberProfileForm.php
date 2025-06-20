<?php


namespace Dcentrica\Profile\Security;

use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\LoginForm as BaseLoginForm;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;

/**
 * Log-in form for the "member" authentication method.
 *
 * Available extension points:
 * - "authenticationFailed": Called when login was not successful.
 *    Arguments: $data containing the form submission
 * - "forgotPassword": Called before forgot password logic kicks in,
 *    allowing extensions to "veto" execution by returning FALSE.
 *    Arguments: $member containing the detected Member record
 */
class MemberProfileForm extends BaseLoginForm
{

    /**
     * Required fields for validation
     *
     * @config
     * @var array
     */
    private static $required_fields = [
        'Email',
    ];
    /**
     * This field is used in the "You are logged in as %s" message
     * @var string
     */
    public $loggedInAsField = 'FirstName';

    /**
     * Constructor
     *
     * @skipUpgrade
     * @param RequestHandler $controller The parent controller, necessary to
     *                               create the appropriate form action tag.
     * @param string $authenticatorClass Authenticator for this LoginForm
     * @param string $name The method on the controller that will return this
     *                     form object.
     * @param FieldList $fields All of the fields in the form - a
     *                                   {@link FieldList} of {@link FormField}
     *                                   objects.
     * @param FieldList|FormAction $actions All of the action buttons in the
     *                                     form - a {@link FieldList} of
     *                                     {@link FormAction} objects
     * @param bool $checkCurrentUser If set to TRUE, it will be checked if a
     *                               the user is currently logged in, and if
     *                               so, only a logout button will be rendered
     */
    public function __construct(
        $controller,
        $authenticatorClass,
        $name,
        $fields = null,
        $actions = null,
        $checkCurrentUser = true
    )
    {
        $this->setController($controller);
        $this->authenticator_class = $authenticatorClass;

        $customCSS = project() . '/css/member_login.css';
        if (Director::fileExists($customCSS)) {
            Requirements::css($customCSS);
        }

//        if ($checkCurrentUser && Security::getCurrentUser()) {
//            // @todo find a more elegant way to handle this
//            $logoutAction = Security::logout_url();
//            $fields = FieldList::create(
//                HiddenField::create('AuthenticationMethod', null, $this->authenticator_class, $this)
//            );
//            $actions = FieldList::create(
//                FormAction::create('logout', _t(
//                    'SilverStripe\\Security\\Member.BUTTONLOGINOTHER',
//                    'Log in as someone else'
//                ))
//            );
//        }
        if (!$fields) {
            $fields = $this->getFormFields()->removeByName('Groups');
        }
        if (!$actions) {
            $actions = $this->getFormActions();
        }

        // Reduce attack surface by enforcing POST requests
        $this->setFormMethod('POST', true);

        parent::__construct($controller, $name, $fields, $actions);

        if (isset($logoutAction)) {
            $this->setFormAction($logoutAction);
        }
        $this->setValidator(RequiredFields::create(self::config()->get('required_fields')));
    }

    /**
     * Build the FieldList for the login form
     *
     * @skipUpgrade
     * @return FieldList
     */
    protected function getFormFields()
    {
        $request = $this->getRequest();
        if ($request->getVar('BackURL')) {
            $backURL = $request->getVar('BackURL');
        } else {
            $backURL = $request->getSession()->get('BackURL');
        }

        $fields = Member::singleton()->getMemberFormFields();

        if (isset($backURL)) {
            $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));
        }

        return $fields;
    }

    /**
     * Build default login form action FieldList
     *
     * @return FieldList
     */
    protected function getFormActions()
    {
        $actions = FieldList::create(
            FormAction::create('doSave', _t('SilverStripe\\Security\\Member.BUTTONSAVE', "Save"))
        );

        return $actions;
    }


    public function restoreFormState()
    {
        parent::restoreFormState();

        $session = $this->getSession();
        $forceMessage = $session->get('MemberProfileForm.force_message');
//        if (($member = Security::getCurrentUser()) && !$forceMessage) {
//            $message = _t(
//                'SilverStripe\\Security\\Member.LOGGEDINAS',
//                "You're logged in as {name}.",
//                array('name' => $member->{$this->loggedInAsField})
//            );
//            $this->setMessage($message, ValidationResult::TYPE_INFO);
//        }

        // Reset forced message
        if ($forceMessage) {
            $session->set('MemberProfileForm.force_message', false);
        }

        return $this;
    }

    /**
     * The name of this login form, to display in the frontend
     * Replaces Authenticator::get_name()
     *
     * @return string
     */
    public function getAuthenticatorName()
    {
        return _t(self::class . '.AUTHENTICATORNAME', "E-mail & Password");
    }
}
