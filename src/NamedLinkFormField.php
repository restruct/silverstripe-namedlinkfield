<?php

namespace Restruct\SilverStripe\Forms;

use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;
use Sheadawson\DependentDropdown\Forms\DependentDropdownField;
use SilverStripe\Assets\File;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\DropdownField;
use SilverStripe\View\Requirements;
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FormField;
use Page;

/**
 * Description of LinkFormField
 *
 * @TODO: check http://localhost/1_documentation/ss-3.3/source-class-HtmlEditorField_Toolbar.html#_LinkForm
 * for inline adding of files etc
 */
class NamedLinkFormField extends FormField
{
    public static $module_dir = ''; // This is initially set in _config.php

    private static $url_handlers = [
        '$Action!/$ID' => '$Action',
    ];

    private static $allowed_actions = [
        'tree', // treedropdown (Page)
        'treefile', // treedropdown (File)
        'load' // dependentdropdown
    ];

    public function __construct($name, $title = null, $value = null)
    {
        // create a reference to NamedLinkField
        $this->namedLinkCompositeField = NamedLinkField::create($name);

        // Create a callable function that returns an array of options for the DependentDropdownField.
        // When the value of the field it depends on changes, this function is called passing the
        // updated value as the first parameter ($val)
        $getanchors = function ($page_id) {
            // Copied from HtmlEditorField_Toolbar::getanchors()
            if ( ( $page = Page::get()->byID($page_id) ) && !empty($page) ) {
                if ( preg_match_all("/\s(name|id)=\"([^\"]+?)\"|\s(name|id)='([^']+?)'/im", $page->Content, $matches) ) {
                    $anchors = array_filter(array_merge($matches[ 2 ], $matches[ 4 ]));

                    return array_combine($anchors, $anchors);
                }
            }

            return [];
        };

        // naming with underscores to prevent values from actually being saved somewhere
        $this->fieldCustomURL = new TextField("{$name}[CustomURL]", '', '', 300);
        $this->fieldShortcode = new TextField("{$name}[Shortcode]", '', '', 300);

        $this->fieldPageID = new TreeDropdownField("{$name}[PageID]", '', SiteTree::class, 'ID', 'MenuTitle');
        $this->fieldPageID->setHasEmptyDefault(true);

        // The DependentDropdownField, setting the source as the callable function
        // and setting the field it depends on to the appropriate field
        $this->fieldPageAnchor = DependentDropdownField::create(
            "{$name}[PageAnchor]",
            'Text-anchor:',
            $getanchors
        )
            ->setEmptyString('Page anchor: (none)')
            ->setDepends($this->fieldPageID)
        ;

        $this->fieldFileID = new TreeDropdownField("{$name}[FileID]", '', File::class, 'ID', 'Name');
        $this->fieldFileID->addExtraClass('filetree');

        $this->fieldTitle = new TextField("{$name}[Title]", 'Title: ', '', 300);
        $this->fieldLinkmode = DropdownField::create("{$name}[Linkmode]", 'Type: ',
            [
                'Page'      => 'Page',
                'URL'       => 'URL',
                'File'      => 'File',
                'Email'     => 'Email',
                'Shortcode' => 'Shortcode',
            ]);
        $this->fieldLinkmode->addExtraClass('LinkmodePicker');
        parent::__construct($name, $title, $value);
    }

    /**
     * Set the container form.
     *
     * This is called automatically when fields are added to forms.
     *
     * @param Form $form
     *
     * @return $this
     */
    public function setForm($form)
    {
        foreach ( $this->namedLinkCompositeField->compositeDatabaseFields() as $field => $spec ) {
            $fieldHandle = 'field' . $field;
            $this->{$fieldHandle}->setForm($form);
        }

        parent::setForm($form);

        return $this;
    }

