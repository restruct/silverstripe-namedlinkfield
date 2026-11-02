<?php

namespace Restruct\SilverStripe\Forms;

use Override;
use Restruct\SilverStripe\NamedLinkField\NamedLinkCtrl;
use Sheadawson\DependentDropdown\Forms\DependentDropdownField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Assets\File;
use SilverStripe\Forms\DropdownField;
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use SilverStripe\Forms\FormField;

/**
 * Description of LinkFormField
 *
 * @TODO: make extend from CompositeField instead of FormField (as thats what this field actually is...)
 */
class NamedLinkFormField extends FieldGroup
{
//    private static $url_handlers = [
//        '$Action!/$ID' => '$Action',
//    ];

//    private static $allowed_actions = [
////        'tree', // treedropdown (Page)
////        'treefile', // treedropdown (File)
//        'load' // dependentdropdown
//    ];


    ////// REACT attempt (based on https://blog.jar.nz/posts/new-field-in-silverstripe)

    // custom since we don't have one specific data type
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

//    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_HTML;

    // name of the react component
    protected $schemaComponent = 'NamedLinkFormField';

//    /**
//     * Gets the defaults for $schemaState.
//     * The keys defined here are immutable, meaning undefined keys passed to {@link setSchemaState()} are ignored.
//     * Instead the `data` array should be used to pass around ad hoc data.
//     * Includes validation data if the field is associated to a {@link Form},
//     * and {@link Form->validate()} has been called.
//     *
//     * @return array $state = [
//    'name' => $this->getName(),
//    'id' => $this->ID(),
//    'value' => $this->Value(),
//    'message' => $this->getSchemaMessage(),
//    'data' => [],
//    ];
//     */
//    public function getSchemaStateDefaults()
//    {
//        $state = parent::getSchemaStateDefaults();
//
////        $state['fieldTitle'] = $this->fieldTitle->getSchemaState();
////        $state['fieldLinkmode'] = $this->fieldLinkmode->getSchemaState();
////        $state['fieldCustomURL'] = $this->fieldCustomURL->getSchemaState();
////        $state['fieldShortcode'] = $this->fieldShortcode->getSchemaState();
////        $state['fieldPageID'] = $this->fieldPageID->getSchemaState();
////        $state['fieldPageAnchor'] = $this->fieldPageAnchor->getSchemaState();
////        $state['fieldFileID'] = $this->fieldFileID->getSchemaState();
//
////        $state['fieldHTML'] = $this->Field()->Value;
//
//        return $state;
//    }

    //////

    // $value is accepted and ignored, as it always was: dropping it from the signature is a
    // breaking change for any caller that passes three arguments.
    public function __construct($name, $title = null, $value = null)
    {
        // create a reference to NamedLinkField
        $this->namedLinkCompositeField = NamedLinkField::create($name);

        // naming with underscores to prevent values from actually being saved somewhere
        $this->fieldCustomURL = new TextField($name . 'CustomURL', '', '', 300);
        $this->fieldShortcode = new TextField($name . 'Shortcode', '', '', 300);

        $this->fieldPageID = new TreeDropdownField($name . 'PageID', '', SiteTree::class, 'ID', 'MenuTitle');
        $this->fieldPageID->setHasEmptyDefault(true);

        // The DependentDropdownField, setting the source as the callable function
        // and setting the field it depends on to the appropriate field
        $this->fieldPageAnchor = DependentDropdownField::create(
            $name . 'PageAnchor',
            'Text-anchor:',
//            $getanchors
            fn($page_id) => NamedLinkCtrl::get_page_anchors($page_id)
        )
            ->setEmptyString('Page anchor: (none)')
            ->setDepends($this->fieldPageID)
        ;

        $this->fieldFileID = new TreeDropdownField($name . 'FileID', '', File::class, 'ID', 'Title');
        $this->fieldFileID->setTitleField('Filename'); // Name = file.jpg / Filename = path/to/file.jpg
        $this->fieldFileID->addExtraClass('filetree');

        $this->fieldTitle = new TextField($name . 'Title', 'Title: ', '', 300);
        $this->fieldLinkmode = DropdownField::create($name . 'Linkmode', 'Type: ',
            [
                'Page'      => 'Page',
                'URL'       => 'URL',
                'File'      => 'File',
                'Email'     => 'Email',
                'Shortcode' => 'Shortcode',
            ]);
        $this->fieldLinkmode->addExtraClass('LinkmodePicker');

        $fields = [
            $this->fieldTitle,
            $this->fieldLinkmode,
            $this->fieldCustomURL,
            $this->fieldFileID,
            $this->fieldPageID,
            $this->fieldPageAnchor,
            $this->fieldShortcode,
        ];
        parent::__construct($fields);

        $this
            ->setName($name)
            ->setValue('')
            ->setTitle($title ?: FormField::name_to_label($name));
    }

