<?php

namespace Restruct\NamedLink\Tests;

use Restruct\NamedLink\Tests\Stub\TestObject;
use Restruct\SilverStripe\Forms\NamedLinkFormField;
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use SilverStripe\Dev\SapphireTest;

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
