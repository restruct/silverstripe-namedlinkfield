<?php

namespace Restruct\NamedLink\Tests;

use Restruct\NamedLink\Tests\Stub\TestObject;
use Restruct\SilverStripe\Forms\NamedLinkFormField;
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;

/**
 * Behavioural tests for the NamedLinkField composite database field.
 *
 * Written against the public contract - link modes, exists(), the generated URL per mode and
 * the scaffolded form field - so they keep their meaning across Silverstripe majors.
 *
 * Compatibility: this suite must run on PHPUnit 9 (Silverstripe 5) and 11 (Silverstripe 6).
 * No doc-comment metadata, static data providers only.
 */
class NamedLinkFieldTest extends SapphireTest
{
    protected static $fixture_file = 'NamedLinkFieldTest.yml';

    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    private function linkWith(array $values): NamedLinkField
    {
        $field = NamedLinkField::create('Link');
        foreach ($values as $key => $value) {
            $field->setField($key, $value);
        }

        return $field;
    }

    public function testScaffoldsItsOwnFormField()
    {
        $field = NamedLinkField::create('Link');
        $this->assertInstanceOf(NamedLinkFormField::class, $field->scaffoldFormField());
    }

    public function testFormFieldStillAcceptsAThirdConstructorArgument()
    {
        // Regression: the value argument was dropped from the signature during an upgrade,
        // which is a fatal for any caller that still passes it.
        $field = new NamedLinkFormField('Link', 'A title', 'a value');
        $this->assertSame('Link', $field->getName());
    }

    // ------------------------------------------ scaffolded title, re-adding (#53)

    /**
     * All data fields of a FieldList: getDataFields() on Silverstripe 6, dataFields() on 5.
     * Throws "appears twice" when a field name occurs twice, like a form does when it renders.
     */
    private function dataFieldsOf(FieldList $fields): array
    {
        return method_exists($fields, 'getDataFields') ? $fields->getDataFields() : $fields->dataFields();
    }

    /** Every NamedLinkFormField called $name anywhere in $fields. */
    private function linkFormFieldsNamed(FieldList $fields, string $name): array
    {
        $found = [];
        $walk = function (FieldList $list) use (&$walk, &$found, $name) {
            foreach ($list as $field) {
                if ($field instanceof NamedLinkFormField && $field->getName() === $name) {
                    $found[] = $field;
                }
                if ($field instanceof CompositeField) {
                    $walk($field->getChildren());
                }
            }
        };
        $walk($fields);

        return $found;
    }

    public function testScaffoldedFormFieldUsesTheGivenTitle()
    {
        $field = NamedLinkField::create('Link');
        $this->assertSame('Custom title', $field->scaffoldFormField('Custom title')->Title());
    }

    public function testScaffoldedFormFieldTakesItsLabelFromFieldLabels()
    {
        // Passes before the #53 fix as well: FormScaffolder sets the title from fieldLabel()
        // after scaffolding. Pinned so the README's statement about field_labels stays true.
        TestObject::config()->set('field_labels', ['Link' => 'Label from field_labels']);
        $fields = TestObject::create()->getCMSFields();

        $scaffolded = $this->linkFormFieldsNamed($fields, 'Link');
        $this->assertCount(1, $scaffolded);
        $this->assertSame('Label from field_labels', $scaffolded[0]->Title());
    }

    public function testInsertingTheFieldUnderItsOwnNameReplacesTheScaffoldedOne()
    {
        // The README pattern: add the field yourself, without removeByName() first.
        $fields = TestObject::create()->getCMSFields();
        $mine = NamedLinkFormField::create('Link', 'Mine');
        $fields->insertBefore('Title', $mine);

        $this->assertSame([$mine], $this->linkFormFieldsNamed($fields, 'Link'));
        $this->assertArrayHasKey('LinkTitle', $this->dataFieldsOf($fields), 'no "appears twice"');
    }

    public function testAddingTheFieldToATabUnderItsOwnNameReplacesTheScaffoldedOne()
    {
        $fields = TestObject::create()->getCMSFields();
        $mine = NamedLinkFormField::create('Link', 'Mine');
        $fields->addFieldToTab('Root.Other', $mine);

        $this->assertSame([$mine], $this->linkFormFieldsNamed($fields, 'Link'));
        $this->assertArrayHasKey('LinkTitle', $this->dataFieldsOf($fields), 'no "appears twice"');
    }

