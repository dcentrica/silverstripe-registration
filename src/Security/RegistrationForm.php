<?php


namespace Dcentrica\Registration\Security;

use Eluceo\iCal\Domain\ValueObject\Member;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\LoginForm;
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
class RegistrationForm extends LoginForm
{
    /**
     * Required fields for validation
     *
     * @config
     * @var string[]
     */
    private static $required_fields = [
        'Email',
        'Password',
        'PasswordConfirm',
    ];

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
    )
    {
        $this->setController($controller);
        $this->authenticator_class = $authenticatorClass;
        $customCSS = project() . '/css/member_login.css';

        if (Director::fileExists($customCSS)) {
            Requirements::css($customCSS);
        }

        if (!$fields) {
            $fields = $this->getFormFields();
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

        $data = $this
            ->getController()
            ->getRequest()
            ->getSession()
            ->get("FormData.{$this->getName()}.data");

        if ($data) {
            $this->loadDataFrom($data);
        }

        $this->setValidator(RequiredFields::create(self::config()->get('required_fields')));
    }

    /**
     * Build the FieldList for the login form
     *
     * @return FieldList
     */
    protected function getFormFields(): FieldList
    {
        $request = $this->getRequest();

        if ($request->getVar('BackURL')) {
            $backURL = $request->getVar('BackURL');
        } else {
            $backURL = $request->getSession()->get('BackURL');
        }

        $fields = FieldList::create([
            LiteralField::create(
                'DoRegister',
                '<p class="message good">' . _t(sprintf('%s.REGFORMINTROSTART', __CLASS__), 'Register your details below.') . '</p>'
            ),
            EmailField::create(
                'Email',
                _t(sprintf('%s.REGFIELDEMAIL', Member::class), 'Email')
            )
                ->setAttribute('autocomplete', 'off')
                ->setAttribute('autofocus', 'true'),
            PasswordField::create(
                'Password',
                _t(sprintf('%s.REGFIELDPASSWD', Member::class), 'Password')
            )->setAttribute('autocomplete', 'off'),
            PasswordField::create(
                'PasswordConfirm',
                _t(sprintf('%s.REGFIELDPASSWDCNFM', Member::class), 'Confirm Password')
            )->setAttribute('autocomplete', 'off'),
            LiteralField::create(
                'DoLogin',
                _t(
                    sprintf('%s.DOLOGINFIELD', Member::class),
                    '<p>Already have an account? <a href="/Security/login">Login Here</a>.</p>'
            )),
        ]);

        $this->extend('updateRegistrationFields', $fields);

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
    protected function getFormActions(): FieldList
    {
        $actions = FieldList::create(
            FormAction::create('doRegister', _t('SilverStripe\\Security\\Member.BUTTONREGISTER', "Register"))
        );

        $this->extend('updateRegistrationActions', $actions);

        return $actions;
    }

    /**
     * The name of this form, to display in the frontend
     * Replaces Authenticator::get_name()
     *
     * @return string
     */
    public function getAuthenticatorName(): string
    {
        return _t(self::class . '.AUTHENTICATORNAME', "Register");
    }
}
