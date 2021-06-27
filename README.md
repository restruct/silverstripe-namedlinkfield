# A link field (with title) for SilverStripe

A single inline link field which allows users to set a title/name for the link,
and select
 - a page + optional text-anchor from the site tree
 - a file from the assets dir
 - an e-mail address (mailto link)
 - define a custom URL to link to
 - or enter a shortcode

## Usage
```php
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use Restruct\SilverStripe\Forms\NamedLinkFormField;

...

private static $db = array(
    'NextAction' => 'NamedLinkField',
);

...

public function getCMSFields()
{
    $fields = parent::getCMSFields();

    $fields->insertBefore(
        "Content",
        NamedLinkFormField::create('ActionButton')
    );

    return $fields;
}
```

#Fix
Use Text fields instead of Varchars (workaround 'Row Size too large' MySQL error)
Restruct\SilverStripe\ORM\FieldType\NamedLinkField:
composite_db:
```yml
'PageID': 'Text'
'PageAnchor': 'Text'
'FileID': 'Text'
'CustomURL': 'Text'
'Shortcode': 'Text'
'Title': 'Text'
```


## Requirements
* SilverStripe CMS 4.0 or greater
* Dependentdropdownfield (for in-page text-anchor selection)

## Screenshots

![](docs/assets/namedlinkfield.png)

Pick page & text-anchor, file, e-mail or (external) URL. Fields will be auto-updated.

## TODO
* Check source-class-HtmlEditorField_Toolbar.html#_LinkForm for inline uploading of files etc
* Make translatable/i18n
