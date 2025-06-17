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
use SilverStripe\Security\LoginForm as BaseLoginForm;
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
class MemberRegistrationForm extends BaseLoginForm
{

    /**
     * This field is used in the "You are logged in as %s" message
     * @var string
     */
    public $loggedInAsField = 'FirstName';

    /**
     * Required fields for validation
     *
     * @config
     * @var array
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
                'Intro',
                _t(
                    sprintf('%s.DOREGFIELD', Member::class),
                    '<p>Enter your details to register.</p>'
            )),
            EmailField::create(
                'Email',
                _t(sprintf('%s.REGFIELDEMAIL', Member::class), 'Email')
            ),
            PasswordField::create(
                'Password',
                _t(sprintf('%s.REGFIELDPASSWD', Member::class), 'Password')
            ),
            PasswordField::create(
                'PasswordConfirm',
                _t(sprintf('%s.REGFIELDPASSWDCNFM', Member::class), 'Confirm Password')
            ),
            LiteralField::create(
                'DoLogin',
                _t(
                    sprintf('%s.DOLOGINFIELD', Member::class),
                    '<p>Already have an account? <a href="/Security/login">login</a> instead.</p>'
            )),
        ]);

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
