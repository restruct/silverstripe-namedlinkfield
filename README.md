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

## Version compatibility

| Branch | Module version | Silverstripe | PHP |
|--------|----------------|--------------|-----|
| `main` | `3.x` | `^6` | `^8.3` |
| `v2` | `2.1.x` | `^4 \|\| ^5` | `>=7.4` |

Silverstripe 6 required breaking changes here (typed method signatures and `ModelData`), so the
lines are split rather than shared. `v2` receives security and bug fixes until Silverstripe 5
reaches end of life in April 2027; new work goes on `main`.

**`composer.json` is the source of truth** for exact constraints; this table is a quick reference.

## Running the tests

The module needs a host Silverstripe project. Require it there through a Composer **path repository
with `symlink: true`** - `/tests` is `export-ignore`, so a dist or mirrored install has no tests -
add its test namespace to the host's `autoload-dev`, then:

```bash
SS_PHPUNIT_FLUSH=1 vendor/bin/phpunit --testsuite namedlinkfield
```

CI runs the same suite on every push; see `.github/workflows/ci.yml`.
