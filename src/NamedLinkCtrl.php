<?php

namespace Restruct\SilverStripe\NamedLinkField;

use Page;
use SilverStripe\Control\Controller;

/**
 * Class CRMController
 */
class NamedLinkCtrl
    extends Controller
{
    private static $url_segment = 'admin/namedlinkpageanchors';

    private static $allowed_actions = [
        'index' => 'CMS_ACCESS_CMSMain',
    ];

    public function index()
    {
        $anchors = self::get_page_anchors($this->getRequest()->getVar('pid') ?: 0);

        $this->getResponse()->addHeader('Content-type', 'application/json;charset=utf-8');
        $this->getResponse()->setBody(json_encode($anchors));
        $this->getResponse()->output();
        die();
    }

    // Create a callable function that returns an array of options for the DependentDropdownField.
    // When the value of the field it depends on changes, this function is called passing the
    // updated value as the first parameter ($val)
    public static function get_page_anchors ($page_id) {
        // Copied from HtmlEditorField_Toolbar::getanchors()
        if ( ( $page = Page::get()->byID($page_id) ) && !empty($page) ) {
            if ( preg_match_all("/\s(name|id)=\"([^\"]+?)\"|\s(name|id)='([^']+?)'/im", $page->Content, $matches) ) {
                $anchors = array_filter(array_merge($matches[ 2 ], $matches[ 4 ]));

                return array_combine($anchors, $anchors);
            }
        }

        return [];
    }
}
