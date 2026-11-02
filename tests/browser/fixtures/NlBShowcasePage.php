<?php

namespace Restruct\NlBrowser;

use Page;
use Restruct\SilverStripe\Forms\NamedLinkFormField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;

/**
 * BROWSER-TEST FIXTURE ONLY - the page the README screenshots are taken of
 * (tests/browser/screenshots/readme.shot.ts). No spec uses it.
 *
 * A page type with four NamedLinkFields, one per common link type, placed in the CMS form the way
 * the README's usage example does it. Every dev/build seeds neutral demo content: a "Visit us" page
 * with two anchors, a PDF in Files, and the "Open day" page whose links point at them.
 * Never loaded by a real install (tests/browser/ carries a _manifest_exclude marker; see NlBRecord).
 */
class NlBShowcasePage extends Page
{
    private static $table_name = 'NlBShowcasePage';

    private static $singular_name = 'Showcase page';

    private static $db = [
        'PrimaryButton' => 'Restruct\\SilverStripe\\ORM\\FieldType\\NamedLinkField',
        'SecondaryButton' => 'Restruct\\SilverStripe\\ORM\\FieldType\\NamedLinkField',
        'Brochure' => 'Restruct\\SilverStripe\\ORM\\FieldType\\NamedLinkField',
        'ContactLink' => 'Restruct\\SilverStripe\\ORM\\FieldType\\NamedLinkField',
    ];

    public const SHOWCASE_TITLE = 'Open day';

    public const TARGET_TITLE = 'Visit us';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        # Silverstripe 6 scaffolds the four fields already (after Content, with labels from their
        # names). To place and label them, remove the scaffolded ones first, exactly as the README
        # shows: NamedLinkFormField is a FieldGroup, so adding one under the same name does not
        # replace the scaffolded field (FieldList only replaces data fields) and the form throws
        # "a field called 'PrimaryButtonTitle' appears twice".
        # Since 3.0.5 (#53) adding them replaces the scaffolded ones without this; the removal
        # stays, as it is harmless and still what a project on an older version needs.
        $fields->removeByName(['PrimaryButton', 'SecondaryButton', 'Brochure', 'ContactLink']);
        $fields->addFieldsToTab('Root.Main', [
            NamedLinkFormField::create('PrimaryButton', 'Primary button'),
            NamedLinkFormField::create('SecondaryButton', 'Secondary button'),
            NamedLinkFormField::create('Brochure'),
            NamedLinkFormField::create('ContactLink', 'Contact link'),
        ], 'Content');

        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Start from the same state on every build: archive what earlier builds made.
        foreach (Page::get()->filter('Title', [self::SHOWCASE_TITLE, self::TARGET_TITLE]) as $old) {
            $old->doArchive();
        }
        foreach (File::get()->filter('Name', 'visitor-guide.pdf') as $old) {
            $old->doArchive();
        }

        # The page the primary button links to: its id= anchors fill the anchor dropdown.
        $target = Page::create([
            'Title' => self::TARGET_TITLE,
            'URLSegment' => 'visit-us',
            'Content' => '<h2 id="opening-hours">Opening hours</h2><p>Daily from 10:00.</p>'
                . '<h2 id="directions">Directions</h2><p>Ten minutes from the station.</p>',
        ]);
        $target->write();
        $target->publishRecursive();

        # A small but valid PDF for the File link (shown by its path in the file tree).
        $folder = Folder::find_or_make('Brochures');
        $pdf = File::create(['Title' => 'Visitor guide', 'ParentID' => $folder->ID]);
        $pdf->setFromString(self::tinyPdf(), 'Brochures/visitor-guide.pdf');
        $pdf->write();
        $pdf->publishSingle();

        $showcase = static::create([
            'Title' => self::SHOWCASE_TITLE,
            'URLSegment' => 'open-day',
            'Content' => '<p>Come and see us on Saturday.</p>',
            'PrimaryButtonTitle' => 'Plan your visit',
            'PrimaryButtonLinkmode' => 'Page',
            'PrimaryButtonPageID' => $target->ID,
            'PrimaryButtonPageAnchor' => 'directions',
            'SecondaryButtonTitle' => 'Book a ticket',
            'SecondaryButtonLinkmode' => 'URL',
            'SecondaryButtonCustomURL' => 'https://tickets.example.com/open-day',
            'BrochureTitle' => 'Download the visitor guide',
            'BrochureLinkmode' => 'File',
            'BrochureFileID' => $pdf->ID,
            'ContactLinkTitle' => 'Questions? Mail us',
            'ContactLinkLinkmode' => 'Email',
            'ContactLinkCustomURL' => 'info@example.com',
        ]);
        $showcase->write();
        $showcase->publishRecursive();
    }

    /** A one-page blank PDF, built here so the fixture needs no binary file. */
    private static function tinyPdf(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

        return $pdf;
    }
}
