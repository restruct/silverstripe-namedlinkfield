<?php

namespace Restruct\NamedLink\Tests;

use Page;
use Restruct\SilverStripe\NamedLinkField\NamedLinkCtrl;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the page-anchor lookup behind the anchor dropdown.
 *
 * This exists because the lookup silently returned an empty list for every page: an upgrade
 * folded the guard clauses into one condition and dropped the parentheses around the
 * assignment, so `$page = expr && ...` assigned a boolean and tested !empty($page) against
 * the previous value. Nothing errored; the dropdown was simply always empty.
 */
class NamedLinkCtrlTest extends SapphireTest
{
    protected static $fixture_file = 'NamedLinkCtrlTest.yml';

    public function testAnchorsAreFoundInPageContent()
    {
        $page = $this->objFromFixture(Page::class, 'pageWithAnchors');

        $anchors = NamedLinkCtrl::get_page_anchors($page->ID);

        $this->assertSame(
            ['first-anchor' => 'first-anchor', 'second-anchor' => 'second-anchor'],
            $anchors,
            'Anchors must be extracted from the page content - an empty result here is the regression'
        );
    }

    public function testSingleQuotedAnchorsAreAlsoFound()
    {
        $page = $this->objFromFixture(Page::class, 'pageWithSingleQuotedAnchor');

        $this->assertSame(['quoted' => 'quoted'], NamedLinkCtrl::get_page_anchors($page->ID));
    }

    public function testNameAttributesCountAsAnchors()
    {
        $page = $this->objFromFixture(Page::class, 'pageWithNamedAnchor');

        $this->assertSame(['named' => 'named'], NamedLinkCtrl::get_page_anchors($page->ID));
    }

    public function testPageWithoutAnchorsReturnsAnEmptyList()
    {
        $page = $this->objFromFixture(Page::class, 'pageWithoutAnchors');

        $this->assertSame([], NamedLinkCtrl::get_page_anchors($page->ID));
    }

    public function testPageWithNoContentReturnsAnEmptyList()
    {
        $page = $this->objFromFixture(Page::class, 'pageWithoutContent');

        $this->assertSame([], NamedLinkCtrl::get_page_anchors($page->ID));
    }

    public function testUnknownPageIdReturnsAnEmptyList()
    {
        $this->assertSame([], NamedLinkCtrl::get_page_anchors(0));
        $this->assertSame([], NamedLinkCtrl::get_page_anchors(999999));
    }
}