    public function testAReAddedFieldStillSavesIntoTheRecord()
    {
        $object = TestObject::create(['Title' => 'Holder']);
        $fields = $object->getCMSFields();
        $fields->insertBefore('Title', NamedLinkFormField::create('Link', 'Mine'));
        $form = Form::create(null, 'Form', $fields, FieldList::create());

        $form->loadDataFrom([
            'Title' => 'Holder',
            'LinkTitle' => 'Saved title',
            'LinkLinkmode' => 'URL',
            'LinkCustomURL' => 'https://example.org/saved',
        ]);
        $form->saveInto($object);
        $object->write();

        $reloaded = TestObject::get()->byID($object->ID);
        $this->assertSame('Saved title', $reloaded->Link->getField('Title'));
        $this->assertSame('URL', $reloaded->Link->getField('Linkmode'));
        $this->assertSame('https://example.org/saved', $reloaded->Link->getField('CustomURL'));
    }

    // ------------------------------------------------------------- link modes

    public function testLegacyLinkmodesAreMappedToCurrentNames()
    {
        $this->assertSame('URL', $this->linkWith(['Linkmode' => 'external'])->getLinkmode());
        $this->assertSame('Page', $this->linkWith(['Linkmode' => 'internal'])->getLinkmode());
    }

    public function testCurrentLinkmodesArePassedThrough()
    {
        foreach (['Page', 'URL', 'File', 'Email', 'Shortcode'] as $mode) {
            $this->assertSame($mode, $this->linkWith(['Linkmode' => $mode])->getLinkmode());
        }
    }

    // ---------------------------------------------------------------- exists()

    public function testExistsIsFalseForAnEmptyLink()
    {
        $this->assertFalse(NamedLinkField::create('Link')->exists());
    }

    public function testExistsIsTrueWhenAnyLinkTargetIsSet()
    {
        $this->assertTrue($this->linkWith(['PageID' => 5])->exists(), 'a page target counts');
        $this->assertTrue($this->linkWith(['FileID' => 5])->exists(), 'a file target counts');
        $this->assertTrue($this->linkWith(['CustomURL' => 'https://example.org'])->exists(), 'a URL counts');
        $this->assertTrue(
            $this->linkWith(['Shortcode' => '[x]', 'Title' => 'x'])->exists(),
            'a shortcode counts only together with a title'
        );
    }

    // -------------------------------------------------------------------- URL

    public function testUrlForACustomUrl()
    {
        $link = $this->linkWith(['Linkmode' => 'URL', 'CustomURL' => 'https://example.org/page']);
        $this->assertSame('https://example.org/page', $link->getURL());
    }

    public function testUrlForALegacyExternalLinkmode()
    {
        $link = $this->linkWith(['Linkmode' => 'external', 'CustomURL' => 'https://example.org/page']);
        $this->assertSame('https://example.org/page', $link->getURL());
    }

    public function testUrlForAnEmailIsAMailtoLink()
    {
        $link = $this->linkWith(['Linkmode' => 'Email', 'CustomURL' => 'someone@example.org']);
        $this->assertSame('mailto:someone@example.org', $link->getURL());
    }

    public function testUrlForAShortcodeIsEmpty()
    {
        $link = $this->linkWith(['Linkmode' => 'Shortcode', 'Shortcode' => '[test]']);
        $this->assertSame('', $link->getURL());
    }

    public function testUrlForAnUntargetedPageIsAnEmptyString()
    {
        // Documenting the contract as it is, not as it arguably should be: Page mode with no
        // page selected yields '' (it builds an empty string and escapes it), whereas File
        // mode with no file selected yields null. Worth unifying one day; changing it now
        // would silently alter what templates receive.
        $this->assertSame('', $this->linkWith(['Linkmode' => 'Page'])->getURL());
    }

    public function testUrlForAnUntargetedFileIsNull()
    {
        $this->assertNull($this->linkWith(['Linkmode' => 'File'])->getURL());
    }

    // ------------------------------------------------------- stored on a record

    public function testCompositeValuesRoundTripThroughTheDatabase()
    {
        $object = TestObject::create();
        $object->Title = 'Holder';
        $object->Link->setField('Linkmode', 'URL');
        $object->Link->setField('CustomURL', 'https://example.org/stored');
        $object->Link->setField('Title', 'Stored link');
        $object->write();

        $reloaded = TestObject::get()->byID($object->ID);

        $this->assertSame('URL', $reloaded->Link->getLinkmode());
        $this->assertSame('https://example.org/stored', $reloaded->Link->getField('CustomURL'));
        $this->assertSame('Stored link', $reloaded->Link->getField('Title'));
        $this->assertTrue($reloaded->Link->exists());
    }
}
