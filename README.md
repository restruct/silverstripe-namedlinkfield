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

## This branch

`v2` is the maintenance line: Silverstripe 4 and 5, module versions `2.1.x`. It receives security and
bug fixes until Silverstripe 5 reaches end of life in April 2027. New work goes on `main` (`3.x`,
Silverstripe 6).

Silverstripe 4 reached end of life in April 2025. It is still allowed by `composer.json` so existing
installs keep working, but it is **not tested** - CI runs Silverstripe 5 only.

## Running the tests

The module needs a host Silverstripe project. Require it there through a Composer **path repository
with `symlink: true`** - `/tests` is `export-ignore`, so a dist or mirrored install has no tests -
add its test namespace to the host's `autoload-dev`, then (the path must precede `flush=1`):

```bash
vendor/bin/phpunit vendor/restruct/silverstripe-namedlinkfield/tests flush=1
```
