<?php

namespace Restruct\NamedLink\Tests\Stub;

use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test-only holder for a NamedLinkField, so the composite field can be exercised as it is
 * actually used: as a $db field on a DataObject.
 *
 * The namespace is deliberately short - a composite field's columns are prefixed with the
 * owner's table name, and a full test namespace runs into database identifier limits.
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'NamedLinkTestObject';

    private static $db = [
        'Title' => 'Varchar',
        'Link' => NamedLinkField::class,
    ];
}
