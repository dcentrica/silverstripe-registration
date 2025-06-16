<?php

namespace Dcentrica\Registration\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBBoolean;

/**
 * Augments a Member record with a field which flags to userland systems
 * that users were created by means of registration.
 */
class MemberRegistrationExtension extends Extension
{
    /**
     * @var string[]
     */
    private static $db = [
        'CreatedByRegistration' => DBBoolean::class,
    ];

    /**
     * @var array<string, string>
     */
    private static $defaults = [
        'CreatedByRegistration' => 1,
    ];
}