    /**
     * @return string
     */
    public function Field($properties = [])
    {
        Requirements::javascript('restruct/silverstripe-namedlinkfield:/client/js/LinkFormField.js');
        Requirements::css('restruct/silverstripe-namedlinkfield:/client/css/namedlinkfield.css');

        return "<div class=\"fieldgroup LinkFormField \">" .
            "<div class=\"fieldgroupField LinkFormFieldTitle\">" .
            $this->fieldTitle->SmallFieldHolder() .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldLinkmode\">" .
            $this->fieldLinkmode->SmallFieldHolder() .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldPageID\">" .
            $this->fieldPageID->SmallFieldHolder() .
            '<label class="right">(&uarr; Select Page to link to)</label>' .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldPageAnchor\">" .
            $this->fieldPageAnchor->SmallFieldHolder() .
//				'<label class="right">(&uarr; Anchor on page (optional))</label>' .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldFileID\">" .
            $this->fieldFileID->SmallFieldHolder() .
            '<label class="right">(&uarr; Select File to link to)</label>' .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldCustomURL\">" .
            $this->fieldCustomURL->SmallFieldHolder() .
            '<label class="right">(&uarr; Enter URL/E-mail)</label>' .
            "</div>" .
            "<div class=\"fieldgroupField LinkFormFieldShortcode\">" .
            $this->fieldShortcode->SmallFieldHolder() .
            '<label class="right">(&uarr; Enter Shortcode)</label>' .
            "</div>" .
            "</div>";
    }

    public function setValue($value, $data = null)
    {
        $this->namedLinkCompositeField->setValue($value);
        foreach ( $this->namedLinkCompositeField->compositeDatabaseFields() as $fieldName => $fieldSpec ) {
            $fieldHandle = 'field' . $fieldName;
            if ( is_array($value) && isset($value[ $fieldName ]) ) {
                $this->{$fieldHandle}->setValue($value[ $fieldName ]);
            }
            if ( $value instanceof NamedLinkField && $value->{$fieldName} ) {
                $this->{$fieldHandle}->setValue($value->{$fieldName});
            }
        }
    }

    /**
     * SaveInto checks if set-methods are available and use them instead of setting the values directly. saveInto
     * initiates a new LinkField class object to pass through the values to the setter method.
     */
    public function saveInto(DataObjectInterface $record)
    {
        return $this->namedLinkCompositeField->saveInto($record);
    }

    /**
     * Returns a readonly version of this field.
     */
    public function performReadonlyTransformation()
    {
        return new ReadonlyField($this->Name, $this->Title, $this->Value);
    }

    /**
     * @todo Implement removal of readonly state with $bool=false
     * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
     */
    public function setReadonly($bool)
    {
        parent::setReadonly($bool);

        if ( $bool ) {
            $this->fieldPageID = $this->fieldPageID->performReadonlyTransformation();
            $this->fieldPageAnchor = $this->fieldPageAnchor->performReadonlyTransformation();
            $this->fieldCustomURL = $this->fieldCustomURL->performReadonlyTransformation();
            $this->fieldShortcode = $this->fieldShortcode->performReadonlyTransformation();
            $this->fieldFileID = $this->fieldFileID->performReadonlyTransformation();
            $this->fieldTitle = $this->fieldTitle->performReadonlyTransformation();
            $this->fieldLinkmode = $this->fieldLinkmode->performReadonlyTransformation();
        }
    }

    //
    // Various field-ajax helpers
    //

    // pass the request on to TreeDropdown
    public function treefile($request)
    {
        return $this->fieldFileID->tree($request);
    }

    // pass the request on to TreeDropdown
    public function tree($request)
    {
        return $this->fieldPageID->tree($request);
    }

    // pass the request on to DependentDropdown
    public function load($request)
    {
        return $this->fieldPageAnchor->load($request);
    }

    public function getAnchors($page_id)
    {
        // Copied from HtmlEditorField_Toolbar::getanchors()
        if ( ( $page = Page::get()->byID($page_id) ) && !empty($page) ) {
            // Similar to the regex found in HtmlEditorField.js / getAnchors method.
            if ( preg_match_all("/\s(name|id)=\"([^\"]+?)\"|\s(name|id)='([^']+?)'/im", $page->Content, $matches) ) {
                return array_filter(array_merge($matches[ 2 ], $matches[ 4 ]));
            }
        }
    }


}

