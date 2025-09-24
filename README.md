# A link field (with title) for SilverStripe

A single inline link field which allows users to set a title/name for the link,
and select
 - a page + optional text-anchor from the site tree
 - a file from the assets dir
 - an e-mail address (mailto link)
 - define a custom URL to link to
 - or enter a shortcode

<img width="1069" height="114" alt="namedlinkfield" src="https://github.com/user-attachments/assets/4ee67dd7-9e0d-40d9-b2b0-bfb508275db2" />

## Usage
```php
use Restruct\SilverStripe\ORM\FieldType\NamedLinkField;
use Restruct\SilverStripe\Forms\NamedLinkFormField;

...

private static $db = array(
    'NextAction' => NamedLinkField::class,
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

## TODO
* Check source-class-HtmlEditorField_Toolbar.html#_LinkForm for inline uploading of files etc
* Make translatable/i18n
