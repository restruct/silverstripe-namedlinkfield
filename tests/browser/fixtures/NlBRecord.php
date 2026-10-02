<?php

namespace Restruct\NlBrowser;

use Page;
use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - a record with one NamedLinkField, whose CMS form gets the
 * NamedLinkFormField by plain scaffolding (NamedLinkField::scaffoldFormField), as a module user
 * gets it.
 *
 * Never loaded by a real install: it lives under tests/browser/, which carries a _manifest_exclude
 * marker, and the browser-test runner copies it into a scratch host's app/ before dev/build.
 * Written to load on both Silverstripe 5 and 6 (no class imports that moved between the two).
 *
 * Every dev/build (the runner does one per run) wipes and re-seeds the records and the anchor
 * page, so a run starts from the same state whatever earlier runs saved.
 *
 * @property string $Title
 */
class NlBRecord extends DataObject
{
    # Short table name: the composite field's columns and MySQL's 64-character limit.
    private static $table_name = 'NlBRecord';

    private static $singular_name = 'Browser Record';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Link' => 'Restruct\\SilverStripe\\ORM\\FieldType\\NamedLinkField',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
    ];

    /** The page whose Content carries the anchors the anchor dropdown must offer. */
    public const ANCHOR_PAGE_TITLE = 'NlB anchor page';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # The anchor page: one id= and one name= anchor, the two attributes get_page_anchors()
        # parses. (The single quotes below come back double-quoted from the database, measured on
        # SS6 2026-10-02, so the single-quote branch of its pattern is not exercised here.) Published: the edit form reads the draft stage, but admin/namedlinkpageanchors is a
        # plain Controller and reads the live one. doArchive() removes old copies from both stages.
        foreach (Page::get()->filter('Title', self::ANCHOR_PAGE_TITLE) as $old) {
            $old->doArchive();
        }
        $page = Page::create([
            'Title' => self::ANCHOR_PAGE_TITLE,
            'URLSegment' => 'nlb-anchor-page',
            'Content' => '<h2 id="intro">Intro</h2><p><a name=\'contact\'></a>Contact</p>',
        ]);
        $page->write();
        $page->publishRecursive();

        foreach (static::get() as $old) {
            $old->delete();
        }
        # One record per spec, so specs never share a row and a save in one cannot break another.
        $seeds = [
            ['Title' => 'URL record', 'LinkTitle' => 'Example', 'LinkLinkmode' => 'URL',
                'LinkCustomURL' => 'https://example.com/'],
            ['Title' => 'Email record', 'LinkTitle' => 'Mail us', 'LinkLinkmode' => 'Email',
                'LinkCustomURL' => 'info@example.com'],
            ['Title' => 'Page record', 'LinkTitle' => 'To the page', 'LinkLinkmode' => 'Page',
                'LinkPageID' => $page->ID],
            ['Title' => 'Shortcode record', 'LinkTitle' => 'Code', 'LinkLinkmode' => 'Shortcode',
                'LinkShortcode' => '[sitetree_link,id=1]'],
            ['Title' => 'Switch record', 'LinkTitle' => 'Switch', 'LinkLinkmode' => 'URL',
                'LinkCustomURL' => 'https://example.org/'],
            ['Title' => 'Save record'],
        ];
        foreach ($seeds as $seed) {
            static::create($seed)->write();
        }
    }
}
