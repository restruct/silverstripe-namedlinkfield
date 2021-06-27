<?php

namespace Restruct\SilverStripe\ORM\FieldType;

use SilverStripe\Dev\Debug;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBEnum;
use Restruct\SilverStripe\Forms\NamedLinkFormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;


/**
 * A link field which will store a link in the database.
 *
 */
class NamedLinkField extends DBComposite
{

    /**
     * @var int The PageID for this link.
     */
    protected $page_id;

    /**
     * @var string A custom URL for this link (or e-mail address)
     */
    protected $custom_url;

    /**
     * @var string A shortcode
     */
    protected $shortcode;

    /**
     * @var int The FileID for this link.
     */
    protected $file_id;

    /**
     * @var string A custom URL for this link
     */
    protected $page_anchor;

    /**
     * @var string A title for this link.
     */
    protected $title;

    /**
     * @var string A LinkType for this link
     */
    protected $LinkType;

    /**
     * @var boolean Is this record changed or not?
     */
    protected $isChanged = false;


    /**
     * Similiar to {@link DataObject::$db},
     * holds an array of composite field names.
     * Don't include the fields "main name",
     * it will be prefixed in {@link requireField()}.
     *
     * @var array $composite_db
     */
    private static $composite_db = [
        //'PageID' => 'Int',
        'PageID'     => 'Text', // seems only way to prevent "Column 'LinkPageID' cannot be null" error
        'PageAnchor' => 'Text',
        'FileID'     => 'Text',
        'CustomURL'  => 'Text',
        'Shortcode'  => 'Text',
        'Title'      => 'Text',
        'Linkmode'   => "Enum(array('Page','URL','File','Email','Shortcode'))",
    ];

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     *
     * @return NamedLinkFormField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return NamedLinkFormField::create($this->name);
    }

    public function saveInto($dataObject)
    {
        foreach ( $this->compositeDatabaseFields() as $field => $spec ) {
            // Save into record
            $key = $this->getName() . $field;
            $dataObject->setField($key, $this->getField($field));
        }
    }

    /**
     * Determines if any of the properties in this field have a value,
     * meaning at least one of them is not NULL.
     *
     * @return boolean
     */
    public function exists()
    {
        return ( $this->page_id > 0 || $this->file_id > 0 || $this->custom_url !== null
            || ( $this->shortcode !== null && $this->title !== null ) );
    }

    public function getLinkmode()
    {
        // legacy Linkmodes
        $linkmode = $this->getField('Linkmode');
        if ( $linkmode === 'external' ) return 'URL';
        if ( $linkmode === 'internal' ) return 'Page';

        return $linkmode;
    }

    public function Page()
    {
        $pageID = $this->getField('PageID');
        if ( $pageID && $page = DataObject::get_by_id('Page', $pageID) ) {
            return $page;
        }

        return null;
    }

    public function File()
    {
        $fileID = $this->getField('FileID');

        if ( $fileID && $file = DataObject::get_by_id(File::class, $fileID) ) {
            return $file;
        }

        return null;
    }

    public function ShortcodeOutput()
    {
        if ( $this->getField('Linkmode') === 'Shortcode' && $sc = $this->getField('Shortcode') ) {
            return ShortcodeParser::get_active()->parse($sc);
        }

        return null;
    }

    public function getEmail()
    {
        if ( $this->getField('Linkmode') === 'Email' && filter_var($this->getField('CustomURL'), FILTER_VALIDATE_EMAIL) ) {
            return "mailto:" . $this->getField('CustomURL');
        }

        return null;
    }

    public function getURL()
    {
        switch ( $this->getField('Linkmode') ) {

            case "external": // legacy
            case "URL" :
                $url = $this->getField('CustomURL');

                return Convert::raw2htmlatt($url);

            case "Shortcode":
                // Should probably be handled differently from template (<% if IsShortcode ...)
                return '';

            case "internal": // legacy
            case "Page" :
                $url = '';
                if ( $page = $this->Page() ) $url = $page->AbsoluteLink();
                if ( $anchor = $this->getField('PageAnchor') ) $url .= "#$anchor";

                return Convert::raw2htmlatt($url);

            case 'Email' :
                return Convert::raw2htmlatt($this->getEmail());

            default : // File
                if ( $file = $this->File() ) return $file->AbsoluteLink();

        }

    }
}