    /**
     * Adding this field to a FieldList replaces an existing NamedLinkFormField of the same name
     * anywhere in that form, as FieldList does for a core data field (#53).
     *
     * FieldList::onBeforeInsert() only removes a same-named field when it reports hasData(), and
     * a FieldGroup does not (making it report data would change how the form loads and saves it).
     * Without this, re-adding the field under the scaffolded field's name kept both, and the form
     * threw "a field called '...Title' appears twice". Every FieldList insert (push, unshift,
     * insertBefore/After, addFieldToTab, replaceField) calls this either before the item is in
     * the list or right before it returns, so removing an item here never disturbs its loop.
     *
     * @param FieldList|null $containerFieldList
     * @return $this
     */
    #[Override]
    public function setContainerFieldList($containerFieldList)
    {
        if ($containerFieldList instanceof FieldList && $this->getName()) {
            $this->removeSameNamedLinkFields($containerFieldList->rootFieldList());
        }

        return parent::setContainerFieldList($containerFieldList);
    }

    /**
     * Remove every other NamedLinkFormField called like this one from $list and its composites.
     * Only this class is matched: any other same-named field is left to FieldList itself.
     */
    private function removeSameNamedLinkFields(FieldList $list): void
    {
        $removed = false;
        foreach ($list->toArray() as $field) {
            if ($field !== $this && $field instanceof self && $field->getName() === $this->getName()) {
                $list->remove($field);
                $field->setContainerFieldList(null);
                $removed = true;
            } elseif ($field instanceof CompositeField) {
                $this->removeSameNamedLinkFields($field->getChildren());
            }
        }

        if ($removed) {
            # ArrayList::remove() leaves FieldList's data-field caches alone. removeByName()
            # flushes every list it walks; with $dataFieldOnly it removes only a data field of
            # this name, which FieldList::onBeforeInsert() has removed already.
            $list->rootFieldList()->removeByName($this->getName(), true);
        }
    }

    /**
     * @return string
     */
    #[Override]
    public function Field($properties = [])
    {
        return $this->renderWith('NamedLinkFormField');
    }

    #[Override]
    public function FieldHolder($properties = [])
    {
        // Admin (CMS-theme) formfield templates are here:
        // vendor/silverstripe/admin/themes/cms-forms/templates/SilverStripe/Forms/FormField_holder.ss
        $context = $this;
        $this->extend('onBeforeRenderHolder', $context, $properties);
        if (count($properties) > 0) {
            $context = $this->customise($properties);
        }

        return $context->renderWith(FormField::class.'_holder');
    }

//    /**
//     * SaveInto checks if set-methods are available and use them instead of setting the values directly. saveInto
//     * initiates a new LinkField class object to pass through the values to the setter method.
//     */
//    public function saveInto(DataObjectInterface $record)
//    {
//        return $this->namedLinkCompositeField->saveInto($record);
//    }

//    /**
//     * Returns a readonly version of this field.
//     */
//    public function performReadonlyTransformation()
//    {
//        return new ReadonlyField($this->Name, $this->Title, $this->Value);
//    }

//    /**
//     * @todo Implement removal of readonly state with $bool=false
//     * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
//     */
//    public function setReadonly($bool)
//    {
//        parent::setReadonly($bool);
//
//        if ( $bool ) {
//            $this->fieldPageID = $this->fieldPageID->performReadonlyTransformation();
//            $this->fieldPageAnchor = $this->fieldPageAnchor->performReadonlyTransformation();
//            $this->fieldCustomURL = $this->fieldCustomURL->performReadonlyTransformation();
//            $this->fieldShortcode = $this->fieldShortcode->performReadonlyTransformation();
//            $this->fieldFileID = $this->fieldFileID->performReadonlyTransformation();
//            $this->fieldTitle = $this->fieldTitle->performReadonlyTransformation();
//            $this->fieldLinkmode = $this->fieldLinkmode->performReadonlyTransformation();
//        }
//    }

}

